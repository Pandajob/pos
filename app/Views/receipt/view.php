<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title>ใบเสร็จ - <?= esc($sale['bill_number']) ?></title>
  <link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <style>
    <?php
      $fsShopname = (int)($billFsShopname ?? 22);
      $fsMeta     = (int)($billFsMeta     ?? 13);
      $fsItems    = (int)($billFsItems    ?? 14);
      $fsTotal    = (int)($billFsTotal    ?? 16);
      $fsFooter   = (int)($billFsFooter   ?? 12);
      $color      = esc($billFontColor    ?? '#000000');
      $fsBase     = (int)($billFontSize   ?? 14);
    ?>
    body { background: #f1f5f9; font-family: 'Sarabun', sans-serif; color: <?= $color ?>; font-size: <?= $fsBase ?>px; }
    .receipt-wrapper { max-width: 420px; margin: 2rem auto; }
    .receipt {
      background: #fff; padding: 1.5rem;
      border-radius: .75rem; box-shadow: 0 4px 24px rgba(0,0,0,.1);
    }
    .receipt-header {
      text-align: center; border-bottom: 2px dashed #e2e8f0;
      padding-bottom: 1rem; margin-bottom: 1rem;
    }
    .store-name { font-size: <?= $fsShopname ?>px; font-weight: 800; }
    .bill-meta  { font-size: <?= $fsMeta ?>px; color: #64748b; }

    .member-strip {
      background: #eff6ff; border: 1px solid #bfdbfe;
      border-radius: .5rem; padding: .5rem .75rem;
      margin-bottom: .75rem; font-size: <?= $fsMeta ?>px;
    }

    /* ตีเส้นตารางทุกช่อง — อ่านง่าย ไล่บรรทัดไม่หลง */
    .receipt-table { width: 100%; font-size: <?= $fsItems ?>px; border-collapse: collapse; }
    .receipt-table th {
      font-weight: 600; color: #334155; font-size: <?= max(10, $fsItems - 2) ?>px;
      background: #f1f5f9; border: 1px solid #cbd5e1; padding: .3rem .35rem;
    }
    .receipt-table td { padding: .35rem .35rem; vertical-align: top; border: 1px solid #cbd5e1; }
    .receipt-table tbody tr:nth-child(even) td { background: #f8fafc; }
    .receipt-table .item-name   { font-weight: 500; }
    .receipt-table .item-detail { font-size: <?= max(10, $fsItems - 2) ?>px; color: #94a3b8; }

    .receipt-summary { border-top: 2px dashed #e2e8f0; margin-top: .75rem; padding-top: .75rem; }
    .receipt-summary td { padding: .2rem; font-size: <?= $fsItems ?>px; }
    .total-row td { font-size: <?= $fsTotal ?>px; font-weight: 800; border-top: 1px solid #e2e8f0; padding-top: .5rem; }
    .change-row td { color: #16a34a; font-weight: 700; }
    .points-row td { color: #d97706; font-size: <?= $fsMeta ?>px; }

    .receipt-footer {
      text-align: center; margin-top: 1rem; padding-top: 1rem;
      border-top: 2px dashed #e2e8f0; color: #94a3b8; font-size: <?= $fsFooter ?>px;
    }

    /* ── พิมพ์ A4 ──────────────────────────────────────────────────────────
       หน้าจอเป็นการ์ดกว้าง 420px แต่กระดาษ A4 กว้าง 210mm
       ถ้าปล่อยให้ยืดเต็มหน้าโดยไม่จัดใหม่ ตัวหนังสือจะเล็กจิ๋ว ช่องตารางถ่างโบ๋ ดูเละ
       ตรงนี้จึงจัดใหม่ทั้งชุดให้เป็น "เอกสาร A4" จริง ๆ — ขนาดตัวอักษรเป็น pt
       สีทุกอย่างเป็นดำ (ไม่พึ่ง background graphics ที่ผู้ใช้มักปิดไว้) */
    @media print {
      @page { size: A4 portrait; margin: 14mm 15mm; }

      html, body {
        background: #fff !important; margin: 0; padding: 0;
        color: #000 !important; font-size: 11pt;
      }
      .no-print { display: none !important; }

      /* เลิกเป็นการ์ด — บนกระดาษคือเอกสารเต็มหน้า */
      .receipt-wrapper { margin: 0; max-width: none; width: 100%; }
      .receipt { box-shadow: none; border-radius: 0; padding: 0; background: #fff; }

      /* หัวเอกสาร */
      .receipt-header {
        border-bottom: 1.2pt solid #000; padding-bottom: 4mm; margin-bottom: 5mm;
      }
      .store-name { font-size: 20pt; font-weight: 900; }
      .bill-meta  { font-size: 10pt; color: #000 !important; line-height: 1.5; }

      .member-strip {
        font-size: 10pt; border: 0.6pt solid #000; background: #fff !important;
        padding: 2mm 3mm; margin-bottom: 4mm; border-radius: 0;
      }

      /* ตารางรายการ — เต็มความกว้างกระดาษ ตีเส้นแบบเอกสาร */
      .receipt-table { width: 100%; font-size: 10.5pt; }
      .receipt-table thead { display: table-header-group; }   /* ขึ้นหน้าใหม่ให้มีหัวตารางซ้ำ */
      .receipt-table tr { page-break-inside: avoid; }
      .receipt-table th {
        font-size: 10pt; font-weight: 700; color: #000 !important;
        background: #fff !important; border: 0.6pt solid #000; padding: 2mm 2.5mm;
      }
      .receipt-table td { border: 0.5pt solid #555; padding: 2mm 2.5mm; }
      .receipt-table tbody tr:nth-child(even) td { background: #fff !important; }
      .receipt-table .item-detail { font-size: 8.5pt; color: #555 !important; }

      /* วิธีชำระเงิน */
      .badge {
        border: 0.6pt solid #000 !important; background: #fff !important;
        color: #000 !important; font-size: 10pt !important; padding: 1.5mm 3mm !important;
      }

      /* สรุปยอด — ชิดขวา ไม่ถ่างเต็มหน้า */
      /* ต้องมี !important — ตัวตารางติดคลาส .w-100 ของ Bootstrap ซึ่งเป็น width:100% !important */
      .receipt-summary {
        width: 80mm !important; margin: 4mm 0 0 auto; border-top: none; padding-top: 0;
      }
      .receipt-summary td { font-size: 11pt; padding: 1.2mm 2mm; color: #000 !important; }
      .receipt-summary .small,
      .receipt-summary .text-muted,
      .receipt-summary .text-success,
      .receipt-summary .text-warning,
      .receipt-summary .text-danger { color: #000 !important; font-size: 10pt !important; }
      .total-row  td { font-size: 14pt; font-weight: 900; border-top: 1pt solid #000; padding-top: 2.5mm; }
      .change-row td { color: #000 !important; font-size: 12pt; }
      .points-row td { color: #000 !important; }

      .receipt-footer {
        margin-top: 10mm; padding-top: 4mm;
        border-top: 1pt dashed #000; color: #000 !important; font-size: 10pt;
      }
    }
  </style>
</head>
<body>

<div class="receipt-wrapper">

  <!-- Toolbar -->
  <div class="no-print d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= site_url('/sales') ?>" class="btn btn-primary">
      <i class="bi bi-cart-plus me-1"></i>ขายต่อ
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary">
      <i class="bi bi-printer me-1"></i>พิมพ์ A4
    </button>
    <button onclick="printCompactReceipt()" class="btn btn-outline-dark">
      <i class="bi bi-receipt me-1"></i>สลิป 80mm
    </button>
    <button onclick="printTaxInvoice()" class="btn btn-outline-primary">
      <i class="bi bi-file-earmark-text me-1"></i>ใบกำกับภาษี (ต้นฉบับ+สำเนา)
    </button>
    <button onclick="printDeliveryNote()" class="btn btn-outline-success">
      <i class="bi bi-truck me-1"></i>ใบส่งของ (A5 ×2 แผ่น)
    </button>
    <?php $authRole = (session()->get('auth_user') ?? [])['role'] ?? ''; ?>
    <?php if (empty($sale['voided_at'])): ?>
      <a href="<?= site_url('/returns/create/' . $sale['id']) ?>" class="btn btn-outline-warning">
        <i class="bi bi-arrow-return-left me-1"></i>คืนสินค้า
      </a>
      <?php if ($authRole === 'admin'): ?>
        <button class="btn btn-outline-danger" onclick="showVoidModal()">
          <i class="bi bi-x-octagon me-1"></i>ยกเลิกบิล
        </button>
      <?php endif; ?>
    <?php else: ?>
      <span class="badge bg-danger fs-6 align-self-center px-3 py-2">
        <i class="bi bi-x-octagon me-1"></i>บิลถูกยกเลิกแล้ว
      </span>
    <?php endif; ?>
    <a href="<?= site_url('/reports/daily') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-bar-chart me-1"></i>รายงาน
    </a>
    <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary ms-auto">
      <i class="bi bi-house me-1"></i>หน้าหลัก
    </a>
  </div>


  <?php if (!empty($sale['voided_at'])): ?>
  <div class="no-print alert alert-danger mb-3">
    <i class="bi bi-x-octagon-fill me-2"></i>
    <strong>บิลนี้ถูกยกเลิกแล้ว</strong>
    เมื่อ <?= date('d/m/Y H:i', strtotime($sale['voided_at'])) ?>
    <?php if ($sale['void_reason']): ?> â€” เหตุผล: <?= esc($sale['void_reason']) ?><?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Receipt -->
  <div class="receipt">

    <!-- Header -->
    <div class="receipt-header">
      <div class="store-name"><?= esc($shopName) ?></div>
      <?php if ($shopAddress): ?>
        <div class="bill-meta" style="white-space:pre-line"><?= esc($shopAddress) ?></div>
      <?php endif; ?>
      <?php if ($shopTaxId): ?>
        <div class="bill-meta">เลขภาษี: <?= esc($shopTaxId) ?></div>
      <?php endif; ?>
      <div class="mt-2">
        <div class="bill-meta">เลขบิล: <strong><?= esc($sale['bill_number']) ?></strong></div>
        <div class="bill-meta"><?= date('d/m/Y H:i:s', strtotime($sale['created_at'])) ?></div>
        <div class="bill-meta">พนักงาน: <?= esc($sale['cashier']) ?></div>
      </div>
    </div>

    <!-- Member strip -->
    <?php
      // บิลเงินเชื่อ: แต้มจะได้ตอนชำระครบเท่านั้น (Credit::pay) — อย่าแสดงแต้มถ้ายังค้างอยู่
      $rcCreditUnsettled = ($sale['payment_method'] ?? '') === 'credit' && empty($sale['credit_settled_at']);
    ?>
    <?php if (! empty($sale['member_name'])): ?>
      <?php $earnedPts = $rcCreditUnsettled ? 0 : (int) floor($sale['total_amount'] / $shopPointsRate); ?>
      <div class="member-strip">
        <i class="bi bi-person-check-fill text-primary me-1"></i>
        <strong>สมาชิก:</strong> <?= esc($sale['member_name']) ?>
        <?php if ($earnedPts > 0): ?>
          <span class="badge bg-warning text-dark ms-2">
            <i class="bi bi-star-fill"></i> +<?= $earnedPts ?> แต้ม
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Items -->
    <table class="receipt-table">
      <thead>
        <tr>
          <th style="width:45%">สินค้า</th>
          <th class="text-center" style="width:10%">จำนวน</th>
          <th class="text-end" style="width:20%">ราคา</th>
          <th class="text-end" style="width:25%">รวม</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="item-name"><?= esc($item['product_name']) ?></div>
              <?php if ($item['barcode']): ?>
                <div class="item-detail"><?= esc($item['barcode']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= $item['quantity'] ?></td>
            <td class="text-end"><?= number_format($item['price'], 2) ?></td>
            <td class="text-end fw-semibold"><?= number_format($item['subtotal'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Summary -->
    <?php
      $discountAmt  = (float)($sale['discount_amount'] ?? 0);
      $discountPct  = (float)($sale['discount_pct']    ?? 0);
      $pointsUsed   = (int)  ($sale['points_used']     ?? 0);
      $pointsDisc   = (float)($sale['points_discount'] ?? 0);
      $hasReductions = $discountAmt > 0 || $pointsDisc > 0;
      $subtotalBeforeDiscount = $sale['total_amount'] + $discountAmt + $pointsDisc;
    ?>
    <!-- Payment method badge -->
    <?php
      $pmLabel = ['cash' => '💵 เงินสด', 'qr' => '📱 QR / พร้อมเพย์', 'transfer' => '🏦 โอนเงิน'];
      $pm = $sale['payment_method'] ?? 'cash';
      $pmLabel['credit'] = 'เงินเชื่อ (ค้างชำระ)';
      $isCredit     = $pm === 'credit';
      $creditOwed   = $isCredit ? max(0, (float) $sale['total_amount'] - (float) $sale['paid_amount']) : 0;
      $creditPaidUp = $isCredit && ! empty($sale['credit_settled_at']);
    ?>
    <div class="text-center mb-2">
      <span class="badge bg-light text-dark border" style="font-size:.8rem">
        <?= $pmLabel[$pm] ?? $pm ?>
      </span>
    </div>

    <table class="receipt-summary w-100">
      <?php if ($hasReductions): ?>
        <tr>
          <td class="text-muted">ยอดก่อนลด</td>
          <td class="text-end text-muted">฿<?= number_format($subtotalBeforeDiscount, 2) ?></td>
        </tr>
        <?php if ($discountAmt > 0): ?>
          <tr>
            <td class="text-success small">
              <i class="bi bi-tag-fill me-1"></i>ส่วนลด<?= $discountPct > 0 ? " {$discountPct}%" : '' ?>
            </td>
            <td class="text-end text-success small">-฿<?= number_format($discountAmt, 2) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($pointsDisc > 0): ?>
          <tr>
            <td class="text-warning small">
              <i class="bi bi-star-fill me-1"></i>ใช้แต้ม <?= $pointsUsed ?> แต้ม
            </td>
            <td class="text-end text-warning small">-฿<?= number_format($pointsDisc, 2) ?></td>
          </tr>
        <?php endif; ?>
      <?php endif; ?>
      <tr class="total-row">
        <td><?= $hasReductions ? 'ยอดสุทธิ' : 'ยอดรวมทั้งสิ้น' ?></td>
        <td class="text-end">฿<?= number_format($sale['total_amount'], 2) ?></td>
      </tr>
      <?php if ($isCredit): ?>
      <tr>
        <td class="text-muted">ชำระแล้ว<?= (float) $sale['paid_amount'] > 0 ? ' (รวมมัดจำ)' : '' ?></td>
        <td class="text-end text-muted">฿<?= number_format($sale['paid_amount'], 2) ?></td>
      </tr>
      <tr class="change-row">
        <?php if ($creditPaidUp): ?>
          <td><strong class="text-success">ชำระครบแล้ว ✓</strong></td>
          <td class="text-end"><strong class="text-success">฿0.00</strong></td>
        <?php else: ?>
          <td><strong class="text-danger">ค้างชำระ</strong></td>
          <td class="text-end"><strong class="text-danger">฿<?= number_format($creditOwed, 2) ?></strong></td>
        <?php endif; ?>
      </tr>
      <?php else: ?>
      <tr>
        <td class="text-muted">รับเงิน</td>
        <td class="text-end text-muted">฿<?= number_format($sale['paid_amount'], 2) ?></td>
      </tr>
      <tr class="change-row">
        <td><strong>เงินทอน</strong></td>
        <td class="text-end"><strong>฿<?= number_format($sale['change_amount'], 2) ?></strong></td>
      </tr>
      <?php endif; ?>
      <?php if (! empty($sale['member_name']) && $earnedPts > 0): ?>
        <tr class="points-row">
          <td><i class="bi bi-star-fill me-1"></i>แต้มที่ได้รับ</td>
          <td class="text-end">+<?= $earnedPts ?> แต้ม</td>
        </tr>
      <?php endif; ?>
    </table>

    <!-- Footer -->
    <div class="receipt-footer">
      <p class="mb-1">ขอบคุณที่ใช้บริการ</p>
      <p class="mb-0">กรุณาเก็บใบเสร็จไว้เป็นหลักฐาน</p>
    </div>
  </div>

  <div class="no-print text-center mt-3">
    <small class="text-muted">
      <i class="bi bi-info-circle me-1"></i>กด <kbd>Ctrl+P</kbd> เพื่อพิมพ์ใบเสร็จ
    </small>
  </div>

</div>


<!-- Void modal (แสดงเฉพาะ admin â€” ถูก render ฝั่ง server แล้ว) -->
<div class="modal fade" id="voidModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-x-octagon me-2"></i>ยกเลิกบิล</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          การยกเลิกบิลจะ<strong>คืนสต๊อกสินค้าทั้งหมด</strong>ในบิลนี้ ดำเนินการต่อ?
        </p>
        <label class="form-label fw-semibold small">เหตุผล (ถ้ามี)</label>
        <input type="text" id="void-reason" class="form-control" placeholder="เช่น ลูกค้าเปลี่ยนใจ / กรอกผิด">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger btn-sm" id="void-confirm-btn">
          <i class="bi bi-x-octagon me-1"></i>ยืนยันยกเลิกบิล
        </button>
      </div>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/bootstrap.bundle.min.js') ?>"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function showVoidModal() {
  new bootstrap.Modal(document.getElementById('voidModal')).show();
}

document.getElementById('void-confirm-btn')?.addEventListener('click', function () {
  const reason = document.getElementById('void-reason').value.trim();
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังดำเนินการ...';

  fetch('<?= base_url('sales/void/' . $sale['id']) ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: 'reason=' + encodeURIComponent(reason) + '&csrf_token=' + CSRF_TOKEN,
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert(data.message);
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-x-octagon me-1"></i>ยืนยันยกเลิกบิล';
      }
    });
});

// â”€â”€ Auto-print after checkout â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
(function() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('autoprint') === '1') {
    const ptype = params.get('ptype') || 'slip80';
    setTimeout(() => {
      if (ptype === 'slip80') {
        printCompactReceipt();
      } else {
        window.print();
      }
    }, 600);
  }
})();

// â”€â”€ ใบกำกับภาษีเต็มรูปแบบ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function printTaxInvoice() {
  <?php
    $vatRate    = 7;
    $totalAmt   = (float) $sale['total_amount'];
    $vatAmt     = round($totalAmt * $vatRate / (100 + $vatRate), 2);
    $beforeVat  = $totalAmt - $vatAmt;
  ?>
  const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const fmt = n => parseFloat(n||0).toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2});

  let rows = '';
  <?php foreach ($items as $idx => $item): ?>
  rows += `<tr>
    <td class="tc"><?= $idx + 1 ?></td>
    <td><?= esc($item['product_name']) ?><?php if ($item['barcode']): ?><br><small style="color:#888"><?= esc($item['barcode']) ?></small><?php endif; ?></td>
    <td class="tr"><?= $item['quantity'] ?></td>
    <td class="tr"><?= number_format($item['price'], 2) ?></td>
    <td class="tr"><?= number_format($item['subtotal'], 2) ?></td>
  </tr>`;
  <?php endforeach; ?>

  const headHtml = `<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>ใบกำกับภาษี <?= esc($sale['bill_number']) ?></title>
<style>
  *  { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Sarabun',Arial,sans-serif; font-size:9pt; color:#000; }
  .page { width:190mm; margin:0 auto; padding:5mm 0; }

  /* 1 copy = ครึ่งบนหรือครึ่งล่างของ A4 (ขนาดเท่า A5) */
  .copy { height:136mm; overflow:hidden; }
  .copy-tag { text-align:right; font-size:7.5pt; color:#555; margin-bottom:1mm; }
  .cutline { border-top:1.5px dashed #999; margin:3mm 0; text-align:center; }
  .cutline span { position:relative; top:-2.6mm; background:#fff; padding:0 3mm; font-size:7pt; color:#999; }

  /* Header */
  .hdr { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4mm; }
  .shop-name { font-size:13pt; font-weight:900; }
  .shop-info { font-size:7.5pt; color:#444; margin-top:1mm; }
  .doc-title { text-align:right; }
  .doc-title h1 { font-size:12pt; font-weight:900; border-bottom:2px solid #000; padding-bottom:1.5mm; }
  .doc-title .meta { font-size:7.5pt; color:#444; margin-top:1.5mm; line-height:1.6; }

  /* Parties */
  .parties { display:flex; gap:3mm; margin-bottom:3.5mm; }
  .party-box { flex:1; border:1px solid #ccc; border-radius:1.5mm; padding:2mm 3mm; }
  .party-box h3 { font-size:7.5pt; color:#888; margin-bottom:1mm; }
  .party-box p  { font-size:8.5pt; font-weight:600; }
  .party-box small { font-size:7pt; color:#555; }

  /* Items table — ตีเส้นตารางทุกช่องให้อ่านง่าย */
  table { width:100%; border-collapse:collapse; margin-bottom:3mm; }
  thead th { background:#1e293b; color:#fff; padding:1.5mm 2mm; font-size:7.5pt; text-align:left; }
  thead th.tr { text-align:right; }
  thead th.tc { text-align:center; }
  tbody td { padding:1.2mm 2mm; font-size:8pt; }
  td.tr { text-align:right; }
  td.tc { text-align:center; }
  .items { border:0.4mm solid #000; }
  .items thead th { border:0.25mm solid #000; }
  .items tbody td { border:0.2mm solid #666; padding:1.5mm 2mm; }
  .items tbody tr:nth-child(even) td { background:#f4f6f8; }

  /* Summary */
  .summary { margin-left:auto; width:70mm; }
  .summary table { margin:0; }
  .summary td { padding:1mm 2mm; font-size:8.5pt; border:none; }
  .summary .label { color:#555; }
  .summary .val   { text-align:right; font-weight:600; }
  .summary .total-row td { font-size:11pt; font-weight:900; border-top:2px solid #000; padding-top:2mm; }

  /* Footer */
  .footer { margin-top:6mm; display:flex; justify-content:space-between; gap:6mm; }
  .sign-box { flex:1; border-top:1px dashed #999; text-align:center; padding-top:1.5mm; font-size:7.5pt; color:#777; }

  @media print {
    @page { size:A4 portrait; margin:5mm 10mm; }
    body  { margin:0; }
    .page { width:190mm; padding:0; }
  }
</style>
</head><body>`;

  // เนื้อหาใบกำกับ 1 ชุด — ใช้ซ้ำทั้งต้นฉบับ (ลูกค้า) และสำเนา (ร้าน)
  const copyBody = `
  <!-- Header -->
  <div class="hdr">
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
      <h1>ใบกำกับภาษี</h1>
      <div class="meta">
        เลขที่: <strong><?= esc($sale['bill_number']) ?></strong><br>
        วันที่: <?= date('d/m/Y', strtotime($sale['created_at'])) ?><br>
        เวลา:  <?= date('H:i:s', strtotime($sale['created_at'])) ?><br>
        พนักงาน: <?= esc($sale['cashier']) ?>
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
      <?php if ($shopTaxId): ?><small>เลขภาษี: <?= esc($shopTaxId) ?></small><?php endif; ?>
    </div>
    <div class="party-box">
      <h3>ผู้ซื้อ (Buyer)</h3>
      <?php if (!empty($sale['member_name'])): ?>
      <p><?= esc($sale['member_name']) ?></p>
      <small>รหัสสมาชิก: <?= esc($sale['member_id'] ?? '-') ?></small>
      <?php else: ?>
      <p style="color:#aaa">ลูกค้าทั่วไป</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items -->
  <table class="items">
    <thead>
      <tr>
        <th class="tc" style="width:7mm">#</th>
        <th>รายการสินค้า</th>
        <th class="tr" style="width:12mm">จำนวน</th>
        <th class="tr" style="width:21mm">ราคา/หน่วย (฿)</th>
        <th class="tr" style="width:21mm">จำนวนเงิน (฿)</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
  </table>

  <!-- Summary -->
  <div class="summary">
    <table>
      <tr>
        <td class="label">มูลค่าสินค้าก่อน VAT</td>
        <td class="val">฿<?= number_format($beforeVat, 2) ?></td>
      </tr>
      <?php if ((float)($sale['discount_amount'] ?? 0) > 0): ?>
      <tr>
        <td class="label">ส่วนลด</td>
        <td class="val" style="color:#16a34a">-฿<?= number_format($sale['discount_amount'], 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="label">ภาษีมูลค่าเพิ่ม <?= $vatRate ?>% (รวมใน)</td>
        <td class="val">฿<?= number_format($vatAmt, 2) ?></td>
      </tr>
      <tr class="total-row">
        <td>ยอดรวมทั้งสิ้น</td>
        <td>฿<?= number_format($sale['total_amount'], 2) ?></td>
      </tr>
    </table>
  </div>

  <!-- Footer -->
  <div class="footer" style="margin-top:8mm">
    <div class="sign-box">ลายมือชื่อผู้รับเงิน<br><br>............................</div>
    <div class="sign-box">ลายมือชื่อผู้ซื้อ / ตรวจรับ<br><br>............................</div>
    <div class="sign-box">ประทับตราบริษัท<br><br>&nbsp;</div>
  </div>

  <div style="text-align:center;font-size:8pt;color:#aaa;margin-top:6mm">
    เอกสารนี้ออกโดยระบบ POS â€” <?= esc($shopName) ?> â€” <?= date('d/m/Y H:i:s') ?>
  </div>

`;

  const html = headHtml + `
<div class="page">
  <div class="copy"><div class="copy-tag">[ ต้นฉบับ — สำหรับลูกค้า ]</div>` + copyBody + `</div>
  <div class="cutline"><span>ตัดตามแนวเส้นประ</span></div>
  <div class="copy"><div class="copy-tag">[ สำเนา — สำหรับร้านค้า ]</div>` + copyBody + `</div>
</div>
<script>setTimeout(() => window.print(), 500);<\/script>
</body></html>`;

  const win = window.open('', '_blank', 'width=780,height=1000');
  win.document.write(html);
  win.document.close();
}

// ── ใบส่งของ A5 แยก 2 แผ่น (แผ่น 1 = ร้านเก็บ, แผ่น 2 = ให้ลูกค้า) ─────────────
function printDeliveryNote() {
  <?php
    $dnPm        = $sale['payment_method'] ?? 'cash';
    $dnPmLabels  = ['cash' => 'เงินสด', 'qr' => 'QR/พร้อมเพย์', 'transfer' => 'โอนเงิน', 'credit' => 'เงินเชื่อ (ค้างชำระ)'];
    $dnOwed      = max(0, (float) $sale['total_amount'] - (float) $sale['paid_amount']);
    $dnUnsettled = $dnPm === 'credit' && empty($sale['credit_settled_at']);
    $dnDiscount  = (float) ($sale['discount_amount'] ?? 0) + (float) ($sale['points_discount'] ?? 0);
    $dnSubtotal  = (float) $sale['total_amount'] + $dnDiscount;
    $dnQtyTotal  = 0;
    foreach ($items as $it) { $dnQtyTotal += (int) $it['quantity']; }
  ?>
  let dnRows = '';
  <?php foreach ($items as $idx => $item): ?>
  dnRows += `<tr>
    <td class="tc"><?= $idx + 1 ?></td>
    <td><?= esc($item['product_name']) ?><?php if ($item['barcode']): ?><br><small style="color:#888"><?= esc($item['barcode']) ?></small><?php endif; ?></td>
    <td class="tr"><?= $item['quantity'] ?></td>
    <td class="tr"><?= number_format($item['price'], 2) ?></td>
    <td class="tr"><?= number_format($item['subtotal'], 2) ?></td>
  </tr>`;
  <?php endforeach; ?>

  const dnHead = `<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>ใบส่งของ <?= esc($sale['bill_number']) ?></title>
<style>
  *  { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Sarabun',Arial,sans-serif; font-size:9pt; color:#000; }
  .sheet { width:130mm; margin:0 auto; padding:5mm 0; }

  .copy-tag { text-align:right; font-size:7.5pt; color:#555; margin-bottom:1mm; }

  .hdr { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4mm; }
  .shop-name { font-size:13pt; font-weight:900; }
  .shop-info { font-size:7.5pt; color:#444; margin-top:1mm; }
  .doc-title { text-align:right; }
  .doc-title h1 { font-size:13pt; font-weight:900; border-bottom:2px solid #000; padding-bottom:1.5mm; }
  .doc-title .meta { font-size:7.5pt; color:#444; margin-top:1.5mm; line-height:1.6; }

  .recv-box { border:1px solid #ccc; border-radius:1.5mm; padding:2mm 3mm; margin-bottom:3.5mm; }
  .recv-box h3 { font-size:7.5pt; color:#888; margin-bottom:1mm; }
  .recv-box p  { font-size:9pt; font-weight:600; }

  /* ตารางรายการ — ตีเส้นทุกช่องให้อ่านง่าย */
  table { width:100%; border-collapse:collapse; margin-bottom:3mm; }
  thead th { background:#1e293b; color:#fff; padding:1.5mm 2mm; font-size:7.5pt; text-align:left; }
  thead th.tr { text-align:right; }
  thead th.tc { text-align:center; }
  tbody td { padding:1.2mm 2mm; font-size:8pt; }
  td.tr { text-align:right; }
  td.tc { text-align:center; }
  .items { border:0.4mm solid #000; }
  .items thead th { border:0.25mm solid #000; }
  .items tbody td { border:0.2mm solid #666; padding:1.5mm 2mm; }
  .items tbody tr:nth-child(even) td { background:#f4f6f8; }

  .summary { margin-left:auto; width:64mm; }
  .summary table { margin:0; }
  .summary td { padding:1mm 2mm; font-size:8.5pt; border:none; }
  .summary .label { color:#555; }
  .summary .val   { text-align:right; font-weight:600; }
  .summary .total-row td { font-size:11pt; font-weight:900; border-top:2px solid #000; padding-top:2mm; }
  .owe { color:#b91c1c; }

  .pay-line { font-size:8pt; color:#444; margin:2mm 0 4mm; }

  .footer { margin-top:8mm; display:flex; justify-content:space-between; gap:8mm; }
  .sign-box { flex:1; text-align:center; font-size:7.5pt; color:#777; }
  .sign-box .line { border-top:1px dashed #999; margin-top:12mm; padding-top:1.5mm; }

  @media print {
    @page { size:A5 portrait; margin:8mm 9mm; }
    body  { margin:0; }
    .sheet { width:130mm; padding:0; }
    .sheet.brk { page-break-after:always; }
  }
  @media screen {
    body { background:#e5e7eb; padding:10px 0; }
    .sheet { background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.25); padding:8mm 9mm; margin-bottom:10px; }
  }
</style>
</head><body>`;

  // เนื้อหาใบส่งของ 1 แผ่น — ใช้ซ้ำทั้ง 2 แผ่น ต่างกันแค่ป้ายมุมขวาบน
  const dnBody = `
  <div class="hdr">
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
      <h1>ใบส่งของ</h1>
      <div class="meta">
        เลขที่: <strong><?= esc($sale['bill_number']) ?></strong><br>
        วันที่: <?= date('d/m/Y', strtotime($sale['created_at'])) ?><br>
        ผู้ส่ง: <?= esc($sale['cashier']) ?>
      </div>
    </div>
  </div>

  <div class="recv-box">
    <h3>ผู้รับสินค้า (Deliver To)</h3>
    <?php if (!empty($sale['member_name'])): ?>
    <p><?= esc($sale['member_name']) ?> <small style="font-weight:400;color:#888">(รหัสสมาชิก: <?= esc($sale['member_id'] ?? '-') ?>)</small></p>
    <?php else: ?>
    <p style="color:#aaa">ลูกค้าทั่วไป</p>
    <?php endif; ?>
  </div>

  <table class="items">
    <thead>
      <tr>
        <th class="tc" style="width:7mm">#</th>
        <th>รายการสินค้า</th>
        <th class="tr" style="width:12mm">จำนวน</th>
        <th class="tr" style="width:21mm">ราคา/หน่วย (฿)</th>
        <th class="tr" style="width:21mm">จำนวนเงิน (฿)</th>
      </tr>
    </thead>
    <tbody>${dnRows}</tbody>
  </table>

  <div class="summary">
    <table>
      <tr>
        <td class="label">รวม <?= $dnQtyTotal ?> ชิ้น</td>
        <td class="val">฿<?= number_format($dnSubtotal, 2) ?></td>
      </tr>
      <?php if ($dnDiscount > 0): ?>
      <tr>
        <td class="label">ส่วนลด</td>
        <td class="val" style="color:#16a34a">-฿<?= number_format($dnDiscount, 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr class="total-row">
        <td>ยอดสุทธิ</td>
        <td>฿<?= number_format($sale['total_amount'], 2) ?></td>
      </tr>
      <?php if ($dnUnsettled): ?>
      <tr>
        <td class="label owe">*** ค้างชำระ ***</td>
        <td class="val owe">฿<?= number_format($dnOwed, 2) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="pay-line">การชำระเงิน: <strong><?= esc($dnPmLabels[$dnPm] ?? $dnPm) ?></strong></div>

  <div class="footer">
    <div class="sign-box"><div class="line">ผู้ส่งสินค้า<br>วันที่ ........./........./.........</div></div>
    <div class="sign-box"><div class="line">ผู้รับสินค้า (ตรวจรับของครบถ้วน)<br>วันที่ ........./........./.........</div></div>
  </div>`;

  const dnHtml = dnHead + `
<div class="sheet brk">
  <div class="copy-tag">[ แผ่นที่ 1 — สำหรับร้านค้า ]</div>` + dnBody + `</div>
<div class="sheet">
  <div class="copy-tag">[ แผ่นที่ 2 — สำหรับลูกค้า ]</div>` + dnBody + `</div>
<script>setTimeout(() => window.print(), 500);<\/script>
</body></html>`;

  const win = window.open('', '_blank', 'width=620,height=880');
  win.document.write(dnHtml);
  win.document.close();
}


function printCompactReceipt() {
  <?php
    $slip80DiscAmt   = (float)($sale['discount_amount'] ?? 0);
    $slip80DiscPct   = (float)($sale['discount_pct']    ?? 0);
    $slip80PtsUsed   = (int)  ($sale['points_used']     ?? 0);
    $slip80PtsDisc   = (float)($sale['points_discount'] ?? 0);
    $slip80HasReduc  = $slip80DiscAmt > 0 || $slip80PtsDisc > 0;
    $slip80SubBefore = $sale['total_amount'] + $slip80DiscAmt + $slip80PtsDisc;
    $slip80EarnedPts = $rcCreditUnsettled ? 0 : (int) floor($sale['total_amount'] / $shopPointsRate);
  ?>
  let itemRows80 = '';
  <?php foreach ($items as $item): ?>
  itemRows80 += `<tr>
    <td class="name"><?= esc($item['product_name']) ?><?php if ($item['barcode']): ?><br><span class="dim"><?= esc($item['barcode']) ?></span><?php endif; ?></td>
    <td class="num"><?= $item['quantity'] ?></td>
    <td class="num"><?= number_format($item['price'], 2) ?></td>
    <td class="num"><?= number_format($item['subtotal'], 2) ?></td>
  </tr>`;
  <?php endforeach; ?>

  const html80 = `<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>ใบเสร็จ <?= esc($sale['bill_number']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  /* size:80mm auto = กว้าง 80mm ความสูงต่อเนื่อง (กันสลิปแตกเป็น 2 หน้า) */
  @page { size:80mm auto; margin:0; }
  body { font-family:'Sarabun',Arial,sans-serif; font-size:<?= $fsBase ?>px; color:#000; width:72mm; margin:0 auto; padding:2mm 0; font-weight:500; }
  .center { text-align:center; }
  .right  { text-align:right; }
  .sep    { border-top:1px dashed #000; margin:2mm 0; }
  .shopname { font-size:<?= $fsShopname ?>px; font-weight:900; text-align:center; }
  .meta { font-size:<?= $fsMeta ?>px; text-align:center; color:#000; }
  .dim  { font-size:<?= max(8, $fsItems - 2) ?>px; color:#333; }
  /* ตีเส้นเฉพาะช่องตัวเลข (จำนวน/ราคา/รวม) — ตัวเลขอ่านง่ายแต่ไม่รกเหมือนตีกรอบทุกช่อง */
  table.items { width:100%; border-collapse:collapse; font-size:<?= $fsItems ?>px; }
  table.items th { font-size:<?= max(8,$fsItems-2) ?>px; padding:1mm 1mm; text-align:left; font-weight:700;
                   border-bottom:1px solid #000; }
  table.items th.num, table.items td.num { text-align:right; white-space:nowrap; border-left:1px solid #000; }
  table.items td { padding:1mm 1mm; vertical-align:top; }
  table.items tbody tr:last-child td { border-bottom:1px solid #000; }
  table.items td.name { max-width:38mm; word-break:break-word; }
  .sum { width:100%; font-size:<?= $fsItems ?>px; }
  .sum td { padding:.5mm 1mm; }
  .sum .r { text-align:right; }
  .total-lbl { font-size:<?= $fsTotal ?>px; font-weight:900; }
  .total-val { font-size:<?= $fsTotal ?>px; font-weight:900; text-align:right; }
  .footer-txt { font-size:<?= $fsFooter ?>px; text-align:center; color:#333; margin-top:2mm; }
  /* บังคับให้ทุกตัวอักษรเป็นสีดำสนิทตอนพิมพ์ — เครื่องพิมพ์ความร้อนเป็นขาวดำ ทำให้คมเข้มขึ้น */
  @media print { html, body { margin:0; } body * { color:#000 !important; } }
</style>
</head><body>
<div class="shopname"><?= esc($shopName) ?></div>
<?php if ($shopAddress): ?>
<div class="meta" style="white-space:pre-line"><?= esc($shopAddress) ?></div>
<?php endif; ?>
<?php if ($shopTaxId): ?>
<div class="meta">เลขภาษี: <?= esc($shopTaxId) ?></div>
<?php endif; ?>
<div class="sep"></div>
<div class="meta">เลขบิล: <strong><?= esc($sale['bill_number']) ?></strong></div>
<div class="meta"><?= date('d/m/Y H:i:s', strtotime($sale['created_at'])) ?></div>
<div class="meta">พนักงาน: <?= esc($sale['cashier']) ?></div>
<?php if (!empty($sale['member_name'])): ?>
<div class="meta">สมาชิก: <?= esc($sale['member_name']) ?></div>
<?php endif; ?>
<div class="sep"></div>
<table class="items">
  <thead><tr>
    <th>สินค้า</th>
    <th class="num">จำนวน</th>
    <th class="num">ราคา</th>
    <th class="num">รวม</th>
  </tr></thead>
  <tbody>${itemRows80}</tbody>
</table>
<div class="sep"></div>
<table class="sum">
  <?php if ($slip80HasReduc): ?>
  <tr><td>ยอดก่อนลด</td><td class="r">฿<?= number_format($slip80SubBefore, 2) ?></td></tr>
  <?php if ($slip80DiscAmt > 0): ?>
  <tr><td>ส่วนลด<?= $slip80DiscPct > 0 ? " {$slip80DiscPct}%" : '' ?></td><td class="r" style="color:#16a34a">-฿<?= number_format($slip80DiscAmt, 2) ?></td></tr>
  <?php endif; ?>
  <?php if ($slip80PtsDisc > 0): ?>
  <tr><td>ใช้แต้ม <?= $slip80PtsUsed ?> แต้ม</td><td class="r" style="color:#d97706">-฿<?= number_format($slip80PtsDisc, 2) ?></td></tr>
  <?php endif; ?>
  <?php endif; ?>
  <tr>
    <td class="total-lbl"><?= $slip80HasReduc ? 'ยอดสุทธิ' : 'ยอดรวม' ?></td>
    <td class="total-val">฿<?= number_format($sale['total_amount'], 2) ?></td>
  </tr>
  <?php if ($isCredit): ?>
  <tr><td style="color:#555">ชำระแล้ว<?= (float) $sale['paid_amount'] > 0 ? ' (รวมมัดจำ)' : '' ?></td><td class="r" style="color:#555">฿<?= number_format($sale['paid_amount'], 2) ?></td></tr>
  <?php if ($creditPaidUp): ?>
  <tr><td><strong>ชำระครบแล้ว</strong></td><td class="r" style="color:#16a34a"><strong>฿0.00</strong></td></tr>
  <?php else: ?>
  <tr><td><strong>*** ค้างชำระ ***</strong></td><td class="r"><strong>฿<?= number_format($creditOwed, 2) ?></strong></td></tr>
  <?php endif; ?>
  <?php else: ?>
  <tr><td style="color:#555">รับเงิน</td><td class="r" style="color:#555">฿<?= number_format($sale['paid_amount'], 2) ?></td></tr>
  <tr><td><strong>เงินทอน</strong></td><td class="r" style="color:#16a34a"><strong>฿<?= number_format($sale['change_amount'], 2) ?></strong></td></tr>
  <?php endif; ?>
  <?php if (!empty($sale['member_name']) && $slip80EarnedPts > 0): ?>
  <tr><td style="color:#d97706">แต้มที่ได้รับ</td><td class="r" style="color:#d97706">+<?= $slip80EarnedPts ?> แต้ม</td></tr>
  <?php endif; ?>
</table>
<div class="sep"></div>
<div class="footer-txt">ขอบคุณที่ใช้บริการ</div>
<div class="footer-txt">กรุณาเก็บใบเสร็จไว้เป็นหลักฐาน</div>
<script>
  // พิมพ์ทันทีที่โหลดเสร็จ แล้วปิดหน้าต่างเอง — เร็วและไม่ค้าง
  window.onload = function () { window.focus(); window.print(); };
  window.onafterprint = function () { window.close(); };
  // กันบางเบราว์เซอร์ที่ onafterprint ไม่ยิง
  setTimeout(function () { try { window.close(); } catch (e) {} }, 8000);
<\/script>
</body></html>`;

  const win80 = window.open('', '_blank', 'width=340,height=600');
  if (!win80) {
    alert('เบราว์เซอร์บล็อกหน้าต่างพิมพ์ — กรุณาอนุญาต pop-up สำหรับเว็บนี้ แล้วลองอีกครั้ง');
    return;
  }
  win80.document.write(html80);
  win80.document.close();
}
</script>
<!-- ── พิมพ์ผ่าน Agent: สลิปแบบซ่อนสำหรับแปลงเป็นภาพ ESC/POS ─────────────── -->
<div id="slip-agent" style="position:absolute; left:-9999px; top:0; width:288px; background:#fff; color:#000; font-family:'Sarabun',Arial,sans-serif; font-size:<?= $fsBase ?>px; padding:4px">
  <div style="font-size:<?= $fsShopname ?>px; font-weight:900; text-align:center"><?= esc($shopName) ?></div>
  <?php if ($shopAddress): ?>
  <div style="font-size:<?= $fsMeta ?>px; text-align:center; white-space:pre-line"><?= esc($shopAddress) ?></div>
  <?php endif; ?>
  <?php if ($shopTaxId): ?>
  <div style="font-size:<?= $fsMeta ?>px; text-align:center">เลขภาษี: <?= esc($shopTaxId) ?></div>
  <?php endif; ?>
  <div style="border-top:1px dashed #000; margin:4px 0"></div>
  <div style="font-size:<?= $fsMeta ?>px; text-align:center">เลขบิล: <strong><?= esc($sale['bill_number']) ?></strong></div>
  <div style="font-size:<?= $fsMeta ?>px; text-align:center"><?= date('d/m/Y H:i:s', strtotime($sale['created_at'])) ?></div>
  <div style="font-size:<?= $fsMeta ?>px; text-align:center">พนักงาน: <?= esc($sale['cashier']) ?></div>
  <?php if (!empty($sale['member_name'])): ?>
  <div style="font-size:<?= $fsMeta ?>px; text-align:center">สมาชิก: <?= esc($sale['member_name']) ?></div>
  <?php endif; ?>
  <div style="border-top:1px dashed #000; margin:4px 0"></div>
  <?php
    // ตีเส้นเฉพาะช่องตัวเลข (จำนวน/ราคา/รวม) ให้ตรงกับสลิปฝั่งพิมพ์ผ่านเบราว์เซอร์
    // ที่นี่ใช้ inline style ล้วน (html2canvas แปลงเป็นภาพ) จึงใช้ :last-child ไม่ได้ ต้องเช็คแถวสุดท้ายเอง
    $agLastKey = array_key_last($items);
    $agNumCell = 'text-align:right; padding:2px 1px; border-left:1px solid #000;';
  ?>
  <table style="width:100%; border-collapse:collapse; font-size:<?= $fsItems ?>px">
    <thead><tr>
      <th style="text-align:left; padding:2px 1px; border-bottom:1px solid #000">สินค้า</th>
      <th style="<?= $agNumCell ?> border-bottom:1px solid #000">จำนวน</th>
      <th style="<?= $agNumCell ?> border-bottom:1px solid #000">ราคา</th>
      <th style="<?= $agNumCell ?> border-bottom:1px solid #000">รวม</th>
    </tr></thead>
    <tbody>
    <?php foreach ($items as $agKey => $item): ?>
      <?php $agBottom = $agKey === $agLastKey ? ' border-bottom:1px solid #000;' : ''; ?>
      <tr>
        <td style="padding:2px 1px; word-break:break-word;<?= $agBottom ?>"><?= esc($item['product_name']) ?></td>
        <td style="<?= $agNumCell . $agBottom ?>"><?= $item['quantity'] ?></td>
        <td style="<?= $agNumCell . $agBottom ?>"><?= number_format($item['price'], 2) ?></td>
        <td style="<?= $agNumCell . $agBottom ?>"><?= number_format($item['subtotal'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div style="border-top:1px dashed #000; margin:4px 0"></div>
  <table style="width:100%; font-size:<?= $fsItems ?>px">
    <?php if ($slip80HasReduc): ?>
    <tr><td>ยอดก่อนลด</td><td style="text-align:right">฿<?= number_format($slip80SubBefore, 2) ?></td></tr>
    <?php if ($slip80DiscAmt > 0): ?>
    <tr><td>ส่วนลด<?= $slip80DiscPct > 0 ? " {$slip80DiscPct}%" : '' ?></td><td style="text-align:right">-฿<?= number_format($slip80DiscAmt, 2) ?></td></tr>
    <?php endif; ?>
    <?php if ($slip80PtsDisc > 0): ?>
    <tr><td>ใช้แต้ม <?= $slip80PtsUsed ?> แต้ม</td><td style="text-align:right">-฿<?= number_format($slip80PtsDisc, 2) ?></td></tr>
    <?php endif; ?>
    <?php endif; ?>
    <tr>
      <td style="font-size:<?= $fsTotal ?>px; font-weight:900"><?= $slip80HasReduc ? 'ยอดสุทธิ' : 'ยอดรวม' ?></td>
      <td style="font-size:<?= $fsTotal ?>px; font-weight:900; text-align:right">฿<?= number_format($sale['total_amount'], 2) ?></td>
    </tr>
    <?php if ($isCredit): ?>
    <tr><td>ชำระแล้ว<?= (float) $sale['paid_amount'] > 0 ? ' (รวมมัดจำ)' : '' ?></td><td style="text-align:right">฿<?= number_format($sale['paid_amount'], 2) ?></td></tr>
    <?php if ($creditPaidUp): ?>
    <tr><td style="font-weight:900">ชำระครบแล้ว</td><td style="text-align:right; font-weight:900">฿0.00</td></tr>
    <?php else: ?>
    <tr><td style="font-weight:900">*** ค้างชำระ ***</td><td style="text-align:right; font-weight:900">฿<?= number_format($creditOwed, 2) ?></td></tr>
    <?php endif; ?>
    <?php else: ?>
    <tr><td>รับเงิน</td><td style="text-align:right">฿<?= number_format($sale['paid_amount'], 2) ?></td></tr>
    <tr><td style="font-weight:700">เงินทอน</td><td style="text-align:right; font-weight:700">฿<?= number_format($sale['change_amount'], 2) ?></td></tr>
    <?php endif; ?>
    <?php if (!empty($sale['member_name']) && $slip80EarnedPts > 0): ?>
    <tr><td>แต้มที่ได้รับ</td><td style="text-align:right">+<?= $slip80EarnedPts ?> แต้ม</td></tr>
    <?php endif; ?>
  </table>
  <div style="border-top:1px dashed #000; margin:4px 0"></div>
  <div style="font-size:<?= $fsFooter ?>px; text-align:center">ขอบคุณที่ใช้บริการ</div>
  <div style="font-size:<?= $fsFooter ?>px; text-align:center">กรุณาเก็บใบเสร็จไว้เป็นหลักฐาน</div>
</div>

<script src="<?= base_url('assets/html2canvas.min.js') ?>"></script>
<script>
// ── พิมพ์สลิปตรงผ่าน Local Agent (ESC/POS raster) ──────────────────────────────
const PRINT_MODE      = '<?= esc($printMode ?? 'browser', 'js') ?>';
const RECEIPT_PRINTER = '<?= esc($receiptPrinter ?? '', 'js') ?>';
const AGENT_PORT      = '<?= esc($agentPort ?? '9991', 'js') ?>';
const AGENT_TOKEN     = '<?= esc($agentToken ?? '', 'js') ?>';
const OPEN_DRAWER     = <?= !empty($agentOpenDrawer) ? 'true' : 'false' ?>; // เด้งลิ้นชักพร้อมพิมพ์สลิป

function canvasToEscpos(canvas) {
  const ctx = canvas.getContext('2d');
  const w = canvas.width, h = canvas.height;
  const img = ctx.getImageData(0, 0, w, h).data;
  const bpr = Math.ceil(w / 8); // bytes per row
  const out = [0x1B, 0x40]; // ESC @ init

  // แบ่งภาพเป็นท่อนละ 256 แถว (GS v 0 ต่อท่อน) กันบางรุ่น buffer เต็ม
  for (let y0 = 0; y0 < h; y0 += 256) {
    const rows = Math.min(256, h - y0);
    out.push(0x1D, 0x76, 0x30, 0x00, bpr & 0xFF, (bpr >> 8) & 0xFF, rows & 0xFF, (rows >> 8) & 0xFF);
    for (let y = y0; y < y0 + rows; y++) {
      for (let bx = 0; bx < bpr; bx++) {
        let byte = 0;
        for (let bit = 0; bit < 8; bit++) {
          const x = bx * 8 + bit;
          if (x >= w) continue;
          const i = (y * w + x) * 4;
          const a = img[i + 3];
          const lum = a < 16 ? 255 : (img[i] * 0.299 + img[i+1] * 0.587 + img[i+2] * 0.114);
          if (lum < 160) byte |= (0x80 >> bit);
        }
        out.push(byte);
      }
    }
  }
  out.push(0x1B, 0x64, 0x04);             // feed 4 lines
  out.push(0x1D, 0x56, 0x42, 0x00);       // partial cut
  return new Uint8Array(out);
}

function u8ToBase64(u8) {
  let s = '';
  const CHUNK = 0x8000;
  for (let i = 0; i < u8.length; i += CHUNK) {
    s += String.fromCharCode.apply(null, u8.subarray(i, i + CHUNK));
  }
  return btoa(s);
}

// ── หน้าต่างพิมพ์ของโปรแกรม: พรีวิวบิล + เลือกเครื่องพิมพ์ + จำนวนชุด ──────────
// ไม่ส่งงานพิมพ์โดยไม่ระบุเครื่องเด็ดขาด (กันไปตกเครื่องผิดแล้วพิมพ์เป็นตัวอักษรต่างดาว)
const LS_PRINTER_KEY = 'pos_receipt_printer';
function resolvedPrinter() {
  return localStorage.getItem(LS_PRINTER_KEY) || RECEIPT_PRINTER || '';
}

function slipToast(msg, ok) {
  const toast = document.createElement('div');
  toast.className = 'alert alert-' + (ok ? 'success' : 'danger') + ' position-fixed top-0 end-0 m-3 shadow no-print';
  toast.style.zIndex = 99999;
  toast.innerHTML = '<i class="bi bi-printer-fill me-2"></i>' + msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

async function sendSlipToPrinter(printerName, copies) {
  // เด้งลิ้นชัก 2 ทางพร้อมกัน (ทางไหนถึงก่อนลิ้นชักก็เปิด — สั่งซ้ำตอนลิ้นชักเปิดอยู่ไม่มีผลอะไร):
  // 1) คำสั่งแยก /open-drawer (เส้นทางเดียวกับปุ่ม "เปิดลิ้นชัก") — ใช้ได้แม้ agent เวอร์ชันเก่า/ตัว .exe
  // 2) ฝาก openDrawer ไปกับงานพิมพ์ใบแรก — สำรองกรณี /open-drawer ล้ม (เช่น config ใน agent ชี้ผิดเครื่อง)
  if (OPEN_DRAWER) {
    try {
      await fetch(`http://127.0.0.1:${AGENT_PORT}/open-drawer`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Agent-Token': AGENT_TOKEN },
        body: JSON.stringify({ triggeredBy: 'receipt', reason: 'pop with slip print' }),
        signal: AbortSignal.timeout(10000),
      });
    } catch (e) { /* เปิดลิ้นชักไม่ได้ ไม่ควร block การพิมพ์สลิป — ยังมี kick ในงานพิมพ์เป็นสำรอง */ }
  }
  const el = document.getElementById('slip-agent');
  const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#ffffff', logging: false });
  const b64 = u8ToBase64(canvasToEscpos(canvas));
  for (let i = 0; i < (copies || 1); i++) {
    const r = await fetch(`http://127.0.0.1:${AGENT_PORT}/print`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Agent-Token': AGENT_TOKEN },
      body: JSON.stringify({ printerName, dataBase64: b64, openDrawer: OPEN_DRAWER && i === 0 }),
      signal: AbortSignal.timeout(15000),
    });
    const d = await r.json();
    if (!d.ok) throw new Error(d.error || 'print failed');
  }
}

// สร้าง modal หน้าต่างพิมพ์ (ใช้ Bootstrap ที่โหลดอยู่แล้ว)
(function buildPrintDialog() {
  const div = document.createElement('div');
  div.innerHTML = `
<div class="modal fade no-print" id="printDialog" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white py-2">
        <h6 class="modal-title"><i class="bi bi-printer-fill me-2"></i>พิมพ์ใบเสร็จ</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-7">
            <div class="border rounded bg-light p-2" style="max-height:320px; overflow:auto">
              <div id="pd-preview" style="transform:scale(.82); transform-origin:top left"></div>
            </div>
            <div class="text-muted small mt-1">
              <i class="bi bi-info-circle me-1"></i>ปรับฟอนต์/สีบิลได้ที่
              <a href="<?= site_url('/settings') ?>" target="_blank">หน้าตั้งค่าระบบ</a>
            </div>
          </div>
          <div class="col-5">
            <label class="form-label fw-semibold small mb-1">เครื่องพิมพ์</label>
            <select id="pd-printer" class="form-select form-select-sm mb-2"></select>
            <label class="form-label fw-semibold small mb-1">จำนวนชุด</label>
            <select id="pd-copies" class="form-select form-select-sm mb-2">
              <option value="1">1 ชุด</option><option value="2">2 ชุด</option><option value="3">3 ชุด</option>
            </select>
            <div id="pd-msg" class="small text-danger"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="pd-browser">
          <i class="bi bi-window me-1"></i>พิมพ์ผ่านเบราว์เซอร์แทน
        </button>
        <button type="button" class="btn btn-dark btn-sm px-4" id="pd-print">
          <i class="bi bi-printer-fill me-1"></i>พิมพ์
        </button>
      </div>
    </div>
  </div>
</div>`;
  document.body.appendChild(div.firstElementChild);
})();

let pdModal = null;
async function openPrintDialog(errMsg) {
  // พรีวิว: clone สลิปตัวจริงที่จะถูกแปลงเป็นภาพ
  const prev = document.getElementById('pd-preview');
  prev.innerHTML = '';
  const clone = document.getElementById('slip-agent').cloneNode(true);
  clone.removeAttribute('id');
  clone.style.position = 'static';
  clone.style.left = 'auto';
  prev.appendChild(clone);
  document.getElementById('pd-msg').textContent = errMsg || '';

  // โหลดรายชื่อเครื่องพิมพ์จาก Agent
  const sel = document.getElementById('pd-printer');
  sel.innerHTML = '<option value="">กำลังโหลด...</option>';
  pdModal = pdModal || new bootstrap.Modal(document.getElementById('printDialog'));
  pdModal.show();
  try {
    const r = await fetch(`http://127.0.0.1:${AGENT_PORT}/printers`, {
      headers: { 'X-Agent-Token': AGENT_TOKEN }, signal: AbortSignal.timeout(6000),
    });
    const d = await r.json();
    if (!d.ok) throw new Error(d.error);
    sel.innerHTML = '';
    d.printers.forEach(name => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name + (name === d.suggested ? ' ★' : '');
      sel.appendChild(opt);
    });
    const want = resolvedPrinter();
    if (want && d.printers.includes(want)) sel.value = want;
    else if (d.suggested)                  sel.value = d.suggested;
  } catch (e) {
    sel.innerHTML = '<option value="">-- เชื่อมต่อ Agent ไม่ได้ --</option>';
    document.getElementById('pd-msg').textContent =
      'เชื่อมต่อโปรแกรมพิมพ์ไม่ได้ — เปิด local-agent/start.bat ก่อน หรือกด "พิมพ์ผ่านเบราว์เซอร์แทน"';
  }
}

document.getElementById('pd-browser').addEventListener('click', () => {
  pdModal?.hide();
  _browserSlipPrint();
});

document.getElementById('pd-print').addEventListener('click', async function () {
  const printer = document.getElementById('pd-printer').value;
  const copies  = parseInt(document.getElementById('pd-copies').value, 10) || 1;
  const msg     = document.getElementById('pd-msg');
  if (!printer) { msg.textContent = 'เลือกเครื่องพิมพ์ก่อน'; return; }
  this.disabled = true;
  msg.className = 'small text-secondary';
  msg.textContent = 'กำลังส่งงานพิมพ์...';
  try {
    await sendSlipToPrinter(printer, copies);
    localStorage.setItem(LS_PRINTER_KEY, printer); // จำเครื่องนี้ไว้ใช้ครั้งหน้า
    pdModal?.hide();
    slipToast('ส่งงานพิมพ์ไปที่ ' + printer + ' แล้ว', true);
  } catch (e) {
    msg.className = 'small text-danger';
    msg.textContent = 'พิมพ์ไม่สำเร็จ: ' + e.message;
  }
  this.disabled = false;
});

// กดปุ่มพิมพ์สลิป → เปิดหน้าต่างพิมพ์ของโปรแกรม
async function printViaAgent() { openPrintDialog(); }

// autoprint หลัง checkout: ถ้ารู้เครื่องพิมพ์แล้ว → พิมพ์เงียบ ๆ เลย / ยังไม่รู้ → เปิดหน้าต่างให้เลือก
async function printViaAgentAuto() {
  const printer = resolvedPrinter();
  if (!printer) { openPrintDialog(); return; }
  try {
    await sendSlipToPrinter(printer, 1);
    slipToast('พิมพ์ใบเสร็จที่ ' + printer + ' แล้ว', true);
  } catch (e) {
    openPrintDialog('พิมพ์อัตโนมัติไม่สำเร็จ: ' + e.message);
  }
}

// ปุ่ม "สลิป 80mm" + autoprint หลัง checkout: ใช้ agent ถ้าตั้งค่าไว้
// (override ฟังก์ชัน global เพื่อให้ autoprint timer ที่ตั้งไว้แล้ววิ่งเข้า agent ด้วย)
const _browserSlipPrint = window.printCompactReceipt;
(function () {
  if (PRINT_MODE !== 'agent') return;
  let autoprintWindow = true;
  setTimeout(() => { autoprintWindow = false; }, 3000); // 3 วิแรก = งานจาก autoprint timer
  window.printCompactReceipt = () => autoprintWindow ? printViaAgentAuto() : printViaAgent();
  document.querySelectorAll('button[onclick="printCompactReceipt()"]').forEach(btn => {
    btn.innerHTML = '<i class="bi bi-printer-fill me-1"></i>พิมพ์สลิป (เครื่องปริ้น)';
    btn.classList.remove('btn-outline-dark');
    btn.classList.add('btn-dark');
  });
})();
</script>
</body>
</html>
