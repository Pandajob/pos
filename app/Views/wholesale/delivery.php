<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ใบส่งของ / ใบเสร็จ — <?= esc($sale['bill_number']) ?></title>
  <link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <style>
    body { background: #f1f5f9; font-family: 'Sarabun', sans-serif; }

    /* ── Toolbar ── */
    .toolbar { max-width: 900px; margin: 1.5rem auto .75rem; }

    /* ── A4 container ── */
    .a4-page {
      max-width: 210mm;
      margin: 0 auto 3rem;
      background: #fff;
      box-shadow: 0 4px 24px rgba(0,0,0,.1);
      border-radius: .75rem;
      overflow: hidden;
    }

    /* ── A5 half: ใบส่งของ (บน) ── */
    .half {
      padding: 8mm 10mm;
      min-height: 138mm; /* A5 height */
      position: relative;
    }
    .half-top { border-bottom: none; }
    .half-bottom { border-top: none; }

    /* cut line */
    .cut-line {
      display: flex; align-items: center; gap: 3mm;
      padding: 2mm 8mm;
      background: #f8fafc;
      border-top: 2px dashed #94a3b8;
      border-bottom: 2px dashed #94a3b8;
      color: #94a3b8; font-size: 8pt;
    }
    .cut-line::before, .cut-line::after {
      content: '✂';
    }
    .cut-line .line { flex: 1; border-top: 1px dashed #cbd5e1; }

    /* doc header inside each half */
    .doc-hdr { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4mm; }
    .shop-name { font-size: 14pt; font-weight: 900; }
    .shop-info { font-size: 8pt; color: #555; line-height: 1.4; }
    .doc-title-box { text-align: right; }
    .doc-title-box h2 { font-size: 15pt; font-weight: 900; margin: 0; }
    .doc-title-box .meta { font-size: 8.5pt; color: #555; line-height: 1.6; }
    .title-delivery { color: #0369a1; border-bottom: 2px solid #0369a1; padding-bottom: 1mm; }
    .title-receipt  { color: #166534; border-bottom: 2px solid #166534; padding-bottom: 1mm; }

    .parties-row { display: flex; gap: 4mm; margin-bottom: 3mm; }
    .pbox { flex: 1; border: 1px solid #e2e8f0; border-radius: 3px; padding: 2.5mm 3mm; font-size: 8.5pt; }
    .pbox h3 { font-size: 7pt; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin: 0 0 1mm; }
    .pbox p  { font-weight: 700; font-size: 10pt; margin: 0 0 1mm; }

    /* Items table */
    table.t { width: 100%; border-collapse: collapse; margin-bottom: 3mm; font-size: 9pt; }
    table.t thead th { background: #1e293b; color: #fff; padding: 2mm; text-align: left; font-size: 8pt; }
    table.t thead th.tr { text-align: right; }
    table.t thead th.tc { text-align: center; }
    table.t tbody td { padding: 1.5mm 2mm; border-bottom: 1px solid #f1f5f9; }
    td.tr { text-align: right; }
    td.tc { text-align: center; }
    .t .total-row td { font-weight: 800; font-size: 10pt; border-top: 1.5px solid #1e293b; }

    /* Delivery-specific: signature row */
    .sign-row { display: flex; gap: 6mm; margin-top: 5mm; }
    .sbox { flex: 1; border-top: 1px dashed #94a3b8; padding-top: 2mm; text-align: center; font-size: 8pt; color: #64748b; }

    /* Receipt-specific: payment summary */
    .pay-summary { margin-left: auto; width: 70mm; }
    .pay-summary table { width: 100%; }
    .pay-summary td { padding: 1mm 2mm; font-size: 9.5pt; }
    .pay-summary .lbl { color: #555; }
    .pay-summary .val { text-align: right; font-weight: 600; }
    .pay-summary .grand-row td { font-size: 13pt; font-weight: 900; border-top: 2px solid #000; color: #166534; padding-top: 2mm; }

    .receipt-footer { text-align: center; font-size: 8.5pt; color: #94a3b8; margin-top: 4mm; }

    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .a4-page { box-shadow: none; border-radius: 0; margin: 0; max-width: 100%; }
      .toolbar { display: none !important; }

      @page { size: A4 portrait; margin: 5mm; }

      /* ทำให้แต่ละครึ่งพอดี A5 */
      .half { min-height: 130mm; page-break-inside: avoid; }
      .cut-line { page-break-inside: avoid; }
    }
  </style>
</head>
<body>

<!-- Toolbar (no-print) -->
<div class="toolbar no-print">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <h6 class="mb-0 text-dark">
      <i class="bi bi-file-earmark-arrow-down me-2 text-success"></i>
      ใบส่งของ + ใบเสร็จ — <?= esc($sale['bill_number']) ?>
    </h6>
    <button onclick="window.print()" class="btn btn-success fw-bold ms-3">
      <i class="bi bi-printer me-2"></i>พิมพ์เอกสาร (A4)
    </button>
    <?php if ($quote): ?>
      <a href="<?= site_url('/wholesale/quotation/' . $quote['id']) ?>" class="btn btn-outline-primary">
        <i class="bi bi-file-earmark-text me-1"></i>ดูใบเสนอราคา
      </a>
    <?php endif; ?>
    <a href="<?= site_url('/wholesale/list') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-list-ul me-1"></i>รายการทั้งหมด
    </a>
    <a href="<?= site_url('/wholesale') ?>" class="btn btn-outline-primary ms-auto">
      <i class="bi bi-plus-circle me-1"></i>ใบเสนอราคาใหม่
    </a>
  </div>
  <div class="alert alert-info mt-2 py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    กด <kbd>Ctrl+P</kbd> เพื่อพิมพ์ — เอกสารจะออกเป็น <strong>A4 แผ่นเดียว</strong>
    มี <strong>ใบส่งของ (บน)</strong> และ <strong>ใบเสร็จ (ล่าง)</strong> ตัดแยกตามเส้น
  </div>
</div>

<?php
  $discAmt = (float) ($sale['discount_amount'] ?? 0);
  $discPct = (float) ($sale['discount_pct']    ?? 0);
  $total   = (float) $sale['total_amount'];
  $paid    = (float) $sale['paid_amount'];
  $change  = (float) $sale['change_amount'];
  $custName = $quote ? $quote['customer_name'] : ($sale['member_name'] ?? '');
  $custAddr = $quote ? $quote['customer_address'] : '';
  $custTax  = $quote ? $quote['customer_tax_id']  : '';
  $quoteNum = $quote ? $quote['quote_number'] : '';
  $pmLabels = ['cash' => 'เงินสด', 'qr' => 'QR / พร้อมเพย์', 'transfer' => 'โอนเงิน'];
  $pm = $pmLabels[$sale['payment_method'] ?? 'transfer'] ?? 'โอนเงิน';
  $subtotalBeforeDisc = $total + $discAmt;
?>

<!-- A4 Page -->
<div class="a4-page">

  <!-- ══════════════════ ใบส่งของ (บน) ══════════════════ -->
  <div class="half half-top">

    <div class="doc-hdr">
      <div>
        <div class="shop-name"><?= esc($shopName) ?></div>
        <?php if ($shopAddress): ?>
          <div class="shop-info" style="white-space:pre-line"><?= esc($shopAddress) ?></div>
        <?php endif; ?>
        <?php if ($shopTaxId): ?>
          <div class="shop-info">เลขภาษี: <?= esc($shopTaxId) ?></div>
        <?php endif; ?>
      </div>
      <div class="doc-title-box">
        <h2 class="title-delivery">ใบส่งของ</h2>
        <div class="meta">
          อ้างอิง: <strong><?= esc($sale['bill_number']) ?></strong><br>
          <?php if ($quoteNum): ?>เสนอราคา: <?= esc($quoteNum) ?><br><?php endif; ?>
          วันที่: <?= date('d/m/Y', strtotime($sale['created_at'])) ?><br>
          ผู้ส่ง: <?= esc($sale['cashier']) ?>
        </div>
      </div>
    </div>

    <div class="parties-row">
      <div class="pbox">
        <h3>ผู้ส่ง (Sender)</h3>
        <p><?= esc($shopName) ?></p>
        <?php if ($shopAddress): ?><div style="white-space:pre-line;font-size:8pt"><?= esc($shopAddress) ?></div><?php endif; ?>
      </div>
      <div class="pbox">
        <h3>ผู้รับ (Recipient)</h3>
        <p><?= esc($custName ?: 'ลูกค้าทั่วไป') ?></p>
        <?php if ($custAddr): ?><div style="white-space:pre-line;font-size:8pt"><?= esc($custAddr) ?></div><?php endif; ?>
        <?php if ($custTax): ?><div style="font-size:8pt">เลขภาษี: <?= esc($custTax) ?></div><?php endif; ?>
      </div>
    </div>

    <table class="t">
      <thead>
        <tr>
          <th class="tc" style="width:7mm">#</th>
          <th>รายการสินค้า</th>
          <th class="tc" style="width:18mm">จำนวน</th>
          <th class="tr" style="width:25mm">ราคา/หน่วย (฿)</th>
          <th class="tr" style="width:25mm">จำนวนเงิน (฿)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $item): ?>
          <tr>
            <td class="tc text-muted"><?= $idx + 1 ?></td>
            <td>
              <?= esc($item['product_name']) ?>
              <?php if ($item['barcode']): ?><br><small style="color:#94a3b8"><?= esc($item['barcode']) ?></small><?php endif; ?>
            </td>
            <td class="tc fw-bold"><?= number_format($item['quantity']) ?></td>
            <td class="tr"><?= number_format($item['price'], 2) ?></td>
            <td class="tr fw-bold"><?= number_format($item['subtotal'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($discAmt > 0): ?>
          <tr>
            <td colspan="4" class="tr text-success">ส่วนลด<?= $discPct > 0 ? " {$discPct}%" : '' ?></td>
            <td class="tr text-success">-฿<?= number_format($discAmt, 2) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="total-row">
          <td colspan="4" class="tr">ยอดรวมทั้งสิ้น</td>
          <td class="tr">฿<?= number_format($total, 2) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="sign-row">
      <div class="sbox">ผู้ส่งของ<br><br><br>............................</div>
      <div class="sbox">ผู้รับของ<br><br><br>............................</div>
      <div class="sbox">วันที่รับ<br><br><br>............................</div>
    </div>

  </div><!-- end half-top -->

  <!-- Cut line -->
  <div class="cut-line">
    <div class="line"></div>
    <span>ตัดตรงนี้</span>
    <div class="line"></div>
  </div>

  <!-- ══════════════════ ใบเสร็จรับเงิน (ล่าง) ══════════════════ -->
  <div class="half half-bottom">

    <div class="doc-hdr">
      <div>
        <div class="shop-name"><?= esc($shopName) ?></div>
        <?php if ($shopAddress): ?>
          <div class="shop-info" style="white-space:pre-line"><?= esc($shopAddress) ?></div>
        <?php endif; ?>
        <?php if ($shopTaxId): ?>
          <div class="shop-info">เลขภาษี: <?= esc($shopTaxId) ?></div>
        <?php endif; ?>
      </div>
      <div class="doc-title-box">
        <h2 class="title-receipt">ใบเสร็จรับเงิน</h2>
        <div class="meta">
          เลขที่: <strong><?= esc($sale['bill_number']) ?></strong><br>
          วันที่: <?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?><br>
          พนักงาน: <?= esc($sale['cashier']) ?><br>
          ชำระโดย: <?= esc($pm) ?>
        </div>
      </div>
    </div>

    <div class="parties-row">
      <div class="pbox">
        <h3>ผู้รับเงิน (Payee)</h3>
        <p><?= esc($shopName) ?></p>
        <?php if ($shopTaxId): ?><small>เลขภาษี: <?= esc($shopTaxId) ?></small><?php endif; ?>
      </div>
      <div class="pbox">
        <h3>ผู้ชำระเงิน (Payer)</h3>
        <p><?= esc($custName ?: 'ลูกค้าทั่วไป') ?></p>
        <?php if ($custTax): ?><small>เลขภาษี: <?= esc($custTax) ?></small><?php endif; ?>
        <?php if ($quoteNum): ?><small>อ้างอิงใบเสนอราคา: <?= esc($quoteNum) ?></small><?php endif; ?>
      </div>
    </div>

    <table class="t">
      <thead>
        <tr>
          <th class="tc" style="width:7mm">#</th>
          <th>รายการสินค้า</th>
          <th class="tc" style="width:18mm">จำนวน</th>
          <th class="tr" style="width:25mm">ราคา/หน่วย (฿)</th>
          <th class="tr" style="width:25mm">จำนวนเงิน (฿)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $item): ?>
          <tr>
            <td class="tc text-muted"><?= $idx + 1 ?></td>
            <td><?= esc($item['product_name']) ?></td>
            <td class="tc fw-bold"><?= number_format($item['quantity']) ?></td>
            <td class="tr"><?= number_format($item['price'], 2) ?></td>
            <td class="tr fw-bold"><?= number_format($item['subtotal'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="pay-summary">
      <table>
        <?php if ($discAmt > 0): ?>
          <tr>
            <td class="lbl">ราคาสินค้ารวม</td>
            <td class="val">฿<?= number_format($subtotalBeforeDisc, 2) ?></td>
          </tr>
          <tr>
            <td class="lbl">ส่วนลด<?= $discPct > 0 ? " ({$discPct}%)" : '' ?></td>
            <td class="val" style="color:#16a34a">-฿<?= number_format($discAmt, 2) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="grand-row">
          <td>ยอดรวมทั้งสิ้น</td>
          <td>฿<?= number_format($total, 2) ?></td>
        </tr>
        <tr>
          <td class="lbl">รับชำระ</td>
          <td class="val">฿<?= number_format($paid, 2) ?></td>
        </tr>
        <?php if ($change > 0): ?>
          <tr>
            <td class="lbl" style="color:#16a34a">เงินทอน</td>
            <td class="val" style="color:#16a34a">฿<?= number_format($change, 2) ?></td>
          </tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="receipt-footer">
      <p>ขอบคุณที่ใช้บริการ — กรุณาเก็บใบเสร็จไว้เป็นหลักฐาน</p>
    </div>

    <div class="sign-row">
      <div class="sbox">ลายมือชื่อผู้รับเงิน<br><br><br>............................</div>
      <div class="sbox">ลายมือชื่อผู้ชำระเงิน<br><br><br>............................</div>
    </div>

  </div><!-- end half-bottom -->

</div><!-- end a4-page -->

<script src="<?= base_url('assets/bootstrap.bundle.min.js') ?>"></script>
<script>
// Auto-print ถ้ามี ?print=1 ใน URL
if (new URLSearchParams(location.search).get('print') === '1') {
  setTimeout(() => window.print(), 600);
}
</script>
</body>
</html>
