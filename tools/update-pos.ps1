<#
  อัพเดทระบบ POS จากอินเทอร์เน็ต — รันที่ "เครื่องร้าน"

      .\update-pos.ps1 -Check            # เช็คเฉย ๆ ว่ามีเวอร์ชันใหม่ไหม ไม่ติดตั้ง
      .\update-pos.ps1                   # เช็คแล้วติดตั้งถ้ามีใหม่
      .\update-pos.ps1 -Force            # ติดตั้งทับแม้เวอร์ชันเท่าเดิม (ใช้ซ่อมไฟล์ที่หาย/เพี้ยน)
      .\update-pos.ps1 -InstallSchedule  # ตั้งให้รันอัตโนมัติทุกคืนตี 3
      .\update-pos.ps1 -RemoveSchedule

  ทำอะไรบ้าง
    1) โหลด latest.json มาดูว่ามีเวอร์ชันใหม่ไหม
    2) โหลดไฟล์ .zip แล้วตรวจ SHA-256 ของทั้งก้อน
    3) แตกไฟล์ แล้วตรวจ SHA-256 "ทีละไฟล์" เทียบกับ manifest ในก้อน
       ไฟล์ไหนไม่ตรงหรือหายแม้แต่ไฟล์เดียว = ยกเลิกทั้งชุด ไม่แตะของเดิมเลย
    4) สำรองไฟล์เดิมที่กำลังจะถูกทับ เป็น zip ไว้ในโฟลเดอร์ backup\
    5) คัดลอกทับ แล้วรัน migration ฐานข้อมูล
    6) ถ้าพังระหว่างทาง คืนค่าจากไฟล์สำรองอัตโนมัติ

  ไม่แตะของประจำเครื่องเด็ดขาด: .env, writable\, public\uploads\
#>

#Requires -Version 5.1
[CmdletBinding()]
param(
    [string] $PosRoot   = '',
    [string] $XamppRoot = 'C:\xampp',
    [string] $Source,                  # URL หรือพาธของ latest.json
    [string] $Package,                 # ติดตั้งจากไฟล์ .zip ในเครื่องโดยตรง (ไม่ต้องมีเน็ต)
    [switch] $Check,
    [switch] $Force,
    [switch] $InstallSchedule,
    [switch] $RemoveSchedule,
    [switch] $Relaunched               # ใช้ภายใน — บอกว่ากำลังรันจากสำเนาใน temp แล้ว
)

$ErrorActionPreference = 'Stop'
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch {}
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

function Say  ([string]$m, [string]$c = 'Gray') { Write-Host "   $m" -ForegroundColor $c }
function Step ([string]$m) { Write-Host ''; Write-Host "== $m" -ForegroundColor Cyan }
function Die  ([string]$m) { Write-Host ''; Write-Host "  ล้มเหลว: $m" -ForegroundColor Red; Write-Host ''; exit 1 }

if (-not $PosRoot) { $PosRoot = Split-Path -Parent $PSScriptRoot }

$STAMP     = Get-Date -Format 'yyyyMMdd-HHmmss'
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

# ── ตั้ง/ยกเลิกงานอัตโนมัติ ──────────────────────────────────────────────────

$taskName = 'POS Auto Update'
if ($RemoveSchedule) {
    try {
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
        Write-Host "  ยกเลิกงานอัตโนมัติแล้ว" -ForegroundColor Green
    } catch { Write-Host "  ไม่พบงานชื่อ '$taskName'" -ForegroundColor Yellow }
    exit 0
}
if ($InstallSchedule) {
    $self   = Join-Path $PosRoot 'tools\update-pos.ps1'
    $action = New-ScheduledTaskAction -Execute 'powershell.exe' `
                -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$self`""
    $trig   = New-ScheduledTaskTrigger -Daily -At 3am
    $set    = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd `
                -ExecutionTimeLimit (New-TimeSpan -Minutes 30)
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trig `
        -Settings $set -RunLevel Highest -Force | Out-Null
    Write-Host ''
    Write-Host "  ตั้งงานอัตโนมัติแล้ว — จะเช็คและอัพเดทให้ทุกคืนตี 3" -ForegroundColor Green
    Write-Host "  (ยกเลิกด้วย .\update-pos.ps1 -RemoveSchedule)" -ForegroundColor DarkGray
    Write-Host ''
    exit 0
}

# ── หา URL ต้นทาง ────────────────────────────────────────────────────────────

