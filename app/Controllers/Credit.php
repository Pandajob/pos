<?php

namespace App\Controllers;

use App\Models\CreditPaymentModel;
use App\Models\MemberModel;
use App\Models\SaleModel;

/** ลูกหนี้การค้า — บิลขายเงินเชื่อ (payment_method = 'credit') และการรับชำระหนี้ */
class Credit extends BaseController
{
    protected SaleModel          $saleModel;
    protected CreditPaymentModel $paymentModel;
    protected MemberModel        $memberModel;

    public function __construct()
    {
        $this->saleModel    = new SaleModel();
        $this->paymentModel = new CreditPaymentModel();
        $this->memberModel  = new MemberModel();
    }

    public function index()
    {
        $bills = $this->saleModel->getOutstandingCreditBills();

        // สรุปยอดค้างรายลูกหนี้
        $byMember = [];
        $totalOutstanding = 0;
        foreach ($bills as $b) {
            $key = $b['member_id'] ?? ('n-' . $b['id']);
            $byMember[$key]['name']         = $b['member_name'] ?? 'ไม่ระบุ';
            $byMember[$key]['bills']        = ($byMember[$key]['bills'] ?? 0) + 1;
            $byMember[$key]['outstanding']  = ($byMember[$key]['outstanding'] ?? 0) + (float) $b['outstanding'];
            $totalOutstanding += (float) $b['outstanding'];
        }
        usort($byMember, fn ($a, $b) => $b['outstanding'] <=> $a['outstanding']);

        return view('credit/index', [
            'title'            => 'ค้างชำระ / เงินเชื่อ',
            'bills'            => $bills,
            'byMember'         => $byMember,
            'totalOutstanding' => $totalOutstanding,
            'settled'          => $this->saleModel->getSettledCreditBills(30),
        ]);
    }

    /** POST: รับชำระหนี้ (ชำระบางส่วนได้) */
    public function pay(int $saleId)
    {
        $sale = $this->saleModel->find($saleId);
        if (! $sale || $sale['payment_method'] !== 'credit') {
            return redirect()->to('/credit')->with('error', 'ไม่พบบิลเงินเชื่อนี้');
        }
        if (! empty($sale['voided_at'])) {
            return redirect()->to('/credit')->with('error', 'บิลนี้ถูกยกเลิกไปแล้ว');
        }
        if (! empty($sale['credit_settled_at'])) {
            return redirect()->to('/credit')->with('error', 'บิลนี้ชำระครบแล้ว');
        }

        $outstanding = (float) $sale['total_amount'] - (float) $sale['paid_amount'];
        $amount      = (float) ($this->request->getPost('amount') ?? 0);
        $method      = $this->request->getPost('method') ?? 'cash';
        if (! in_array($method, ['cash', 'qr', 'transfer'])) {
            $method = 'cash';
        }
        $note = trim($this->request->getPost('note') ?? '');

        if ($amount <= 0) {
            return redirect()->to('/credit')->with('error', 'จำนวนเงินต้องมากกว่า 0');
        }
        if ($amount > $outstanding + 0.001) {
            return redirect()->to('/credit')->with('error',
                'จำนวนเงินเกินยอดค้าง (ค้างอยู่ ' . number_format($outstanding, 2) . ' บาท)');
        }

        $authUser = session()->get('auth_user') ?? [];

        // บันทึกประวัติการชำระ
        $this->paymentModel->insert([
            'sale_id'     => $saleId,
            'member_id'   => $sale['member_id'],
            'amount'      => $amount,
            'method'      => $method,
            'note'        => $note,
            'received_by' => $authUser['full_name'] ?? setting('cashier_name', 'Admin'),
        ]);

        // อัพเดตยอดชำระสะสมในบิล
        $newPaid = (float) $sale['paid_amount'] + $amount;
        $update  = ['paid_amount' => $newPaid];
        $settledNow = $newPaid >= (float) $sale['total_amount'] - 0.001;
        if ($settledNow) {
            $update['credit_settled_at'] = date('Y-m-d H:i:s');
        }
        $this->saleModel->update($saleId, $update);

        // เงินสดเข้าลิ้นชัก (ถ้ามีกะเปิดอยู่)
        if ($method === 'cash') {
            $cashSession = (new \App\Models\CashSessionModel())->getOpenSession();
            if ($cashSession) {
                (new \App\Models\CashMovementModel())->log([
                    'session_id'   => $cashSession['id'],
                    'type'         => 'sale',
                    'amount'       => $amount,
                    'note'         => 'รับชำระหนี้บิล ' . $sale['bill_number'],
                    'created_by'   => $authUser['id'] ?? null,
                    'reference_id' => $saleId,
                ]);
            }
        }

        // เก็บครบ → ให้แต้มสะสมจากยอดบิล (ตอนขายเชื่อยังไม่ได้ให้)
        if ($settledNow && ! empty($sale['member_id'])) {
            $ptsRate = max(1, (int) setting('points_rate', '10'));
            $pts     = (int) floor((float) $sale['total_amount'] / $ptsRate);
            if ($pts > 0) {
                $this->memberModel->addPoints((int) $sale['member_id'], $pts);
            }
        }

        $msg = $settledNow
            ? 'รับชำระ ' . number_format($amount, 2) . ' บาท — บิล ' . $sale['bill_number'] . ' ชำระครบแล้ว ✓'
            : 'รับชำระ ' . number_format($amount, 2) . ' บาท — บิล ' . $sale['bill_number']
              . ' คงค้าง ' . number_format($outstanding - $amount, 2) . ' บาท';

        return redirect()->to('/credit')->with('success', $msg);
    }

    /** GET (AJAX): ประวัติการชำระของบิล */
    public function payments(int $saleId)
    {
        $sale = $this->saleModel->find($saleId);
        if (! $sale) {
            return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบบิล']);
        }
        return $this->response->setJSON([
            'success'     => true,
            'bill_number' => $sale['bill_number'],
            'total'       => (float) $sale['total_amount'],
            'paid'        => (float) $sale['paid_amount'],
            'outstanding' => (float) $sale['total_amount'] - (float) $sale['paid_amount'],
            'settled'     => ! empty($sale['credit_settled_at']),
            'payments'    => $this->paymentModel->getBySale($saleId),
        ]);
    }
}
