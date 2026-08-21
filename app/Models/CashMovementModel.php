<?php

namespace App\Models;

use CodeIgniter\Model;

class CashMovementModel extends Model
{
    protected $table      = 'cash_movements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'session_id', 'type', 'amount', 'note',
        'created_by', 'created_at', 'ip_address', 'reference_id',
    ];

    public function getBySession(int $sessionId): array
    {
        return $this->db->query("
            SELECT m.*, u.username, u.full_name
            FROM cash_movements m
            LEFT JOIN users u ON u.id = m.created_by
            WHERE m.session_id = ?
            ORDER BY m.created_at DESC
        ", [$sessionId])->getResultArray();
    }

    public function getRecentLog(int $limit = 100): array
    {
        return $this->db->query("
            SELECT m.*, u.username, u.full_name,
                   s.opened_at, s.status AS session_status
            FROM cash_movements m
            LEFT JOIN users u ON u.id = m.created_by
            LEFT JOIN cash_sessions s ON s.id = m.session_id
            ORDER BY m.created_at DESC
            LIMIT ?
        ", [$limit])->getResultArray();
    }

    /** บันทึก movement พร้อม IP */
    public function log(array $data): int|bool
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['ip_address'] = \Config\Services::request()->getIPAddress();
        return $this->insert($data);
    }
}
