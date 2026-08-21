<?php

namespace App\Controllers;

use App\Models\SaleModel;
use App\Models\SaleItemModel;

class Receipt extends BaseController
{
    public function view(int $saleId)
    {
        $saleModel     = new SaleModel();
        $saleItemModel = new SaleItemModel();

        $sale = $saleModel->find($saleId);
        if (! $sale) {
            return redirect()->to('/sales')->with('error', 'ไม่พบบิลนี้');
        }

        $items = $saleItemModel->getItemsBySaleId($saleId);

        return view('receipt/view', [
            'title'          => 'ใบเสร็จ #' . $sale['bill_number'],
            'sale'           => $sale,
            'items'          => $items,
            'shopName'       => setting('shop_name', 'ร้านของเรา'),
            'shopAddress'    => setting('shop_address', ''),
            'shopTaxId'      => setting('shop_tax_id', ''),
            'shopPointsRate' => max(1, (int) setting('points_rate', '10')),
            'billFontColor'  => setting('bill_font_color', '#000000'),
            'billFontSize'   => setting('bill_font_size', '14'),
            'billFsShopname' => setting('bill_fs_shopname', '22'),
            'billFsMeta'     => setting('bill_fs_meta',     '13'),
            'billFsItems'    => setting('bill_fs_items',    '14'),
            'billFsTotal'    => setting('bill_fs_total',    '16'),
            'billFsFooter'   => setting('bill_fs_footer',   '12'),
            'printMode'      => setting('print_mode', 'browser'),
            'receiptPrinter' => setting('receipt_printer', ''),
            'agentPort'      => setting('agent_port', '9991'),
            'agentToken'     => setting('agent_token', ''),
            // เปิดลิ้นชักพร้อมพิมพ์สลิป (เมื่อรับเงินสดจริง: บิลเงินสด หรือขายเชื่อที่มีมัดจำ)
            'agentOpenDrawer'=> setting('agent_open_on_checkout', '0') === '1'
                                 && (($sale['payment_method'] ?? 'cash') === 'cash'
                                     || (($sale['payment_method'] ?? '') === 'credit'
                                         && (float) $sale['paid_amount'] > 0)),
        ]);
    }
}
