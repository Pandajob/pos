<?php

namespace App\Models;

use CodeIgniter\Model;

class CashSessionModel extends Model
{
    protected $table      = 'cash_sessions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'opened_by', 'opened_at', 'starting_cash',
        'closed_by', 'closed_at', 'closing_cash',
        'expected_cash', 'difference', 'note', 'status',
    ];

    /** Session ที่เปิดอยู่ตอนนี้ (ถ้ามี) พร้อมชื่อผู้เปิด */
    public function getOpenSession(): ?array
    {
        return $this->db->query("
            SELECT s.*, u.username AS opened_by_name, u.full_name AS opened_by_fullname
            FROM cash_sessions s
            LEFT JOIN users u ON u.id = s.opened_by
            WHERE s.status = 'open'
            ORDER BY s.opened_at DESC
            LIMIT 1
        ")->getRowArray() ?: null;
    }

    /** คำนวณเงินในลิ้นชักที่ควรมี = starting + sum(movements) */
    public function calcExpected(int $sessionId): float
    {
        $start = (float)($this->find($sessionId)['starting_cash'] ?? 0);
        $row   = $this->db->query("
            SELECT COALESCE(SUM(
                CASE WHEN type IN ('cash_in','sale') THEN amount
                     WHEN type IN ('cash_out','refund','void') THEN -amount
                     ELSE 0 END
            ), 0) AS total
            FROM cash_movements WHERE session_id = ?
        ", [$sessionId])->getRowArray();
        return $start + (float)($row['total'] ?? 0);
    }

    /** สรุปรายการตาม type ในช่วง session */
    public function getSessionSummary(int $sessionId): array
    {
        $rows = $this->db->query("
            SELECT type, COUNT(*) AS cnt, SUM(amount) AS total
            FROM cash_movements WHERE session_id = ?
            GROUP BY type
        ", [$sessionId])->getResultArray();
        $out = [];
        foreach ($rows as $r) $out[$r['type']] = $r;
        return $out;
    }
}
