<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ดัชนี (index) เพื่อความเร็ว — สำคัญมากกับเครื่องสเปคน้อย
 *
 * ก่อนหน้านี้ตาราง sales มีแค่ PRIMARY กับ uq_bill_number เท่านั้น
 * ทุกครั้งที่เปิดแดชบอร์ด/รายงาน/หน้าไหนก็ตาม MySQL ต้องไล่อ่านทั้งตาราง
 * ยิ่งขายมานานยิ่งช้าลงเรื่อย ๆ
 *
 * - idx_sales_created  : แดชบอร์ด, รายงานรายวัน/รายเดือน, บิลล่าสุด
 * - idx_sales_member   : ประวัติการซื้อของสมาชิก
 * - idx_sales_credit   : badge "ค้างชำระ" ที่แถบเมนู — ทำงานทุกหน้าที่เปิด
 * - idx_items_product  : รายงานสินค้าขายดี
 */
class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        $this->addIndex('sales',      'idx_sales_created', '`created_at`');
        $this->addIndex('sales',      'idx_sales_member',  '`member_id`');
        $this->addIndex('sales',      'idx_sales_credit',  '`payment_method`, `credit_settled_at`');
        $this->addIndex('sale_items', 'idx_items_product', '`product_id`, `sale_id`');
    }

    public function down()
    {
        // ไม่ drop อะไร — นโยบายอัพเดทฐานข้อมูลแบบ additive เท่านั้น
    }

    /**
     * สร้าง index แบบกันรันซ้ำ และข้ามไปเงียบ ๆ ถ้าคอลัมน์ยังไม่มี
     * (เช่น credit_settled_at ที่มาจาก migration ก่อนหน้า)
     */
    private function addIndex(string $table, string $name, string $columns): void
    {
        try {
            if (! $this->db->tableExists($table)) {
                return;
            }
            $exists = $this->db->query(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$name]
            )->getNumRows();

            if ($exists === 0) {
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$name}` ({$columns})");
            }
        } catch (\Throwable $e) {
            // คอลัมน์ยังไม่มี / สิทธิ์ไม่พอ — ข้ามไป ไม่ทำให้การอัพเดทฐานข้อมูลล้มทั้งชุด
            log_message('warning', "ข้าม index {$name} บน {$table}: " . $e->getMessage());
        }
    }
}
