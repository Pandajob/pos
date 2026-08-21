<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title>ใบเสนอราคา <?= esc($quote['quote_number']) ?></title>
  <link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <style>
    body { background: #f1f5f9; font-family: 'Sarabun', sans-serif; }

    .toolbar { max-width: 900px; margin: 1.5rem auto .75rem; }

    /* ── A4 document ── */
    .doc-wrapper {
      max-width: 900px; margin: 0 auto 3rem;
      background: #fff;
      box-shadow: 0 4px 24px rgba(0,0,0,.1);
      border-radius: .75rem;
      padding: 2rem 2.5rem;
    }

    .doc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8mm; }
    .shop-name  { font-size: 22pt; font-weight: 900; color: #0f172a; }
    .shop-info  { font-size: 9.5pt; color: #555; margin-top: 2mm; line-height: 1.5; }
    .doc-title  { text-align: right; }
    .doc-title h1 { font-size: 18pt; font-weight: 900; color: #0369a1; border-bottom: 3px solid #0369a1; padding-bottom: 2mm; }
    .doc-title .meta { font-size: 9.5pt; color: #555; margin-top: 2mm; line-height: 1.7; }

    .parties { display: flex; gap: 6mm; margin-bottom: 6mm; }
    .party-box { flex: 1; border: 1.5px solid #cbd5e1; border-radius: 4px; padding: 4mm 5mm; }
    .party-box h3 { font-size: 8pt; color: #0369a1; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2mm; }
    .party-box p  { font-size: 11pt; font-weight: 700; margin: 0 0 1mm; }
    .party-box small { font-size: 9pt; color: #555; line-height: 1.5; }

    /* validity strip */
    .validity { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 4px; padding: 3mm 5mm; margin-bottom: 5mm; font-size: 9.5pt; color: #0369a1; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
    table.items thead th { background: #0369a1; color: #fff; padding: 3mm 3mm; font-size: 9.5pt; text-align: left; }
    table.items thead th.tr { text-align: right; }
    table.items thead th.tc { text-align: center; }
    table.items tbody td { padding: 2.5mm 3mm; font-size: 10pt; border-bottom: 1px solid #e2e8f0; }
    table.items tbody tr:last-child td { border-bottom: 2px solid #cbd5e1; }
    td.tr { text-align: right; }
    td.tc { text-align: center; }

    .summary-block { margin-left: auto; width: 85mm; }
    .summary-block table { width: 100%; }
    .summary-block td { padding: 1.5mm 3mm; font-size: 10pt; }
    .summary-block .lbl { color: #555; }
    .summary-block .val { text-align: right; font-weight: 600; }
    .summary-block .total-row td { font-size: 14pt; font-weight: 900; border-top: 2px solid #0369a1; padding-top: 3mm; color: #0369a1; }

    .note-box { margin-top: 5mm; padding: 3mm 5mm; border: 1px dashed #94a3b8; border-radius: 4px; font-size: 9.5pt; color: #475569; }

    .sign-section { display: flex; gap: 10mm; margin-top: 12mm; }
    .sign-box { flex: 1; text-align: center; border-top: 1px dashed #94a3b8; padding-top: 2mm; font-size: 9pt; color: #64748b; }

    .status-badge-cancelled { color: #dc2626; font-size: 28pt; font-weight: 900; opacity: .15; position: absolute; top: 40%; left: 50%; transform: translate(-50%,-50%) rotate(-20deg); pointer-events: none; white-space: nowrap; }

    .doc-wrapper { position: relative; }

    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .doc-wrapper { box-shadow: none; border-radius: 0; margin: 0; padding: 10mm 12mm; max-width: 100%; }
      .toolbar { display: none !important; }
      @page { size: A4 portrait; margin: 10mm 8mm; }
    }
  </style>
</head>
<body>

<!-- Toolbar (no-print) -->
<div class="toolbar no-print">
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= site_url('/wholesale') ?>" class="btn btn-outline-primary">
      <i class="bi bi-plus-circle me-1"></i>ใบเสนอราคาใหม่
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary">
      <i class="bi bi-printer me-1"></i>พิมพ์ใบเสนอราคา
    </button>

    <?php if ($quote['status'] === 'pending'): ?>
      <button class="btn btn-success fw-bold" onclick="showConfirmModal()">
        <i class="bi bi-check-circle me-1"></i>ลูกค้ายืนยันออเดอร์ → พิมพ์ใบส่งของ+ใบเสร็จ
      </button>
      <button class="btn btn-outline-danger" onclick="cancelQuote()">
        <i class="bi bi-x-circle me-1"></i>ยกเลิกใบเสนอราคา
      </button>
    <?php elseif ($quote['status'] === 'confirmed'): ?>
      <span class="badge bg-success fs-6 align-self-center px-3 py-2">
        <i class="bi bi-check-circle me-1"></i>ยืนยันแล้ว — บิล #<?= esc($quote['sale_id']) ?>
      </span>
      <a href="<?= site_url('/wholesale/delivery/' . $quote['sale_id'] . '?quote=' . $quote['id']) ?>"
         class="btn btn-outline-success">
        <i class="bi bi-file-earmark-arrow-down me-1"></i>พิมพ์ใบส่งของ+ใบเสร็จ
      </a>
    <?php elseif ($quote['status'] === 'cancelled'): ?>
      <span class="badge bg-danger fs-6 align-self-center px-3 py-2">
        <i class="bi bi-x-octagon me-1"></i>ยกเลิกแล้ว
      </span>
    <?php endif; ?>

    <a href="<?= site_url('/wholesale/list') ?>" class="btn btn-outline-secondary ms-auto">
      <i class="bi bi-list-ul me-1"></i>รายการทั้งหมด
    </a>
  </div>
</div>

<!-- A4 Document -->
<div class="doc-wrapper">

  <?php if ($quote['status'] === 'cancelled'): ?>
    <div class="status-badge-cancelled">ยกเลิกแล้ว</div>
  <?php endif; ?>

  <!-- Header -->
  <div class="doc-header">
    <div>
      <div class="shop-name"><?= esc($shopName) ?></div>
      <?php if ($shopAddress): ?>
        <div class="shop-info" style="white-space:pre-line"><?= esc($shopAddress) ?></div>
      <?php endif; ?>
      <?php if ($shopTaxId): ?>
        <div class="shop-info"><strong>เลขประจำตัวผู้เสียภาษี:</strong> <?= esc($shopTaxId) ?></div>
      <?php endif; ?>
    </div>
    <div class="doc-title">
      <h1>ใบเสนอราคา</h1>
      <div class="meta">
        เลขที่: <strong><?= esc($quote['quote_number']) ?></strong><br>
        วันที่: <?= date('d/m/Y', strtotime($quote['created_at'])) ?><br>
        ผู้เสนอ: <?= esc($quote['created_by']) ?>
      </div>
    </div>
  </div>

  <!-- Parties -->
  <div class="parties">
    <div class="party-box">
      <h3>ผู้ขาย (Seller)</h3>
      <p><?= esc($shopName) ?></p>
      <?php if ($shopAddress): ?>
        <small style="white-space:pre-line"><?= esc($shopAddress) ?></small><br>
      <?php endif; ?>
      <?php if ($shopTaxId): ?>
        <small>เลขภาษี: <?= esc($shopTaxId) ?></small>
      <?php endif; ?>
    </div>
    <div class="party-box">
      <h3>ผู้ซื้อ (Buyer)</h3>
      <p><?= esc($quote['customer_name'] ?: 'ลูกค้าทั่วไป') ?></p>
      <?php if ($quote['customer_address']): ?>
        <small style="white-space:pre-line"><?= esc($quote['customer_address']) ?></small><br>
      <?php endif; ?>
      <?php if ($quote['customer_tax_id']): ?>
        <small>เลขภาษี: <?= esc($quote['customer_tax_id']) ?></small><br>
      <?php endif; ?>
      <?php if ($quote['customer_phone']): ?>
        <small>โทร: <?= esc($quote['customer_phone']) ?></small>
      <?php endif; ?>
    </div>
  </div>

  <!-- Validity -->
  <div class="validity">
    <i class="bi bi-clock-history me-1"></i>
    ใบเสนอราคานี้มีผล <strong>30 วัน</strong> นับจากวันที่ออก
    (ถึง <?= date('d/m/Y', strtotime($quote['created_at'] . ' +30 days')) ?>)
  </div>

  <!-- Items table -->
  <?php
    $subtotal = 0;
    foreach ($items as $i => $item) { $subtotal += $item['subtotal']; }
    $discPct = (float) $quote['discount_pct'];
    $discAmt = (float) $quote['discount_amount'];
    $total   = (float) $quote['total_amount'];
  ?>
  <table class="items">
    <thead>
      <tr>
        <th class="tc" style="width:8mm">#</th>
        <th>รายการสินค้า</th>
        <th class="tc" style="width:18mm">จำนวน</th>
        <th class="tr" style="width:28mm">ราคาต่อหน่วย (฿)</th>
        <th class="tr" style="width:28mm">จำนวนเงิน (฿)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $idx => $item): ?>
        <tr>
          <td class="tc text-muted"><?= $idx + 1 ?></td>
          <td>
            <strong><?= esc($item['product_name']) ?></strong>
            <?php if ($item['barcode']): ?>
              <br><small style="color:#888"><?= esc($item['barcode']) ?></small>
            <?php endif; ?>
          </td>
          <td class="tc"><?= number_format($item['quantity']) ?></td>
          <td class="tr"><?= number_format($item['price'], 2) ?></td>
          <td class="tr fw-bold"><?= number_format($item['subtotal'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Summary -->
  <div class="summary-block">
    <table>
      <tr>
        <td class="lbl">ราคาสินค้ารวม</td>
        <td class="val">฿<?= number_format($subtotal, 2) ?></td>
      </tr>
      <?php if ($discPct > 0 || $discAmt > 0): ?>
        <tr>
          <td class="lbl">ส่วนลด<?= $discPct > 0 ? " ({$discPct}%)" : '' ?></td>
          <td class="val" style="color:#16a34a">-฿<?= number_format($discAmt, 2) ?></td>
        </tr>
      <?php endif; ?>
      <tr class="total-row">
        <td>ยอดรวมทั้งสิ้น</td>
        <td>฿<?= number_format($total, 2) ?></td>
      </tr>
    </table>
  </div>

  <!-- Note -->
  <?php if ($quote['note']): ?>
    <div class="note-box">
      <strong>หมายเหตุ:</strong> <?= esc($quote['note']) ?>
    </div>
  <?php endif; ?>

  <!-- Signatures -->
  <div class="sign-section">
    <div class="sign-box">
      ลายมือชื่อผู้เสนอราคา<br><br><br>
      (....................................)
      <br><small><?= esc($quote['created_by']) ?></small>
    </div>
    <div class="sign-box">
      ลายมือชื่อผู้มีอำนาจ<br><br><br>
      (....................................)
      <br><small>ประทับตราบริษัท</small>
    </div>
    <div class="sign-box">
      ลายมือชื่อผู้ซื้อ / ตรวจรับ<br><br><br>
      (....................................)
      <br><small>วันที่ยืนยัน ...................</small>
    </div>
  </div>

  <div style="text-align:center;font-size:8pt;color:#aaa;margin-top:8mm">
    เอกสารนี้ออกโดยระบบ POS — <?= esc($shopName) ?> — <?= date('d/m/Y H:i') ?>
  </div>

</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title"><i class="bi bi-check-circle me-2"></i>ยืนยันออเดอร์และสร้างบิล</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          ระบบจะสร้างบิลขาย และหักสต๊อกสินค้า จากนั้นเปิดหน้าพิมพ์ <strong>ใบส่งของ + ใบเสร็จ</strong> (A4)
        </p>
        <div class="mb-3">
          <label class="form-label fw-semibold small">วิธีชำระเงิน</label>
          <select id="payment-method" class="form-select">
            <option value="transfer">โอนเงิน</option>
            <option value="cash">เงินสด</option>
            <option value="qr">QR / พร้อมเพย์</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">ยอดรับชำระ (฿) — ปล่อยว่างถ้าเท่ากับยอดรวม</label>
          <input type="number" id="paid-amount" class="form-control"
                 placeholder="<?= number_format($quote['total_amount'], 2) ?>"
                 min="0" step="0.01">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-success" id="btn-confirm">
          <i class="bi bi-check-circle me-1"></i>ยืนยันและพิมพ์เอกสาร
        </button>
      </div>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/bootstrap.bundle.min.js') ?>"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const BASE = '<?= base_url() ?>';

function showConfirmModal() {
  new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

document.getElementById('btn-confirm')?.addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังดำเนินการ...';

  const paidAmt = document.getElementById('paid-amount').value || '<?= $quote['total_amount'] ?>';
  const pm      = document.getElementById('payment-method').value;

  fetch(`${BASE}wholesale/confirm/<?= $quote['id'] ?>`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF },
    body: `payment_method=${pm}&paid_amount=${paidAmt}&csrf_token=${CSRF}`,
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      location.href = data.redirect;
    } else {
      alert(data.message);
      this.disabled = false;
      this.innerHTML = '<i class="bi bi-check-circle me-1"></i>ยืนยันและพิมพ์เอกสาร';
    }
  });
});

function cancelQuote() {
  if (!confirm('ยืนยันยกเลิกใบเสนอราคานี้?')) return;
  fetch(`${BASE}wholesale/cancel/<?= $quote['id'] ?>`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF },
    body: `csrf_token=${CSRF}`,
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) location.reload();
    else alert(data.message);
  });
}
</script>
</body>
</html>
