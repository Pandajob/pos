<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT',          'constraint' => 11,  'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR',       'constraint' => 255, 'null' => false],
            'barcode'     => ['type' => 'VARCHAR',       'constraint' => 100, 'null' => true,  'default' => null],
            'price'       => ['type' => 'DECIMAL',       'constraint' => '10,2', 'null' => false, 'default' => '0.00'],
            'stock'       => ['type' => 'INT',           'constraint' => 11,  'null' => false, 'default' => 0],
            'category'    => ['type' => 'VARCHAR',       'constraint' => 100, 'null' => true,  'default' => null],
            'description' => ['type' => 'TEXT',          'null' => true,  'default' => null],
            'created_at'  => ['type' => 'DATETIME',      'null' => true,  'default' => null],
            'updated_at'  => ['type' => 'DATETIME',      'null' => true,  'default' => null],
            'deleted_at'  => ['type' => 'DATETIME',      'null' => true,  'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('barcode');
        $this->forge->createTable('products', true); // IF NOT EXISTS — ปลอดภัยบนเครื่องที่มีตารางอยู่แล้ว
    }

    public function down(): void
    {
        $this->forge->dropTable('products');
    }
}
