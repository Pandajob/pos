<#
  ปรับตั้งค่าเครื่อง POS ให้เร็วขึ้น — รันครั้งเดียวจบ
  ทำตามที่เขียนไว้ในไฟล์ "ปรับเครื่องร้านให้เร็วขึ้น.md" ให้อัตโนมัติ

  ใช้งาน (คลิกขวาที่ tune-pos-machine.bat -> Run as administrator)
  หรือสั่งเองจาก PowerShell:

      .\tune-pos-machine.ps1
      .\tune-pos-machine.ps1 -Restart              # ปรับแล้วรีสตาร์ท Apache/MySQL ให้เลย
      .\tune-pos-machine.ps1 -ClearSessions        # ล้าง session เก่าด้วย (ทุกคนต้อง login ใหม่)
      .\tune-pos-machine.ps1 -FastDiskWrites       # เร่ง MySQL เพิ่ม (มีข้อแลกเปลี่ยน อ่านหมายเหตุ)
      .\tune-pos-machine.ps1 -DryRun               # ดูว่าจะแก้อะไรบ้าง โดยยังไม่แก้จริง

  ทุกไฟล์ที่แก้จะถูกสำรองไว้เป็น <ชื่อไฟล์>.bak-YYYYMMDD-HHmmss ก่อนเสมอ
  รันซ้ำได้ ไม่ทำให้ค่าซ้ำซ้อน
#>

#Requires -Version 5.1
[CmdletBinding()]
param(
    [string] $XamppRoot = 'C:\xampp',
    [string] $PosRoot   = 'C:\xampp\htdocs\POS',
    [switch] $Restart,
    [switch] $ClearSessions,
    [switch] $FastDiskWrites,
    [switch] $SkipMysqlTuning,
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch {}

# php.ini ใช้ ; เป็นคอมเมนต์ (ใช้ # จะพังทั้งไฟล์) ส่วน my.ini ใช้ #
$MARKER_TEXT = '===== POS tune (tune-pos-machine.ps1) ====='
$MARKER_INI  = ';' + $MARKER_TEXT   # php.ini
$MARKER_CNF  = '#' + $MARKER_TEXT   # my.ini
$STAMP   = Get-Date -Format 'yyyyMMdd-HHmmss'
$script:Changed = @()
$script:Skipped = @()
$script:Failed  = @()

# ── helpers ──────────────────────────────────────────────────────────────────

function Write-Step  ([string]$m) { Write-Host ''; Write-Host "== $m" -ForegroundColor Cyan }
function Write-Ok    ([string]$m) { Write-Host "   [OK]   $m" -ForegroundColor Green;  $script:Changed += $m }
function Write-Skip  ([string]$m) { Write-Host "   [ข้าม] $m" -ForegroundColor DarkGray; $script:Skipped += $m }
function Write-Warn2 ([string]$m) { Write-Host "   [!]    $m" -ForegroundColor Yellow }
function Write-Fail  ([string]$m) { Write-Host "   [พลาด] $m" -ForegroundColor Red;    $script:Failed  += $m }

# อ่าน/เขียนไฟล์ตั้งค่าแบบไม่ใส่ BOM
# สำคัญมาก: ถ้า httpd.conf หรือ php.ini มี BOM นำหน้า Apache/PHP จะไม่ยอมสตาร์ท
function Read-TextFile ([string]$Path) {
    return [System.IO.File]::ReadAllText($Path)
}
function Save-TextFile ([string]$Path, [string]$Text) {
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Text, $utf8NoBom)
}
function Backup-Once ([string]$Path) {
    $bak = "$Path.bak-$STAMP"
    if (-not (Test-Path -LiteralPath $bak)) {
        Copy-Item -LiteralPath $Path -Destination $bak -Force
        Write-Host "   สำรองไว้ที่ $(Split-Path -Leaf $bak)" -ForegroundColor DarkGray
    }
}

