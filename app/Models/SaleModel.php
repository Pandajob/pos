<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table            = 'sales';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'bill_number', 'total_amount', 'paid_amount', 'change_amount',
        'payment_method',
        'cashier', 'member_id', 'member_name', 'note',
        'discount_pct', 'discount_amount',
        'points_used', 'points_discount',
        'voided_at', 'void_reason',
        'credit_settled_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function generateBillNumber(): string
    {
        $prefix = 'BILL-' . date('Ymd') . '-';

        // ใช้ MAX บน DB เพื่อหลีกเลี่ยง race condition
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(bill_number, ?) AS UNSIGNED)) AS last_seq FROM sales WHERE bill_number LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        )->getRowArray();

        $seq = ($row['last_seq'] ?? 0) + 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /** สรุปยอดแต่ละเดือนในปีที่เลือก */
    public function getYearlySummary(int $year): array
    {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at,'%Y-%m') as ym,
                DATE_FORMAT(created_at,'%m')    as m,
                SUM(total_amount)               as revenue,
                COUNT(id)                       as bills,
                AVG(total_amount)               as avg_bill,
                SUM(CASE WHEN payment_method='cash'     THEN total_amount ELSE 0 END) as cash_rev,
                SUM(CASE WHEN payment_method='qr'       THEN total_amount ELSE 0 END) as qr_rev,
                SUM(CASE WHEN payment_method='transfer' THEN total_amount ELSE 0 END) as transfer_rev,
                SUM(CASE WHEN payment_method='credit'   THEN total_amount ELSE 0 END) as credit_rev
            FROM sales
            WHERE YEAR(created_at) = ?
              AND voided_at IS NULL
            GROUP BY DATE_FORMAT(created_at,'%Y-%m')
            ORDER BY ym
        ", [$year])->getResultArray();
    }

    /** ยอดขายปีที่มีข้อมูล */
    public function getAvailableYears(): array
    {
        return $this->db->query("
            SELECT DISTINCT YEAR(created_at) as y FROM sales
            WHERE voided_at IS NULL ORDER BY y DESC
        ")->getResultArray();
    }

    public function getMonthlySummary(string $yearMonth): array
    {
        return $this->select("DATE(created_at) as sale_date, SUM(total_amount) as revenue, COUNT(id) as bills")
                    ->where('created_at >=', $yearMonth . '-01 00:00:00')
                    ->where('created_at <', date('Y-m-01', strtotime($yearMonth . '-01 +1 month')) . ' 00:00:00')
                    ->where('voided_at IS NULL')
                    ->groupBy('DATE(created_at)')
                    ->orderBy('sale_date')
                    ->findAll();
    }

    /** ยอดขาย 7 วันล่าสุด สำหรับกราฟ */
    public function getLast7Days(): array
    {
        return $this->db->query("
            SELECT DATE(created_at) as d, SUM(total_amount) as revenue, COUNT(id) as bills
            FROM sales
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND voided_at IS NULL
            GROUP BY DATE(created_at)
            ORDER BY d
        ")->getResultArray();
    }

    /** ค้นหาบิล (date range + keyword) */
    public function searchBills(string $from, string $to, string $keyword = ''): array
    {
        $builder = $this->db->table('sales s')
            ->select('s.*, GROUP_CONCAT(si.product_name ORDER BY si.id SEPARATOR ", ") as product_list')
            ->join('sale_items si', 'si.sale_id = s.id', 'left')
            ->where('s.created_at >=', $from . ' 00:00:00')
            ->where('s.created_at <', self::dayAfter($to))
            ->groupBy('s.id')
            ->orderBy('s.created_at', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('s.bill_number', $keyword)
                ->orLike('s.member_name', $keyword)
                ->orLike('s.cashier', $keyword)
                ->orLike('si.product_name', $keyword)
            ->groupEnd();
        }

        return $builder->limit(200)->get()->getResultArray();
    }

    /** บิลเงินเชื่อที่ยังค้างชำระ (เก่าสุดขึ้นก่อน) — outstanding = ยอดบิล - ที่ชำระแล้ว */
    public function getOutstandingCreditBills(): array
    {
        return $this->db->query("
            SELECT s.*, (s.total_amount - s.paid_amount) AS outstanding
            FROM sales s
            WHERE s.payment_method = 'credit'
              AND s.credit_settled_at IS NULL
              AND s.voided_at IS NULL
            ORDER BY s.created_at ASC
        ")->getResultArray();
    }

    /** บิลเงินเชื่อที่เก็บครบแล้วล่าสุด */
    public function getSettledCreditBills(int $limit = 30): array
    {
        return $this->where('payment_method', 'credit')
                    ->where('credit_settled_at IS NOT NULL')
                    ->where('voided_at IS NULL')
                    ->orderBy('credit_settled_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /** จำนวนบิลเงินเชื่อที่ยังค้าง (ใช้แสดง badge ในเมนู) */
    public function countOutstandingCredit(): int
    {
        return $this->where('payment_method', 'credit')
                    ->where('credit_settled_at IS NULL')
                    ->where('voided_at IS NULL')
                    ->countAllResults();
    }

    /** void บิล (admin only) */
    public function voidBill(int $id, string $reason): bool
    {
        return $this->update($id, [
            'voided_at'   => date('Y-m-d H:i:s'),
            'void_reason' => $reason,
        ]);
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

    public function getDailySales(string $date): array
    {
        return $this->where('created_at >=', $date . ' 00:00:00')
                    ->where('created_at <', self::dayAfter($date))
                    ->where('voided_at IS NULL')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    public function getDailySummary(string $date): array
    {
        $row = $this->selectSum('total_amount', 'total_revenue')
                    ->selectCount('id', 'total_bills')
                    ->where('created_at >=', $date . ' 00:00:00')
                    ->where('created_at <', self::dayAfter($date))
                    ->where('voided_at IS NULL')
                    ->first();

        return $row ?? ['total_revenue' => 0, 'total_bills' => 0];
    }
}
