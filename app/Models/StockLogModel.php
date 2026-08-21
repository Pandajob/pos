<?php

namespace App\Models;

use CodeIgniter\Model;

class StockLogModel extends Model
{
    protected $table         = 'stock_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'product_id', 'product_name', 'type',
        'qty_before', 'qty_change', 'qty_after',
        'note', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function log(array $product, string $type, int $qtyChange, string $note = '', string $by = ''): void
    {
        $before = (int) $product['stock'];
        $after  = $before + $qtyChange;
        $this->insert([
            'product_id'   => $product['id'],
            'product_name' => $product['name'],
            'type'         => $type,
            'qty_before'   => $before,
            'qty_change'   => $qtyChange,
            'qty_after'    => $after,
            'note'         => $note,
            'created_by'   => $by,
        ]);
    }

    public function getByProduct(int $productId, int $limit = 50): array
    {
        return $this->where('product_id', $productId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }

    public function getRecent(int $limit = 100): array
    {
        return $this->orderBy('created_at', 'DESC')->findAll($limit);
    }
}
