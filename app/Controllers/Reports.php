<?php

namespace App\Controllers;

use App\Models\SaleModel;
use App\Models\SaleItemModel;

class Reports extends BaseController
{
    protected SaleModel     $saleModel;
    protected SaleItemModel $saleItemModel;

    public function __construct()
    {
        $this->saleModel     = new SaleModel();
        $this->saleItemModel = new SaleItemModel();
    }

    public function index()
    {
        return redirect()->to('/reports/daily');
    }

    public function daily()
    {
        $date    = $this->request->getGet('date') ?? date('Y-m-d');
        $sales   = $this->saleModel->getDailySales($date);
        $summary = $this->saleModel->getDailySummary($date);
        $topProducts = $this->saleItemModel->getTopProducts($date, 5);

        foreach ($sales as &$sale) {
            $sale['items'] = $this->saleItemModel->getItemsBySaleId($sale['id']);
        }
        unset($sale);

        return view('reports/index', [
            'title'       => 'รายงานยอดขาย',
            'date'        => $date,
            'sales'       => $sales,
            'summary'     => $summary,
            'topProducts' => $topProducts,
        ]);
    }

    public function profit()
    {
        $date    = $this->request->getGet('date') ?? date('Y-m-d');
        $items   = $this->saleItemModel->getDailyProfit($date);
        $summary = $this->saleItemModel->getProfitSummary($date);

        return view('reports/profit', [
            'title'   => 'รายงานกำไร',
            'date'    => $date,
            'items'   => $items,
            'summary' => $summary,
        ]);
    }

    public function bill(int $saleId)
    {
        $sale = $this->saleModel->find($saleId);
        if (! $sale) {
            return redirect()->to('/reports/daily')->with('error', 'ไม่พบบิล');
        }
        $items = $this->saleItemModel->getItemsBySaleId($saleId);

        return view('receipt/view', [
            'title'          => 'ใบเสร็จ #' . $sale['bill_number'],
            'sale'           => $sale,
            'items'          => $items,
            'shopName'       => setting('shop_name', 'ร้านของเรา'),
            'shopAddress'    => setting('shop_address', ''),
            'shopTaxId'      => setting('shop_tax_id', ''),
            'shopPointsRate' => max(1, (int) setting('points_rate', '10')),
        ]);
    }

    /** รายงานสรุปรายเดือน */
    public function monthly()
    {
        $year  = (int) ($this->request->getGet('year') ?? date('Y'));
        $rows  = $this->saleModel->getYearlySummary($year);
        $years = $this->saleModel->getAvailableYears();
        if (empty($years)) { $years = [['y' => date('Y')]]; }

        // เติมเดือนที่ไม่มียอดให้ครบ 12 เดือน
        $byMonth = [];
        foreach ($rows as $r) { $byMonth[(int)$r['m']] = $r; }
        $thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.',
                       'ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = $byMonth[$m] ?? [
                'ym' => $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT),
                'm'  => $m, 'revenue' => 0, 'bills' => 0, 'avg_bill' => 0,
                'cash_rev' => 0, 'qr_rev' => 0, 'transfer_rev' => 0, 'credit_rev' => 0,
            ];
            $months[$m] += ['credit_rev' => 0];
            $months[$m]['label'] = $thaiMonths[$m];
        }

        $totalRevenue = array_sum(array_column($rows, 'revenue'));
        $totalBills   = array_sum(array_column($rows, 'bills'));

        return view('reports/monthly', [
            'title'        => 'รายงานรายเดือน',
            'year'         => $year,
            'years'        => array_column($years, 'y'),
            'months'       => $months,
            'totalRevenue' => $totalRevenue,
            'totalBills'   => $totalBills,
        ]);
    }

    /** ค้นหาบิล (date range + keyword) */
    public function search()
    {
        $from    = $this->request->getGet('from')    ?? date('Y-m-d', strtotime('-30 days'));
        $to      = $this->request->getGet('to')      ?? date('Y-m-d');
        $keyword = $this->request->getGet('keyword') ?? '';

        $sales   = $this->saleModel->searchBills($from, $to, $keyword);
        $total   = array_sum(array_filter(array_column($sales, 'total_amount'), fn($r) => true));

        return view('reports/search', [
            'title'   => 'ค้นหาบิล',
            'sales'   => $sales,
            'from'    => $from,
            'to'      => $to,
            'keyword' => $keyword,
            'total'   => $total,
        ]);
    }

    /** Export CSV รายงาน */
    public function export()
    {
        $type = $this->request->getGet('type') ?? 'daily';
        $from = $this->request->getGet('from') ?? date('Y-m-d');
        $to   = $this->request->getGet('to')   ?? date('Y-m-d');

        if ($type === 'profit') {
            $rows = $this->saleItemModel->getProfitRange($from, $to);
            $filename = "profit_{$from}_{$to}.csv";
            $headers  = ['สินค้า', 'จำนวนขาย', 'รายได้', 'ต้นทุน', 'กำไร'];
            $data = array_map(fn($r) => [
                $r['product_name'],
                $r['qty_sold'],
                $r['revenue'],
                $r['total_cost'],
                $r['profit'],
            ], $rows);
        } else {
            $rows = $this->saleModel->searchBills($from, $to);
            $filename = "sales_{$from}_{$to}.csv";
            $headers  = ['เลขบิล', 'วันที่', 'พนักงาน', 'สมาชิก', 'รายการ', 'ยอดรวม', 'รับเงิน', 'เงินทอน', 'สถานะ'];
            $data = array_map(fn($r) => [
                $r['bill_number'],
                $r['created_at'],
                $r['cashier'],
                $r['member_name'] ?? '',
                $r['product_list'] ?? '',
                $r['total_amount'],
                $r['paid_amount'],
                $r['change_amount'],
                empty($r['voided_at']) ? 'ปกติ' : 'ยกเลิก',
            ], $rows);
        }

        $response = $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        ob_start();
        $fp = fopen('php://output', 'w');
        fputs($fp, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($fp, $headers);
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        $csv = ob_get_clean();

        return $response->setBody($csv);
    }
}
