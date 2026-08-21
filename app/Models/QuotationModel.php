<?php

namespace App\Models;

use CodeIgniter\Model;

class QuotationModel extends Model
{
    protected $table            = 'quotations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'quote_number', 'customer_name', 'customer_address',
        'customer_tax_id', 'customer_phone',
        'subtotal', 'discount_pct', 'discount_amount', 'total_amount',
        'note', 'status', 'sale_id', 'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function generateQuoteNumber(): string
    {
        $prefix = 'QT-' . date('Ymd') . '-';
        $last   = $this->like('quote_number', $prefix, 'after')
                       ->orderBy('id', 'DESC')
                       ->first();
        $seq = $last ? ((int) substr($last['quote_number'], -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
