<?php
$baseUrl      = base_url();
$memberData   = json_encode($member ?? null, JSON_UNESCAPED_UNICODE);
$discountPct  = $discount_pct ?? 0;
$discountAmt  = $discount_amt ?? 0;
$subtotalAmt  = $subtotal ?? $total;
$pointsUsed   = $points_used ?? 0;
$pointsDisc   = $points_discount ?? 0;

$extraHead = <<<CSS
<style>
  body { background: #0f172a; }
  #main  { background: #0f172a; }
  #topbar { background: #1e293b; border-color: #334155; }
  #topbar .page-title { color: #e2e8f0; }
  .content-wrapper { padding: 1rem; }

  #pos-left {
    background: #1e293b;
    border-radius: .75rem;
    height: calc(100vh - 100px);
    display: flex; flex-direction: column; overflow: hidden;
  }
  #pos-right {
    background: #fff;
    border-radius: .75rem;
    height: calc(100vh - 100px);
    display: flex; flex-direction: column; overflow: hidden;
  }

  /* Product grid */
  #product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .5rem; padding: .75rem; overflow-y: auto; flex: 1;
  }
  .product-card {
    background: #0f172a; border: 1px solid #334155;
    border-radius: .5rem; padding: .75rem .5rem;
    text-align: center; cursor: pointer;
    transition: all .15s; color: #e2e8f0;
  }
  .product-card:hover { background: #1d4ed8; border-color: #3b82f6; transform: scale(1.02); }
  .product-card .p-name  { font-size: .8rem; font-weight: 600; line-height: 1.2; margin-bottom: .25rem; }
  .product-card .p-price { font-size: .95rem; font-weight: 700; color: #4ade80; }
  .product-card .p-stock { font-size: .7rem; color: #94a3b8; }
  .product-card.out-of-stock { opacity: .4; pointer-events: none; }

  /* Member bar */
  #member-bar {
    padding: .5rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    background: #f8faff;
  }
  #member-bar.has-member { background: #eff6ff; border-left: 3px solid #3b82f6; }
  #member-info { font-size: .85rem; }

  /* Cart items */
  #cart-body { flex: 1; overflow-y: auto; }
  .cart-row { border-bottom: 1px solid #f1f5f9; padding: .5rem .75rem; }
  .cart-row:last-child { border-bottom: none; }
  .cart-row .item-name  { font-weight: 600; font-size: .9rem; }
  .cart-row .item-price { font-size: .8rem; color: #64748b; }
  .qty-btn {
    width: 28px; height: 28px; border-radius: 50%;
    border: 1px solid #e2e8f0; background: #f8fafc;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-weight: 700; transition: all .1s;
  }
  .qty-btn:hover { background: #dbeafe; border-color: #3b82f6; color: #3b82f6; }
  .qty-input {
    width: 40px; text-align: center; border: 1px solid #e2e8f0;
    border-radius: .25rem; font-weight: 700; font-size: .95rem;
  }

  #payment-panel { border-top: 2px solid #f1f5f9; padding: 1rem; background: #fafbff; }
  #total-display  { font-size: 2rem; font-weight: 800; color: #1e293b; }
  #change-display { font-size: 1.5rem; font-weight: 700; }
  .quick-cash-btn { border-radius: 2rem; font-size: .85rem; }

  #barcode-field {
    background: #0f172a !important; color: #4ade80 !important;
    border-color: #334155 !important; font-family: monospace;
    font-size: 1.1rem; letter-spacing: .05em;
  }
  #barcode-field:focus { box-shadow: 0 0 0 2px #4ade8050 !important; }
  #barcode-field::placeholder { color: #475569 !important; }
  @keyframes scan-pulse {
    0%   { box-shadow: 0 0 0 0 #4ade8060; }
    70%  { box-shadow: 0 0 0 8px transparent; }
    100% { box-shadow: 0 0 0 0 transparent; }
  }
  #barcode-field.scanning { animation: scan-pulse .4s ease-out; }

  /* Member search dropdown */
  #member-dropdown {
    position: absolute; z-index: 3000; width: 100%;
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: .5rem; box-shadow: 0 4px 16px rgba(0,0,0,.1);
    max-height: 220px; overflow-y: auto;
  }
  .member-item {
    padding: .5rem .75rem; cursor: pointer; font-size: .9rem;
    border-bottom: 1px solid #f1f5f9;
  }
  .member-item:last-child { border-bottom: none; }
  .member-item:hover { background: #eff6ff; }
</style>
CSS;
?>
<?= view('templates/header', ['title' => 'หน้าขาย (POS)', 'extraHead' => $extraHead]) ?>

<div class="row g-3">
  <!-- ══ LEFT: ค้นหาสินค้า ══════════════════════════════════════════════════ -->
  <div class="col-lg-7">
    <div id="pos-left">

      <!-- Barcode / Search input -->
      <div class="p-3 border-bottom" style="border-color:#334155 !important">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-0 text-success">
            <i class="bi bi-upc-scan fs-5"></i>
          </span>
          <input type="text" id="barcode-field" class="form-control"
                 placeholder="สแกนบาร์โค้ด หรือพิมพ์ชื่อสินค้าค้นหา..."
                 autocomplete="off" autofocus>
          <button class="btn btn-outline-secondary border-0 text-muted" id="clearSearch" type="button">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div id="search-status" class="text-muted small mt-1 ms-1"></div>
      </div>

      <!-- Product grid -->
      <div id="product-grid">
        <div class="text-center text-muted py-5 w-100" id="grid-placeholder" style="grid-column:1/-1">
          <i class="bi bi-search fs-2 d-block mb-2" style="color:#334155"></i>
          <span style="color:#475569">พิมพ์ชื่อสินค้า หรือ สแกนบาร์โค้ด</span>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ RIGHT: ตะกร้า + ชำระเงิน ═════════════════════════════════════════ -->
  <div class="col-lg-5">
    <div id="pos-right">

      <!-- Cart header -->
      <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-cart3 me-1"></i>ตะกร้า
          <span class="badge bg-primary ms-1" id="cart-count"><?= count($cart) ?></span>
        </h6>
        <div class="d-flex gap-1">
          <a href="<?= site_url('/sales/hold') ?>" class="btn btn-sm btn-warning text-dark fw-semibold"
             title="พักบิลนี้ไว้ เพื่อเริ่มขายลูกค้าคนใหม่ก่อน แล้วค่อยกลับมาขายต่อ">
            <i class="bi bi-pause-circle me-1"></i>พักบิล
          </a>
          <a href="<?= site_url('/sales/clear-cart') ?>" class="btn btn-sm btn-outline-danger"
             onclick="return confirm('ล้างตะกร้าและรีเซ็ตสมาชิก?')">
            <i class="bi bi-trash me-1"></i>ล้าง
          </a>
        </div>
      </div>

      <!-- ── บิลที่พักไว้ (ขายลูกค้าหลายคนพร้อมกัน) ── -->
      <?php if (! empty($held_bills)): ?>
        <div id="held-bills-strip" class="px-2 py-2 border-bottom d-flex gap-2 align-items-center"
             style="overflow-x:auto; background:#fff7ed; border-color:#fed7aa !important">
          <span class="small text-muted fw-semibold flex-shrink-0">
            <i class="bi bi-pause-circle-fill text-warning me-1"></i>พักไว้ <?= count($held_bills) ?> บิล:
          </span>
          <?php foreach ($held_bills as $hb): ?>
            <div class="d-inline-flex align-items-center flex-shrink-0 bg-white rounded border border-warning px-1">
              <a href="<?= site_url('/sales/resume/' . $hb['id']) ?>"
                 class="btn btn-sm btn-link text-dark text-decoration-none py-0 px-1 d-inline-flex align-items-center gap-1"
                 title="เรียกบิลนี้กลับมาขายต่อ">
                <i class="bi bi-bag-check text-warning"></i>
                <span class="fw-semibold" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc($hb['label']) ?></span>
                <span class="badge bg-secondary"><?= $hb['count'] ?> ชิ้น</span>
                <span class="text-success fw-bold">฿<?= number_format($hb['total'], 0) ?></span>
              </a>
              <a href="<?= site_url('/sales/discard-held/' . $hb['id']) ?>"
                 class="btn btn-sm btn-link text-danger p-0 ms-1 me-1"
                 onclick="return confirm('ลบบิลที่พักไว้นี้ทิ้ง? (รายการสินค้าในบิลนี้จะหาย)')"
                 title="ลบบิลที่พักไว้">
                <i class="bi bi-x-circle"></i>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Member bar -->
      <div id="member-bar" class="<?= $member ? 'has-member' : '' ?>">
        <?php if ($member): ?>
          <div id="member-info" class="d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-person-check-fill text-primary me-1"></i>
              <strong><?= esc($member['name']) ?></strong>
              <span class="text-muted ms-1"><?= esc($member['phone'] ?? '') ?></span>
              <span class="badge bg-warning text-dark ms-1">
                <i class="bi bi-star-fill"></i> <?= number_format($member['points']) ?> แต้ม
              </span>
              <?php if (!empty($member['tier_name'])): ?>
                <span class="badge bg-<?= esc($member['badge_color'] ?? 'secondary') ?> ms-1">
                  <?= esc($member['tier_name']) ?>
                  <?php if (($member['discount_pct'] ?? 0) > 0): ?>
                    -<?= $member['discount_pct'] ?>%
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </div>
            <button class="btn btn-sm btn-link text-danger p-0" id="removeMemberBtn">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>
        <?php else: ?>
          <div id="member-info">
            <div class="position-relative">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
                <input type="text" id="memberSearch" class="form-control"
                       placeholder="ค้นหาสมาชิก (ชื่อ/เบอร์/รหัส)..." autocomplete="off">
                <button class="btn btn-outline-primary btn-sm" onclick="window.open('<?= site_url('/members/create') ?>', '_blank')">
                  <i class="bi bi-person-plus"></i>
                </button>
              </div>
              <div id="member-dropdown" style="display:none"></div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Cart items -->
      <div id="cart-body">
        <?php if (empty($cart)): ?>
          <div id="empty-cart" class="text-center text-muted py-5">
            <i class="bi bi-cart-x fs-1 d-block mb-2"></i>ตะกร้าว่างเปล่า
          </div>
        <?php else: ?>
          <div id="empty-cart" style="display:none"></div>
        <?php endif; ?>
        <div id="cart-items">
          <?php foreach ($cart as $productId => $item): ?>
            <div class="cart-row" data-product-id="<?= $productId ?>">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                  <div class="item-name"><?= esc($item['name']) ?></div>
                  <div class="item-price">฿<?= number_format($item['price'], 2) ?> / ชิ้น</div>
                </div>
                <button class="btn btn-sm btn-link text-danger p-0 remove-item" data-id="<?= $productId ?>">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-1">
                  <span class="qty-btn dec-qty" data-id="<?= $productId ?>">-</span>
                  <input type="number" class="qty-input" value="<?= $item['quantity'] ?>"
                         min="1" max="<?= $item['stock'] ?>" data-id="<?= $productId ?>">
                  <span class="qty-btn inc-qty" data-id="<?= $productId ?>">+</span>
                </div>
                <div class="fw-bold text-primary">฿<?= number_format($item['subtotal'], 2) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Payment panel -->
      <div id="payment-panel">
        <!-- ยอดรวม (แสดงเฉพาะเมื่อมีส่วนลดหรือใช้แต้ม) -->
        <div id="subtotal-row" class="d-flex justify-content-between align-items-center mb-1"
             style="<?= ($discountPct > 0 || $pointsDisc > 0) ? '' : 'display:none' ?>">
          <span class="text-muted small">ยอดรวม</span>
          <span class="text-muted small">฿<span id="subtotal-amount"><?= number_format($subtotalAmt, 2) ?></span></span>
        </div>
        <!-- แถวส่วนลด tier -->
        <div id="discount-row" class="d-flex justify-content-between align-items-center mb-1"
             style="<?= $discountPct > 0 ? '' : 'display:none' ?>">
          <span class="text-success small">
            <i class="bi bi-tag-fill me-1"></i>ส่วนลด <span id="discount-pct-label"><?= $discountPct ?></span>%
          </span>
          <span class="text-success fw-bold small">-฿<span id="discount-amount"><?= number_format($discountAmt, 2) ?></span></span>
        </div>
        <!-- แถวใช้แต้ม -->
        <div id="points-discount-row" class="d-flex justify-content-between align-items-center mb-1"
             style="<?= $pointsDisc > 0 ? '' : 'display:none' ?>">
          <span class="text-warning small">
            <i class="bi bi-star-fill me-1"></i>ใช้แต้ม <span id="points-used-label"><?= $pointsUsed ?></span> แต้ม
          </span>
          <span class="text-warning fw-bold small">-฿<span id="points-discount-amount"><?= number_format($pointsDisc, 2) ?></span></span>
        </div>
        <!-- ยอดสุทธิ -->
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted" id="total-label"><?= ($discountPct > 0 || $pointsDisc > 0) ? 'ยอดสุทธิ' : 'ยอดรวม' ?></span>
          <div id="total-display">฿<span id="total-amount"><?= number_format($total, 2) ?></span></div>
        </div>
        <!-- แผงใช้แต้ม (แสดงเมื่อสมาชิกมีแต้ม) -->
        <div id="points-panel" style="<?= ($member && ($member['points'] ?? 0) > 0) ? '' : 'display:none' ?>"
             class="mb-2 p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25">
          <div class="d-flex align-items-center gap-1">
            <i class="bi bi-star-fill text-warning small"></i>
            <span class="small fw-semibold">แต้มสะสม:</span>
            <span class="small text-warning fw-bold" id="avail-points"><?= $member ? number_format($member['points']) : 0 ?></span>
            <span class="small text-muted">แต้ม</span>
            <input type="number" id="redeem-input" class="form-control form-control-sm ms-1"
                   style="width:70px" min="0" value="<?= $pointsUsed ?>"
                   placeholder="0">
            <button class="btn btn-warning btn-sm py-0 px-2" id="redeem-all-btn" title="ใช้ทั้งหมด">
              ทั้งหมด
            </button>
            <button class="btn btn-outline-secondary btn-sm py-0 px-2" id="redeem-clear-btn" title="ล้าง">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>

        <!-- ── ช่องทางชำระเงิน ── -->
        <div class="mb-3">
          <div class="btn-group w-100" role="group" id="payment-method-group">
            <input type="radio" class="btn-check" name="payment_method_radio" id="pm_cash" value="cash" checked autocomplete="off">
            <label class="btn btn-outline-secondary fw-semibold" for="pm_cash">
              <i class="bi bi-cash-coin me-1"></i>เงินสด
            </label>
            <input type="radio" class="btn-check" name="payment_method_radio" id="pm_qr" value="qr" autocomplete="off">
            <label class="btn btn-outline-primary fw-semibold" for="pm_qr">
              <i class="bi bi-qr-code me-1"></i>QR
            </label>
            <input type="radio" class="btn-check" name="payment_method_radio" id="pm_transfer" value="transfer" autocomplete="off">
            <label class="btn btn-outline-info fw-semibold" for="pm_transfer">
              <i class="bi bi-bank me-1"></i>โอน
            </label>
            <input type="radio" class="btn-check" name="payment_method_radio" id="pm_credit" value="credit" autocomplete="off">
            <label class="btn btn-outline-warning fw-semibold" for="pm_credit"
                   title="ขายเชื่อ — บันทึกเป็นหนี้ค้างชำระของสมาชิก แล้วมาเก็บเงินทีหลัง">
              <i class="bi bi-journal-text me-1"></i>ค้างชำระ
            </label>
          </div>
        </div>

        <!-- Quick cash (ซ่อนเมื่อ QR/โอน) -->
        <div id="cash-panel">
          <div class="d-flex gap-1 flex-wrap mb-2">
            <button class="btn btn-success btn-sm fw-bold" id="exact-btn" style="border-radius:2rem">
              <i class="bi bi-check2-circle me-1"></i>พอดี
            </button>
            <?php foreach ([20, 50, 100, 500, 1000] as $amount): ?>
              <button class="btn btn-outline-secondary quick-cash-btn btn-sm" data-amount="<?= $amount ?>">
                ฿<?= number_format($amount) ?>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="input-group mb-2">
            <span class="input-group-text fw-semibold">รับเงิน ฿</span>
            <input type="number" id="paid-amount" class="form-control form-control-lg text-end fw-bold"
                   placeholder="0.00" min="0" step="0.01">
            <button class="btn btn-outline-danger" type="button" id="clear-paid-btn" title="เคลียร์ช่องเงิน">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">เงินทอน</span>
            <div id="change-display" class="text-success">฿<span id="change-amount">0.00</span></div>
          </div>

          <!-- เตือนเมื่อรับเงินน้อยกว่ายอดที่ต้องชำระ -->
          <div id="paid-warning" class="alert alert-danger text-center fw-bold py-2 px-3 mb-3"
               style="display:none; font-size:1.15rem; border:2px solid #dc2626">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>เงินไม่พอ! ขาดอีก ฿<span id="paid-short">0.00</span>
          </div>
        </div>

        <!-- QR / Transfer panel -->
        <div id="exact-panel" style="display:none" class="mb-3">
          <div class="alert alert-info border-0 py-2 mb-2 text-center small">
            <i class="bi bi-check-circle me-1 text-success"></i>
            ชำระเต็มจำนวน — ไม่มีเงินทอน
          </div>
        </div>

        <!-- Credit (ค้างชำระ) panel -->
        <div id="credit-panel" style="display:none" class="mb-3">
          <div class="alert alert-warning border-0 py-2 mb-2 small">
            <i class="bi bi-journal-text me-1"></i>
            <strong>ขายเชื่อ:</strong> บันทึกเป็นหนี้ค้างชำระของ
            <span class="fw-bold" id="credit-member-name">สมาชิก</span>
            — ไปเก็บเงิน/ดูยอดค้างได้ที่เมนู "ค้างชำระ / เงินเชื่อ"
          </div>
          <div class="input-group input-group-sm mb-1">
            <span class="input-group-text">มัดจำ / ชำระบางส่วน ฿</span>
            <input type="number" id="credit-deposit" class="form-control text-end fw-bold"
                   placeholder="0.00 (ไม่บังคับ)" min="0" step="0.01">
          </div>
          <div class="small text-muted">เว้นว่าง = ค้างชำระทั้งบิล</div>
        </div>

        <button id="checkout-btn" class="btn btn-success w-100 btn-lg fw-bold" disabled>
          <i class="bi bi-check-circle me-2"></i>ชำระเงิน
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Qty modal -->
<div class="modal fade" id="qtyModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="qtyModalTitle">ใส่จำนวน</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div class="text-muted small mb-2" id="qtyModalStock"></div>
        <input type="number" id="qtyModalInput" class="form-control form-control-lg text-center" value="1" min="1">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="qtyModalConfirm">
          <i class="bi bi-cart-plus me-1"></i>เพิ่มในตะกร้า
        </button>
      </div>
    </div>
  </div>
</div>

<!-- เงินไม่พอ modal -->
<div class="modal fade" id="insufficientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-danger border-2">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>เงินไม่พอ</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body text-center py-4">
        <div class="text-muted mb-1">รับเงินมาน้อยกว่ายอดที่ต้องชำระ</div>
        <div class="d-flex justify-content-between mt-3 px-2">
          <span class="text-muted">ยอดที่ต้องชำระ</span>
          <span class="fw-semibold">฿<span id="ins-total">0.00</span></span>
        </div>
        <div class="d-flex justify-content-between px-2">
          <span class="text-muted">รับเงินมา</span>
          <span class="fw-semibold">฿<span id="ins-paid">0.00</span></span>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between px-2 fs-5 fw-bold text-danger">
          <span>ขาดอีก</span>
          <span>฿<span id="ins-short">0.00</span></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger w-100 fw-bold" data-bs-dismiss="modal">
          <i class="bi bi-arrow-left me-1"></i>กลับไปแก้ไขจำนวนเงิน
        </button>
      </div>
    </div>
  </div>
</div>

<?php $extraScript = '<script>
const BASE        = ' . json_encode($baseUrl) . ';
const AUTO_PRINT  = ' . json_encode($auto_print ?? '0') . ';
const PRINTER_TYPE= ' . json_encode($printer_type ?? 'slip80') . ';
let currentMember = ' . $memberData . ';

// ── CSRF ──────────────────────────────────────────────────────────────────
const CSRF = () => document.querySelector(\'meta[name="csrf-token"]\')?.content ?? \'\';

// ── Elements ──────────────────────────────────────────────────────────────
const barcodeField  = document.getElementById(\'barcode-field\');
const cartItemsEl   = document.getElementById(\'cart-items\');
const emptyCartEl   = document.getElementById(\'empty-cart\');
const totalEl       = document.getElementById(\'total-amount\');
const changeEl      = document.getElementById(\'change-amount\');
const cartCountEl   = document.getElementById(\'cart-count\');
const paidInput     = document.getElementById(\'paid-amount\'); // null-safe ถ้าซ่อน
const checkoutBtn   = document.getElementById(\'checkout-btn\');
const memberBar     = document.getElementById(\'member-bar\');

let selectedProduct = null;
let searchTimeout   = null;
const qtyModal      = new bootstrap.Modal(document.getElementById(\'qtyModal\'));
const insufficientModal = new bootstrap.Modal(document.getElementById(\'insufficientModal\'));

// ── Barcode scanner (Enter key) ───────────────────────────────────────────
barcodeField.addEventListener(\'keydown\', function(e) {
  if (e.key === \'Enter\') {
    e.preventDefault();
    const val = barcodeField.value.trim();
    if (val.length >= 2) triggerBarcodeLookup(val);
    barcodeField.value = \'\';
  }
});

function triggerBarcodeLookup(barcode) {
  barcodeField.classList.add(\'scanning\');
  setTimeout(() => barcodeField.classList.remove(\'scanning\'), 500);
  setStatus(\'กำลังค้นหาบาร์โค้ด: \' + barcode, \'text-warning\');

  fetch(BASE + \'sales/barcode?barcode=\' + encodeURIComponent(barcode))
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        addToCart(data.product.id, 1, true);
        setStatus(\'✓ พบ: \' + data.product.name, \'text-success\');
      } else {
        setStatus(\'✗ \' + data.message, \'text-danger\');
      }
    })
    .catch(() => setStatus(\'เกิดข้อผิดพลาด\', \'text-danger\'));
}

// ── Product search (typing) ───────────────────────────────────────────────
barcodeField.addEventListener(\'input\', function() {
  const val = this.value.trim();
  clearTimeout(searchTimeout);
  if (val.length === 0) { clearGrid(); setStatus(\'\'); return; }
  setStatus(\'กำลังค้นหา...\', \'text-muted\');
  searchTimeout = setTimeout(() => {
    fetch(BASE + \'sales/search?q=\' + encodeURIComponent(val))
      .then(r => r.json())
      .then(renderProductGrid);
  }, 300);
});

document.getElementById(\'clearSearch\').addEventListener(\'click\', () => {
  barcodeField.value = \'\'; clearGrid(); setStatus(\'\'); barcodeField.focus();
});

function clearGrid() {
  document.getElementById(\'product-grid\').innerHTML =
    `<div class="text-center text-muted py-5 w-100" style="grid-column:1/-1;color:#475569">
       <i class="bi bi-search fs-2 d-block mb-2"></i>พิมพ์ชื่อสินค้า หรือ สแกนบาร์โค้ด
     </div>`;
}

function renderProductGrid(products) {
  const grid = document.getElementById(\'product-grid\');
  if (!Array.isArray(products)) {
    setStatus(\'เกิดข้อผิดพลาดในการค้นหา\', \'text-danger\'); return;
  }
  if (products.length === 0) {
    grid.innerHTML = `<div class="text-center py-5 w-100" style="grid-column:1/-1;color:#94a3b8">
      <i class="bi bi-inbox fs-2 d-block mb-2"></i>ไม่พบสินค้า</div>`;
    setStatus(\'ไม่พบสินค้าที่ตรงกัน\', \'text-muted\'); return;
  }
  setStatus(`พบ ${products.length} รายการ`, \'text-success\');
  grid.innerHTML = products.map(p => `
    <div class="product-card ${p.stock == 0 ? \'out-of-stock\' : \'\'}"
         data-id="${p.id}" data-name="${escAttr(p.name)}"
         data-price="${p.price}" data-stock="${p.stock}">
      <div class="p-name">${escHtml(p.name)}</div>
      <div class="p-price">฿${parseFloat(p.price).toFixed(2)}</div>
      <div class="p-stock">${p.stock > 0 ? \'สต๊อก: \' + p.stock : \'หมดสต๊อก\'}</div>
    </div>
  `).join(\'\');
}

document.getElementById(\'product-grid\').addEventListener(\'click\', function(e) {
  const card = e.target.closest(\'.product-card\');
  if (!card || card.classList.contains(\'out-of-stock\')) return;
  onProductClick(
    parseInt(card.dataset.id),
    card.dataset.name,
    parseFloat(card.dataset.price),
    parseInt(card.dataset.stock)
  );
});

// ── Product click → qty modal ─────────────────────────────────────────────
function onProductClick(id, name, price, stock) {
  selectedProduct = { id, name, price, stock };
  document.getElementById(\'qtyModalTitle\').textContent = name + \' (฿\' + parseFloat(price).toFixed(2) + \')\';
  document.getElementById(\'qtyModalStock\').textContent = \'สต๊อกคงเหลือ: \' + stock + \' ชิ้น\';
  document.getElementById(\'qtyModalInput\').value = 1;
  document.getElementById(\'qtyModalInput\').max   = stock;
  qtyModal.show();
  setTimeout(() => document.getElementById(\'qtyModalInput\').select(), 300);
}

document.getElementById(\'qtyModalConfirm\').addEventListener(\'click\', function() {
  if (!selectedProduct) return;
  const qty = parseInt(document.getElementById(\'qtyModalInput\').value) || 1;
  addToCart(selectedProduct.id, qty, false);
  qtyModal.hide();
});

document.getElementById(\'qtyModalInput\').addEventListener(\'keydown\', function(e) {
  if (e.key === \'Enter\') document.getElementById(\'qtyModalConfirm\').click();
});

// ── Add to cart ───────────────────────────────────────────────────────────
function addToCart(productId, qty, autoScan) {
  fetch(BASE + \'sales/add-to-cart\', {
    method: \'POST\',
    headers: { \'Content-Type\': \'application/x-www-form-urlencoded\', \'X-CSRF-TOKEN\': CSRF() },
    body: `product_id=${productId}&quantity=${qty}&csrf_token=${CSRF()}`,
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        refreshCart(data);
        if (autoScan) { barcodeField.value = \'\'; barcodeField.focus(); }
      } else {
        alert(data.message);
      }
    });
}

// ── Cart rendering ────────────────────────────────────────────────────────
function refreshCart(data) {
  cartCountEl.textContent = data.count;

  // ── อัปเดตแถวส่วนลด ──
  const subAmt      = parseFloat(data.subtotal !== undefined ? data.subtotal : data.total).toFixed(2);
  const discPct     = data.discount_pct || 0;
  const discAmt     = parseFloat(data.discount_amt || 0).toFixed(2);
  const ptsUsed     = data.points_used || 0;
  const ptsDisc     = parseFloat(data.points_discount || 0).toFixed(2);
  const hasDiscount = discPct > 0;
  const hasPtsDisc  = ptsUsed > 0;
  const hasAny      = hasDiscount || hasPtsDisc;

  document.getElementById(\'subtotal-amount\').textContent       = subAmt;
  document.getElementById(\'discount-pct-label\').textContent    = discPct;
  document.getElementById(\'discount-amount\').textContent       = discAmt;
  document.getElementById(\'points-used-label\').textContent     = ptsUsed;
  document.getElementById(\'points-discount-amount\').textContent = ptsDisc;

  document.getElementById(\'subtotal-row\').style.display       = hasAny    ? \'\' : \'none\';
  document.getElementById(\'discount-row\').style.display       = hasDiscount ? \'\' : \'none\';
  document.getElementById(\'points-discount-row\').style.display = hasPtsDisc ? \'\' : \'none\';
  document.getElementById(\'total-label\').textContent          = hasAny ? \'ยอดสุทธิ\' : \'ยอดรวม\';

  // sync redeem input
  const redeemIn = document.getElementById(\'redeem-input\');
  if (redeemIn) redeemIn.value = ptsUsed;

  totalEl.textContent = parseFloat(data.total).toFixed(2);
  recalcChange(); updateCheckoutBtn();

  cartItemsEl.innerHTML = data.items.map(item => `
    <div class="cart-row" data-product-id="${item.product_id}">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <div>
          <div class="item-name">${escHtml(item.name)}</div>
          <div class="item-price">฿${parseFloat(item.price).toFixed(2)} / ชิ้น</div>
        </div>
        <button class="btn btn-sm btn-link text-danger p-0 remove-item" data-id="${item.product_id}">
          <i class="bi bi-x-circle"></i>
        </button>
      </div>
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-1">
          <span class="qty-btn dec-qty" data-id="${item.product_id}">-</span>
          <input type="number" class="qty-input" value="${item.quantity}"
                 min="1" max="${item.stock}" data-id="${item.product_id}">
          <span class="qty-btn inc-qty" data-id="${item.product_id}">+</span>
        </div>
        <div class="fw-bold text-primary">฿${parseFloat(item.subtotal).toFixed(2)}</div>
      </div>
    </div>
  `).join(\'\');

  emptyCartEl.style.display = data.items.length === 0 ? \'\' : \'none\';
}

// ── Cart interactions ─────────────────────────────────────────────────────
cartItemsEl.addEventListener(\'click\', function(e) {
  const btn = e.target.closest(\'.dec-qty, .inc-qty, .remove-item\');
  if (!btn) return;
  const id  = btn.dataset.id;
  const row = document.querySelector(`.cart-row[data-product-id="${id}"]`);
  const qtyEl = row?.querySelector(\'.qty-input\');

  if (btn.classList.contains(\'remove-item\')) {
    postCart(\'sales/remove-from-cart\', { product_id: id });
  } else if (btn.classList.contains(\'dec-qty\')) {
    postCart(\'sales/update-cart\', { product_id: id, quantity: parseInt(qtyEl.value) - 1 });
  } else {
    postCart(\'sales/update-cart\', { product_id: id, quantity: parseInt(qtyEl.value) + 1 });
  }
});

cartItemsEl.addEventListener(\'change\', function(e) {
  if (!e.target.classList.contains(\'qty-input\')) return;
  postCart(\'sales/update-cart\', { product_id: e.target.dataset.id, quantity: parseInt(e.target.value) });
});

function postCart(path, params) {
  const body = Object.entries({ ...params, csrf_token: CSRF() })
    .map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join(\'&\');
  fetch(BASE + path, {
    method: \'POST\',
    headers: { \'Content-Type\': \'application/x-www-form-urlencoded\', \'X-CSRF-TOKEN\': CSRF() },
    body,
  }).then(r => r.json()).then(d => { if (d.success) refreshCart(d); else alert(d.message); });
}

// ── Member search ─────────────────────────────────────────────────────────
let memberTimeout = null;

function attachMemberSearch() {
  const searchEl = document.getElementById(\'memberSearch\');
  const dropEl   = document.getElementById(\'member-dropdown\');
  if (!searchEl) return;

  searchEl.addEventListener(\'input\', function() {
    const q = this.value.trim();
    clearTimeout(memberTimeout);
    const dd = document.getElementById(\'member-dropdown\');
    if (q.length < 1) { if (dd) dd.style.display = \'none\'; return; }
    memberTimeout = setTimeout(() => {
      fetch(BASE + \'sales/member-search?q=\' + encodeURIComponent(q))
        .then(r => r.json())
        .then(members => {
          const dd2 = document.getElementById(\'member-dropdown\');
          if (!dd2) return;
          if (members.length === 0) {
            dd2.innerHTML = \'<div class="member-item text-muted">ไม่พบสมาชิก</div>\';
          } else {
            dd2.innerHTML = members.map(m => `
              <div class="member-item"
                   data-id="${m.id}" data-name="${escAttr(m.name)}"
                   data-phone="${escAttr(m.phone ?? \'\')}" data-points="${m.points}">
                <strong>${escHtml(m.name)}</strong>
                <span class="text-muted ms-1">${escHtml(m.phone ?? \'\')}</span>
                <span class="badge bg-warning text-dark ms-1">
                  <i class="bi bi-star-fill"></i> ${m.points} แต้ม
                </span>
                <small class="text-muted ms-1">${escHtml(m.code)}</small>
              </div>
            `).join(\'\');
          }
          dd2.style.display = \'\';
        });
    }, 300);
  });
}

// Delegated click on member-bar for dropdown items
memberBar.addEventListener(\'click\', function(e) {
  const item = e.target.closest(\'.member-item[data-id]\');
  if (!item) return;
  selectMember(
    parseInt(item.dataset.id),
    item.dataset.name,
    item.dataset.phone,
    parseInt(item.dataset.points)
  );
  const dd = document.getElementById(\'member-dropdown\');
  if (dd) dd.style.display = \'none\';
});

document.addEventListener(\'click\', function(e) {
  const dd = document.getElementById(\'member-dropdown\');
  const ms = document.getElementById(\'memberSearch\');
  if (dd && ms && !ms.contains(e.target) && !dd.contains(e.target)) {
    dd.style.display = \'none\';
  }
});

attachMemberSearch();

function selectMember(id, name, phone, points) {
  fetch(BASE + \'sales/set-member\', {
    method: \'POST\',
    headers: { \'Content-Type\': \'application/x-www-form-urlencoded\', \'X-CSRF-TOKEN\': CSRF() },
    body: `member_id=${id}&csrf_token=${CSRF()}`,
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        currentMember = data.member;
        if (data.cart) refreshCart(data.cart);
        const m = data.member;
        const tierBadge = (m && m.tier_name)
          ? `<span class="badge bg-${escHtml(m.badge_color || \'secondary\')} ms-1">${escHtml(m.tier_name)}${m.discount_pct > 0 ? \' -\' + m.discount_pct + \'%\' : \'\'}</span>`
          : \'\';
        memberBar.className = \'has-member\';
        memberBar.innerHTML = `
          <div id="member-info" class="d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-person-check-fill text-primary me-1"></i>
              <strong>${escHtml(name)}</strong>
              <span class="text-muted ms-1">${escHtml(phone ?? \'\')}</span>
              <span class="badge bg-warning text-dark ms-1">
                <i class="bi bi-star-fill"></i> ${points} แต้ม
              </span>
              ${tierBadge}
            </div>
            <button class="btn btn-sm btn-link text-danger p-0" onclick="removeMember()">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>`;
        // แสดงแผงแต้มด้วยข้อมูลล่าสุดจาก server
        updatePointsPanel(parseInt(m.points) || 0);
      }
    });
}

function removeMember() {
  fetch(BASE + \'sales/set-member\', {
    method: \'POST\',
    headers: { \'Content-Type\': \'application/x-www-form-urlencoded\', \'X-CSRF-TOKEN\': CSRF() },
    body: `member_id=0&csrf_token=${CSRF()}`,
  })
    .then(r => r.json())
    .then(data => {
      currentMember = null;
      updatePointsPanel(0);
      // ถ้าเลือกค้างชำระค้างไว้ ให้กลับเป็นเงินสด (ขายเชื่อต้องมีสมาชิก)
      if (getPaymentMethod() === \'credit\') {
        document.getElementById(\'pm_cash\').checked = true;
        document.getElementById(\'pm_cash\').dispatchEvent(new Event(\'change\'));
      }
      if (data.cart) refreshCart(data.cart);
      memberBar.className = \'\';
      memberBar.innerHTML = `
        <div id="member-info">
          <div class="position-relative">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
              <input type="text" id="memberSearch" class="form-control"
                     placeholder="ค้นหาสมาชิก (ชื่อ/เบอร์/รหัส)..." autocomplete="off">
              <button class="btn btn-outline-primary btn-sm" onclick="window.open(\'${BASE}members/create\', \'_blank\')">
                <i class="bi bi-person-plus"></i>
              </button>
            </div>
            <div id="member-dropdown" style="display:none"></div>
          </div>
        </div>`;
      attachMemberSearch();
    });
}

// removeMemberBtn (server-rendered when member is set)
const removeMemberBtn = document.getElementById(\'removeMemberBtn\');
if (removeMemberBtn) removeMemberBtn.addEventListener(\'click\', removeMember);

// ── Payment method toggle ─────────────────────────────────────────────────
function getPaymentMethod() {
  return document.querySelector(\'input[name="payment_method_radio"]:checked\')?.value ?? \'cash\';
}

document.querySelectorAll(\'input[name="payment_method_radio"]\').forEach(radio => {
  radio.addEventListener(\'change\', function () {
    // ขายเชื่อต้องมีสมาชิก (ต้องรู้ว่าใครค้างชำระ)
    if (this.value === \'credit\' && !currentMember) {
      alert(\'ขายเชื่อ (ค้างชำระ) ต้องเลือกสมาชิกก่อน\\nกรุณาค้นหา/เลือกสมาชิกที่แผงด้านขวา แล้วค่อยเลือกค้างชำระ\');
      document.getElementById(\'pm_cash\').checked = true;
      document.getElementById(\'pm_cash\').dispatchEvent(new Event(\'change\'));
      return;
    }
    const isCash   = this.value === \'cash\';
    const isCredit = this.value === \'credit\';
    document.getElementById(\'cash-panel\').style.display   = isCash ? \'\' : \'none\';
    document.getElementById(\'credit-panel\').style.display = isCredit ? \'\' : \'none\';
    document.getElementById(\'exact-panel\').style.display  = (isCash || isCredit) ? \'none\' : \'\';
    if (isCredit) {
      const nameEl = document.getElementById(\'credit-member-name\');
      if (nameEl) nameEl.textContent = currentMember?.name ?? \'สมาชิก\';
      const dep = document.getElementById(\'credit-deposit\');
      if (dep) dep.value = \'\';
    }
    if (!isCash && !isCredit) {
      // QR/โอน: ตั้งเป็นพอดีอัตโนมัติ
      const total = getTotal();
      if (paidInput) paidInput.value = total.toFixed(2);
    }
    updateCheckoutBtn();
  });
});

// ── Payment ───────────────────────────────────────────────────────────────
document.querySelectorAll(\'.quick-cash-btn\').forEach(btn => {
  btn.addEventListener(\'click\', function() {
    // บวกสะสมเหมือนยื่นแบงก์จริง: กด ฿20 = รับ 20, กดอีกที = 40 ฯลฯ
    const amount  = parseFloat(this.dataset.amount);
    const current = parseFloat(paidInput.value) || 0;
    paidInput.value = (current + amount).toFixed(2);
    recalcChange(); updateCheckoutBtn();
  });
});

document.getElementById(\'exact-btn\').addEventListener(\'click\', function() {
  const total = getTotal();
  if (total > 0) { paidInput.value = total.toFixed(2); recalcChange(); updateCheckoutBtn(); }
});

// ปุ่มเคลียร์ช่องรับเงิน
document.getElementById(\'clear-paid-btn\').addEventListener(\'click\', function() {
  if (paidInput) { paidInput.value = \'\'; paidInput.focus(); }
  recalcChange(); updateCheckoutBtn();
});

paidInput.addEventListener(\'input\', () => { recalcChange(); updateCheckoutBtn(); });

function recalcChange() {
  const warn    = document.getElementById(\'paid-warning\');
  const shortEl = document.getElementById(\'paid-short\');
  const cd      = document.getElementById(\'change-display\');
  if (getPaymentMethod() !== \'cash\') { if (warn) warn.style.display = \'none\'; return; }
  const total  = getTotal();
  const paid   = parseFloat(paidInput?.value) || 0;
  const change = paid - total;
  const short  = paid > 0 && total > 0 && paid < total; // กรอกเงินแล้วแต่ยังไม่พอ

  // ช่อง "เงินทอน": ถ้าเงินไม่พอ ให้ขึ้น "เงินไม่พอ" สีแดงแทนเลข 0.00 (กันพนักงานเข้าใจผิด)
  if (short) {
    if (cd) { cd.innerHTML = \'<span class="text-danger">เงินไม่พอ</span>\'; }
  } else {
    if (cd) { cd.innerHTML = \'฿<span id="change-amount">\' + (change >= 0 ? change.toFixed(2) : \'0.00\') + \'</span>\'; cd.style.color = \'#16a34a\'; }
  }

  // แถบเตือน "เงินไม่พอ — ขาดอีก ฿x"
  if (warn) {
    if (short) {
      if (shortEl) shortEl.textContent = (total - paid).toFixed(2);
      warn.style.display = \'block\';
    } else {
      warn.style.display = \'none\';
    }
  }
}

function updateCheckoutBtn() {
  const total  = getTotal();
  const method = getPaymentMethod();
  if (method !== \'cash\') {
    checkoutBtn.disabled = (total === 0);
    checkoutBtn.innerHTML = method === \'credit\'
      ? \'<i class="bi bi-journal-text me-2"></i>บันทึกขายเชื่อ (ค้างชำระ)\'
      : \'<i class="bi bi-check-circle me-2"></i>ชำระเงิน\';
    checkoutBtn.classList.remove(\'btn-danger\');
    checkoutBtn.classList.add(\'btn-success\');
    return;
  }
  const paid = parseFloat(paidInput.value) || 0;
  const notEnough = total > 0 && paid < total;
  // ไม่ disable ตอนเงินไม่พอ — ปล่อยให้กดได้เพื่อเด้ง popup เตือน (กันไปต่อใน handler)
  checkoutBtn.disabled = (total === 0);
  // ปุ่มบอกเหตุผลชัด ๆ ว่าเงินยังไม่พอ
  checkoutBtn.innerHTML = notEnough
    ? \'<i class="bi bi-exclamation-triangle-fill me-2"></i>เงินไม่พอ\'
    : \'<i class="bi bi-check-circle me-2"></i>ชำระเงิน\';
  checkoutBtn.classList.toggle(\'btn-danger\', notEnough);
  checkoutBtn.classList.toggle(\'btn-success\', !notEnough);
}

// ── Checkout ──────────────────────────────────────────────────────────────
checkoutBtn.addEventListener(\'click\', function() {
  const method = getPaymentMethod();
  const total  = getTotal();
  let paid;
  if (method === \'credit\') {
    paid = (parseFloat(document.getElementById(\'credit-deposit\')?.value) || 0).toFixed(2);
  } else {
    paid = method !== \'cash\' ? total.toFixed(2) : (paidInput?.value ?? \'0\');
  }

  // ── ขายเชื่อ: มัดจำต้องน้อยกว่ายอดบิล + ยืนยันก่อนบันทึกหนี้ ──
  if (method === \'credit\') {
    const dep = parseFloat(paid) || 0;
    if (dep >= total && total > 0) {
      alert(\'มัดจำเท่ากับ/เกินยอดบิลแล้ว — กรุณาใช้ช่องทางเงินสด/QR/โอน แทน\');
      return;
    }
    const fmt = n => n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    const who = currentMember?.name ?? \'สมาชิก\';
    if (!confirm(\'บันทึกขายเชื่อ (ค้างชำระ)?\\n\\nลูกค้า: \' + who
        + \'\\nยอดบิล: ฿\' + fmt(total)
        + (dep > 0 ? \'\\nมัดจำ: ฿\' + fmt(dep) : \'\')
        + \'\\nค้างชำระ: ฿\' + fmt(total - dep))) {
      return;
    }
  }

  // ── กันไปต่อเมื่อเงินสดไม่พอ → เด้ง popup แจ้งเตือน ──
  if (method === \'cash\' && total > 0 && (parseFloat(paid) || 0) < total) {
    const paidNum = parseFloat(paid) || 0;
    document.getElementById(\'ins-total\').textContent = total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById(\'ins-paid\').textContent  = paidNum.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById(\'ins-short\').textContent = (total - paidNum).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    insufficientModal.show();
    if (paidInput) paidInput.focus();
    return; // ไม่ส่ง checkout
  }

  this.disabled = true;
  this.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...\';

  fetch(BASE + \'sales/checkout\', {
    method: \'POST\',
    headers: { \'Content-Type\': \'application/x-www-form-urlencoded\', \'X-CSRF-TOKEN\': CSRF() },
    body: `paid_amount=${paid}&payment_method=${method}&csrf_token=${CSRF()}`,
  })
    .then(r => r.json())
    .then(async data => {
      if (data.success) {
        // เปิดลิ้นชักอัตโนมัติ (ถ้าตั้งค่า agent ไว้)
        const agentToken = \'' . setting('agent_token', '') . '\';
        const agentPort  = \'' . setting('agent_port', '9991') . '\';
        const autoOpen   = \'' . setting('agent_open_on_checkout', '0') . '\' === \'1\';
        const printMode  = \'' . setting('print_mode', 'browser') . '\';
        const autoPrint  = \'' . setting('auto_print', '0') . '\' === \'1\';
        const creditDep = method === \'credit\' ? (parseFloat(paid) || 0) : 0;
        // ถ้าสลิปจะถูกพิมพ์ผ่าน agent อยู่แล้ว ลิ้นชักจะเด้งพร้อมสลิป — ไม่ต้องเด้งซ้ำตรงนี้
        const slipWillPop = printMode === \'agent\' && autoPrint
                            && (method === \'cash\' || creditDep > 0);
        // ขายเชื่อแบบไม่มีมัดจำ = ไม่ได้รับเงินเลย — ไม่ต้องเด้งลิ้นชัก
        const noCashAtAll = method === \'credit\' && creditDep <= 0;
        if (agentToken && autoOpen && !noCashAtAll && !slipWillPop) {
          // ต้อง "รอ" ให้คำสั่งเปิดลิ้นชักไปถึง agent ก่อนค่อยเปลี่ยนหน้า (เพดาน 1.5 วิ)
          // ยิงทิ้งไว้เฉย ๆ ไม่ได้ — browser จะยกเลิก request ที่ยังวิ่งอยู่ตอน redirect
          // และ keepalive ใช้กับ request ข้าม origin ที่มี header พิเศษไม่ได้ในบางเบราว์เซอร์ (fail ทุกครั้ง)
          const kick = fetch(`http://127.0.0.1:${agentPort}/open-drawer`, {
            method: \'POST\',
            headers: { \'Content-Type\': \'application/json\', \'X-Agent-Token\': agentToken },
            body: JSON.stringify({ triggeredBy: \'checkout\', reason: \'auto after sale\' }),
            signal: AbortSignal.timeout(5000),
          }).catch(() => {}); // silent fail — ลิ้นชักเปิดไม่ได้ไม่ควรขวางการขาย
          await Promise.race([kick, new Promise(res => setTimeout(res, 1500))]);
        }
        window.location.href = data.redirect;
      } else {
        alert(data.message);
        this.disabled = false;
        updateCheckoutBtn(); // คืนข้อความปุ่มตามช่องทางที่เลือกอยู่ (เงินสด/ขายเชื่อ)
      }
    });
});

function getTotal() {
  return parseFloat(totalEl.textContent.replace(/,/g, \'\')) || 0;
}

// ── Helpers ───────────────────────────────────────────────────────────────
function escHtml(s) {
  return String(s ?? \'\').replace(/&/g,\'&amp;\').replace(/</g,\'&lt;\').replace(/>/g,\'&gt;\');
}
function escAttr(s) {
  return String(s ?? \'\').replace(/&/g,\'&amp;\').replace(/"/g,\'&quot;\').replace(/</g,\'&lt;\').replace(/>/g,\'&gt;\');
}
function setStatus(msg, cls = \'\') {
  const el = document.getElementById(\'search-status\');
  el.className = \'small mt-1 ms-1 \' + cls;
  el.textContent = msg;
}

// ── Points redemption ─────────────────────────────────────────────────────
function updatePointsPanel(points) {
  const panel   = document.getElementById(\'points-panel\');
  const availEl = document.getElementById(\'avail-points\');
  if (!panel) return;
  panel.style.display = points > 0 ? \'\' : \'none\';
  if (availEl) availEl.textContent = points.toLocaleString(\'th-TH\');
  const input = document.getElementById(\'redeem-input\');
  if (input) { input.max = points; input.value = 0; }
}

function applyRedeemPoints(pts) {
  fetch(BASE + \'sales/set-redeem-points\', {
    method: \'POST\',
    headers: { \'Content-Type\': \'application/x-www-form-urlencoded\', \'X-CSRF-TOKEN\': CSRF() },
    body: `points=${pts}&csrf_token=${CSRF()}`,
  }).then(r => r.json()).then(d => { if (d.success) refreshCart(d); else alert(d.message); });
}

const redeemAllBtn   = document.getElementById(\'redeem-all-btn\');
const redeemClearBtn = document.getElementById(\'redeem-clear-btn\');
const redeemInput    = document.getElementById(\'redeem-input\');

if (redeemAllBtn) {
  redeemAllBtn.addEventListener(\'click\', () => {
    const maxPts = parseInt(document.getElementById(\'avail-points\').textContent.replace(/,/g, \'\')) || 0;
    applyRedeemPoints(maxPts);
  });
}
if (redeemClearBtn) {
  redeemClearBtn.addEventListener(\'click\', () => applyRedeemPoints(0));
}
if (redeemInput) {
  let redeemTimeout = null;
  redeemInput.addEventListener(\'input\', () => {
    clearTimeout(redeemTimeout);
    redeemTimeout = setTimeout(() => applyRedeemPoints(parseInt(redeemInput.value) || 0), 600);
  });
}

recalcChange();
updateCheckoutBtn();
barcodeField.focus();
</script>';
?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
