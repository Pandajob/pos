<?= view('templates/header', ['title' => $title]) ?>

<!-- Year selector + summary -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <form method="get" action="<?= site_url('/reports/monthly') ?>" class="d-flex gap-2">
          <select name="year" class="form-select">
            <?php foreach ($years as $y): ?>
              <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary px-3" type="submit"><i class="bi bi-search"></i></button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">รายได้รวมปี <?= $year ?></div>
        <div class="fs-4 fw-bold text-success">฿<?= number_format($totalRevenue, 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">จำนวนบิลรวมปี <?= $year ?></div>
        <div class="fs-4 fw-bold text-primary"><?= number_format($totalBills) ?> บิล</div>
      </div>
    </div>
  </div>
</div>

<!-- Chart -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body" style="height:260px">
    <canvas id="monthlyChart"></canvas>
  </div>
</div>

<!-- Monthly table -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>ยอดขายรายเดือน ปี <?= $year ?></h6>
    <a href="<?= site_url('/reports/export?type=sales&from=' . $year . '-01-01&to=' . $year . '-12-31') ?>"
       class="btn btn-sm btn-outline-success">
      <i class="bi bi-download me-1"></i>Export CSV
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>เดือน</th>
            <th class="text-center">บิล</th>
            <th class="text-end">รายได้</th>
            <th class="text-end">เฉลี่ย/บิล</th>
            <th class="text-end">เงินสด</th>
            <th class="text-end">QR/พร้อมเพย์</th>
            <th class="text-end">โอนเงิน</th>
            <th class="text-end">เงินเชื่อ</th>
            <th class="text-center">ดูรายละเอียด</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($months as $m => $row): ?>
            <tr class="<?= $row['bills'] == 0 ? 'text-muted' : '' ?>">
              <td class="fw-semibold"><?= $row['label'] ?> <?= $year ?></td>
              <td class="text-center">
                <?= $row['bills'] > 0
                  ? '<span class="badge bg-primary">' . number_format($row['bills']) . '</span>'
                  : '<span class="text-muted">-</span>' ?>
              </td>
              <td class="text-end fw-bold <?= $row['revenue'] > 0 ? 'text-success' : '' ?>">
                <?= $row['revenue'] > 0 ? '฿' . number_format($row['revenue'], 2) : '-' ?>
              </td>
              <td class="text-end text-muted small">
                <?= $row['bills'] > 0 ? '฿' . number_format($row['avg_bill'], 2) : '-' ?>
              </td>
              <td class="text-end small"><?= $row['cash_rev'] > 0 ? '฿' . number_format($row['cash_rev'], 2) : '-' ?></td>
              <td class="text-end small"><?= $row['qr_rev'] > 0 ? '฿' . number_format($row['qr_rev'], 2) : '-' ?></td>
              <td class="text-end small"><?= $row['transfer_rev'] > 0 ? '฿' . number_format($row['transfer_rev'], 2) : '-' ?></td>
              <td class="text-end small text-warning"><?= ($row['credit_rev'] ?? 0) > 0 ? '฿' . number_format($row['credit_rev'], 2) : '-' ?></td>
              <td class="text-center">
                <?php if ($row['bills'] > 0):
                  $from = $year . '-' . str_pad($m, 2,'0',STR_PAD_LEFT) . '-01';
                  $to   = date('Y-m-t', strtotime($from));
                ?>
                  <a href="<?= site_url('/reports/search?from=' . $from . '&to=' . $to) ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-list-ul"></i>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="table-light fw-bold">
            <td>รวมทั้งปี</td>
            <td class="text-center"><?= number_format($totalBills) ?> บิล</td>
            <td class="text-end text-success">฿<?= number_format($totalRevenue, 2) ?></td>
            <td colspan="6"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/chart.umd.min.js') ?>"></script>
<script>
const labels   = <?= json_encode(array_column($months, 'label')) ?>;
const revenues = <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $months)) ?>;
const cashArr  = <?= json_encode(array_map(fn($r) => (float)$r['cash_rev'], $months)) ?>;
const qrArr    = <?= json_encode(array_map(fn($r) => (float)$r['qr_rev'], $months)) ?>;
const tranArr  = <?= json_encode(array_map(fn($r) => (float)$r['transfer_rev'], $months)) ?>;
const credArr  = <?= json_encode(array_map(fn($r) => (float)($r['credit_rev'] ?? 0), $months)) ?>;

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      { label: 'เงินสด',      data: cashArr, backgroundColor: 'rgba(34,197,94,.75)',  borderRadius: 3 },
      { label: 'QR/พร้อมเพย์', data: qrArr,   backgroundColor: 'rgba(59,130,246,.75)', borderRadius: 3 },
      { label: 'โอนเงิน',     data: tranArr,  backgroundColor: 'rgba(168,85,247,.75)', borderRadius: 3 },
      { label: 'เงินเชื่อ',   data: credArr,  backgroundColor: 'rgba(245,158,11,.75)', borderRadius: 3 },
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top' } },
    scales: {
      x: { stacked: true },
      y: { stacked: true, beginAtZero: true,
           ticks: { callback: v => '฿' + v.toLocaleString('th-TH') } }
    }
  }
});
</script>

<?= view('templates/footer') ?>
