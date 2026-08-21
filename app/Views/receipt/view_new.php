<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title>à¹ƒà¸šà¹€à¸ªà¸£à¹‡à¸ˆ - <?= esc($sale['bill_number']) ?></title>
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

    .receipt-table { width: 100%; font-size: <?= $fsItems ?>px; }
    .receipt-table th {
      font-weight: 600; color: #64748b; font-size: <?= max(10, $fsItems - 2) ?>px;
      border-bottom: 1px solid #e2e8f0; padding: .3rem .2rem;
    }
    .receipt-table td { padding: .35rem .2rem; vertical-align: top; }
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

    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .receipt-wrapper { margin: 0; max-width: 100%; }
      .receipt { box-shadow: none; border-radius: 0; padding: .5rem; }
      @page { margin: 5mm; }
    }
  </style>
</head>
<body>

<div class="receipt-wrapper">

  <!-- Toolbar -->
  <div class="no-print d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= site_url('/sales') ?>" class="btn btn-primary">
      <i class="bi bi-cart-plus me-1"></i>à¸‚à¸²à¸¢à¸•à¹ˆà¸­
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary">
      <i class="bi bi-printer me-1"></i>à¸žà¸´à¸¡à¸žà¹Œ A4
    </button>
    <button onclick="printCompactReceipt()" class="btn btn-outline-dark">
      <i class="bi bi-receipt me-1"></i>à¸ªà¸¥à¸´à¸› 80mm
    </button>
    <button onclick="printTaxInvoice()" class="btn btn-outline-primary">
      <i class="bi bi-file-earmark-text me-1"></i>à¹ƒà¸šà¸à¸³à¸à¸±à¸šà¸ à¸²à¸©à¸µ
    </button>
    <?php $authRole = (session()->get('auth_user') ?? [])['role'] ?? ''; ?>
    <?php if (empty($sale['voided_at'])): ?>
      <a href="<?= site_url('/returns/create/' . $sale['id']) ?>" class="btn btn-outline-warning">
        <i class="bi bi-arrow-return-left me-1"></i>à¸„à¸·à¸™à¸ªà¸´à¸™à¸„à¹‰à¸²
      </a>
      <?php if ($authRole === 'admin'): ?>
        <button class="btn btn-outline-danger" onclick="showVoidModal()">
          <i class="bi bi-x-octagon me-1"></i>à¸¢à¸à¹€à¸¥à¸´à¸à¸šà¸´à¸¥
        </button>
      <?php endif; ?>
    <?php else: ?>
      <span class="badge bg-danger fs-6 align-self-center px-3 py-2">
        <i class="bi bi-x-octagon me-1"></i>à¸šà¸´à¸¥à¸–à¸¹à¸à¸¢à¸à¹€à¸¥à¸´à¸à¹à¸¥à¹‰à¸§
      </span>
    <?php endif; ?>
    <a href="<?= site_url('/reports/daily') ?>" class="btn btn-outline-secondary">
      <i class="bi bi-bar-chart me-1"></i>à¸£à¸²à¸¢à¸‡à¸²à¸™
    </a>
    <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary ms-auto">
      <i class="bi bi-house me-1"></i>à¸«à¸™à¹‰à¸²à¸«à¸¥à¸±à¸
    </a>
  </div>


  <?php if (!empty($sale['voided_at'])): ?>
  <div class="no-print alert alert-danger mb-3">
    <i class="bi bi-x-octagon-fill me-2"></i>
    <strong>à¸šà¸´à¸¥à¸™à¸µà¹‰à¸–à¸¹à¸à¸¢à¸à¹€à¸¥à¸´à¸à¹à¸¥à¹‰à¸§</strong>
    à¹€à¸¡à¸·à¹ˆà¸­ <?= date('d/m/Y H:i', strtotime($sale['voided_at'])) ?>
    <?php if ($sale['void_reason']): ?> â€” à¹€à¸«à¸•à¸¸à¸œà¸¥: <?= esc($sale['void_reason']) ?><?php endif; ?>
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
        <div class="bill-meta">à¹€à¸¥à¸‚à¸ à¸²à¸©à¸µ: <?= esc($shopTaxId) ?></div>
      <?php endif; ?>
      <div class="mt-2">
        <div class="bill-meta">à¹€à¸¥à¸‚à¸šà¸´à¸¥: <strong><?= esc($sale['bill_number']) ?></strong></div>
        <div class="bill-meta"><?= date('d/m/Y H:i:s', strtotime($sale['created_at'])) ?></div>
        <div class="bill-meta">à¸žà¸™à¸±à¸à¸‡à¸²à¸™: <?= esc($sale['cashier']) ?></div>
      </div>
    </div>

    <!-- Member strip -->
    <?php if (! empty($sale['member_name'])): ?>
      <?php $earnedPts = (int) floor($sale['total_amount'] / $shopPointsRate); ?>
      <div class="member-strip">
        <i class="bi bi-person-check-fill text-primary me-1"></i>
        <strong>à¸ªà¸¡à¸²à¸Šà¸´à¸:</strong> <?= esc($sale['member_name']) ?>
        <?php if ($earnedPts > 0): ?>
          <span class="badge bg-warning text-dark ms-2">
            <i class="bi bi-star-fill"></i> +<?= $earnedPts ?> à¹à¸•à¹‰à¸¡
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Items -->
    <table class="receipt-table">
      <thead>
        <tr>
          <th style="width:45%">à¸ªà¸´à¸™à¸„à¹‰à¸²</th>
          <th class="text-center" style="width:10%">à¸ˆà¸³à¸™à¸§à¸™</th>
          <th class="text-end" style="width:20%">à¸£à¸²à¸„à¸²</th>
          <th class="text-end" style="width:25%">à¸£à¸§à¸¡</th>
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
      $pmLabel = ['cash' => 'ðŸ’µ à¹€à¸‡à¸´à¸™à¸ªà¸”', 'qr' => 'ðŸ“± QR / à¸žà¸£à¹‰à¸­à¸¡à¹€à¸žà¸¢à¹Œ', 'transfer' => 'ðŸ¦ à¹‚à¸­à¸™à¹€à¸‡à¸´à¸™'];
      $pm = $sale['payment_method'] ?? 'cash';
    ?>
    <div class="text-center mb-2">
      <span class="badge bg-light text-dark border" style="font-size:.8rem">
        <?= $pmLabel[$pm] ?? $pm ?>
      </span>
    </div>

    <table class="receipt-summary w-100">
      <?php if ($hasReductions): ?>
        <tr>
          <td class="text-muted">à¸¢à¸­à¸”à¸à¹ˆà¸­à¸™à¸¥à¸”</td>
          <td class="text-end text-muted">à¸¿<?= number_format($subtotalBeforeDiscount, 2) ?></td>
        </tr>
        <?php if ($discountAmt > 0): ?>
          <tr>
            <td class="text-success small">
              <i class="bi bi-tag-fill me-1"></i>à¸ªà¹ˆà¸§à¸™à¸¥à¸”<?= $discountPct > 0 ? " {$discountPct}%" : '' ?>
            </td>
            <td class="text-end text-success small">-à¸¿<?= number_format($discountAmt, 2) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($pointsDisc > 0): ?>
          <tr>
            <td class="text-warning small">
              <i class="bi bi-star-fill me-1"></i>à¹ƒà¸Šà¹‰à¹à¸•à¹‰à¸¡ <?= $pointsUsed ?> à¹à¸•à¹‰à¸¡
            </td>
            <td class="text-end text-warning small">-à¸¿<?= number_format($pointsDisc, 2) ?></td>
          </tr>
        <?php endif; ?>
      <?php endif; ?>
      <tr class="total-row">
        <td><?= $hasReductions ? 'à¸¢à¸­à¸”à¸ªà¸¸à¸—à¸˜à¸´' : 'à¸¢à¸­à¸”à¸£à¸§à¸¡à¸—à¸±à¹‰à¸‡à¸ªà¸´à¹‰à¸™' ?></td>
        <td class="text-end">à¸¿<?= number_format($sale['total_amount'], 2) ?></td>
      </tr>
      <tr>
        <td class="text-muted">à¸£à¸±à¸šà¹€à¸‡à¸´à¸™</td>
        <td class="text-end text-muted">à¸¿<?= number_format($sale['paid_amount'], 2) ?></td>
      </tr>
      <tr class="change-row">
        <td><strong>à¹€à¸‡à¸´à¸™à¸—à¸­à¸™</strong></td>
        <td class="text-end"><strong>à¸¿<?= number_format($sale['change_amount'], 2) ?></strong></td>
      </tr>
      <?php if (! empty($sale['member_name']) && $earnedPts > 0): ?>
        <tr class="points-row">
          <td><i class="bi bi-star-fill me-1"></i>à¹à¸•à¹‰à¸¡à¸—à¸µà¹ˆà¹„à¸”à¹‰à¸£à¸±à¸š</td>
          <td class="text-end">+<?= $earnedPts ?> à¹à¸•à¹‰à¸¡</td>
        </tr>
      <?php endif; ?>
    </table>

    <!-- Footer -->
    <div class="receipt-footer">
      <p class="mb-1">à¸‚à¸­à¸šà¸„à¸¸à¸“à¸—à¸µà¹ˆà¹ƒà¸Šà¹‰à¸šà¸£à¸´à¸à¸²à¸£</p>
      <p class="mb-0">à¸à¸£à¸¸à¸“à¸²à¹€à¸à¹‡à¸šà¹ƒà¸šà¹€à¸ªà¸£à¹‡à¸ˆà¹„à¸§à¹‰à¹€à¸›à¹‡à¸™à¸«à¸¥à¸±à¸à¸à¸²à¸™</p>
    </div>
  </div>

  <div class="no-print text-center mt-3">
    <small class="text-muted">
      <i class="bi bi-info-circle me-1"></i>à¸à¸” <kbd>Ctrl+P</kbd> à¹€à¸žà¸·à¹ˆà¸­à¸žà¸´à¸¡à¸žà¹Œà¹ƒà¸šà¹€à¸ªà¸£à¹‡à¸ˆ
    </small>
  </div>

