<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ระบบขายเงินเชื่อ / ค้างชำระ (ลูกหนี้การค้า)
 * - sales.credit_settled_at : เวลาเก็บหนี้ครบ (NULL = ยังค้างอยู่) เฉพาะบิล payment_method = 'credit'
 * - credit_payments         : ประวัติการรับชำระหนี้ทีละงวด
 */
class AddCreditSystem extends Migration
{
    public function up()
    {
        // เพิ่มคอลัมน์ให้ตาราง sales (กันรันซ้ำด้วย fieldExists)
        if (! $this->db->fieldExists('credit_settled_at', 'sales')) {
            $this->forge->addColumn('sales', [
                'credit_settled_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'payment_method',
                ],
            ]);
        }

        // ตารางประวัติการรับชำระหนี้
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'sale_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'member_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'cash',
            ],
            'note' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'received_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sale_id');
        $this->forge->addKey('member_id');
        $this->forge->createTable('credit_payments', true); // IF NOT EXISTS
    }

    public function down()
    {
        // ไม่ drop อะไร — นโยบายอัพเดทฐานข้อมูลแบบ additive เท่านั้น
    }
}
