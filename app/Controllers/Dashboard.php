<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\SaleModel;
use App\Models\ReturnModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $productModel = new ProductModel();
        $saleModel    = new SaleModel();
        $returnModel  = new ReturnModel();

        $todaySummary     = $saleModel->getDailySummary(date('Y-m-d'));
        $yesterdaySummary = $saleModel->getDailySummary(date('Y-m-d', strtotime('-1 day')));

        $threshold = max(1, (int) setting('low_stock_threshold', '10'));
        // ยิง getLowStock ครั้งเดียวแล้วใช้ซ้ำ — เดิมเรียก 2 รอบ (นับ + แสดงรายการ)
        $lowStock  = $productModel->getLowStock($threshold);

        $data = [
            'title'                 => 'แดชบอร์ด',
            'total_products'        => $productModel->where('deleted_at IS NULL')->countAllResults(),
            'low_stock'             => count($lowStock),
            'today_summary'         => $todaySummary,
            'yesterday_summary'     => $yesterdaySummary,
            'recent_sales'          => $saleModel->orderBy('created_at', 'DESC')->limit(8)->findAll(),
            'low_stock_items'       => $lowStock,
            'pending_returns'       => $returnModel->getPending(5),
            'pending_returns_count' => $returnModel->countPending(),
            'chart_7days'           => $saleModel->getLast7Days(),
        ];

        return view('dashboard/index', $data);
    }
}
