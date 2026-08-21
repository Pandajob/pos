<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoidToSales extends Migration
{
    public function up(): void
    {
        // voided_at / void_reason
        if (! $this->db->fieldExists('voided_at', 'sales')) {
            $this->forge->addColumn('sales', [
                'voided_at'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
                'void_reason' => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => true, 'after' => 'voided_at'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('voided_at', 'sales')) {
            $this->forge->dropColumn('sales', ['voided_at', 'void_reason']);
        }
    }
}
