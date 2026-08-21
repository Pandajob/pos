<?php

/**
 * generate_snapshot.php — สร้าง "ภาพถ่ายโครงสร้างฐานข้อมูล" จากเครื่องนี้ (ต้นแบบ)
 * ------------------------------------------------------------------------------
 * รันบนเครื่อง DEV (เครื่องที่โครงสร้างถูกต้องครบ) ทุกครั้งที่แก้ schema:
 *
 *     C:/xampp/php/php.exe app/Database/generate_snapshot.php
 *
 * ผลลัพธ์: เขียนทับไฟล์ app/Database/SchemaSnapshot.php
 * แล้วก๊อปโค้ดไปเครื่องจริง → หน้า "ตั้งค่าระบบ → อัพเดทฐานข้อมูล" จะ sync ให้ครบ
 * โดยเพิ่มตาราง/คอลัมน์ที่ขาดเท่านั้น ไม่แตะข้อมูลเดิม
 *
 * อ่านค่าการเชื่อมต่อจาก .env (database.default.*) ถ้ามี ไม่งั้นใช้ค่า default ของ XAMPP
 */

$root = dirname(__DIR__, 2);
$env  = [];
if (is_file("$root/.env")) {
    foreach (file("$root/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (! str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $env[$k] = trim($v, "'\"");
    }
}

$host = $env['database.default.hostname'] ?? 'localhost';
$user = $env['database.default.username'] ?? 'root';
$pass = $env['database.default.password'] ?? '';
$name = $env['database.default.database'] ?? 'pos_db';
$port = (int) ($env['database.default.port'] ?? 3306);

$mysqli = @new mysqli($host, $user, $pass, $name, $port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "เชื่อมต่อฐานข้อมูลไม่ได้: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

/** ดึงรายชื่อตารางทั้งหมด (ข้ามตาราง migrations — ปล่อยให้ CI4 จัดการเอง) */
$tableNames = [];
$res = $mysqli->query('SHOW TABLES');
while ($row = $res->fetch_row()) {
    if ($row[0] === 'migrations') continue;
    $tableNames[] = $row[0];
}
sort($tableNames);

$tables = [];
foreach ($tableNames as $t) {
    $row    = $mysqli->query("SHOW CREATE TABLE `$t`")->fetch_assoc();
    $create = $row['Create Table'];

    // แยกบรรทัดคอลัมน์ออกมา (แต่ละคอลัมน์อยู่คนละบรรทัดใน SHOW CREATE TABLE เสมอ)
    $columns = [];
    $order   = [];
    foreach (explode("\n", $create) as $line) {
        $trim = trim($line);
        if ($trim === '' || $trim[0] !== '`') continue;       // เฉพาะบรรทัดที่ขึ้นต้นด้วย `ชื่อคอลัมน์`
        $ddl = rtrim($trim, ',');                              // ตัด , ท้ายบรรทัด
        if (preg_match('/^`([^`]+)`/', $ddl, $m)) {
            $columns[$m[1]] = $ddl;
            $order[]        = $m[1];
        }
    }

    $tables[$t] = [
        'create'  => $create,
        'columns' => $columns,
        'order'   => $order,
    ];
}

$snapshot = [
    'generated_at' => date('Y-m-d H:i:s'),
    'database'     => $name,
    'tables'       => $tables,
];

$php = "<?php\n\n"
     . "/**\n"
     . " * SchemaSnapshot.php — โครงสร้างฐานข้อมูลต้นแบบ (สร้างอัตโนมัติ ห้ามแก้มือ)\n"
     . " * สร้างโดย app/Database/generate_snapshot.php เมื่อ {$snapshot['generated_at']}\n"
     . " */\n\n"
     . 'return ' . var_export($snapshot, true) . ";\n";

file_put_contents(__DIR__ . '/SchemaSnapshot.php', $php);

echo "สร้าง SchemaSnapshot.php สำเร็จ — " . count($tables) . " ตาราง\n";
foreach ($tables as $t => $def) {
    echo "  - $t (" . count($def['columns']) . " คอลัมน์)\n";
}
