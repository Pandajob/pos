<?php

namespace App\Models;

use CodeIgniter\Model;

/** ประวัติการรับชำระหนี้ของบิลขายเงินเชื่อ (payment_method = 'credit') */
class CreditPaymentModel extends Model
{
    protected $table            = 'credit_payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'sale_id', 'member_id', 'amount', 'method', 'note', 'received_by',
        'created_at',
    ];

    // ตารางนี้มีแค่ created_at — ห้ามใช้ $updatedField = false (บั๊ก CI4: integer array key)
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /** รายการชำระของบิลหนึ่ง (เก่า→ใหม่) */
    public function getBySale(int $saleId): array
    {
        return $this->where('sale_id', $saleId)->orderBy('id', 'ASC')->findAll();
    }
}