if ($Package -and -not (Test-Path -LiteralPath $Package)) {
    Die "ไม่พบไฟล์ package ที่ $Package"
}
if (-not $Source -and -not $Package) {
    $srcFile = Join-Path $PSScriptRoot 'update-source.txt'
    if (Test-Path -LiteralPath $srcFile) {
        # เอาบรรทัดแรกที่ไม่ใช่คอมเมนต์ (#) และไม่ว่าง — จะได้ใส่คำอธิบายไว้ในไฟล์ได้
        $Source = ([System.IO.File]::ReadAllText($srcFile) -split "`r?`n" |
                   Where-Object { $_.Trim() -ne '' -and -not $_.Trim().StartsWith('#') } |
                   Select-Object -First 1)
        if ($Source) { $Source = $Source.Trim() }
    }
}
if (-not $Source -and -not $Package) {
    Die "ยังไม่ได้ตั้งที่อยู่อัพเดท — ใส่ URL ของ latest.json ลงในไฟล์ tools\update-source.txt"
}
if ($Source -match '/USER/REPO/') {
    Write-Host ''
    Write-Host '  ยังไม่ได้ตั้งที่อยู่อัพเดท' -ForegroundColor Red
    Write-Host "  ไฟล์ tools\update-source.txt ยังเป็นค่าตัวอย่าง (USER/REPO) ที่ยังไม่มีจริง" -ForegroundColor Yellow
    Write-Host ''
    Write-Host '  ทำอย่างใดอย่างหนึ่ง:' -ForegroundColor White
    Write-Host '   1) แก้ tools\update-source.txt ให้เป็น URL ของ latest.json ใน repo จริง' -ForegroundColor DarkGray
    Write-Host '   2) หรือติดตั้งจากไฟล์ในเครื่องแทน:' -ForegroundColor DarkGray
    Write-Host '      .\update-pos.ps1 -Package "D:\pos-update-xxxx.zip"' -ForegroundColor DarkGray
    Write-Host ''
    exit 1
}

Write-Host ''
Write-Host '  อัพเดทระบบ POS' -ForegroundColor White
Write-Host '  --------------' -ForegroundColor DarkGray
Say ("ต้นทาง: " + $(if ($Package) { $Package } else { $Source })) 'DarkGray'
Say "ติดตั้งที่: $PosRoot" 'DarkGray'

# ── เวอร์ชันที่ติดตั้งอยู่ ────────────────────────────────────────────────────

$verFile = Join-Path $PosRoot 'VERSION'
$current = if (Test-Path -LiteralPath $verFile) { [System.IO.File]::ReadAllText($verFile).Trim() } else { '0.0.0.0' }

# ── ดึง latest.json ──────────────────────────────────────────────────────────

Step '1. ตรวจเวอร์ชันล่าสุด'

function Fetch-Text ([string]$src) {
    if ($src -match '^https?://') {
        return (Invoke-WebRequest -Uri $src -UseBasicParsing -TimeoutSec 30).Content
    }
    return [System.IO.File]::ReadAllText($src)
}
function Fetch-File ([string]$src, [string]$dest) {
    if ($src -match '^https?://') {
        Invoke-WebRequest -Uri $src -UseBasicParsing -TimeoutSec 600 -OutFile $dest
    } else {
        Copy-Item -LiteralPath $src -Destination $dest -Force
    }
}

function Is-Newer ([string]$a, [string]$b) {
    try { return ([version]$a) -gt ([version]$b) } catch { return $a -ne $b }
}

