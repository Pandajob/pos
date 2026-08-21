<?php

namespace App\Controllers;

use App\Models\ReturnModel;
use App\Models\ReturnItemModel;
use App\Models\SaleModel;
use App\Models\SaleItemModel;

class Returns extends BaseController
{
    protected ReturnModel     $returnModel;
    protected ReturnItemModel $returnItemModel;
    protected SaleModel       $saleModel;
    protected SaleItemModel   $saleItemModel;

    public function __construct()
    {
        $this->returnModel     = new ReturnModel();
        $this->returnItemModel = new ReturnItemModel();
        $this->saleModel       = new SaleModel();
        $this->saleItemModel   = new SaleItemModel();
    }

    /** รายการคืนสินค้าทั้งหมด */
    public function index()
    {
        $status  = $this->request->getGet('status') ?? 'pending';
        $returns = $this->returnModel->getWithSaleInfo(in_array($status, ['pending','approved','rejected','']) ? $status : 'pending');

        return view('returns/index', [
            'title'        => 'รายการคืนสินค้า',
            'returns'      => $returns,
            'status'       => $status,
            'pendingCount' => $this->returnModel->countPending(),
        ]);
    }

    /** ฟอร์มสร้างการคืนสินค้าจากบิล */
    public function create(int $saleId)
    {
        $sale = $this->saleModel->find($saleId);
        if (! $sale) {
            return redirect()->to('/returns')->with('error', 'ไม่พบบิลนี้');
        }

        // มีรายการ pending อยู่แล้ว
        $existing = $this->returnModel
            ->where('sale_id', $saleId)
            ->where('status', 'pending')
            ->first();
        if ($existing) {
            return redirect()->to('/returns')
                ->with('error', 'บิล ' . $sale['bill_number'] . ' มีรายการรออนุมัติคืนอยู่แล้ว (เลขที่: ' . $existing['return_number'] . ')');
        }

        $items = $this->saleItemModel->getItemsBySaleId($saleId);

        return view('returns/create', [
            'title' => 'สร้างรายการคืนสินค้า',
            'sale'  => $sale,
            'items' => $items,
        ]);
    }

    /** บันทึกรายการคืน → status = pending */
    public function store()
    {
        $saleId = (int) $this->request->getPost('sale_id');
        $reason = trim((string) ($this->request->getPost('reason') ?? ''));
        $qtys   = $this->request->getPost('quantities') ?? [];   // [sale_item_id => qty]

        if (empty($reason)) {
            return redirect()->back()->withInput()
                ->with('error', 'กรุณาระบุเหตุผลการคืนสินค้า');
        }

        $sale = $this->saleModel->find($saleId);
        if (! $sale) {
            return redirect()->to('/returns')->with('error', 'ไม่พบบิล');
        }

        // รวบรวมรายการที่ต้องการคืน
        $returnItems = [];
        $totalRefund = 0.0;

        foreach ($qtys as $saleItemId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $saleItem = $this->saleItemModel->find((int) $saleItemId);
            if (! $saleItem || (int) $saleItem['sale_id'] !== $saleId) continue;
            if ($qty > $saleItem['quantity']) $qty = $saleItem['quantity'];

            $refund       = round((float) $saleItem['price'] * $qty, 2);
            $totalRefund += $refund;

            $returnItems[] = [
                'sale_item_id' => (int) $saleItemId,
                'product_id'   => $saleItem['product_id'],
                'product_name' => $saleItem['product_name'],
                'unit_price'   => $saleItem['price'],
                'quantity'     => $qty,
                'refund_amount'=> $refund,
            ];
        }

        if (empty($returnItems)) {
            return redirect()->back()->withInput()
                ->with('error', 'กรุณาเลือกสินค้าที่ต้องการคืนอย่างน้อย 1 รายการ');
        }

        // สร้าง return header
        $returnNumber = $this->returnModel->generateReturnNumber();
        $returnId = $this->returnModel->insert([
            'return_number' => $returnNumber,
            'sale_id'       => $saleId,
            'cashier'       => (session()->get('auth_user') ?? [])['full_name'] ?? setting('cashier_name', 'Admin'),
            'reason'        => $reason,
            'total_refund'  => $totalRefund,
            'status'        => 'pending',
        ]);

        // สร้าง return items
        foreach ($returnItems as $item) {
            $this->returnItemModel->insert(array_merge(['return_id' => $returnId], $item));
        }

        return redirect()->to('/returns')
            ->with('success', "สร้างรายการคืนสินค้า {$returnNumber} สำเร็จ รอการอนุมัติ");
    }

    /** อนุมัติ → คืนสต๊อก */
    public function approve(int $id)
    {
        $return = $this->returnModel->find($id);
        if (! $return || $return['status'] !== 'pending') {
            return redirect()->to('/returns')->with('error', 'ไม่พบรายการหรือดำเนินการไปแล้ว');
        }

        $items = $this->returnItemModel->getByReturnId($id);
        $db    = db_connect();

        foreach ($items as $item) {
            $db->query(
                'UPDATE `products` SET `stock` = `stock` + ? WHERE `id` = ?',
                [$item['quantity'], $item['product_id']]
            );
        }

        $this->returnModel->update($id, ['status' => 'approved']);

        // ถ้าบิลเดิมจ่ายเงินสด → ตัดเงินคืนออกจากลิ้นชักกะปัจจุบัน
        $sale = $this->saleModel->find($return['sale_id']);
        if ($sale && ($sale['payment_method'] ?? 'cash') === 'cash') {
            $cashSession = (new \App\Models\CashSessionModel())->getOpenSession();
            if ($cashSession) {
                (new \App\Models\CashMovementModel())->log([
                    'session_id'   => $cashSession['id'],
                    'type'         => 'refund',
                    'amount'       => (float) $return['total_refund'],
                    'note'         => 'คืนสินค้า ' . $return['return_number'],
                    'created_by'   => (session()->get('auth_user') ?? [])['id'] ?? null,
                    'reference_id' => $id,
                ]);
            }
        }

        return redirect()->to('/returns')
            ->with('success', 'อนุมัติการคืนสินค้า ' . $return['return_number'] . ' สำเร็จ — สต๊อกถูกอัพเดตแล้ว');
    }

    /** ปฏิเสธ */
    public function reject(int $id)
    {
        $return = $this->returnModel->find($id);
        if (! $return || $return['status'] !== 'pending') {
            return redirect()->to('/returns')->with('error', 'ไม่พบรายการหรือดำเนินการไปแล้ว');
        }

        $this->returnModel->update($id, ['status' => 'rejected']);

        return redirect()->to('/returns')
            ->with('success', 'ปฏิเสธรายการคืนสินค้า ' . $return['return_number'] . ' แล้ว');
    }

    /** รายละเอียดการคืน */
    public function detail(int $id)
    {
        $return = $this->returnModel->find($id);
        if (! $return) {
            return redirect()->to('/returns')->with('error', 'ไม่พบรายการ');
        }

        return view('returns/detail', [
            'title'  => 'รายละเอียด ' . $return['return_number'],
            'return' => $return,
            'items'  => $this->returnItemModel->getByReturnId($id),
            'sale'   => $this->saleModel->find($return['sale_id']),
        ]);
    }
}
