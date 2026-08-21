<?php

namespace App\Models;

use CodeIgniter\Model;

class ReturnModel extends Model
{
    protected $table            = 'returns';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'return_number', 'sale_id', 'cashier', 'reason',
        'total_refund', 'status', 'note',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function generateReturnNumber(): string
    {
        $prefix = 'RTN-' . date('Ymd') . '-';
        $last   = $this->like('return_number', $prefix, 'after')
                       ->orderBy('id', 'DESC')
                       ->first();
        $seq = $last ? ((int) substr($last['return_number'], -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function countPending(): int
    {
        return (int) $this->where('status', 'pending')->countAllResults();
    }

    /** รายการคืนพร้อมเลขบิล */
    public function getWithSaleInfo(string $status = ''): array
    {
        $builder = $this->db->table('returns r')
            ->select('r.*, s.bill_number')
            ->join('sales s', 's.id = r.sale_id', 'left');

        if ($status !== '') {
            $builder->where('r.status', $status);
        }

        return $builder->orderBy('r.created_at', 'DESC')->get()->getResultArray();
    }

    public function getPending(int $limit = 10): array
    {
        return $this->db->table('returns r')
            ->select('r.*, s.bill_number')
            ->join('sales s', 's.id = r.sale_id', 'left')
            ->where('r.status', 'pending')
            ->orderBy('r.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }
}
