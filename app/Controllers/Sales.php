<?php

namespace App\Controllers;

use App\Models\CustomerTierModel;
use App\Models\MemberModel;
use App\Models\ProductModel;
use App\Models\SaleModel;
use App\Models\SaleItemModel;
use App\Models\SettingModel;

class Sales extends BaseController
{
    protected ProductModel     $productModel;
    protected SaleModel        $saleModel;
    protected SaleItemModel    $saleItemModel;
    protected MemberModel      $memberModel;
    protected CustomerTierModel $tierModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->saleModel     = new SaleModel();
        $this->saleItemModel = new SaleItemModel();
        $this->memberModel   = new MemberModel();
        $this->tierModel     = new CustomerTierModel();
    }

    public function index()
    {
        $cart        = session()->get('cart') ?? [];
        $member      = session()->get('pos_member');
        $cd          = $this->cartData($cart);

        return view('sales/index', [
            'title'           => 'หน้าขาย (POS)',
            'cart'            => $cart,
            'subtotal'        => $cd['subtotal'],
            'discount_pct'    => $cd['discount_pct'],
            'discount_amt'    => $cd['discount_amt'],
            'points_used'     => $cd['points_used'],
            'points_discount' => $cd['points_discount'],
            'total'           => $cd['total'],
            'member'          => $member,
            'held_bills'      => $this->heldBillsSummary(),
            'auto_print'      => setting('auto_print', '0'),
            'printer_type'    => setting('printer_type', 'slip80'),
        ]);
    }

    // AJAX: add product to cart
    public function addToCart()
    {
        $productId = (int) $this->request->getPost('product_id');
        $qty       = max(1, (int) ($this->request->getPost('quantity') ?? 1));

        $product = $this->productModel->find($productId);
        if (! $product) {
            return $this->jsonError('ไม่พบสินค้า');
        }

        $cart       = session()->get('cart') ?? [];
        $currentQty = isset($cart[$productId]) ? $cart[$productId]['quantity'] : 0;
        $newQty     = $currentQty + $qty;

        if ($newQty > $product['stock']) {
            return $this->jsonError('สต๊อกไม่พอ (คงเหลือ: ' . $product['stock'] . ' ชิ้น)');
        }

        // ใช้ราคาส่งถ้าสมาชิกเป็นลูกค้าส่ง
        $posMember   = session()->get('pos_member');
        $isWholesale = $posMember && ($posMember['is_wholesale'] ?? 0);
        $price = ($isWholesale && !empty($product['wholesale_price']))
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

        session()->set('cart', $cart);
        return $this->cartResponse($cart);
    }

    // AJAX: update cart item quantity
    public function updateCart()
    {
        $productId = (int) $this->request->getPost('product_id');
        $qty       = (int) $this->request->getPost('quantity');
        $cart      = session()->get('cart') ?? [];

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

        session()->set('cart', $cart);
        return $this->cartResponse($cart);
    }

    // AJAX: remove item from cart
    public function removeFromCart()
    {
        $productId = (int) $this->request->getPost('product_id');
        $cart      = session()->get('cart') ?? [];
        unset($cart[$productId]);
        session()->set('cart', $cart);
        return $this->cartResponse($cart);
    }

    // AJAX: set member for this sale
    public function setMember()
    {
        $memberId = (int) $this->request->getPost('member_id');
        if ($memberId === 0) {
            session()->remove('pos_member');
            session()->remove('pos_points_redeem');
            // ปรับราคาสินค้าในตะกร้ากลับเป็นราคาปกติ
            $cart = session()->get('cart') ?? [];
            foreach ($cart as $pid => &$item) {
                $product = $this->productModel->find($pid);
                if ($product) {
                    $item['price']    = (float) $product['price'];
                    $item['subtotal'] = $item['price'] * $item['quantity'];
                }
            }
            unset($item);
            session()->set('cart', $cart);
            $cd = $this->cartData($cart);
            return $this->response->setJSON(['success' => true, 'member' => null, 'cart' => $cd]);
        }

        $member = $this->memberModel->find($memberId);
        if (! $member) {
            return $this->jsonError('ไม่พบสมาชิก');
        }

        // โหลดข้อมูล tier
        $tier = $member['tier_id'] ? $this->tierModel->find($member['tier_id']) : null;

        $posMember = [
            'id'           => $member['id'],
            'code'         => $member['code'],
            'name'         => $member['name'],
            'phone'        => $member['phone'],
            'points'       => $member['points'],
            'tier_id'      => $member['tier_id'] ?? null,
            'tier_name'    => $tier['name'] ?? null,
            'badge_color'  => $tier['badge_color'] ?? 'secondary',
            'discount_pct' => $tier ? (float) $tier['discount_pct'] : 0,
            'is_wholesale' => $tier ? (int)  $tier['is_wholesale']  : 0,
        ];
        session()->set('pos_member', $posMember);

        // ปรับราคาในตะกร้าถ้าเป็นลูกค้าส่ง
        $cart = session()->get('cart') ?? [];
        foreach ($cart as $pid => &$item) {
            $product = $this->productModel->find($pid);
            if ($product) {
                $price = ($posMember['is_wholesale'] && !empty($product['wholesale_price']))
                    ? (float) $product['wholesale_price']
                    : (float) $product['price'];
                $item['price']    = $price;
                $item['subtotal'] = $price * $item['quantity'];
            }
        }
        unset($item);
        session()->set('cart', $cart);

        $cd = $this->cartData($cart);
        return $this->response->setJSON(['success' => true, 'member' => $posMember, 'cart' => $cd]);
    }

    // AJAX: checkout / process payment
    public function checkout()
    {
        $cart = session()->get('cart') ?? [];
        if (empty($cart)) {
            return $this->jsonError('ตะกร้าว่างเปล่า');
        }

        $member      = session()->get('pos_member');
        $cd          = $this->cartData($cart);
        $subtotal    = $cd['subtotal'];
        $discountPct = $cd['discount_pct'];
        $discountAmt = $cd['discount_amt'];
        $total       = $cd['total'];
        $paidAmount  = (float) $this->request->getPost('paid_amount');

        $paymentMethod = $this->request->getPost('payment_method') ?? 'cash';
        if (! in_array($paymentMethod, ['cash', 'qr', 'transfer', 'credit'])) {
            $paymentMethod = 'cash';
        }

        // ── ขายเงินเชื่อ (ค้างชำระ) ──
        if ($paymentMethod === 'credit') {
            if (! $member) {
                return $this->jsonError('ขายเงินเชื่อต้องเลือกสมาชิกก่อน (ต้องรู้ว่าใครค้างชำระ)');
            }
            // paid_amount = เงินมัดจำ/ชำระบางส่วน (0 ได้) — ถ้าจ่ายครบให้ใช้เงินสด/QR แทน
            $paidAmount = max(0, min($paidAmount, $total));
            if ($total > 0 && $paidAmount >= $total) {
                return $this->jsonError('ชำระครบแล้ว — กรุณาเลือกช่องทางเงินสด/QR/โอน แทนเงินเชื่อ');
            }
        } elseif ($paidAmount < $total) {
            return $this->jsonError('รับเงินไม่พอ ต้องการ ' . number_format($total, 2) . ' บาท');
        }

        foreach ($cart as $productId => $item) {
            $product = $this->productModel->find($productId);
            if (! $product || $product['stock'] < $item['quantity']) {
                return $this->jsonError('สินค้า "' . $item['name'] . '" สต๊อกไม่พอ');
            }
        }

        $billNumber = $this->saleModel->generateBillNumber();

        $pointsUsed     = $cd['points_used'];
        $pointsDiscount = $cd['points_discount'];

        $saleId = $this->saleModel->insert([
            'bill_number'     => $billNumber,
            'total_amount'    => $total,
            'paid_amount'     => $paidAmount,
            'change_amount'   => $paymentMethod === 'credit' ? 0 : $paidAmount - $total,
            'payment_method'  => $paymentMethod,
            'cashier'         => (session()->get('auth_user') ?? [])['full_name'] ?? setting('cashier_name', 'Admin'),
            'member_id'       => $member['id']   ?? null,
            'member_name'     => $member['name'] ?? null,
            'discount_pct'    => $discountPct,
            'discount_amount' => $discountAmt,
            'points_used'     => $pointsUsed,
            'points_discount' => $pointsDiscount,
        ]);

        foreach ($cart as $item) {
            $this->saleItemModel->insert([
                'sale_id'      => $saleId,
                'product_id'   => $item['product_id'],
                'product_name' => $item['name'],
                'barcode'      => $item['barcode'],
                'price'        => $item['price'],
                'quantity'     => $item['quantity'],
                'subtotal'     => $item['subtotal'],
            ]);
            $this->productModel->decreaseStock($item['product_id'], $item['quantity']);
        }

        if ($member) {
            // หักแต้มที่ใช้ก่อน
            if ($pointsUsed > 0) {
                $this->memberModel->subPoints($member['id'], $pointsUsed);
            }
            // สะสมแต้มจากยอดสุทธิ — บิลเงินเชื่อยังไม่ให้แต้ม (จะได้ตอนเก็บหนี้ครบใน Credit::pay)
            if ($paymentMethod !== 'credit') {
                $ptsRate = max(1, (int) setting('points_rate', '10'));
                $pts     = (int) floor($total / $ptsRate);
                if ($pts > 0) {
                    $this->memberModel->addPoints($member['id'], $pts);
                }
            }
        }

        // บันทึกการขายเข้า "ลิ้นชักเงิน" — เฉพาะเงินสด และเมื่อมีกะ (session) เปิดอยู่
        // เงินสดที่เข้าลิ้นชักสุทธิ = รับเงิน - เงินทอน = ยอดสุทธิของบิล (total)
        // บิลเงินเชื่อ: เข้าลิ้นชักเฉพาะเงินมัดจำที่รับมาจริง (ถือว่ารับเป็นเงินสด)
        $cashIntoDrawer = $paymentMethod === 'cash' ? $total
                        : ($paymentMethod === 'credit' ? $paidAmount : 0);
        if ($cashIntoDrawer > 0) {
            $cashSession = (new \App\Models\CashSessionModel())->getOpenSession();
            if ($cashSession) {
                (new \App\Models\CashMovementModel())->log([
                    'session_id'   => $cashSession['id'],
                    'type'         => 'sale',
                    'amount'       => $cashIntoDrawer,
                    'note'         => ($paymentMethod === 'credit' ? 'มัดจำบิลเงินเชื่อ ' : 'ขายบิล ') . $billNumber,
                    'created_by'   => (session()->get('auth_user') ?? [])['id'] ?? null,
                    'reference_id' => $saleId,
                ]);
            }
        }

        session()->remove('cart');
        session()->remove('pos_member');
        session()->remove('pos_points_redeem');
        $this->pushDisplayClear();

        $autoPrint    = setting('auto_print', '0') === '1';
        $printerType  = setting('printer_type', 'slip80');
        $redirectUrl  = site_url('/receipt/' . $saleId)
            . ($autoPrint ? '?autoprint=1&ptype=' . $printerType : '');

        return $this->response->setJSON([
            'success'     => true,
            'sale_id'     => $saleId,
            'bill_number' => $billNumber,
            'redirect'    => $redirectUrl,
        ]);
    }

    public function clearCart()
    {
        session()->remove('cart');
        session()->remove('pos_member');
        session()->remove('pos_points_redeem');
        $this->pushDisplayClear();
        return redirect()->to('/sales');
    }

    // ─── พักบิล (ขายลูกค้าหลายคนพร้อมกัน) ───────────────────────────────────────

    /** GET: พักบิลปัจจุบันไว้ แล้วล้างจอเพื่อเริ่มขายลูกค้าคนใหม่ */
    public function holdBill()
    {
        $cart = session()->get('cart') ?? [];
        if (empty($cart)) {
            return redirect()->to('/sales')->with('error', 'ตะกร้าว่าง ยังไม่มีบิลให้พัก');
        }
        $this->parkActiveCart();
        // ล้าง active cart เพื่อเริ่มลูกค้าใหม่ (บิลเดิมถูกย้ายไปที่พักแล้ว ไม่หาย)
        session()->remove('cart');
        session()->remove('pos_member');
        session()->remove('pos_points_redeem');
        $this->pushDisplayClear();
        return redirect()->to('/sales')->with('success', 'พักบิลแล้ว — เริ่มขายลูกค้าคนใหม่ได้เลย');
    }

    /** GET: เรียกบิลที่พักไว้กลับมาขายต่อ (พักบิลปัจจุบันให้อัตโนมัติก่อน กันรายการหาย) */
    public function resumeBill(int $id)
    {
        $held = session()->get('held_bills') ?? [];
        if (! isset($held[$id])) {
            return redirect()->to('/sales')->with('error', 'ไม่พบบิลที่พักไว้ (อาจถูกเรียกหรือลบไปแล้ว)');
        }

        // ถ้ายังมีบิลที่กำลังขายค้างอยู่ → พักไว้ก่อน เพื่อไม่ให้รายการของลูกค้าคนปัจจุบันหาย
        if (! empty(session()->get('cart'))) {
            $this->parkActiveCart();
            $held = session()->get('held_bills') ?? [];
        }

        $bill = $held[$id];
        unset($held[$id]);
        session()->set('held_bills', $held);

        // โหลดบิลที่พักไว้กลับเข้า active cart
        session()->set('cart', $bill['cart'] ?? []);
        if (! empty($bill['member'])) {
            session()->set('pos_member', $bill['member']);
        } else {
            session()->remove('pos_member');
        }
        session()->set('pos_points_redeem', (int) ($bill['points'] ?? 0));

        $cart = session()->get('cart') ?? [];
        $this->pushDisplayState($cart, $this->cartData($cart));

        return redirect()->to('/sales')->with('success', 'เรียกบิลที่พักไว้กลับมาขายต่อแล้ว');
    }

    /** GET: ลบบิลที่พักไว้ทิ้ง (ไม่ขายแล้ว) */
    public function discardHeldBill(int $id)
    {
        $held = session()->get('held_bills') ?? [];
        if (isset($held[$id])) {
            unset($held[$id]);
            session()->set('held_bills', $held);
            return redirect()->to('/sales')->with('success', 'ลบบิลที่พักไว้แล้ว');
        }
        return redirect()->to('/sales')->with('error', 'ไม่พบบิลที่พักไว้');
    }

    /** บันทึก active cart ปัจจุบันลงรายการบิลที่พักไว้ (snapshot cart + สมาชิก + แต้ม) */
    private function parkActiveCart(): void
    {
        $cart = session()->get('cart') ?? [];
        if (empty($cart)) {
            return;
        }
        $held   = session()->get('held_bills') ?? [];
        $seq    = (int) (session()->get('held_seq') ?? 0) + 1;
        $member = session()->get('pos_member');
        $cd     = $this->cartData($cart);

        $held[$seq] = [
            'id'     => $seq,
            'label'  => $member['name'] ?? ('บิล #' . $seq),
            'member' => $member,
            'cart'   => $cart,
            'points' => (int) (session()->get('pos_points_redeem') ?? 0),
            'count'  => $cd['count'],
            'total'  => $cd['total'],
            'ts'     => time(),
        ];
        session()->set('held_bills', $held);
        session()->set('held_seq', $seq);
    }

    /** สรุปบิลที่พักไว้สำหรับแสดงในหน้าขาย (id, label, จำนวน, ยอดรวม) */
    private function heldBillsSummary(): array
    {
        $held = session()->get('held_bills') ?? [];
        return array_map(fn ($b) => [
            'id'    => $b['id'],
            'label' => $b['label'],
            'count' => $b['count'] ?? count($b['cart'] ?? []),
            'total' => $b['total'] ?? 0,
        ], array_values($held));
    }

    // AJAX: JSON สถานะตะกร้า สำหรับ Customer Display
    public function getCartJson()
    {
        $cart   = session()->get('cart') ?? [];
        $member = session()->get('pos_member');
        $cd     = $this->cartData($cart);
        return $this->response->setJSON([
            'items'           => array_values($cart),
            'member'          => $member,
            'subtotal'        => $cd['subtotal'],
            'discount_pct'    => $cd['discount_pct'],
            'discount_amt'    => $cd['discount_amt'],
            'points_used'     => $cd['points_used'],
            'points_discount' => $cd['points_discount'],
            'total'           => $cd['total'],
            'count'           => $cd['count'],
            'shop_name'       => setting('shop_name', 'ร้านค้า'),
            'qr_image'        => setting('qr_payment_image', ''),
        ]);
    }

