<?= view('templates/header', ['title' => $title]) ?>

<!-- ── Stat Cards ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <!-- Today Revenue -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card border-start border-primary border-4 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">ยอดขายวันนี้</div>
            <div class="fs-4 fw-bold text-primary">
              ฿<?= number_format($today_summary['total_revenue'] ?? 0, 2) ?>
            </div>
          </div>
          <div class="text-primary opacity-50"><i class="bi bi-cash-coin fs-1"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Today Bills -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card border-start border-success border-4 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">จำนวนบิลวันนี้</div>
            <div class="fs-4 fw-bold text-success"><?= $today_summary['total_bills'] ?? 0 ?> บิล</div>
          </div>
          <div class="text-success opacity-50"><i class="bi bi-receipt fs-1"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Products -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card border-start border-info border-4 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">สินค้าทั้งหมด</div>
            <div class="fs-4 fw-bold text-info"><?= $total_products ?> รายการ</div>
          </div>
          <div class="text-info opacity-50"><i class="bi bi-box-seam fs-1"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Low Stock -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card border-start border-warning border-4 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">สต๊อกใกล้หมด</div>
            <div class="fs-4 fw-bold text-warning"><?= $low_stock ?> รายการ</div>
          </div>
          <div class="text-warning opacity-50"><i class="bi bi-exclamation-triangle fs-1"></i></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Pending Returns Alert -->
<?php if ($pending_returns_count > 0): ?>
<div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between mb-4 px-4 py-3">
  <div>
    <i class="bi bi-arrow-return-left fs-5 me-2"></i>
    <strong>มีรายการคืนสินค้ารออนุมัติ <?= $pending_returns_count ?> รายการ</strong>
    <span class="text-muted ms-2 small">กรุณาตรวจสอบและอนุมัติหรือปฏิเสธ</span>
  </div>
  <a href="<?= site_url('/returns?status=pending') ?>" class="btn btn-danger btn-sm px-3">
    ดูรายการ <i class="bi bi-arrow-right ms-1"></i>
  </a>
</div>
<?php endif; ?>