# แก้ไฟล์ตั้งค่าด้วย scriptblock ที่รับข้อความเดิม คืนข้อความใหม่
function Edit-ConfigFile {
    param(
        [string]      $Path,
        [scriptblock] $Transform,
        [string]      $What
    )
    if (-not (Test-Path -LiteralPath $Path)) { Write-Fail "ไม่พบไฟล์ $Path"; return }

    $before = Read-TextFile $Path
    $after  = & $Transform $before

    if ($after -eq $before) { Write-Skip "$What — ตั้งไว้อยู่แล้ว"; return }
    if ($DryRun)            { Write-Host "   [จะแก้] $What" -ForegroundColor Magenta; return }

    Backup-Once $Path
    Save-TextFile $Path $after
    Write-Ok $What
}

# ── ตรวจสภาพแวดล้อมก่อน ──────────────────────────────────────────────────────

Write-Host ''
Write-Host '  ปรับตั้งค่าเครื่อง POS ให้เร็วขึ้น' -ForegroundColor White
Write-Host '  ---------------------------------' -ForegroundColor DarkGray

$phpIni    = Join-Path $XamppRoot 'php\php.ini'
$httpdConf = Join-Path $XamppRoot 'apache\conf\httpd.conf'
$myIni     = Join-Path $XamppRoot 'mysql\bin\my.ini'
$phpExe    = Join-Path $XamppRoot 'php\php.exe'
$envFile   = Join-Path $PosRoot   '.env'

foreach ($p in @($phpIni, $httpdConf, $phpExe)) {
    if (-not (Test-Path -LiteralPath $p)) {
        Write-Host ''
        Write-Host "  ไม่พบ $p" -ForegroundColor Red
        Write-Host "  ถ้า XAMPP ไม่ได้อยู่ที่ $XamppRoot ให้สั่งแบบนี้แทน:" -ForegroundColor Yellow
        Write-Host '     .\tune-pos-machine.ps1 -XamppRoot "D:\xampp" -PosRoot "D:\xampp\htdocs\POS"' -ForegroundColor Yellow
        exit 1
    }
}
if (-not (Test-Path -LiteralPath $PosRoot)) {
    Write-Host ''
    Write-Host "  ไม่พบโฟลเดอร์ POS ที่ $PosRoot — สั่งใหม่พร้อม -PosRoot ""<path>""" -ForegroundColor Red
    exit 1
}

$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()
           ).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Warn2 'ไม่ได้รันแบบ Administrator — ถ้าแก้ไฟล์ไม่ได้ ให้คลิกขวาที่ .bat แล้วเลือก Run as administrator'
}
if ($DryRun) { Write-Warn2 'โหมด DryRun — แสดงว่าจะแก้อะไร แต่ยังไม่แก้จริง' }

Write-Host ''
Write-Host "  XAMPP : $XamppRoot"
Write-Host "  POS   : $PosRoot"

# ── 1. OPcache ───────────────────────────────────────────────────────────────

Write-Step '1. เปิด OPcache ใน PHP (ได้ผลมากที่สุด)'

if (-not (Test-Path -LiteralPath (Join-Path $XamppRoot 'php\ext\php_opcache.dll'))) {
    Write-Fail 'ไม่พบ php_opcache.dll ใน php\ext — PHP ชุดนี้ไม่มี OPcache มาด้วย ข้ามข้อนี้'
} else {
    Edit-ConfigFile -Path $phpIni -What 'เปิด zend_extension=opcache + ตั้งค่า OPcache' -Transform {
        param($t)

        # เอา ; หน้า zend_extension=opcache ออก (รองรับทั้ง opcache และ php_opcache.dll)
        $t = [regex]::Replace(
            $t,
            '(?m)^\s*;\s*(zend_extension\s*=\s*(?:php_)?opcache(?:\.dll)?\s*)$',
            '$1'
        )

        # ยังไม่มี zend_extension เลย -> เติมให้
        if ($t -notmatch '(?m)^\s*zend_extension\s*=\s*(?:php_)?opcache') {
            $t = $t.TrimEnd() + "`r`n`r`nzend_extension=opcache`r`n"
        }

        if ($t -notmatch [regex]::Escape($MARKER_TEXT)) {
            $block = @"

$MARKER_INI
[opcache]
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=96
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=60
opcache.save_comments=1
$MARKER_INI

"@
            $t = $t.TrimEnd() + "`r`n" + $block
        }
        return $t
    }
}

