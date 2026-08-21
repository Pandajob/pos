<?php

namespace App\Database;

/**
 * SchemaSync — ปรับโครงสร้างฐานข้อมูลของเครื่องนี้ให้ "ตรงกับต้นแบบ" (SchemaSnapshot.php)
 * แบบปลอดภัย: เพิ่มตารางที่ขาด + เพิ่มคอลัมน์ที่ขาดเท่านั้น
 * ไม่ลบ ไม่แก้ ไม่ย่อ ของเดิม → ข้อมูลเดิมไม่เสียหาย
 *
 * ใช้สำหรับกรณีก๊อปโค้ดไปเครื่องอื่นแล้วฐานข้อมูลสร้างไม่ครบ
 */
class SchemaSync
{
    /**
     * @return array{tables: string[], columns: string[], skipped: bool}
     *   tables  = รายชื่อตารางที่เพิ่งสร้าง
     *   columns = รายการ "ตาราง.คอลัมน์" ที่เพิ่งเพิ่ม
     *   skipped = true ถ้าไม่มีไฟล์ snapshot
     */
    public static function apply(): array
    {
        $file = __DIR__ . '/SchemaSnapshot.php';
        if (! is_file($file)) {
            return ['tables' => [], 'columns' => [], 'skipped' => true];
        }

        $snapshot = require $file;
        $tables   = $snapshot['tables'] ?? [];

        $db            = \Config\Database::connect();
        $createdTables = [];
        $addedColumns  = [];

        // ปิด FK check ชั่วคราว เพื่อสร้างตารางที่อ้างถึงกันได้โดยไม่ต้องเรียงลำดับ
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        try {
            // รอบ 1: สร้างตารางที่ยังไม่มี (พร้อม index/constraint ครบตามต้นแบบ)
            foreach ($tables as $name => $def) {
                if ($db->tableExists($name)) {
                    continue;
                }
                $sql = preg_replace('/^\s*CREATE TABLE/i', 'CREATE TABLE IF NOT EXISTS', $def['create'], 1);
                $db->query($sql);
                $createdTables[] = $name;
            }

            // รอบ 2: เติมคอลัมน์ที่ขาดให้ตารางที่มีอยู่แล้ว
            foreach ($tables as $name => $def) {
                if (in_array($name, $createdTables, true)) {
                    continue; // เพิ่งสร้างใหม่ — คอลัมน์ครบอยู่แล้ว
                }
                if (! $db->tableExists($name)) {
                    continue;
                }

                $existing = array_map('strtolower', $db->getFieldNames($name));
                $prev     = null;
                foreach ($def['order'] as $col) {
                    if (! in_array(strtolower($col), $existing, true)) {
                        $position = $prev !== null ? "AFTER `{$prev}`" : 'FIRST';
                        $db->query("ALTER TABLE `{$name}` ADD COLUMN {$def['columns'][$col]} {$position}");
                        $addedColumns[] = "{$name}.{$col}";
                    }
                    $prev = $col;
                }
            }
        } finally {
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        return ['tables' => $createdTables, 'columns' => $addedColumns, 'skipped' => false];
    }
}
