<?php

namespace App\Controllers;

use App\Models\SettingModel;

class CustomerDisplay extends BaseController
{
    public function index()
    {
        return view('customer_display/index', [
            'shopName' => setting('shop_name', 'ร้านค้า'),
            'qrImage'  => setting('qr_payment_image', ''),
        ]);
    }

    /** AJAX: คืนสถานะตะกร้าปัจจุบัน (อ่านจาก DB) */
    public function data()
    {
        $sm  = new SettingModel();
        $raw = $sm->getValue('display_cart', '{}');
        $data = json_decode($raw, true) ?: ['items' => [], 'total' => 0, 'count' => 0, 'status' => 'clear'];

        // แนบ shop_name และ qr_image ทุกครั้ง
        $qrImage           = setting('qr_payment_image', '');
        $data['shop_name'] = setting('shop_name', 'ร้านค้า');
        $data['qr_image']  = $qrImage;
        // เวอร์ชันไฟล์ (mtime) ไว้กันแคช — รูป QR ถูกบันทึกทับชื่อเดิม URL เลยไม่เปลี่ยน
        $qrPath           = FCPATH . 'uploads/' . $qrImage;
        $data['qr_ver']   = ($qrImage && is_file($qrPath)) ? filemtime($qrPath) : 0;

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($data);
    }
}
