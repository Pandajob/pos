<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table            = 'members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = ['code', 'name', 'phone', 'address', 'tax_id', 'points', 'tier_id'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'  => 'required|max_length[255]',
        'phone' => 'required|max_length[20]',
    ];

    /** คืนสมาชิกทั้งหมดพร้อมข้อมูล tier */
    public function getAllWithTiers(): array
    {
        return $this->db->table('members m')
            ->select('m.*, ct.name as tier_name, ct.badge_color, ct.discount_pct as tier_discount, ct.is_wholesale')
            ->join('customer_tiers ct', 'ct.id = m.tier_id', 'left')
            ->where('m.deleted_at IS NULL')
            ->orderBy('m.name')
            ->get()->getResultArray();
    }

    public function generateCode(): string
    {
        $last = $this->withDeleted()->orderBy('id', 'DESC')->first();
        $seq  = $last ? $last['id'] + 1 : 1;
        return 'M' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function search(string $q): array
    {
        return $this->groupStart()
                    ->like('name', $q)
                    ->orLike('phone', $q)
                    ->orLike('code', $q)
                    ->groupEnd()
                    ->orderBy('name')
                    ->findAll(20);
    }

    /** ค้นหาสมาชิกพร้อม tier info */
    public function searchWithTiers(string $q): array
    {
        return $this->db->table('members m')
            ->select('m.*, ct.name as tier_name, ct.badge_color, ct.discount_pct as tier_discount, ct.is_wholesale')
            ->join('customer_tiers ct', 'ct.id = m.tier_id', 'left')
            ->groupStart()
                ->like('m.name', $q)
                ->orLike('m.phone', $q)
                ->orLike('m.code', $q)
            ->groupEnd()
            ->where('m.deleted_at IS NULL')
            ->orderBy('m.name')
            ->limit(20)
            ->get()->getResultArray();
    }

    public function addPoints(int $memberId, int $points): void
    {
        $this->set('points', "points + {$points}", false)
             ->where('id', $memberId)
             ->update();
    }

    public function subPoints(int $memberId, int $points): void
    {
        $this->db->query(
            'UPDATE members SET points = GREATEST(0, points - ?) WHERE id = ?',
            [$points, $memberId]
        );
    }
}