</div>


<!-- Void modal (à¹à¸ªà¸”à¸‡à¹€à¸‰à¸žà¸²à¸° admin â€” à¸–à¸¹à¸ render à¸à¸±à¹ˆà¸‡ server à¹à¸¥à¹‰à¸§) -->
<div class="modal fade" id="voidModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-x-octagon me-2"></i>à¸¢à¸à¹€à¸¥à¸´à¸à¸šà¸´à¸¥</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">
          à¸à¸²à¸£à¸¢à¸à¹€à¸¥à¸´à¸à¸šà¸´à¸¥à¸ˆà¸°<strong>à¸„à¸·à¸™à¸ªà¸•à¹Šà¸­à¸à¸ªà¸´à¸™à¸„à¹‰à¸²à¸—à¸±à¹‰à¸‡à¸«à¸¡à¸”</strong>à¹ƒà¸™à¸šà¸´à¸¥à¸™à¸µà¹‰ à¸”à¸³à¹€à¸™à¸´à¸™à¸à¸²à¸£à¸•à¹ˆà¸­?
        </p>
        <label class="form-label fw-semibold small">à¹€à¸«à¸•à¸¸à¸œà¸¥ (à¸–à¹‰à¸²à¸¡à¸µ)</label>
        <input type="text" id="void-reason" class="form-control" placeholder="à¹€à¸Šà¹ˆà¸™ à¸¥à¸¹à¸à¸„à¹‰à¸²à¹€à¸›à¸¥à¸µà¹ˆà¸¢à¸™à¹ƒà¸ˆ / à¸à¸£à¸­à¸à¸œà¸´à¸”">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">à¸¢à¸à¹€à¸¥à¸´à¸</button>
        <button type="button" class="btn btn-danger btn-sm" id="void-confirm-btn">
          <i class="bi bi-x-octagon me-1"></i>à¸¢à¸·à¸™à¸¢à¸±à¸™à¸¢à¸à¹€à¸¥à¸´à¸à¸šà¸´à¸¥
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
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>à¸à¸³à¸¥à¸±à¸‡à¸”à¸³à¹€à¸™à¸´à¸™à¸à¸²à¸£...';

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
        this.innerHTML = '<i class="bi bi-x-octagon me-1"></i>à¸¢à¸·à¸™à¸¢à¸±à¸™à¸¢à¸à¹€à¸¥à¸´à¸à¸šà¸´à¸¥';
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