# ── 2. โหมด production ───────────────────────────────────────────────────────

Write-Step '2. เปลี่ยน CodeIgniter เป็นโหมด production'

if (-not (Test-Path -LiteralPath $envFile)) {
    Write-Fail "ไม่พบไฟล์ .env ที่ $envFile"
} else {
    Edit-ConfigFile -Path $envFile -What 'CI_ENVIRONMENT = production' -Transform {
        param($t)
        if ($t -match '(?m)^\s*CI_ENVIRONMENT\s*=') {
            return [regex]::Replace($t, '(?m)^\s*#?\s*CI_ENVIRONMENT\s*=.*$', 'CI_ENVIRONMENT = production')
        }
        if ($t -match '(?m)^\s*#\s*CI_ENVIRONMENT\s*=') {
            return [regex]::Replace($t, '(?m)^\s*#\s*CI_ENVIRONMENT\s*=.*$', 'CI_ENVIRONMENT = production')
        }
        return "CI_ENVIRONMENT = production`r`n" + $t
    }
}

# ── 3. โมดูล Apache ──────────────────────────────────────────────────────────

Write-Step '3. เปิดโมดูล Apache (gzip + cache)'

Edit-ConfigFile -Path $httpdConf -What 'เปิด mod_deflate / mod_expires / mod_filter / mod_headers' -Transform {
    param($t)
    foreach ($m in @('deflate', 'expires', 'filter', 'headers')) {
        # เอา # หน้า LoadModule <ชื่อ>_module ออก
        $t = [regex]::Replace(
            $t,
            "(?m)^\s*#\s*(LoadModule\s+${m}_module\s+modules/mod_${m}\.so\s*)$",
            '$1'
        )
    }
    return $t
}
Write-Host '   หมายเหตุ: mod_filter จำเป็นคู่กับ mod_deflate ไม่งั้น Apache จะขึ้น error 500' -ForegroundColor DarkGray

# ── 4. MySQL ─────────────────────────────────────────────────────────────────

Write-Step '4. ตั้งค่า MySQL'

