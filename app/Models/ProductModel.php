<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'name', 'barcode', 'price', 'wholesale_price', 'cost', 'stock', 'category', 'description',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'  => 'required|max_length[255]',
        'price' => 'required|numeric|greater_than_equal_to[0]',
        'stock' => 'required|integer|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'name'  => ['required' => 'กรุณากรอกชื่อสินค้า'],
        'price' => ['required' => 'กรุณากรอกราคา', 'decimal' => 'ราคาต้องเป็นตัวเลข'],
        'stock' => ['required' => 'กรุณากรอกจำนวนสต๊อก', 'integer' => 'สต๊อกต้องเป็นจำนวนเต็ม'],
    ];

    public function searchByNameOrBarcode(string $query): array
    {
        return $this->groupStart()
                    ->like('name', $query)
                    ->orLike('barcode', $query)
                    ->groupEnd()
                    ->orderBy('name')
                    ->findAll(30);
    }

    // ปล่อยบาร์โค้ดจากสินค้าที่ถูกลบ (soft-deleted) เพื่อไม่ให้ชน unique index ตอน insert ใหม่
    public function releaseBarcodeFromDeleted(string $barcode): void
    {
        $this->db->query(
            "UPDATE `{$this->table}` SET `barcode` = NULL WHERE `barcode` = ? AND `deleted_at` IS NOT NULL",
            [$barcode]
        );
    }

    public function findByBarcode(string $barcode): ?array
    {
        return $this->where('barcode', $barcode)->first();
    }

    public function decreaseStock(int $productId, int $quantity): bool
    {
        return $this->db->query(
            "UPDATE `{$this->table}` SET `stock` = `stock` - ? WHERE `id` = ? AND `deleted_at` IS NULL",
            [$quantity, $productId]
        );
    }

    public function increaseStock(int $productId, int $quantity): bool
    {
        return $this->db->query(
            "UPDATE `{$this->table}` SET `stock` = `stock` + ? WHERE `id` = ? AND `deleted_at` IS NULL",
            [$quantity, $productId]
        );
    }

    public function getLowStock(int $threshold = 10): array
    {
        return $this->where('stock <=', $threshold)
                    ->orderBy('stock')
                    ->findAll();
    }
}