if ($Package) {
    # ติดตั้งจากไฟล์ในเครื่อง (USB / โฟลเดอร์แชร์) — ไม่ต้องเช็คเวอร์ชัน ติดตั้งทับเลย
    # ยังตรวจ checksum ทีละไฟล์เหมือนเดิมทุกประการ
    $latest = [pscustomobject]@{ version = '(อ่านจากไฟล์)'; zip = $Package; sha256 = ''; notes = '' }
    $Force  = $true
    Say "เวอร์ชันที่ติดตั้งอยู่ : $current"
    Say "ติดตั้งจากไฟล์        : $(Split-Path -Leaf $Package)"
} else {
    try { $latest = Fetch-Text $Source | ConvertFrom-Json }
    catch {
        $msg = $_.Exception.Message
        if ($msg -match '404|Not Found') {
            Write-Host ''
            Write-Host '  หาไฟล์ latest.json ไม่เจอที่ที่อยู่นี้ (404)' -ForegroundColor Red
            Write-Host "  $Source" -ForegroundColor Yellow
            Write-Host ''
            Write-Host '  เช็ค 3 อย่าง:' -ForegroundColor White
            Write-Host '   1) URL ใน tools\update-source.txt สะกดถูกไหม (ชื่อ user / ชื่อ repo / ชื่อ branch)' -ForegroundColor DarkGray
            Write-Host '   2) push ไฟล์ latest.json ขึ้น repo แล้วหรือยัง' -ForegroundColor DarkGray
            Write-Host '   3) repo เป็น private อยู่หรือเปล่า — raw.githubusercontent ต้องเป็น public' -ForegroundColor DarkGray
            Write-Host ''
            Write-Host '  ระหว่างนี้ติดตั้งจากไฟล์ในเครื่องได้เลย:' -ForegroundColor White
            Write-Host '   .\update-pos.ps1 -Package "D:\pos-update-xxxx.zip"' -ForegroundColor DarkGray
            Write-Host ''
            exit 1
        }
        Die "เชื่อมต่อที่อยู่อัพเดทไม่ได้ ($msg)"
    }

    Say "เวอร์ชันที่ติดตั้งอยู่ : $current"
    Say "เวอร์ชันล่าสุด        : $($latest.version)"
    if ($latest.notes) { Say "รายละเอียด            : $($latest.notes)" 'DarkGray' }
}

$needUpdate = if ($Package) { $true } else { Is-Newer $latest.version $current }
if (-not $needUpdate -and -not $Force) {
    Write-Host ''
    Write-Host '  เป็นเวอร์ชันล่าสุดอยู่แล้ว' -ForegroundColor Green
    Write-Host '  (ถ้าสงสัยว่าไฟล์หาย/เพี้ยน สั่งซ่อมด้วย -Force)' -ForegroundColor DarkGray
    Write-Host ''
    exit 0
}
if ($Check) {
    Write-Host ''
    Write-Host "  มีเวอร์ชันใหม่: $($latest.version)" -ForegroundColor Yellow
    Write-Host '  ติดตั้งด้วยการรัน update-pos.bat' -ForegroundColor DarkGray
    Write-Host ''
    exit 0
}

# ── รันจากสำเนาใน temp เพื่อให้ทับไฟล์ tools\ ตัวเองได้ ──────────────────────

if (-not $Relaunched) {
    $tmpTools = Join-Path ([System.IO.Path]::GetTempPath()) ("posupd_" + [System.IO.Path]::GetRandomFileName())
    New-Item -ItemType Directory -Path $tmpTools -Force | Out-Null
    Copy-Item -LiteralPath $PSCommandPath -Destination (Join-Path $tmpTools 'update-pos.ps1') -Force
    $srcFile = Join-Path $PSScriptRoot 'update-source.txt'
    if (Test-Path -LiteralPath $srcFile) { Copy-Item -LiteralPath $srcFile -Destination $tmpTools -Force }

    Say 'ย้ายไปรันจากสำเนาชั่วคราว (เพื่อให้อัพเดทไฟล์ตัวเองได้)' 'DarkGray'
    $argv = @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass',
        '-File', (Join-Path $tmpTools 'update-pos.ps1'),
        '-PosRoot', $PosRoot, '-XamppRoot', $XamppRoot, '-Relaunched'
    )
    if ($Source)  { $argv += @('-Source', $Source) }
    if ($Package) { $argv += @('-Package', $Package) }
    if ($Force)   { $argv += '-Force' }
    & powershell.exe @argv
    $code = $LASTEXITCODE
    Remove-Item -LiteralPath $tmpTools -Recurse -Force -ErrorAction SilentlyContinue
    exit $code
}

# ── ดาวน์โหลด + ตรวจก้อน ─────────────────────────────────────────────────────

Step $(if ($Package) { '2. เตรียมไฟล์' } else { '2. ดาวน์โหลด' })

$work = Join-Path ([System.IO.Path]::GetTempPath()) ("poswork_" + [System.IO.Path]::GetRandomFileName())
New-Item -ItemType Directory -Path $work -Force | Out-Null
$zipPath = Join-Path $work 'update.zip'

try { Fetch-File $latest.zip $zipPath }
catch { Remove-Item $work -Recurse -Force -EA 0; Die "ดาวน์โหลดไม่สำเร็จ ($($_.Exception.Message))" }

