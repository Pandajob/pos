<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationItemModel extends Model
{
    protected $table            = 'quotation_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'quotation_id', 'product_id', 'product_name', 'barcode',
        'price', 'quantity', 'subtotal',
    ];

    public function getItemsByQuoteId(int $quoteId): array
    {
        return $this->where('quotation_id', $quoteId)->findAll();
    }
}
