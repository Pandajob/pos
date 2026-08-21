<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleItemModel extends Model
{
    protected $table            = 'sale_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'sale_id', 'product_id', 'product_name', 'barcode', 'price', 'quantity', 'subtotal', 'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getItemsBySaleId(int $saleId): array
    {
        return $this->where('sale_id', $saleId)->findAll();
    }

    /** กำไรช่วงวันที่ (สำหรับ export) */
    public function getProfitRange(string $from, string $to): array
    {
        return $this->db->table('sale_items si')
            ->select('si.product_name,
                      SUM(si.quantity) as qty_sold,
                      SUM(si.subtotal) as revenue,
                      SUM(si.quantity * COALESCE(p.cost, 0)) as total_cost,
                      SUM(si.subtotal) - SUM(si.quantity * COALESCE(p.cost, 0)) as profit')
            ->join('sales s', 's.id = si.sale_id')
            ->join('products p', 'p.id = si.product_id', 'left')
            ->where('s.created_at >=', $from . ' 00:00:00')
            ->where('s.created_at <', self::dayAfter($to))
            ->where('s.voided_at IS NULL')
            ->groupBy('si.product_id, si.product_name')
            ->orderBy('profit', 'DESC')
            ->get()->getResultArray();
    }

    public function getDailyProfit(string $date): array
    {
        return $this->db->table('sale_items si')
            ->select('
                si.product_name,
                si.product_id,
                SUM(si.quantity) as qty_sold,
                SUM(si.subtotal) as revenue,
                MAX(p.cost) as unit_cost,
                SUM(si.quantity * COALESCE(p.cost, 0)) as total_cost,
                SUM(si.subtotal) - SUM(si.quantity * COALESCE(p.cost, 0)) as profit
            ')
            ->join('sales s', 's.id = si.sale_id')
            ->join('products p', 'p.id = si.product_id', 'left')
            ->where('s.created_at >=', $date . ' 00:00:00')
            ->where('s.created_at <', self::dayAfter($date))
            ->groupBy('si.product_id, si.product_name')
            ->orderBy('profit', 'DESC')
            ->get()->getResultArray();
    }

    public function getProfitSummary(string $date): array
    {
        $row = $this->db->table('sale_items si')
            ->select('
                SUM(si.subtotal) as revenue,
                SUM(si.quantity * COALESCE(p.cost, 0)) as total_cost,
                SUM(si.subtotal) - SUM(si.quantity * COALESCE(p.cost, 0)) as profit
            ')
            ->join('sales s', 's.id = si.sale_id')
            ->join('products p', 'p.id = si.product_id', 'left')
            ->where('s.created_at >=', $date . ' 00:00:00')
            ->where('s.created_at <', self::dayAfter($date))
            ->get()->getRowArray();

        return $row ?? ['revenue' => 0, 'total_cost' => 0, 'profit' => 0];
    }

    /** ประวัติการซื้อของสมาชิก */
    public function getMemberHistory(int $memberId, int $limit = 50): array
    {
        return $this->db->table('sales s')
            ->select('s.id as sale_id, s.bill_number, s.total_amount, s.created_at, s.voided_at,
                      GROUP_CONCAT(si.product_name, " x", si.quantity ORDER BY si.id SEPARATOR " | ") as items_summary,
                      COUNT(si.id) as item_count')
            ->join('sale_items si', 'si.sale_id = s.id')
            ->where('s.member_id', $memberId)
            ->groupBy('s.id')
            ->orderBy('s.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * เที่ยงคืนของวันถัดไป — ใช้เทียบแบบช่วง (created_at >= X AND < Y)
     * แทนการเขียน DATE(created_at) = X ซึ่งทำให้ MySQL ใช้ index ไม่ได้
     * ต้องไล่อ่านทั้งตาราง ยิ่งบิลเยอะยิ่งช้า
     */
    private static function dayAfter(string $date): string
    {
        return date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
    }

    public function getTopProducts(string $date, int $limit = 10): array
    {
        return $this->select('sale_items.product_name, sale_items.barcode, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_revenue')
                    ->join('sales', 'sales.id = sale_items.sale_id')
                    ->where('sales.created_at >=', $date . ' 00:00:00')
                    ->where('sales.created_at <', self::dayAfter($date))
                    ->groupBy('sale_items.product_id')
                    ->orderBy('total_qty', 'DESC')
                    ->findAll($limit);
    }
}
