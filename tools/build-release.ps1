<#
  สร้าง package อัพเดทสำหรับส่งขึ้น GitHub Releases (หรือที่เก็บไฟล์อื่น)
  รันที่ "เครื่องพัฒนา" เท่านั้น

      .\tools\build-release.ps1                       # ตั้งเลขเวอร์ชันจากวันที่ให้อัตโนมัติ
      .\tools\build-release.ps1 -Version 2026.08.22.1
      .\tools\build-release.ps1 -Notes "แก้ใบเสร็จ A4"

  ได้ออกมา 2 อย่างในโฟลเดอร์ dist\
    pos-update-<version>.zip   ตัว package (ข้างในมี manifest.json = SHA-256 ของทุกไฟล์)
    latest.json                ไฟล์บอกเวอร์ชันล่าสุด ให้เครื่องร้านมาเช็ค

  วิธีปล่อยอัพเดท
    1) รันสคริปต์นี้
    2) อัปโหลด .zip เข้า GitHub Releases (tag = v<version>)
    3) แก้ค่า zip ใน latest.json ให้ชี้ไปที่ลิงก์ของไฟล์ที่เพิ่งอัปโหลด
       (ถ้าตั้ง -ZipUrlBase ไว้ สคริปต์จะเติมลิงก์ให้เองเลย)
    4) อัปโหลด latest.json ไปวางที่ URL คงที่ (เช่น branch main ของ repo)
#>

#Requires -Version 5.1
[CmdletBinding()]
param(
    [string] $Version,
    [string] $Notes      = '',
    [string] $PosRoot    = '',
    [string] $OutDir,
    # เช่น 'https://github.com/USER/REPO/releases/download' -> ลิงก์ zip จะถูกเติมให้อัตโนมัติ
    [string] $ZipUrlBase = ''
)

$ErrorActionPreference = 'Stop'
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch {}

if (-not $PosRoot) { $PosRoot = Split-Path -Parent $PSScriptRoot }
if (-not $OutDir) { $OutDir = Join-Path $PosRoot 'dist' }
if (-not $Version) {
    $Version = (Get-Date -Format 'yyyy.MM.dd') + '.1'
}

# ── สิ่งที่ห้ามใส่ลง package ─────────────────────────────────────────────────
# .env / writable / uploads = ของประจำเครื่องนั้น ๆ ทับไปแล้วข้อมูลหาย
# local-agent = โปรแกรมพิมพ์ที่รันแยกเป็น .exe ของมันเอง (Electron หลายพันไฟล์)
# ทับตอนมันกำลังรันอยู่ไม่ได้ และทับไปก็ไม่มีผลจนกว่าจะ build ใหม่ -> อัพเดทแยกต่างหาก
$excludeDirs = @(
    '.git', 'dist', 'backup', 'releases', 'writable', 'public\uploads',
    'node_modules', 'tests', '.claude', 'local-agent'
)
# ไฟล์ dump มีรหัสผ่าน (hash) พนักงาน ข้อมูลลูกค้า และ agent_token
# GitHub Releases เป็นสาธารณะ -> ห้ามใส่ลงก้อนอัพเดทเด็ดขาด
$excludeFiles = @('.env', 'pos_db.sql', 'pos_db_update.sql')
$excludePatterns = @('*.bak-*', '*.log', '*.zip', '*.sql', 'Thumbs.db', '.DS_Store')