// â”€â”€ à¹ƒà¸šà¸à¸³à¸à¸±à¸šà¸ à¸²à¸©à¸µà¹€à¸•à¹‡à¸¡à¸£à¸¹à¸›à¹à¸šà¸š â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

  const html = `<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>à¹ƒà¸šà¸à¸³à¸à¸±à¸šà¸ à¸²à¸©à¸µ <?= esc($sale['bill_number']) ?></title>
<style>
  *  { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Sarabun',Arial,sans-serif; font-size:11pt; color:#000; }
  .page { width:190mm; margin:0 auto; padding:10mm 0; }

  /* Header */
  .hdr { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6mm; }
  .shop-name { font-size:18pt; font-weight:900; }
  .shop-info { font-size:9pt; color:#444; margin-top:2mm; }
  .doc-title { text-align:right; }
  .doc-title h1 { font-size:16pt; font-weight:900; border-bottom:2px solid #000; padding-bottom:2mm; }
  .doc-title .meta { font-size:9pt; color:#444; margin-top:2mm; line-height:1.6; }

  /* Parties */
  .parties { display:flex; gap:4mm; margin-bottom:5mm; }
  .party-box { flex:1; border:1px solid #ccc; border-radius:2mm; padding:3mm 4mm; }
  .party-box h3 { font-size:9pt; color:#888; margin-bottom:1mm; }
  .party-box p  { font-size:10pt; font-weight:600; }
  .party-box small { font-size:8.5pt; color:#555; }

  /* Items table */
  table { width:100%; border-collapse:collapse; margin-bottom:4mm; }
  thead th { background:#1e293b; color:#fff; padding:2.5mm 3mm; font-size:9pt; text-align:left; }
  thead th.tr { text-align:right; }
  thead th.tc { text-align:center; }
  tbody td { padding:2mm 3mm; font-size:9.5pt; border-bottom:1px solid #eee; }
  tbody tr:nth-child(even) td { background:#f9fafb; }
  td.tr { text-align:right; }
  td.tc { text-align:center; }

  /* Summary */
  .summary { margin-left:auto; width:80mm; }
  .summary table { margin:0; }
  .summary td { padding:1.5mm 3mm; font-size:10pt; }
  .summary .label { color:#555; }
  .summary .val   { text-align:right; font-weight:600; }
  .summary .total-row td { font-size:13pt; font-weight:900; border-top:2px solid #000; padding-top:3mm; }

  /* Footer */
  .footer { margin-top:8mm; display:flex; justify-content:space-between; gap:10mm; }
  .sign-box { flex:1; border-top:1px dashed #999; text-align:center; padding-top:2mm; font-size:9pt; color:#777; }

  @media print {
    @page { size:A4 portrait; margin:15mm 12mm; }
    body  { margin:0; }
  }
</style>
</head><body>
<div class="page">

  <!-- Header -->
  <div class="hdr">
    <div>
      <div class="shop-name"><?= esc($shopName) ?></div>
      <?php if ($shopAddress): ?>
      <div class="shop-info" style="white-space:pre-line"><?= esc($shopAddress) ?></div>
      <?php endif; ?>
      <?php if ($shopTaxId): ?>
      <div class="shop-info"><strong>à¹€à¸¥à¸‚à¸›à¸£à¸°à¸ˆà¸³à¸•à¸±à¸§à¸œà¸¹à¹‰à¹€à¸ªà¸µà¸¢à¸ à¸²à¸©à¸µ:</strong> <?= esc($shopTaxId) ?></div>
      <?php endif; ?>
    </div>
    <div class="doc-title">
      <h1>à¹ƒà¸šà¸à¸³à¸à¸±à¸šà¸ à¸²à¸©à¸µ</h1>
      <div class="meta">
        à¹€à¸¥à¸‚à¸—à¸µà¹ˆ: <strong><?= esc($sale['bill_number']) ?></strong><br>
        à¸§à¸±à¸™à¸—à¸µà¹ˆ: <?= date('d/m/Y', strtotime($sale['created_at'])) ?><br>
        à¹€à¸§à¸¥à¸²:  <?= date('H:i:s', strtotime($sale['created_at'])) ?><br>
        à¸žà¸™à¸±à¸à¸‡à¸²à¸™: <?= esc($sale['cashier']) ?>
      </div>
    </div>
  </div>

  <!-- Parties -->
  <div class="parties">
    <div class="party-box">
      <h3>à¸œà¸¹à¹‰à¸‚à¸²à¸¢ (Seller)</h3>
      <p><?= esc($shopName) ?></p>
      <?php if ($shopAddress): ?>
      <small style="white-space:pre-line"><?= esc($shopAddress) ?></small><br>
      <?php endif; ?>
      <?php if ($shopTaxId): ?><small>à¹€à¸¥à¸‚à¸ à¸²à¸©à¸µ: <?= esc($shopTaxId) ?></small><?php endif; ?>
    </div>
    <div class="party-box">
      <h3>à¸œà¸¹à¹‰à¸‹à¸·à¹‰à¸­ (Buyer)</h3>
      <?php if (!empty($sale['member_name'])): ?>
      <p><?= esc($sale['member_name']) ?></p>
      <small>à¸£à¸«à¸±à¸ªà¸ªà¸¡à¸²à¸Šà¸´à¸: <?= esc($sale['member_id'] ?? '-') ?></small>
      <?php else: ?>
      <p style="color:#aaa">à¸¥à¸¹à¸à¸„à¹‰à¸²à¸—à¸±à¹ˆà¸§à¹„à¸›</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items -->
  <table>
    <thead>
      <tr>
        <th class="tc" style="width:8mm">#</th>
        <th>à¸£à¸²à¸¢à¸à¸²à¸£à¸ªà¸´à¸™à¸„à¹‰à¸²</th>
        <th class="tr" style="width:15mm">à¸ˆà¸³à¸™à¸§à¸™</th>
        <th class="tr" style="width:28mm">à¸£à¸²à¸„à¸²/à¸«à¸™à¹ˆà¸§à¸¢ (à¸¿)</th>
        <th class="tr" style="width:28mm">à¸ˆà¸³à¸™à¸§à¸™à¹€à¸‡à¸´à¸™ (à¸¿)</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
  </table>

  <!-- Summary -->
  <div class="summary">
    <table>
      <tr>
        <td class="label">à¸¡à¸¹à¸¥à¸„à¹ˆà¸²à¸ªà¸´à¸™à¸„à¹‰à¸²à¸à¹ˆà¸­à¸™ VAT</td>
        <td class="val">à¸¿<?= number_format($beforeVat, 2) ?></td>
      </tr>
      <?php if ((float)($sale['discount_amount'] ?? 0) > 0): ?>
      <tr>
        <td class="label">à¸ªà¹ˆà¸§à¸™à¸¥à¸”</td>
        <td class="val" style="color:#16a34a">-à¸¿<?= number_format($sale['discount_amount'], 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="label">à¸ à¸²à¸©à¸µà¸¡à¸¹à¸¥à¸„à¹ˆà¸²à¹€à¸žà¸´à¹ˆà¸¡ <?= $vatRate ?>% (à¸£à¸§à¸¡à¹ƒà¸™)</td>
        <td class="val">à¸¿<?= number_format($vatAmt, 2) ?></td>
      </tr>
      <tr class="total-row">
        <td>à¸¢à¸­à¸”à¸£à¸§à¸¡à¸—à¸±à¹‰à¸‡à¸ªà¸´à¹‰à¸™</td>
        <td>à¸¿<?= number_format($sale['total_amount'], 2) ?></td>
      </tr>
    </table>
  </div>

  <!-- Footer -->
  <div class="footer" style="margin-top:12mm">
    <div class="sign-box">à¸¥à¸²à¸¢à¸¡à¸·à¸­à¸Šà¸·à¹ˆà¸­à¸œà¸¹à¹‰à¸£à¸±à¸šà¹€à¸‡à¸´à¸™<br><br><br>............................</div>
    <div class="sign-box">à¸¥à¸²à¸¢à¸¡à¸·à¸­à¸Šà¸·à¹ˆà¸­à¸œà¸¹à¹‰à¸‹à¸·à¹‰à¸­ / à¸•à¸£à¸§à¸ˆà¸£à¸±à¸š<br><br><br>............................</div>
    <div class="sign-box">à¸›à¸£à¸°à¸—à¸±à¸šà¸•à¸£à¸²à¸šà¸£à¸´à¸©à¸±à¸—<br><br><br>&nbsp;</div>
  </div>

  <div style="text-align:center;font-size:8pt;color:#aaa;margin-top:6mm">
    à¹€à¸­à¸à¸ªà¸²à¸£à¸™à¸µà¹‰à¸­à¸­à¸à¹‚à¸”à¸¢à¸£à¸°à¸šà¸š POS â€” <?= esc($shopName) ?> â€” <?= date('d/m/Y H:i:s') ?>
  </div>

</div>
<script>setTimeout(() => window.print(), 500);<\/script>
</body></html>`;

  const win = window.open('', '_blank', 'width=820,height=1000');
  win.document.write(html);
  win.document.close();
}

