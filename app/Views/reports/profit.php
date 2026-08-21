<?= view('templates/header', ['title' => $title]) ?>

<!-- Date picker + summary cards -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <form method="get" action="<?= site_url('/reports/profit') ?>" class="d-flex gap-2">
          <input type="date" name="date" value="<?= esc($date) ?>"
                 class="form-control" max="<?= date('Y-m-d') ?>">
          <button type="submit" class="btn btn-primary px-3">
            <i class="bi bi-search"></i>
          </button>
        </form>
        <div class="mt-2 d-flex gap-2">
          <a href="<?= site_url('/reports/profit?date=' . date('Y-m-d')) ?>"
             class="btn btn-sm btn-outline-secondary <?= $date === date('Y-m-d') ? 'active' : '' ?>">วันนี้</a>
          <a href="<?= site_url('/reports/profit?date=' . date('Y-m-d', strtotime('-1 day'))) ?>"
             class="btn btn-sm btn-outline-secondary">เมื่อวาน</a>
          <a href="<?= site_url('/reports/daily?date=' . $date) ?>"
             class="btn btn-sm btn-outline-primary">ดูยอดขาย</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4 col-lg-2-5">
    <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">รายได้รวม</div>
        <div class="fs-4 fw-bold text-success">฿<?= number_format($summary['revenue'] ?? 0, 2) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4 col-lg-2-5">
    <div class="card shadow-sm border-0 border-start border-danger border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">ต้นทุนรวม</div>
        <div class="fs-4 fw-bold text-danger">฿<?= number_format($summary['total_cost'] ?? 0, 2) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4 col-lg-3">
    <?php
      $profit  = (float)($summary['profit'] ?? 0);
      $revenue = (float)($summary['revenue'] ?? 0);
      $margin  = $revenue > 0 ? round($profit / $revenue * 100, 1) : 0;
      $isProfit = $profit >= 0;
    ?>
    <div class="card shadow-sm border-0 border-start border-<?= $isProfit ? 'warning' : 'secondary' ?> border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">กำไรขั้นต้น</div>
        <div class="fs-4 fw-bold text-<?= $isProfit ? 'warning' : 'secondary' ?>">
          ฿<?= number_format($profit, 2) ?>
        </div>
        <div class="text-muted small">Margin <?= $margin ?>%</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-graph-up-arrow me-2 text-success"></i>กำไรรายสินค้า
    </h6>
    <small class="text-muted">
      <i class="bi bi-info-circle me-1"></i>ใช้ต้นทุนปัจจุบัน (cost) ณ วันที่ดู
    </small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>สินค้า</th>
            <th class="text-center">จำนวนขาย</th>
            <th class="text-end">ต้นทุน/ชิ้น</th>
            <th class="text-end">รายได้รวม</th>
            <th class="text-end">ต้นทุนรวม</th>
            <th class="text-end">กำไร</th>
            <th class="text-center">Margin</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                ไม่มีข้อมูลวันที่ <?= date('d/m/Y', strtotime($date)) ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $item):
              $itemProfit = (float)$item['profit'];
              $itemRev    = (float)$item['revenue'];
              $itemMargin = $itemRev > 0 ? round($itemProfit / $itemRev * 100, 1) : 0;
              $hasCost    = $item['unit_cost'] !== null && $item['unit_cost'] > 0;
            ?>
              <tr>
                <td class="fw-semibold"><?= esc($item['product_name']) ?></td>
                <td class="text-center">
                  <span class="badge bg-primary"><?= $item['qty_sold'] ?></span>
                </td>
                <td class="text-end text-muted">
                  <?php if ($hasCost): ?>
                    ฿<?= number_format($item['unit_cost'], 2) ?>
                  <?php else: ?>
                    <span class="text-warning small">ไม่ระบุ</span>
                  <?php endif; ?>
                </td>
                <td class="text-end fw-semibold text-success">฿<?= number_format($itemRev, 2) ?></td>
                <td class="text-end text-danger">฿<?= number_format($item['total_cost'], 2) ?></td>
                <td class="text-end fw-bold <?= $itemProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                  <?= $itemProfit >= 0 ? '' : '-' ?>฿<?= number_format(abs($itemProfit), 2) ?>
                </td>
                <td class="text-center">
                  <?php if (! $hasCost): ?>
                    <span class="badge bg-warning text-dark">ไม่มีต้นทุน</span>
                  <?php elseif ($itemMargin >= 20): ?>
                    <span class="badge bg-success"><?= $itemMargin ?>%</span>
                  <?php elseif ($itemMargin >= 0): ?>
                    <span class="badge bg-warning text-dark"><?= $itemMargin ?>%</span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?= $itemMargin ?>%</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (! empty($items)): ?>
          <tfoot class="table-light">
            <tr class="fw-bold">
              <td>รวมทั้งหมด</td>
              <td class="text-center"><?= array_sum(array_column($items, 'qty_sold')) ?></td>
              <td></td>
              <td class="text-end text-success">฿<?= number_format($summary['revenue'] ?? 0, 2) ?></td>
              <td class="text-end text-danger">฿<?= number_format($summary['total_cost'] ?? 0, 2) ?></td>
              <td class="text-end <?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= $profit >= 0 ? '' : '-' ?>฿<?= number_format(abs($profit), 2) ?>
              </td>
              <td class="text-center">
                <span class="badge bg-<?= $margin >= 20 ? 'success' : ($margin >= 0 ? 'warning text-dark' : 'danger') ?>">
                  <?= $margin ?>%
                </span>
              </td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