<div class="row g-3">
  <!-- Recent Sales -->
  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>บิลล่าสุด</h6>
        <a href="<?= site_url('/reports/daily') ?>" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>เลขบิล</th>
                <th>เวลา</th>
                <th class="text-end">ยอดรวม</th>
                <th class="text-end">รับเงิน</th>
                <th class="text-center">ดู</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_sales)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีรายการขาย</td></tr>
              <?php else: ?>
                <?php foreach ($recent_sales as $sale): ?>
                  <tr>
                    <td><span class="badge bg-light text-dark"><?= esc($sale['bill_number']) ?></span></td>
                    <td class="text-muted small"><?= date('H:i', strtotime($sale['created_at'])) ?></td>
                    <td class="text-end fw-bold">฿<?= number_format($sale['total_amount'], 2) ?></td>
                    <td class="text-end text-muted">฿<?= number_format($sale['paid_amount'], 2) ?></td>
                    <td class="text-center">
                      <a href="<?= site_url('/receipt/' . $sale['id']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Returns -->
  <div class="col-lg-4 mb-3 mb-lg-0">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-danger">
          <i class="bi bi-arrow-return-left me-2"></i>รอคืนสินค้า
        </h6>
        <?php if ($pending_returns_count > 0): ?>
          <span class="badge bg-danger"><?= $pending_returns_count ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body p-0" style="max-height:280px;overflow-y:auto;">
        <?php if (empty($pending_returns)): ?>
          <div class="text-center text-muted py-4">
            <i class="bi bi-check-circle text-success fs-2"></i>
            <div class="mt-1 small">ไม่มีรายการรออนุมัติ</div>
          </div>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($pending_returns as $r): ?>
              <li class="list-group-item py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-semibold small"><?= esc($r['return_number']) ?></div>
                    <div class="text-muted" style="font-size:.75rem">
                      บิล: <?= esc($r['bill_number']) ?> · <?= esc($r['cashier']) ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem">
                      <?= esc(mb_strimwidth($r['reason'], 0, 35, '...')) ?>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="text-danger fw-bold small">฿<?= number_format($r['total_refund'], 2) ?></div>
                    <a href="<?= site_url('/returns/detail/' . $r['id']) ?>"
                       class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:.7rem">
                      ดู
                    </a>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white border-0">
        <a href="<?= site_url('/returns') ?>" class="btn btn-sm btn-outline-danger w-100">
          <i class="bi bi-arrow-return-left me-1"></i>จัดการรายการคืนสินค้า
        </a>
      </div>
    </div>
  </div>

  <!-- Low Stock Alert -->
  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-exclamation-triangle me-2"></i>สต๊อกใกล้หมด</h6>
      </div>
      <div class="card-body p-0" style="max-height:350px;overflow-y:auto;">
        <?php if (empty($low_stock_items)): ?>
          <div class="text-center text-muted py-4">
            <i class="bi bi-check-circle text-success fs-2"></i>
            <div class="mt-1">สต๊อกสินค้าปกติทั้งหมด</div>
          </div>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($low_stock_items as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <div>
                  <div class="fw-semibold small"><?= esc($item['name']) ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= esc($item['category'] ?? '-') ?></div>
                </div>
                <span class="badge <?= $item['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                  <?= $item['stock'] == 0 ? 'หมด' : $item['stock'] . ' ชิ้น' ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white border-0">
        <a href="<?= site_url('/products') ?>" class="btn btn-sm btn-warning w-100">
          <i class="bi bi-box-seam me-1"></i>จัดการสต๊อก
        </a>
      </div>
    </div>
  </div>
</div>

<!-- 7-day chart -->
<div class="card shadow-sm border-0 mt-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2 text-primary"></i>ยอดขาย 7 วันล่าสุด</h6>
    <a href="<?= site_url('/reports/search?from=' . date('Y-m-d', strtotime('-6 days')) . '&to=' . date('Y-m-d')) ?>"
       class="btn btn-sm btn-outline-primary">ดูรายละเอียด</a>
  </div>
  <div class="card-body" style="height:220px">
    <canvas id="chart7days"></canvas>
  </div>
</div>

<!-- Shortcut buttons -->
<div class="mt-4 d-flex gap-3 flex-wrap">
  <a href="<?= site_url('/sales') ?>" class="btn btn-primary btn-lg px-4">
    <i class="bi bi-cart-plus me-2"></i>เปิดหน้าขาย
  </a>
  <a href="<?= site_url('/products/create') ?>" class="btn btn-outline-primary btn-lg px-4">
    <i class="bi bi-plus-circle me-2"></i>เพิ่มสินค้าใหม่
  </a>
  <a href="<?= site_url('/reports/search') ?>" class="btn btn-outline-secondary btn-lg px-4">
    <i class="bi bi-search me-2"></i>ค้นหาบิล
  </a>
  <a href="<?= site_url('/customer-display') ?>" class="btn btn-outline-dark btn-lg px-4" target="_blank">
    <i class="bi bi-display me-2"></i>หน้าจอลูกค้า
  </a>
</div>

<?php
$chartData = [];
// สร้าง array 7 วัน ใส่ 0 ถ้าไม่มีข้อมูล
$indexed = [];
foreach (($chart_7days ?? []) as $row) {
    $indexed[$row['d']] = (float) $row['revenue'];
}
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('d/m', strtotime($d));
    $chartData['labels'][] = $label;
    $chartData['data'][]   = $indexed[$d] ?? 0;
}
?>
<script src="<?= base_url('assets/chart.umd.min.js') ?>"></script>
<script>
new Chart(document.getElementById('chart7days'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartData['labels']) ?>,
    datasets: [{
      label: 'ยอดขาย (฿)',
      data: <?= json_encode($chartData['data']) ?>,
      backgroundColor: 'rgba(59,130,246,.7)',
      borderColor: 'rgba(59,130,246,1)',
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { callback: v => '฿' + v.toLocaleString('th-TH') }
      }
    }
  }
});
</script>

<?= view('templates/footer') ?>
