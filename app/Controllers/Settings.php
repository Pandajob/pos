<?php

namespace App\Controllers;

use App\Models\SettingModel;

class Settings extends BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        return view('settings/index', [
            'title'    => 'ตั้งค่าระบบ',
            'settings' => $this->settingModel->getAll(),
            'dbStatus' => $this->migrationStatus(),
        ]);
    }

    /**
     * อัพเดทโครงสร้างฐานข้อมูล (รัน migrations ที่ยังไม่เคยรัน)
     * ปลอดภัย — เพิ่มตาราง/คอลัมน์ใหม่เท่านั้น ไม่ลบข้อมูลเดิม
     * เฉพาะ Admin
     */
    public function updateDatabase()
    {
        $authUser = session()->get('auth_user');
        if (! $authUser || ($authUser['role'] ?? '') !== 'admin') {
            return redirect()->to('/settings')->with('error', 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่อัพเดทฐานข้อมูลได้');
        }

        $db  = \Config\Database::connect();
        $tbl = config('Migrations')->table;

        try {
            // ── ขั้นที่ 1: sync โครงสร้างให้ตรงกับเครื่องต้นแบบ (เพิ่มตาราง/คอลัมน์ที่ขาด) ──
            $sync = \App\Database\SchemaSync::apply();

            // ── ขั้นที่ 2: รัน migration ที่ค้างอยู่ (สำหรับการเปลี่ยนแปลงที่ต้องใช้ลอจิก) ──
            $before = $db->tableExists($tbl)
                ? array_column($db->table($tbl)->get()->getResultArray(), 'version')
                : [];

            service('migrations')->latest();

            $after       = $db->table($tbl)->get()->getResultArray();
            $appliedRows = array_filter($after, static fn ($r) => ! in_array($r['version'], $before, true));

            $migNames = array_map(static function ($r) {
                $cls = $r['class'] ?? '';
                $pos = strrpos($cls, '\\');
                return $pos === false ? $cls : substr($cls, $pos + 1);
            }, $appliedRows);

            // ── สรุปผลรวมทั้งสองขั้น ──
            $parts = [];
            if (! empty($sync['tables'])) {
                $parts[] = 'สร้างตารางใหม่ ' . count($sync['tables']) . ' ตาราง (' . implode(', ', $sync['tables']) . ')';
            }
            if (! empty($sync['columns'])) {
                $parts[] = 'เพิ่มคอลัมน์ ' . count($sync['columns']) . ' รายการ (' . implode(', ', $sync['columns']) . ')';
            }
            if (! empty($migNames)) {
                $parts[] = 'รัน migration ' . count($migNames) . ' รายการ (' . implode(', ', $migNames) . ')';
            }

            if (empty($parts)) {
                return redirect()->to('/settings')
                    ->with('success', 'ฐานข้อมูลครบถ้วนตรงกับต้นแบบอยู่แล้ว — ไม่มีรายการที่ต้องอัพเดท');
            }

            return redirect()->to('/settings')
                ->with('success', 'อัพเดทฐานข้อมูลสำเร็จ — ' . implode(' | ', $parts));
        } catch (\Throwable $e) {
            log_message('error', '[DB Update] ' . $e->getMessage());
            return redirect()->to('/settings')
                ->with('error', 'อัพเดทฐานข้อมูลล้มเหลว: ' . $e->getMessage());
        }
    }

    /** สรุปสถานะ migration สำหรับแสดงในหน้าตั้งค่า */
    private function migrationStatus(): array
    {
        $db  = \Config\Database::connect();
        $tbl = config('Migrations')->table;

        $applied = ($db->tableExists($tbl)) ? $db->table($tbl)->countAllResults() : 0;
        $total   = count(glob(APPPATH . 'Database/Migrations/*.php') ?: []);

        $version = '-';
        if ($applied > 0) {
            $row = $db->table($tbl)->orderBy('version', 'DESC')->limit(1)->get()->getRowArray();
            $version = $row['version'] ?? '-';
        }

        return [
            'applied' => $applied,
            'total'   => $total,
            'pending' => max(0, $total - $applied),
            'version' => $version,
        ];
    }

    public function save()
    {
        $keys = ['shop_name', 'shop_address', 'shop_tax_id', 'cashier_name', 'points_rate', 'points_value',
                 'low_stock_threshold', 'printer_type', 'auto_print', 'printer_paper',
                 'bill_font_color', 'bill_font_size',
                 'bill_fs_shopname', 'bill_fs_meta', 'bill_fs_items', 'bill_fs_total', 'bill_fs_footer',
                 'agent_token', 'agent_port', 'agent_open_on_checkout',
                 'print_mode', 'receipt_printer'];
        foreach ($keys as $key) {
            $val = $this->request->getPost($key);
            if ($val !== null) {
                $this->settingModel->setValue($key, trim((string) $val));
            }
        }

        // QR payment image upload
        $file = $this->request->getFile('qr_payment_file');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $ext = strtolower($file->getClientExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $uploadPath = FCPATH . 'uploads/';
                if (! is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                $newName = 'qr_payment.' . $ext;
                $file->move($uploadPath, $newName, true); // overwrite
                $this->settingModel->setValue('qr_payment_image', $newName);
            }
        }

        // Delete QR image
        if ($this->request->getPost('delete_qr') === '1') {
            $existing = $this->settingModel->getValue('qr_payment_image');
            if ($existing) {
                @unlink(FCPATH . 'uploads/' . $existing);
            }
            $this->settingModel->setValue('qr_payment_image', '');
        }

        return redirect()->to('/settings')->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }
}
