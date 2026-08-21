<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\StockLogModel;

class Stock extends BaseController
{
    protected ProductModel  $productModel;
    protected StockLogModel $logModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->logModel     = new StockLogModel();
    }

    public function index()
    {
        $search   = $this->request->getGet('search') ?? '';
        $category = $this->request->getGet('category') ?? '';

        $builder = $this->productModel->orderBy('name');
        if ($search) {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('barcode', $search)
            ->groupEnd();
        }
        if ($category) {
            $builder->where('category', $category);
        }

        $products   = $builder->findAll();
        $categories = $this->productModel->select('category')
            ->where('category IS NOT NULL')
            ->groupBy('category')->orderBy('category')->findAll();

        $threshold = max(1, (int) setting('low_stock_threshold', '10'));

        return view('stock/index', [
            'title'      => 'จัดการสต๊อก',
            'products'   => $products,
            'categories' => array_column($categories, 'category'),
            'search'     => $search,
            'category'   => $category,
            'threshold'  => $threshold,
        ]);
    }

    // AJAX: ปรับสต๊อก
    public function adjust()
    {
        $productId = (int) $this->request->getPost('product_id');
        $type      = $this->request->getPost('type');   // in / out / adjust
        $qty       = (int) $this->request->getPost('qty');
        $note      = trim($this->request->getPost('note') ?? '');

        if (! in_array($type, ['in', 'out', 'adjust'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'ประเภทไม่ถูกต้อง']);
        }

        $product = $this->productModel->find($productId);
        if (! $product) {
            return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบสินค้า']);
        }

        // คำนวณจำนวนที่เปลี่ยน
        $currentStock = (int) $product['stock'];
        if ($type === 'adjust') {
            // adjust = ตั้งค่าสต๊อกใหม่โดยตรง
            $newStock  = max(0, $qty);
            $qtyChange = $newStock - $currentStock;
        } elseif ($type === 'in') {
            if ($qty <= 0) return $this->response->setJSON(['success' => false, 'message' => 'จำนวนต้องมากกว่า 0']);
            $qtyChange = $qty;
            $newStock  = $currentStock + $qty;
        } else { // out
            if ($qty <= 0) return $this->response->setJSON(['success' => false, 'message' => 'จำนวนต้องมากกว่า 0']);
            if ($qty > $currentStock) return $this->response->setJSON(['success' => false, 'message' => "สต๊อกไม่พอ (มี {$currentStock} ชิ้น)"]);
            $qtyChange = -$qty;
            $newStock  = $currentStock - $qty;
        }

        // อัปเดตสต๊อก
        $this->productModel->update($productId, ['stock' => $newStock]);

        // log
        $by = (session()->get('auth_user') ?? [])['full_name'] ?? 'Admin';
        $this->logModel->log($product, $type, $qtyChange, $note, $by);

        return $this->response->setJSON([
            'success'   => true,
            'new_stock' => $newStock,
            'message'   => "อัปเดตสต๊อก \"{$product['name']}\" เรียบร้อย (คงเหลือ: {$newStock} ชิ้น)",
        ]);
    }

    // หน้ารับสินค้าเข้าสต๊อก (สแกนบาร์โค้ด)
    public function receive()
    {
        return view('stock/receive', ['title' => 'รับสินค้าเข้าสต๊อก']);
    }

    // AJAX: ดึงข้อมูลสินค้าตาม ID
    public function product(int $id)
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบสินค้า']);
        }
        return $this->response->setJSON(['success' => true, 'product' => $product]);
    }

    // หน้าประวัติการปรับสต๊อก
    public function log()
    {
        $productId = (int) ($this->request->getGet('product_id') ?? 0);

        if ($productId) {
            $product = $this->productModel->find($productId);
            $logs    = $this->logModel->getByProduct($productId, 200);
        } else {
            $product = null;
            $logs    = $this->logModel->getRecent(200);
        }

        return view('stock/log', [
            'title'   => 'ประวัติการปรับสต๊อก',
            'logs'    => $logs,
            'product' => $product,
        ]);
    }
}