function Should-Skip ([string]$rel) {
    foreach ($d in $excludeDirs) {
        if ($rel -eq $d -or $rel.StartsWith($d + '\', [StringComparison]::OrdinalIgnoreCase)) { return $true }
    }
    if ($excludeFiles -contains $rel) { return $true }
    $leaf = Split-Path -Leaf $rel
    foreach ($p in $excludePatterns) { if ($leaf -like $p) { return $true } }
    return $false
}

Write-Host ''
Write-Host "  สร้าง package เวอร์ชัน $Version" -ForegroundColor White
Write-Host "  จาก $PosRoot" -ForegroundColor DarkGray
Write-Host ''

# ── รวบรวมไฟล์ + คำนวณ SHA-256 ทีละไฟล์ ─────────────────────────────────────

$rootLen = $PosRoot.TrimEnd('\').Length + 1
$all = Get-ChildItem -LiteralPath $PosRoot -Recurse -File -Force
$picked = New-Object System.Collections.Generic.List[object]

foreach ($f in $all) {
    $rel = $f.FullName.Substring($rootLen)
    if (Should-Skip $rel) { continue }
    $picked.Add([pscustomobject]@{ Rel = $rel; Full = $f.FullName })
}

Write-Host "  ไฟล์ที่จะแพ็ค: $($picked.Count)" -ForegroundColor Cyan

$manifest = [ordered]@{}
$i = 0
foreach ($f in $picked) {
    $i++
    if ($i % 250 -eq 0) { Write-Host "   ...คำนวณ checksum $i/$($picked.Count)" -ForegroundColor DarkGray }
    $manifest[$f.Rel.Replace('\', '/')] = (Get-FileHash -LiteralPath $f.Full -Algorithm SHA256).Hash.ToLower()
}

# ── จัดไฟล์ลง staging แล้วบีบเป็น zip ────────────────────────────────────────

$stage = Join-Path ([System.IO.Path]::GetTempPath()) ("posrel_" + [System.IO.Path]::GetRandomFileName())
New-Item -ItemType Directory -Path $stage -Force | Out-Null
$payload = Join-Path $stage 'payload'

foreach ($f in $picked) {
    $dest = Join-Path $payload $f.Rel
    $dir  = Split-Path -Parent $dest
    if (-not (Test-Path -LiteralPath $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    Copy-Item -LiteralPath $f.Full -Destination $dest -Force
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$manifestObj = [ordered]@{
    version  = $Version
    built    = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
    notes    = $Notes
    count    = $picked.Count
    files    = $manifest
}
[System.IO.File]::WriteAllText(
    (Join-Path $stage 'manifest.json'),
    ($manifestObj | ConvertTo-Json -Depth 5),
    $utf8NoBom
)

if (-not (Test-Path -LiteralPath $OutDir)) { New-Item -ItemType Directory -Path $OutDir -Force | Out-Null }
$zipName = "pos-update-$Version.zip"
$zipPath = Join-Path $OutDir $zipName
if (Test-Path -LiteralPath $zipPath) { Remove-Item -LiteralPath $zipPath -Force }

Add-Type -AssemblyName System.IO.Compression.FileSystem
# ต้องระบุ UTF-8 ไม่งั้นชื่อไฟล์ภาษาไทยใน zip จะเพี้ยน แล้วเครื่องร้านหาไฟล์ไม่เจอ
[System.IO.Compression.ZipFile]::CreateFromDirectory(
    $stage, $zipPath, [System.IO.Compression.CompressionLevel]::Optimal, $false,
    [System.Text.Encoding]::UTF8)

Remove-Item -LiteralPath $stage -Recurse -Force

$zipHash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLower()
$zipMB   = [math]::Round((Get-Item -LiteralPath $zipPath).Length / 1MB, 2)

# ── latest.json — ไฟล์ที่เครื่องร้านจะมาเช็ค ─────────────────────────────────

# ถ้าไม่ได้สั่ง -ZipUrlBase มา ให้เดาจาก git remote origin เอง
# จะได้ไม่ต้องมานั่งพิมพ์ลิงก์เองทุกครั้ง (และไม่พิมพ์ผิดจนเครื่องร้านโดน 404)
$repoSlug = ''
if (-not $ZipUrlBase) {
    try {
        Push-Location $PosRoot
        $remote = (& git remote get-url origin 2>$null)
        Pop-Location
        if ($remote -and $remote -match 'github\.com[:/](?<owner>[^/]+)/(?<repo>[^/.\s]+)') {
            # ตั้งแค่ repoSlug พอ — ห้ามตั้ง ZipUrlBase ที่นี่
            # ไม่งั้นจะไปเข้าโหมด GitHub Releases (ที่ต้องอัปโหลดเอง) แทนโหมดวางไฟล์ในรีโป
            $repoSlug = "$($Matches.owner)/$($Matches.repo)"
            Write-Host "  อ่าน repo จาก git remote: $repoSlug" -ForegroundColor DarkGray
        }
    } catch { }
}

if ($ZipUrlBase) {
    # โหมด GitHub Releases — ต้องอัปโหลดไฟล์ zip เองที่หน้าเว็บ
    $zipUrl = "$($ZipUrlBase.TrimEnd('/'))/v$Version/$zipName"
} elseif ($repoSlug) {
    # โหมดปกติ: ก๊อป zip เข้าโฟลเดอร์ releases\ ในรีโปเลย
    # push ครั้งเดียวจบ ไม่ต้องแตะหน้าเว็บ GitHub (ไฟล์แค่ ~2MB ต่อรุ่น)
    $relDir = Join-Path $PosRoot 'releases'
    if (-not (Test-Path -LiteralPath $relDir)) { New-Item -ItemType Directory -Path $relDir -Force | Out-Null }
    Copy-Item -LiteralPath $zipPath -Destination (Join-Path $relDir $zipName) -Force
    $zipUrl = "https://raw.githubusercontent.com/$repoSlug/main/releases/$zipName"
    Write-Host "  ก๊อป zip เข้า releases\ แล้ว (push ขึ้น repo ได้เลย)" -ForegroundColor DarkGray

    # เก็บไฟล์ในรีโปแค่ 3 รุ่นล่าสุด ไม่งั้นรีโปบวมเรื่อย ๆ
    Get-ChildItem -LiteralPath $relDir -Filter 'pos-update-*.zip' |
        Sort-Object LastWriteTime -Descending | Select-Object -Skip 3 |
        ForEach-Object {
            Remove-Item -LiteralPath $_.FullName -Force
            Write-Host "  ลบรุ่นเก่า $($_.Name)" -ForegroundColor DarkGray
        }
} else {
    $zipUrl = "<<ใส่ลิงก์ไฟล์ zip ตรงนี้>>"
}

$latest = [ordered]@{
    version  = $Version
    released = (Get-Date -Format 'yyyy-MM-dd')
    notes    = $Notes
    zip      = $zipUrl
    sha256   = $zipHash
    count    = $picked.Count
}
[System.IO.File]::WriteAllText(
    (Join-Path $OutDir 'latest.json'),
    ($latest | ConvertTo-Json -Depth 3),
    $utf8NoBom
)

# บันทึกเวอร์ชันไว้ที่โปรเจกต์ด้วย เพื่อให้รู้ว่าเครื่องนี้สร้างถึงเวอร์ชันไหนแล้ว
[System.IO.File]::WriteAllText((Join-Path $PosRoot 'VERSION'), $Version, $utf8NoBom)

# วาง latest.json ไว้ที่ root ของโปรเจกต์ด้วย — เครื่องร้านจะมาอ่านไฟล์นี้จาก repo
# (แค่ git add latest.json แล้ว push ก็ปล่อยอัพเดทได้เลย ไม่ต้องก๊อปเอง)
[System.IO.File]::WriteAllText(
    (Join-Path $PosRoot 'latest.json'),
    ($latest | ConvertTo-Json -Depth 3),
    $utf8NoBom
)

# เติม URL ให้ tools\update-source.txt อัตโนมัติ ถ้ายังไม่ได้ตั้งไว้
if ($repoSlug) {
    $srcFile = Join-Path $PosRoot 'tools\update-source.txt'
    $lines   = if (Test-Path -LiteralPath $srcFile) { [System.IO.File]::ReadAllText($srcFile) -split "`r?`n" } else { @() }
    $hasUrl  = $lines | Where-Object { $_.Trim() -ne '' -and -not $_.Trim().StartsWith('#') }
    if (-not $hasUrl) {
        $rawUrl = "https://raw.githubusercontent.com/$repoSlug/main/latest.json"
        [System.IO.File]::WriteAllText($srcFile, (($lines -join "`r`n").TrimEnd() + "`r`n" + $rawUrl + "`r`n"), $utf8NoBom)
        Write-Host "  ตั้ง tools\update-source.txt ให้แล้ว -> $rawUrl" -ForegroundColor DarkGray
    }
}

Write-Host ''
Write-Host "  [OK] $zipName  ($zipMB MB, $($picked.Count) ไฟล์)" -ForegroundColor Green
Write-Host "  [OK] latest.json" -ForegroundColor Green
Write-Host "       sha256 = $zipHash" -ForegroundColor DarkGray
Write-Host ''
Write-Host "  อยู่ที่ $OutDir" -ForegroundColor White
Write-Host ''
if ($ZipUrlBase) {
    Write-Host '  ขั้นต่อไป: อัปโหลด zip เข้า GitHub Releases (tag = v' -NoNewline -ForegroundColor Yellow
    Write-Host "$Version) แล้ว push latest.json" -ForegroundColor Yellow
} elseif ($repoSlug) {
    Write-Host '  ขั้นต่อไป — push ขึ้น GitHub แล้วเครื่องร้านจะเห็นอัพเดททันที:' -ForegroundColor White
    Write-Host '     git add -A' -ForegroundColor DarkGray
    Write-Host "     git commit -m `"release $Version`"" -ForegroundColor DarkGray
    Write-Host '     git push' -ForegroundColor DarkGray
} else {
    Write-Host '  ยังไม่ได้ตั้ง git remote — แก้ค่า "zip" ใน latest.json เองก่อนอัปโหลด' -ForegroundColor Yellow
}
Write-Host ''
