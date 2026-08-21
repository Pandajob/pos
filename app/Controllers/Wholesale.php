<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\QuotationModel;
use App\Models\QuotationItemModel;
use App\Models\SaleModel;
use App\Models\SaleItemModel;

class Wholesale extends BaseController
{
    protected ProductModel      $productModel;
    protected QuotationModel    $quoteModel;
    protected QuotationItemModel $quoteItemModel;

    public function __construct()
    {
        $this->productModel   = new ProductModel();
        $this->quoteModel     = new QuotationModel();
        $this->quoteItemModel = new QuotationItemModel();
    }

    // GET /wholesale — หน้าขายส่ง
    public function index()
    {
        $cart     = session()->get('ws_cart') ?? [];
        $customer = session()->get('ws_customer') ?? [];
        $discPct  = (float) (session()->get('ws_discount_pct') ?? 0);
        $cd       = $this->calcCart($cart, $discPct);

        return view('wholesale/index', [
            'title'    => 'ขายส่ง / ใบเสนอราคา',
            'cart'     => $cart,
            'customer' => $customer,
            'disc_pct' => $discPct,
            'subtotal' => $cd['subtotal'],
            'disc_amt' => $cd['disc_amt'],
            'total'    => $cd['total'],
        ]);
    }

    // AJAX: เพิ่มสินค้าเข้า ws_cart
    public function addToCart()
    {
        $productId = (int) $this->request->getPost('product_id');
        $qty       = max(1, (int) ($this->request->getPost('quantity') ?? 1));
        $product   = $this->productModel->find($productId);

        if (! $product) {
            return $this->jsonError('ไม่พบสินค้า');
        }

        $cart   = session()->get('ws_cart') ?? [];
        $curQty = isset($cart[$productId]) ? $cart[$productId]['quantity'] : 0;
        $newQty = $curQty + $qty;

        if ($newQty > $product['stock']) {
            return $this->jsonError('สต๊อกไม่พอ (คงเหลือ: ' . $product['stock'] . ' ชิ้น)');
        }

        $price = !empty($product['wholesale_price'])
            ? (float) $product['wholesale_price']
            : (float) $product['price'];

        $cart[$productId] = [
            'product_id' => $product['id'],
            'name'       => $product['name'],
            'barcode'    => $product['barcode'],
            'price'      => $price,
            'quantity'   => $newQty,
            'subtotal'   => $price * $newQty,
            'stock'      => (int) $product['stock'],
        ];

        session()->set('ws_cart', $cart);
        return $this->cartResponse($cart);
    }

    // AJAX: อัปเดตจำนวน
    public function updateCart()
    {
        $productId = (int) $this->request->getPost('product_id');
        $qty       = (int) $this->request->getPost('quantity');
        $cart      = session()->get('ws_cart') ?? [];

        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            if (! isset($cart[$productId])) {
                return $this->jsonError('ไม่พบสินค้าในตะกร้า');
            }
            $product = $this->productModel->find($productId);
            if ($product && $qty > $product['stock']) {
                return $this->jsonError('สต๊อกไม่พอ (คงเหลือ: ' . $product['stock'] . ' ชิ้น)');
            }
            $cart[$productId]['quantity'] = $qty;
            $cart[$productId]['subtotal'] = $cart[$productId]['price'] * $qty;
        }

