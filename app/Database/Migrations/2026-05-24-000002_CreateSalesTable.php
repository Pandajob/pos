<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT',     'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bill_number'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'total_amount'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false, 'default' => '0.00'],
            'paid_amount'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false, 'default' => '0.00'],
            'change_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false, 'default' => '0.00'],
            'cashier'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'default' => 'Admin'],
            'note'          => ['type' => 'TEXT',    'null' => true, 'default' => null],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('bill_number');
        $this->forge->createTable('sales', true); // IF NOT EXISTS — ปลอดภัยบนเครื่องที่มีตารางอยู่แล้ว
    }

    public function down(): void
    {
        $this->forge->dropTable('sales');
    }
}
