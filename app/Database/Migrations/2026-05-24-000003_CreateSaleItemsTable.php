<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSaleItemsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT',     'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sale_id'      => ['type' => 'INT',     'constraint' => 11, 'unsigned' => true, 'null' => false],
            'product_id'   => ['type' => 'INT',     'constraint' => 11, 'unsigned' => true, 'null' => false],
            'product_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'barcode'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            'price'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'quantity'     => ['type' => 'INT',     'constraint' => 11, 'null' => false],
            'subtotal'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'created_at'   => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sale_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('sale_id', 'sales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sale_items', true); // IF NOT EXISTS — ปลอดภัยบนเครื่องที่มีตารางอยู่แล้ว
    }

    public function down(): void
    {
        $this->forge->dropTable('sale_items');
    }
}
