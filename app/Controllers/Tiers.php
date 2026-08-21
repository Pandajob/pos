<?php

namespace App\Controllers;

use App\Models\CustomerTierModel;

class Tiers extends BaseController
{
    protected CustomerTierModel $tierModel;

    public function __construct()
    {
        $this->tierModel = new CustomerTierModel();
    }

    public function index()
    {
        return view('tiers/index', [
            'title' => 'ระดับสมาชิก',
            'tiers' => $this->tierModel->getAll(),
        ]);
    }

    public function store()
    {
        $name        = trim($this->request->getPost('name') ?? '');
        $discPct     = (float) ($this->request->getPost('discount_pct') ?? 0);
        $isWholesale = (int)   ($this->request->getPost('is_wholesale')  ?? 0);
        $badgeColor  = trim($this->request->getPost('badge_color') ?? 'secondary');
        $description = trim($this->request->getPost('description') ?? '');

        if (empty($name)) {
            return redirect()->back()->with('error', 'กรุณากรอกชื่อระดับ');
        }

        $this->tierModel->insert([
            'name'         => $name,
            'discount_pct' => $discPct,
            'is_wholesale' => $isWholesale,
            'badge_color'  => $badgeColor,
            'description'  => $description ?: null,
        ]);

        return redirect()->to('/tiers')->with('success', 'เพิ่มระดับสมาชิกสำเร็จ');
    }

    public function update(int $id)
    {
        $name        = trim($this->request->getPost('name') ?? '');
        $discPct     = (float) ($this->request->getPost('discount_pct') ?? 0);
        $isWholesale = (int)   ($this->request->getPost('is_wholesale')  ?? 0);
        $badgeColor  = trim($this->request->getPost('badge_color') ?? 'secondary');
        $description = trim($this->request->getPost('description') ?? '');

        if (empty($name)) {
            return redirect()->back()->with('error', 'กรุณากรอกชื่อระดับ');
        }

        $this->tierModel->update($id, [
            'name'         => $name,
            'discount_pct' => $discPct,
            'is_wholesale' => $isWholesale,
            'badge_color'  => $badgeColor,
            'description'  => $description ?: null,
        ]);

        return redirect()->to('/tiers')->with('success', 'แก้ไขระดับสมาชิกสำเร็จ');
    }

    public function delete(int $id)
    {
        $this->tierModel->delete($id);
        return redirect()->to('/tiers')->with('success', 'ลบระดับสมาชิกสำเร็จ');
    }
}