if ($SkipMysqlTuning) {
    Write-Skip 'ข้ามตามที่สั่ง (-SkipMysqlTuning)'
} elseif (-not (Test-Path -LiteralPath $myIni)) {
    Write-Fail "ไม่พบ $myIni — ข้ามข้อนี้"
} else {
    $ramGB = 4
    try {
        $ramGB = [math]::Round((Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 0)
    } catch { }
    $pool = if ($ramGB -ge 16) { '1G' } elseif ($ramGB -ge 8) { '512M' } else { '256M' }
    Write-Host "   แรมเครื่องนี้ ~${ramGB}GB -> innodb_buffer_pool_size=$pool" -ForegroundColor DarkGray

    # หมายเหตุใน my.ini เขียนเป็น ASCII ล้วน กันปัญหา mysqld อ่าน option file ไม่ผ่าน
    $flushLine = if ($FastDiskWrites) {
        "innodb_flush_log_at_trx_commit=2"
    } else {
        "# innodb_flush_log_at_trx_commit=2   # faster disk writes, may lose the last second on power loss`r`n#                                     # enable it by re-running the script with -FastDiskWrites"
    }

    Edit-ConfigFile -Path $myIni -What "ตั้งค่า [mysqld] (buffer pool $pool, ปิด performance_schema)" -Transform {
        param($t)
        if ($t -match [regex]::Escape($MARKER_TEXT)) { return $t }   # ใส่ไปแล้ว

        $block = @"
$MARKER_CNF
innodb_buffer_pool_size=$pool
innodb_flush_method=normal
max_connections=30
performance_schema=OFF
table_open_cache=400
$flushLine
$MARKER_CNF
"@

        $lines = $t -split "`r?`n"
        $start = -1
        for ($i = 0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match '^\s*\[mysqld\]\s*$') { $start = $i; break }
        }
        if ($start -lt 0) { return $t + "`r`n`r`n[mysqld]`r`n" + $block + "`r`n" }

        # แทรกไว้ท้ายสุดของหมวด [mysqld] เพื่อให้ค่าของเราชนะค่าเดิมที่อาจตั้งไว้ก่อนหน้า
        $end = $lines.Count
        for ($i = $start + 1; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match '^\s*\[[^\]]+\]\s*$') { $end = $i; break }
        }

        $head = if ($start + 1 -le $end - 1) { $lines[($start)..($end - 1)] } else { @($lines[$start]) }
        $tail = if ($end -lt $lines.Count)   { $lines[$end..($lines.Count - 1)] } else { @() }
        $pre  = if ($start -gt 0)            { $lines[0..($start - 1)] } else { @() }

        return (($pre + $head + @('', $block, '') + $tail) -join "`r`n")
    }
    if (-not $FastDiskWrites) {
        Write-Host '   (ไม่ได้เปิด innodb_flush_log_at_trx_commit=2 — ถ้าเครื่องใช้ฮาร์ดดิสก์จานหมุนและรับความเสี่ยงได้ ให้รันซ้ำพร้อม -FastDiskWrites)' -ForegroundColor DarkGray
    }
}

# ── 5. ล้างไฟล์ขยะ ───────────────────────────────────────────────────────────

Write-Step '5. ล้างไฟล์ที่โหมด development กองไว้'

$debugbar = Join-Path $PosRoot 'writable\debugbar'
if (Test-Path -LiteralPath $debugbar) {
    $files = @(Get-ChildItem -LiteralPath $debugbar -File -Filter '*.json' -ErrorAction SilentlyContinue)
    if ($files.Count -eq 0) {
        Write-Skip 'writable\debugbar ว่างอยู่แล้ว'
    } elseif ($DryRun) {
        Write-Host "   [จะลบ] debugbar $($files.Count) ไฟล์" -ForegroundColor Magenta
    } else {
        $mb = [math]::Round((($files | Measure-Object Length -Sum).Sum) / 1MB, 1)
        $files | Remove-Item -Force -ErrorAction SilentlyContinue
        Write-Ok "ลบ debugbar $($files.Count) ไฟล์ (~$mb MB)"
    }
}

$sessionDir = Join-Path $PosRoot 'writable\session'
if ($ClearSessions -and (Test-Path -LiteralPath $sessionDir)) {
    if ($DryRun) {
        Write-Host '   [จะลบ] session ทั้งหมด' -ForegroundColor Magenta
    } else {
        Get-ChildItem -LiteralPath $sessionDir -File -Filter 'ci_session*' -ErrorAction SilentlyContinue |
            Remove-Item -Force -ErrorAction SilentlyContinue
        Write-Ok 'ล้าง session เก่า (ทุกคนต้อง login ใหม่ 1 ครั้ง)'
    }
} elseif (Test-Path -LiteralPath $sessionDir) {
    Write-Skip 'ไม่ล้าง session (สั่ง -ClearSessions ถ้าต้องการล้าง)'
}

# ── 6. อัพเดทฐานข้อมูล ───────────────────────────────────────────────────────

Write-Step '6. อัพเดทฐานข้อมูล (สร้าง index)'