$mb = [math]::Round((Get-Item -LiteralPath $zipPath).Length / 1MB, 2)
Say "ได้ไฟล์ $mb MB"

if ($latest.sha256) {
    $got = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLower()
    if ($got -ne $latest.sha256.ToLower()) {
        Remove-Item $work -Recurse -Force -EA 0
        Die "ไฟล์ที่โหลดมาไม่ตรงกับต้นทาง (อาจโหลดไม่ครบ) — ลองใหม่อีกครั้ง"
    }
    Say 'ตรวจ checksum ของไฟล์ zip ผ่าน' 'Green'
}

# ── แตกไฟล์ + ตรวจทีละไฟล์ ───────────────────────────────────────────────────

Step '3. ตรวจไฟล์ทีละไฟล์'

Add-Type -AssemblyName System.IO.Compression.FileSystem
$ext = Join-Path $work 'x'
# ระบุ UTF-8 ให้ตรงกับตอนบีบ ไม่งั้นชื่อไฟล์ภาษาไทยจะเพี้ยน
try { [System.IO.Compression.ZipFile]::ExtractToDirectory($zipPath, $ext, [System.Text.Encoding]::UTF8) }
catch { Remove-Item $work -Recurse -Force -EA 0; Die "แตกไฟล์ไม่สำเร็จ ($($_.Exception.Message))" }

$manPath = Join-Path $ext 'manifest.json'
if (-not (Test-Path -LiteralPath $manPath)) { Remove-Item $work -Recurse -Force -EA 0; Die 'ไม่พบ manifest.json ในก้อนอัพเดท' }
# ต้องอ่านเป็น UTF-8 ตรง ๆ — Get-Content ของ PowerShell 5.1 อ่านเป็น ANSI
# ทำให้ชื่อไฟล์ภาษาไทยใน manifest เพี้ยน แล้วรายงานว่า "ไฟล์หาย" ทั้งที่มีอยู่
$man     = [System.IO.File]::ReadAllText($manPath) | ConvertFrom-Json
$payload = Join-Path $ext 'payload'

