<?php

namespace App\Controllers;

use App\Models\CashSessionModel;
use App\Models\CashMovementModel;

class CashDrawer extends BaseController
{
    protected CashSessionModel  $sessionModel;
    protected CashMovementModel $movementModel;
    protected array             $authUser;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->sessionModel  = new CashSessionModel();
        $this->movementModel = new CashMovementModel();
        $this->authUser      = session()->get('auth_user') ?? [];
        $this->db            = \Config\Database::connect();
    }

    // ─── หน้าหลัก ────────────────────────────────────────────────────────────
    public function index()
    {
        $session  = $this->sessionModel->getOpenSession();
        $movements = $session ? $this->movementModel->getBySession($session['id']) : [];
        $summary   = $session ? $this->sessionModel->getSessionSummary($session['id']) : [];
        $expected  = $session ? $this->sessionModel->calcExpected($session['id']) : 0;

        // ดึง session ล่าสุด 10 รายการ
        $history = $this->sessionModel
            ->orderBy('opened_at', 'DESC')
            ->findAll(10);

        return view('cashdrawer/index', [
            'title'     => 'ลิ้นชักเงิน',
            'session'   => $session,
            'movements' => $movements,
            'summary'   => $summary,
            'expected'  => $expected,
            'history'   => $history,
            'authUser'  => $this->authUser,
        ]);
    }

    // ─── เปิดลิ้นชัก ─────────────────────────────────────────────────────────
    public function open()
    {
        // ห้ามเปิดซ้อน
        if ($this->sessionModel->getOpenSession()) {
            return redirect()->to('/cash-drawer')->with('error', 'มีลิ้นชักที่เปิดอยู่แล้ว กรุณาปิดก่อน');
        }

        $startingCash = (float)$this->request->getPost('starting_cash');
        if ($startingCash < 0) {
            return redirect()->to('/cash-drawer')->with('error', 'จำนวนเงินต้องไม่ติดลบ');
        }

        $sessionId = $this->sessionModel->insert([
            'opened_by'    => $this->authUser['id'],
            'opened_at'    => date('Y-m-d H:i:s'),
            'starting_cash'=> $startingCash,
            'status'       => 'open',
            'note'         => trim($this->request->getPost('note') ?? ''),
        ]);
        // หมายเหตุ: ไม่บันทึก movement สำหรับเงินเริ่มต้น เพราะ calcExpected ใช้ starting_cash เป็น base
        // movement จะบันทึกเฉพาะการเคลื่อนไหวหลังเปิดลิ้นชักเท่านั้น

        return redirect()->to('/cash-drawer')
            ->with('success', sprintf('เปิดลิ้นชักสำเร็จ (เงินเริ่มต้น: %s ฿)', number_format($startingCash, 2)));
    }

    // ─── ปิดลิ้นชัก ──────────────────────────────────────────────────────────
    public function close()
    {
        $session = $this->sessionModel->getOpenSession();
        if (! $session) {
            return redirect()->to('/cash-drawer')->with('error', 'ไม่มีลิ้นชักที่เปิดอยู่');
        }

        // admin เท่านั้น หรือผู้เปิด
        if ($this->authUser['role'] !== 'admin' && $this->authUser['id'] != $session['opened_by']) {
            return redirect()->to('/cash-drawer')->with('error', 'ไม่มีสิทธิ์ปิดลิ้นชักที่ผู้อื่นเปิด');
        }

        $closingCash = (float)$this->request->getPost('closing_cash');
        $expected    = $this->sessionModel->calcExpected($session['id']);
        $difference  = round($closingCash - $expected, 2);

        $this->sessionModel->update($session['id'], [
            'closed_by'    => $this->authUser['id'],
            'closed_at'    => date('Y-m-d H:i:s'),
            'closing_cash' => $closingCash,
            'expected_cash'=> $expected,
            'difference'   => $difference,
            'status'       => 'closed',
            'note'         => trim($this->request->getPost('note') ?? ''),
        ]);

        $diffLabel = $difference >= 0 ? "+{$difference}" : "{$difference}";
        $this->movementModel->log([
            'session_id' => $session['id'],
            'type'       => 'cash_out',
            'amount'     => $closingCash,
            'note'       => "ปิดลิ้นชัก — นับได้ {$closingCash} ฿ | คาดหวัง {$expected} ฿ | ผลต่าง {$diffLabel} ฿",
            'created_by' => $this->authUser['id'],
        ]);

        $msg = sprintf('ปิดลิ้นชักสำเร็จ | นับได้ %.2f ฿ | ผลต่าง %+.2f ฿', $closingCash, $difference);
        return redirect()->to('/cash-drawer')->with($difference == 0 ? 'success' : 'warning', $msg);
    }

    // ─── เพิ่มเงิน (Cash In) ─────────────────────────────────────────────────
    public function cashIn()
    {
        $session = $this->sessionModel->getOpenSession();
        if (! $session) {
            return redirect()->to('/cash-drawer')->with('error', 'ไม่มีลิ้นชักที่เปิดอยู่');
        }

        $amount = (float)$this->request->getPost('amount');
        $note   = trim($this->request->getPost('note') ?? '');

        if ($amount <= 0) {
            return redirect()->to('/cash-drawer')->with('error', 'จำนวนเงินต้องมากกว่า 0');
        }
        if (! $note) {
            return redirect()->to('/cash-drawer')->with('error', 'กรุณาระบุเหตุผลการเพิ่มเงิน');
        }

        $this->movementModel->log([
            'session_id' => $session['id'],
            'type'       => 'cash_in',
            'amount'     => $amount,
            'note'       => $note,
            'created_by' => $this->authUser['id'],
        ]);

        return redirect()->to('/cash-drawer')
            ->with('success', sprintf('เพิ่มเงิน %.2f ฿ เรียบร้อย', $amount));
    }

    // ─── นำเงินออก (Cash Out) ────────────────────────────────────────────────
    public function cashOut()
    {
        // เฉพาะ admin เท่านั้น — กันพนักงานนำเงินออกจากลิ้นชักเอง
        if (($this->authUser['role'] ?? '') !== 'admin') {
            return redirect()->to('/cash-drawer')
                ->with('error', 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่นำเงินออกจากลิ้นชักได้');
        }

        $session = $this->sessionModel->getOpenSession();
        if (! $session) {
            return redirect()->to('/cash-drawer')->with('error', 'ไม่มีลิ้นชักที่เปิดอยู่');
        }

        $amount = (float)$this->request->getPost('amount');
        $note   = trim($this->request->getPost('note') ?? '');

        if ($amount <= 0) {
            return redirect()->to('/cash-drawer')->with('error', 'จำนวนเงินต้องมากกว่า 0');
        }
        if (! $note) {
            return redirect()->to('/cash-drawer')->with('error', 'กรุณาระบุเหตุผลการนำเงินออก');
        }

        // ตรวจว่าเงินพอไหม
        $expected = $this->sessionModel->calcExpected($session['id']);
        if ($amount > $expected) {
            return redirect()->to('/cash-drawer')
                ->with('error', sprintf('เงินในลิ้นชักไม่พอ (คาดมี %.2f ฿)', $expected));
        }

        $this->movementModel->log([
            'session_id' => $session['id'],
            'type'       => 'cash_out',
            'amount'     => $amount,
            'note'       => $note,
            'created_by' => $this->authUser['id'],
        ]);

        return redirect()->to('/cash-drawer')
            ->with('success', sprintf('นำเงินออก %.2f ฿ เรียบร้อย', $amount));
    }

    // ─── Log ทั้งหมด (admin) ─────────────────────────────────────────────────
    public function log()
    {
        if ($this->authUser['role'] !== 'admin') {
            return redirect()->to('/cash-drawer')->with('error', 'เฉพาะ Admin เท่านั้น');
        }

        $sessions = $this->sessionModel
            ->orderBy('opened_at', 'DESC')
            ->findAll(50);

        // แนบ opener/closer name
        $userMap = [];
        foreach ($this->db->query("SELECT id, username, full_name FROM users")->getResultArray() as $u) {
            $userMap[$u['id']] = $u;
        }

        $movements = $this->movementModel->getRecentLog(200);

        return view('cashdrawer/log', [
            'title'     => 'Log ลิ้นชักเงิน',
            'sessions'  => $sessions,
            'movements' => $movements,
            'userMap'   => $userMap,
        ]);
    }

    // ─── AJAX: ยอดเงินปัจจุบัน ───────────────────────────────────────────────
    public function status()
    {
        $session  = $this->sessionModel->getOpenSession();
        $expected = $session ? $this->sessionModel->calcExpected($session['id']) : null;
        return $this->response->setJSON([
            'open'     => (bool)$session,
            'expected' => $expected,
            'session'  => $session ? ['id' => $session['id'], 'opened_at' => $session['opened_at']] : null,
        ]);
    }
}