if ($DryRun) {
    Write-Host '   [จะรัน] php spark migrate' -ForegroundColor Magenta
} else {
    Push-Location $PosRoot
    try {
        $out = & $phpExe spark migrate 2>&1 | Out-String
        if ($LASTEXITCODE -eq 0) {
            $line = ($out -split "`r?`n" | Where-Object { $_ -match 'Migrations complete|Running:|No new migrations' }) -join ' / '
            Write-Ok ("รัน migration แล้ว " + $line.Trim())
        } else {
            Write-Fail 'รัน migration ไม่สำเร็จ (MySQL ไม่ได้เปิดอยู่?) — เปิด MySQL แล้วกดปุ่ม "อัพเดทฐานข้อมูล" ในหน้าตั้งค่าแทนได้'
            Write-Host ($out.Trim()) -ForegroundColor DarkGray
        }
    } catch {
        Write-Fail "รัน migration ไม่สำเร็จ: $($_.Exception.Message)"
    } finally {
        Pop-Location
    }
}

# ── 7. รีสตาร์ทบริการ ────────────────────────────────────────────────────────

Write-Step '7. รีสตาร์ท Apache / MySQL'

$svcNames = @('Apache2.4', 'Apache2.2', 'apache', 'mysql', 'MySQL', 'mariadb')
$found    = @(Get-Service -Name $svcNames -ErrorAction SilentlyContinue | Where-Object { $_.Status -ne 'Stopped' })

if (-not $Restart) {
    Write-Warn2 'ยังไม่ได้รีสตาร์ท — การตั้งค่าจะมีผลหลังรีสตาร์ท Apache และ MySQL'
    Write-Host '   ปิด/เปิดจาก XAMPP Control Panel หรือรันสคริปต์นี้ซ้ำพร้อม -Restart' -ForegroundColor DarkGray
} elseif ($DryRun) {
    Write-Host '   [จะรีสตาร์ท] Apache / MySQL' -ForegroundColor Magenta
} elseif ($found.Count -gt 0) {
    foreach ($s in $found) {
        try {
            Restart-Service -Name $s.Name -Force -ErrorAction Stop
            Write-Ok "รีสตาร์ท service $($s.Name)"
        } catch {
            Write-Fail "รีสตาร์ท $($s.Name) ไม่สำเร็จ: $($_.Exception.Message)"
        }
    }
} else {
    Write-Warn2 'Apache/MySQL ไม่ได้ติดตั้งเป็น Windows service (รันผ่าน XAMPP Control Panel)'
    Write-Host '   กรุณากด Stop แล้ว Start ทั้ง Apache และ MySQL ใน XAMPP Control Panel เอง' -ForegroundColor DarkGray
    Write-Host '   (ไม่สั่งปิด mysqld ให้อัตโนมัติ เพราะเสี่ยงต่อข้อมูลถ้ากำลังมีการขายค้างอยู่)' -ForegroundColor DarkGray
}

# ── สรุป ─────────────────────────────────────────────────────────────────────

Write-Host ''
Write-Host '  ---------------------------------' -ForegroundColor DarkGray
Write-Host "  แก้แล้ว $($script:Changed.Count) รายการ / ข้าม $($script:Skipped.Count) / พลาด $($script:Failed.Count)" -ForegroundColor White
if ($script:Failed.Count -gt 0) {
    Write-Host ''
    foreach ($f in $script:Failed) { Write-Host "  พลาด: $f" -ForegroundColor Red }
}
Write-Host ''
Write-Host '  ตรวจว่า OPcache ทำงานแล้วหรือยัง (หลังรีสตาร์ท Apache):' -ForegroundColor DarkGray
Write-Host "     $XamppRoot\php\php.exe -r ""var_dump(extension_loaded('Zend OPcache'));""" -ForegroundColor DarkGray
Write-Host '  ย้อนกลับได้ทุกไฟล์ — สำรองไว้เป็น .bak-' -NoNewline -ForegroundColor DarkGray
Write-Host $STAMP -ForegroundColor DarkGray
Write-Host ''
