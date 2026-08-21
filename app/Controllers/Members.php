<?php

namespace App\Controllers;

use App\Models\CustomerTierModel;
use App\Models\MemberModel;
use App\Models\SaleItemModel;

class Members extends BaseController
{
    protected MemberModel      $memberModel;
    protected CustomerTierModel $tierModel;

    public function __construct()
    {
        $this->memberModel = new MemberModel();
        $this->tierModel   = new CustomerTierModel();
    }

    public function index()
    {
        $q       = $this->request->getGet('q') ?? '';
        $members = $q
            ? $this->memberModel->searchWithTiers($q)
            : $this->memberModel->getAllWithTiers();

        return view('members/index', [
            'title'   => 'จัดการสมาชิก',
            'members' => $members,
            'q'       => $q,
        ]);
    }

    public function create()
    {
        return view('members/create', [
            'title' => 'เพิ่มสมาชิกใหม่',
            'tiers' => $this->tierModel->getAll(),
        ]);
    }

    public function store()
    {
        if (! $this->validate([
            'name'  => 'required|max_length[255]',
            'phone' => 'required|max_length[20]',
        ])) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $phone = $this->request->getPost('phone');
        if ($this->memberModel->where('phone', $phone)->where('deleted_at IS NULL')->countAllResults() > 0) {
            return redirect()->back()->withInput()
                ->with('errors', ['phone' => 'เบอร์โทรนี้มีอยู่ในระบบแล้ว']);
        }

        $tierId = (int) ($this->request->getPost('tier_id') ?? 0);
        $code   = $this->memberModel->generateCode();
        $this->memberModel->insert([
            'code'    => $code,
            'name'    => $this->request->getPost('name'),
            'phone'   => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address') ?: null,
            'tax_id'  => $this->request->getPost('tax_id')  ?: null,
            'tier_id' => $tierId ?: null,
        ]);

        return redirect()->to('/members')
            ->with('success', 'เพิ่มสมาชิก "' . $this->request->getPost('name') . '" สำเร็จ (รหัส: ' . $code . ')');
    }

    public function edit(int $id)
    {
        $member = $this->memberModel->find($id);
        if (! $member) {
            return redirect()->to('/members')->with('error', 'ไม่พบสมาชิก');
        }
        return view('members/edit', [
            'title'  => 'แก้ไขสมาชิก',
            'member' => $member,
            'tiers'  => $this->tierModel->getAll(),
        ]);
    }

    public function update(int $id)
    {
        $member = $this->memberModel->find($id);
        if (! $member) {
            return redirect()->to('/members')->with('error', 'ไม่พบสมาชิก');
        }

        if (! $this->validate([
            'name'  => 'required|max_length[255]',
            'phone' => 'required|max_length[20]',
        ])) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $phone = $this->request->getPost('phone');
        if ($this->memberModel->where('phone', $phone)->where('id !=', $id)->where('deleted_at IS NULL')->countAllResults() > 0) {
            return redirect()->back()->withInput()
                ->with('errors', ['phone' => 'เบอร์โทรนี้มีอยู่ในระบบแล้ว']);
        }

        $tierId = (int) ($this->request->getPost('tier_id') ?? 0);
        $this->memberModel->update($id, [
            'name'    => $this->request->getPost('name'),
            'phone'   => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address') ?: null,
            'tax_id'  => $this->request->getPost('tax_id')  ?: null,
            'tier_id' => $tierId ?: null,
        ]);

        return redirect()->to('/members')->with('success', 'อัปเดตข้อมูลสมาชิกสำเร็จ');
    }

    public function delete(int $id)
    {
        $this->memberModel->delete($id);
        return redirect()->to('/members')->with('success', 'ลบสมาชิกสำเร็จ');
    }

    public function history(int $id)
    {
        $member = $this->memberModel->find($id);
        if (! $member) {
            return redirect()->to('/members')->with('error', 'ไม่พบสมาชิก');
        }
        $saleItemModel = new SaleItemModel();
        $history = $saleItemModel->getMemberHistory($id, 100);

        $totalSpent = array_sum(array_column(
            array_filter($history, fn($r) => empty($r['voided_at'])),
            'total_amount'
        ));

        return view('members/history', [
            'title'      => 'ประวัติการซื้อ: ' . $member['name'],
            'member'     => $member,
            'history'    => $history,
            'totalSpent' => $totalSpent,
        ]);
    }

    // AJAX: search members for POS
    public function ajaxSearch()
    {
        $q = $this->request->getGet('q') ?? '';
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }
        return $this->response->setJSON($this->memberModel->search($q));
    }
}