// AJAX: ตั้งค่าแต้มที่จะใช้
    public function setRedeemPoints()
    {
        $member = session()->get('pos_member');
        if (! $member) {
            return $this->jsonError('ไม่มีสมาชิก');
        }

        $pts    = max(0, (int) $this->request->getPost('points'));
        $maxPts = (int) $member['points'];
        $pts    = min($pts, $maxPts);

        session()->set('pos_points_redeem', $pts);
        $cart = session()->get('cart') ?? [];
        return $this->cartResponse($cart);
    }

    // AJAX: search products
    public function searchProduct()
    {
        $q = $this->request->getGet('q') ?? '';
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }
        return $this->response->setJSON($this->productModel->searchByNameOrBarcode($q));
    }

    // AJAX: lookup by barcode
    public function getByBarcode()
    {
        $barcode = trim($this->request->getGet('barcode') ?? '');
        $product = $this->productModel->findByBarcode($barcode);
        if ($product) {
            return $this->response->setJSON(['success' => true, 'product' => $product]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบสินค้า (บาร์โค้ด: ' . esc($barcode) . ')']);
    }

    // AJAX: search members for POS
    public function searchMember()
    {
        $q = $this->request->getGet('q') ?? '';
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }
        return $this->response->setJSON($this->memberModel->search($q));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** คืน array ข้อมูลตะกร้า (subtotal, discount, points, total) */
    private function cartData(array $cart): array
    {
        $subtotal    = array_sum(array_column($cart, 'subtotal'));
        $member      = session()->get('pos_member');
        $discountPct = $member ? (float) ($member['discount_pct'] ?? 0) : 0;
        $discountAmt = round($subtotal * $discountPct / 100, 2);
        $afterTier   = $subtotal - $discountAmt;

        // แต้มสะสม
        $pointsUsed    = $member ? max(0, (int) (session()->get('pos_points_redeem') ?? 0)) : 0;
        $pointsValue   = max(0.01, (float) setting('points_value', '1'));
        $pointsDiscount = min(round($pointsUsed * $pointsValue, 2), $afterTier);
        $finalTotal    = $afterTier - $pointsDiscount;

        return [
            'items'           => array_values($cart),
            'count'           => count($cart),
            'subtotal'        => $subtotal,
            'discount_pct'    => $discountPct,
            'discount_amt'    => $discountAmt,
            'points_used'     => $pointsUsed,
            'points_discount' => $pointsDiscount,
            'total'           => $finalTotal,
        ];
    }

    private function cartResponse(array $cart): \CodeIgniter\HTTP\Response
    {
        $cd = $this->cartData($cart);
        $this->pushDisplayState($cart, $cd);
        return $this->response->setJSON(['success' => true] + $cd);
    }

    private function jsonError(string $msg): \CodeIgniter\HTTP\Response
    {
        return $this->response->setJSON(['success' => false, 'message' => $msg]);
    }

    // POST: void sale — admin เท่านั้น
    public function voidSale(int $id)
    {
        $authUser = session()->get('auth_user');
        if (! $authUser || $authUser['role'] !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์ — เฉพาะ Admin เท่านั้น']);
        }

        $sale = $this->saleModel->find($id);
        if (! $sale) {
            return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบบิล']);
        }
        if (! empty($sale['voided_at'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'บิลนี้ถูกยกเลิกแล้ว']);
        }

        $reason = trim($this->request->getPost('reason') ?? '');
        $this->saleModel->voidBill($id, $reason);

        // คืนสต๊อกทุกรายการในบิล
        $items = $this->saleItemModel->getItemsBySaleId($id);
        foreach ($items as $item) {
            $this->productModel->increaseStock($item['product_id'], $item['quantity']);
        }

        // ถ้าเป็นบิลเงินสด → ตัดเงินออกจากลิ้นชักกะปัจจุบัน (เงินคืนลูกค้าออกจากลิ้นชัก)
        if (($sale['payment_method'] ?? 'cash') === 'cash') {
            $cashSession = (new \App\Models\CashSessionModel())->getOpenSession();
            if ($cashSession) {
                (new \App\Models\CashMovementModel())->log([
                    'session_id'   => $cashSession['id'],
                    'type'         => 'void',
                    'amount'       => (float) $sale['total_amount'],
                    'note'         => 'ยกเลิกบิล ' . ($sale['bill_number'] ?? $id),
                    'created_by'   => $authUser['id'] ?? null,
                    'reference_id' => $id,
                ]);
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'ยกเลิกบิลสำเร็จ สต๊อกสินค้าถูกคืนแล้ว']);
    }

    /** เขียนสถานะตะกร้าลง DB เพื่อให้ Customer Display อ่านได้ */
    private function pushDisplayState(array $cart, array $cd): void
    {
        try {
            $sm = new SettingModel();
            $sm->setValue('display_cart', json_encode([
                'items'        => array_values($cart),
                'member'       => session()->get('pos_member'),
                'subtotal'     => $cd['subtotal'],
                'discount_pct' => $cd['discount_pct'],
                'discount_amt' => $cd['discount_amt'],
                'points_disc'  => $cd['points_discount'],
                'total'        => $cd['total'],
                'count'        => $cd['count'],
                'ts'           => time(),
                'status'       => 'active',
            ]));
        } catch (\Throwable $e) { /* silent */ }
    }

    private function pushDisplayClear(): void
    {
        try {
            (new SettingModel())->setValue('display_cart', json_encode([
                'items' => [], 'total' => 0, 'count' => 0, 'ts' => time(), 'status' => 'clear',
            ]));
        } catch (\Throwable $e) { /* silent */ }
    }
}