function printCompactReceipt() {

function printCompactReceipt() {
  <?php
    $slip80DiscAmt   = (float)($sale['discount_amount'] ?? 0);
    $slip80DiscPct   = (float)($sale['discount_pct']    ?? 0);
    $slip80PtsUsed   = (int)  ($sale['points_used']     ?? 0);
    $slip80PtsDisc   = (float)($sale['points_discount'] ?? 0);
    $slip80HasReduc  = $slip80DiscAmt > 0 || $slip80PtsDisc > 0;
    $slip80SubBefore = $sale['total_amount'] + $slip80DiscAmt + $slip80PtsDisc;
    $slip80EarnedPts = (int) floor($sale['total_amount'] / $shopPointsRate);
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
  * { box-sizing:border-box; margin:0; padding:0; }
  @page { size:80mm; margin:3mm; }
  body { font-family:'Sarabun',Arial,sans-serif; font-size:<?= $fsBase ?>px; color:<?= $color ?>; width:74mm; }
  .center { text-align:center; }
  .right  { text-align:right; }
  .sep    { border-top:1px dashed #000; margin:2mm 0; }
  .shopname { font-size:<?= $fsShopname ?>px; font-weight:900; text-align:center; }
  .meta { font-size:<?= $fsMeta ?>px; text-align:center; color:#555; }
  .dim  { font-size:<?= max(8, $fsItems - 2) ?>px; color:#888; }
  table.items { width:100%; border-collapse:collapse; font-size:<?= $fsItems ?>px; }
  table.items th { font-size:<?= max(8,$fsItems-2) ?>px; border-bottom:1px solid #000; padding:1mm 1mm; text-align:left; }
  table.items th.num, table.items td.num { text-align:right; white-space:nowrap; }
  table.items td { padding:1mm 1mm; vertical-align:top; }
  table.items td.name { max-width:38mm; word-break:break-word; }
  .sum { width:100%; font-size:<?= $fsItems ?>px; }
  .sum td { padding:.5mm 1mm; }
  .sum .r { text-align:right; }
  .total-lbl { font-size:<?= $fsTotal ?>px; font-weight:900; }
  .total-val { font-size:<?= $fsTotal ?>px; font-weight:900; text-align:right; }
  .footer-txt { font-size:<?= $fsFooter ?>px; text-align:center; color:#888; margin-top:2mm; }
  @media print { body { margin:0; } }
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
  <tr><td style="color:#555">รับเงิน</td><td class="r" style="color:#555">฿<?= number_format($sale['paid_amount'], 2) ?></td></tr>
  <tr><td><strong>เงินทอน</strong></td><td class="r" style="color:#16a34a"><strong>฿<?= number_format($sale['change_amount'], 2) ?></strong></td></tr>
  <?php if (!empty($sale['member_name']) && $slip80EarnedPts > 0): ?>
  <tr><td style="color:#d97706">แต้มที่ได้รับ</td><td class="r" style="color:#d97706">+<?= $slip80EarnedPts ?> แต้ม</td></tr>
  <?php endif; ?>
</table>
<div class="sep"></div>
<div class="footer-txt">ขอบคุณที่ใช้บริการ</div>
<div class="footer-txt">กรุณาเก็บใบเสร็จไว้เป็นหลักฐาน</div>
</body></html>`;

  const win80 = window.open('', '_blank', 'width=340,height=600');
  win80.document.write(html80);
  win80.document.close();
  setTimeout(() => { win80.print(); }, 500);
}
</script>
</body>
</html>