        session()->set('ws_cart', $cart);
        return $this->cartResponse($cart);
    }

    // AJAX: ลบสินค้า
    public function removeFromCart()
    {
        $productId = (int) $this->request->getPost('product_id');
        $cart      = session()->get('ws_cart') ?? [];
        unset($cart[$productId]);
        session()->set('ws_cart', $cart);
        return $this->cartResponse($cart);
    }

    // AJAX: ตั้งค่าส่วนลด %
    public function setDiscount()
    {
        $pct = max(0, min(100, (float) $this->request->getPost('discount_pct')));
        session()->set('ws_discount_pct', $pct);
        $cart = session()->get('ws_cart') ?? [];
        return $this->cartResponse($cart);
    }

    // AJAX: ค้นหาสินค้า
    public function searchProduct()
    {
        $q = $this->request->getGet('q') ?? '';
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }
        return $this->response->setJSON($this->productModel->searchByNameOrBarcode($q));
    }

    // AJAX: barcode lookup
    public function getByBarcode()
    {
        $barcode = trim($this->request->getGet('barcode') ?? '');
        $product = $this->productModel->findByBarcode($barcode);
        if ($product) {
            return $this->response->setJSON(['success' => true, 'product' => $product]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบสินค้า']);
    }

    // POST: บันทึกใบเสนอราคา
    public function saveQuote()
    {
        $cart = session()->get('ws_cart') ?? [];
        if (empty($cart)) {
            return $this->jsonError('ยังไม่มีสินค้าในตะกร้า');
        }

        $customerName    = trim($this->request->getPost('customer_name') ?? '');
        $customerAddress = trim($this->request->getPost('customer_address') ?? '');
        $customerTaxId   = trim($this->request->getPost('customer_tax_id') ?? '');
        $customerPhone   = trim($this->request->getPost('customer_phone') ?? '');
        $note            = trim($this->request->getPost('note') ?? '');
        $discPct         = max(0, min(100, (float) ($this->request->getPost('discount_pct') ?? 0)));

        // บันทึก customer ลง session
        session()->set('ws_customer', [
            'name'    => $customerName,
            'address' => $customerAddress,
            'tax_id'  => $customerTaxId,
            'phone'   => $customerPhone,
        ]);
        session()->set('ws_discount_pct', $discPct);

        $cd = $this->calcCart($cart, $discPct);

        $authUser = session()->get('auth_user');
        $quoteId  = $this->quoteModel->insert([
            'quote_number'     => $this->quoteModel->generateQuoteNumber(),
            'customer_name'    => $customerName,
            'customer_address' => $customerAddress,
            'customer_tax_id'  => $customerTaxId,
            'customer_phone'   => $customerPhone,
            'subtotal'         => $cd['subtotal'],
            'discount_pct'     => $discPct,
            'discount_amount'  => $cd['disc_amt'],
            'total_amount'     => $cd['total'],
            'note'             => $note,
            'status'           => 'pending',
            'created_by'       => $authUser['full_name'] ?? 'Admin',
        ]);

        foreach ($cart as $item) {
            $this->quoteItemModel->insert([
                'quotation_id' => $quoteId,
                'product_id'   => $item['product_id'],
                'product_name' => $item['name'],
                'barcode'      => $item['barcode'],
                'price'        => $item['price'],
                'quantity'     => $item['quantity'],
                'subtotal'     => $item['subtotal'],
            ]);
        }

        // ล้างตะกร้า
        session()->remove('ws_cart');
        session()->remove('ws_discount_pct');

        return $this->response->setJSON([
            'success'  => true,
            'quote_id' => $quoteId,
            'redirect' => site_url('/wholesale/quotation/' . $quoteId),
        ]);
    }

    // GET: ดูใบเสนอราคา
    public function viewQuotation(int $id)
    {
        $quote = $this->quoteModel->find($id);
        if (! $quote) {
            return redirect()->to('/wholesale')->with('error', 'ไม่พบใบเสนอราคา');
        }
        $items = $this->quoteItemModel->getItemsByQuoteId($id);

        return view('wholesale/quotation', [
            'title'    => 'ใบเสนอราคา ' . $quote['quote_number'],
            'quote'    => $quote,
            'items'    => $items,
            'shopName'    => setting('shop_name', 'ร้านของเรา'),
            'shopAddress' => setting('shop_address', ''),
            'shopTaxId'   => setting('shop_tax_id', ''),
        ]);
    }

    // POST: ยืนยันใบเสนอราคา → สร้างบิลขาย
    public function confirmQuote(int $id)
    {
        $quote = $this->quoteModel->find($id);
        if (! $quote) {
            return $this->jsonError('ไม่พบใบเสนอราคา');
        }
        if ($quote['status'] !== 'pending') {
            return $this->jsonError('ใบเสนอราคานี้ถูกดำเนินการแล้ว');
        }

        $items = $this->quoteItemModel->getItemsByQuoteId($id);
        if (empty($items)) {
            return $this->jsonError('ไม่พบรายการสินค้า');
        }

        // ตรวจสต๊อก
        $productModel = new ProductModel();
        foreach ($items as $item) {
            if (! $item['product_id']) continue;
            $product = $productModel->find($item['product_id']);
            if ($product && $product['stock'] < $item['quantity']) {
                return $this->jsonError('สินค้า "' . $item['product_name'] . '" สต๊อกไม่พอ (คงเหลือ: ' . $product['stock'] . ')');
            }
        }

        $saleModel     = new SaleModel();
        $saleItemModel = new SaleItemModel();
        $authUser      = session()->get('auth_user');

        $paymentMethod = $this->request->getPost('payment_method') ?? 'transfer';
        if (! in_array($paymentMethod, ['cash', 'qr', 'transfer'])) {
            $paymentMethod = 'transfer';
        }
        $paidAmount = (float) ($this->request->getPost('paid_amount') ?? $quote['total_amount']);

        $billNumber = $saleModel->generateBillNumber();
        $saleId = $saleModel->insert([
            'bill_number'     => $billNumber,
            'total_amount'    => $quote['total_amount'],
            'paid_amount'     => $paidAmount,
            'change_amount'   => max(0, $paidAmount - $quote['total_amount']),
            'payment_method'  => $paymentMethod,
            'cashier'         => $authUser['full_name'] ?? setting('cashier_name', 'Admin'),
            'member_name'     => $quote['customer_name'],
            'note'            => 'ใบเสนอราคา ' . $quote['quote_number'],
            'discount_pct'    => $quote['discount_pct'],
            'discount_amount' => $quote['discount_amount'],
            'points_used'     => 0,
            'points_discount' => 0,
        ]);

        foreach ($items as $item) {
            $saleItemModel->insert([
                'sale_id'      => $saleId,
                'product_id'   => $item['product_id'],
                'product_name' => $item['product_name'],
                'barcode'      => $item['barcode'],
                'price'        => $item['price'],
                'quantity'     => $item['quantity'],
                'subtotal'     => $item['subtotal'],
            ]);
            if ($item['product_id']) {
                $productModel->decreaseStock($item['product_id'], $item['quantity']);
            }
        }

        // อัปเดตสถานะ quotation
        $this->quoteModel->update($id, ['status' => 'confirmed', 'sale_id' => $saleId]);

        return $this->response->setJSON([
            'success'  => true,
            'sale_id'  => $saleId,
            'redirect' => site_url('/wholesale/delivery/' . $saleId . '?quote=' . $id),
        ]);
    }

    // POST: ยกเลิกใบเสนอราคา
    public function cancelQuote(int $id)
    {
        $quote = $this->quoteModel->find($id);
        if (! $quote || $quote['status'] !== 'pending') {
            return $this->jsonError('ไม่สามารถยกเลิกได้');
        }
        $this->quoteModel->update($id, ['status' => 'cancelled']);
        return $this->response->setJSON(['success' => true]);
    }

    // GET: รายการใบเสนอราคา
    public function listQuotations()
    {
        $quotes = $this->quoteModel->orderBy('id', 'DESC')->findAll(100);
        return view('wholesale/list', [
            'title'  => 'รายการใบเสนอราคา',
            'quotes' => $quotes,
        ]);
    }

    // GET: หน้าพิมพ์ ใบส่งของ + ใบเสร็จ (A5×2 บน A4)
    public function deliveryPrint(int $saleId)
    {
        $saleModel     = new SaleModel();
        $saleItemModel = new SaleItemModel();

        $sale = $saleModel->find($saleId);
        if (! $sale) {
            return redirect()->to('/wholesale/list')->with('error', 'ไม่พบบิล');
        }

        $items   = $saleItemModel->getItemsBySaleId($saleId);
        $quoteId = (int) ($this->request->getGet('quote') ?? 0);
        $quote   = $quoteId ? $this->quoteModel->find($quoteId) : null;

        return view('wholesale/delivery', [
            'title'       => 'ใบส่งของ / ใบเสร็จ',
            'sale'        => $sale,
            'items'       => $items,
            'quote'       => $quote,
            'shopName'    => setting('shop_name', 'ร้านของเรา'),
            'shopAddress' => setting('shop_address', ''),
            'shopTaxId'   => setting('shop_tax_id', ''),
        ]);
    }

    // GET: ล้างตะกร้า
    public function clearCart()
    {
        session()->remove('ws_cart');
        session()->remove('ws_customer');
        session()->remove('ws_discount_pct');
        return redirect()->to('/wholesale');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function calcCart(array $cart, float $discPct): array
    {
        $subtotal = array_sum(array_column($cart, 'subtotal'));
        $discAmt  = round($subtotal * $discPct / 100, 2);
        $total    = $subtotal - $discAmt;
        return [
            'subtotal' => $subtotal,
            'disc_amt' => $discAmt,
            'total'    => $total,
            'count'    => count($cart),
        ];
    }

    private function cartResponse(array $cart): \CodeIgniter\HTTP\Response
    {
        $discPct = (float) (session()->get('ws_discount_pct') ?? 0);
        $cd      = $this->calcCart($cart, $discPct);
        return $this->response->setJSON(['success' => true, 'items' => array_values($cart)] + $cd);
    }

    private function jsonError(string $msg): \CodeIgniter\HTTP\Response
    {
        return $this->response->setJSON(['success' => false, 'message' => $msg]);
    }
}
