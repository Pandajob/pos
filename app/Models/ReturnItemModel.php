<?php

namespace App\Models;

use CodeIgniter\Model;

class ReturnItemModel extends Model
{
    protected $table            = 'return_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'return_id', 'sale_item_id', 'product_id',
        'product_name', 'unit_price', 'quantity', 'refund_amount',
    ];
    protected $useTimestamps = false;

    public function getByReturnId(int $returnId): array
    {
        return $this->where('return_id', $returnId)->findAll();
    }
}