$bad = New-Object System.Collections.Generic.List[string]
$rels = @($man.files.PSObject.Properties)
$n = 0
foreach ($prop in $rels) {
    $n++
    if ($n % 300 -eq 0) { Say "...ตรวจแล้ว $n/$($rels.Count)" 'DarkGray' }
    $full = Join-Path $payload ($prop.Name -replace '/', '\')
    if (-not (Test-Path -LiteralPath $full)) { $bad.Add("หาย: $($prop.Name)"); continue }
    $h = (Get-FileHash -LiteralPath $full -Algorithm SHA256).Hash.ToLower()
    if ($h -ne $prop.Value.ToLower()) { $bad.Add("ไม่ตรง: $($prop.Name)") }
}

if ($bad.Count -gt 0) {
    Write-Host ''
    Say "พบปัญหา $($bad.Count) ไฟล์:" 'Red'
    $bad | Select-Object -First 10 | ForEach-Object { Say "  $_" 'Red' }
    Remove-Item $work -Recurse -Force -EA 0
    Die 'ยกเลิกการอัพเดท — ไม่ได้แตะไฟล์เดิมเลย'
}
Say "ครบ $($rels.Count) ไฟล์ ตรงทุกไฟล์" 'Green'

# ── สำรองของเดิม ─────────────────────────────────────────────────────────────

Step '4. สำรองไฟล์เดิม'

$backupDir = Join-Path $PosRoot 'backup'
if (-not (Test-Path -LiteralPath $backupDir)) { New-Item -ItemType Directory -Path $backupDir -Force | Out-Null }
$bkStage = Join-Path $work 'bk'
$nBk = 0
foreach ($prop in $rels) {
    $rel = $prop.Name -replace '/', '\'
    $cur = Join-Path $PosRoot $rel
    if (-not (Test-Path -LiteralPath $cur)) { continue }
    $dst = Join-Path $bkStage $rel
    $dir = Split-Path -Parent $dst
    if (-not (Test-Path -LiteralPath $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    Copy-Item -LiteralPath $cur -Destination $dst -Force
    $nBk++
}
$bkZip = Join-Path $backupDir "pos-backup-$current-$STAMP.zip"
if ($nBk -gt 0) {
    [System.IO.Compression.ZipFile]::CreateFromDirectory(
        $bkStage, $bkZip, 'Optimal', $false, [System.Text.Encoding]::UTF8)
    Say "สำรอง $nBk ไฟล์ไว้ที่ backup\$(Split-Path -Leaf $bkZip)" 'Green'
} else {
    Say 'ไม่มีไฟล์เดิมให้สำรอง (ติดตั้งใหม่)' 'DarkGray'
}

# ── คัดลอกทับ ────────────────────────────────────────────────────────────────

Step '5. ติดตั้ง'

$copied = 0
try {
    foreach ($prop in $rels) {
        $rel  = $prop.Name -replace '/', '\'
        $src  = Join-Path $payload $rel
        $dst  = Join-Path $PosRoot $rel
        $dir  = Split-Path -Parent $dst
        if (-not (Test-Path -LiteralPath $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
        Copy-Item -LiteralPath $src -Destination $dst -Force
        $copied++
    }
    Say "คัดลอก $copied ไฟล์" 'Green'
} catch {
    Say "คัดลอกล้มเหลวที่ไฟล์ที่ $($copied + 1) — กำลังคืนค่าเดิม" 'Red'
    if (Test-Path -LiteralPath $bkZip) {
        $restore = Join-Path $work 'restore'
        [System.IO.Compression.ZipFile]::ExtractToDirectory($bkZip, $restore, [System.Text.Encoding]::UTF8)
        Get-ChildItem -LiteralPath $restore -Recurse -File | ForEach-Object {
            $r = $_.FullName.Substring($restore.Length + 1)
            Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $PosRoot $r) -Force
        }
        Say 'คืนค่าเดิมเรียบร้อย' 'Yellow'
    }
    Remove-Item $work -Recurse -Force -EA 0
    Die $_.Exception.Message
}

$newVersion = if ($man.version) { $man.version } else { $latest.version }
[System.IO.File]::WriteAllText($verFile, $newVersion, $utf8NoBom)

# ── migration ────────────────────────────────────────────────────────────────

Step '6. อัพเดทฐานข้อมูล'

$phpExe = Join-Path $XamppRoot 'php\php.exe'
if (-not (Test-Path -LiteralPath $phpExe)) {
    Say "ไม่พบ $phpExe — ข้ามไป ให้กดปุ่ม 'อัพเดทฐานข้อมูล' ในหน้าตั้งค่าแทน" 'Yellow'
} else {
    Push-Location $PosRoot
    try {
        $out = & $phpExe spark migrate 2>&1 | Out-String
        if ($LASTEXITCODE -eq 0) { Say 'รัน migration เรียบร้อย' 'Green' }
        else {
            Say "migration ไม่สำเร็จ (MySQL ปิดอยู่?) — กดปุ่ม 'อัพเดทฐานข้อมูล' ในหน้าตั้งค่าแทนได้" 'Yellow'
            # CI4 พ่น stack trace เป็น JSON ยาวเป็นหน้า — เอาเฉพาะสาระ ไม่งั้นคนหน้าร้านอ่านไม่รู้เรื่อง
            $reason = $null
            try { $reason = ($out | ConvertFrom-Json).message } catch { }
            if (-not $reason) { $reason = (($out.Trim() -split "`r?`n") | Select-Object -First 3) -join ' ' }
            Say "สาเหตุ: $reason" 'DarkGray'
            Say "รายละเอียดเต็มดูได้ที่ writable\logs\" 'DarkGray'
        }
    } catch {
        Say "migration ไม่สำเร็จ: $($_.Exception.Message)" 'Yellow'
    } finally { Pop-Location }
}

# ── เก็บกวาด ─────────────────────────────────────────────────────────────────

Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue

# เก็บไฟล์สำรองไว้ 5 ชุดล่าสุดพอ
Get-ChildItem -LiteralPath $backupDir -Filter 'pos-backup-*.zip' -ErrorAction SilentlyContinue |
    Sort-Object LastWriteTime -Descending | Select-Object -Skip 5 |
    Remove-Item -Force -ErrorAction SilentlyContinue

Write-Host ''
Write-Host "  อัพเดทเป็นเวอร์ชัน $newVersion เรียบร้อย ($copied ไฟล์)" -ForegroundColor Green
Write-Host '  ถ้ามีหน้า POS เปิดค้างอยู่ ให้กด Ctrl+F5 หนึ่งครั้ง' -ForegroundColor DarkGray
Write-Host ''
