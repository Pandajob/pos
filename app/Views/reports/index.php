<?= view('templates/header', ['title' => $title]) ?>

<!-- Date picker + summary -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <form method="get" action="<?= site_url('/reports/daily') ?>" class="d-flex gap-2">
          <input type="date" name="date" value="<?= esc($date) ?>" class="form-control" max="<?= date('Y-m-d') ?>">
          <button type="submit" class="btn btn-primary px-3">
            <i class="bi bi-search"></i>
          </button>
        </form>
        <div class="mt-2 d-flex gap-2">
          <a href="<?= site_url('/reports/daily?date=' . date('Y-m-d')) ?>"
             class="btn btn-sm btn-outline-secondary <?= $date === date('Y-m-d') ? 'active' : '' ?>">วันนี้</a>
          <a href="<?= site_url('/reports/daily?date=' . date('Y-m-d', strtotime('-1 day'))) ?>"
             class="btn btn-sm btn-outline-secondary">เมื่อวาน</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="col-md-3 col-lg-4">
    <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">รายได้รวม</div>
        <div class="fs-3 fw-bold text-success">
          ฿<?= number_format($summary['total_revenue'] ?? 0, 2) ?>
        </div>
        <div class="text-muted small"><?= date('d/m/Y', strtotime($date)) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-lg-4">
    <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <div class="text-muted small">จำนวนบิล</div>
        <div class="fs-3 fw-bold text-primary"><?= $summary['total_bills'] ?? 0 ?> บิล</div>
        <?php if (($summary['total_bills'] ?? 0) > 0): ?>
          <div class="text-muted small">
            เฉลี่ย ฿<?= number_format(($summary['total_revenue'] ?? 0) / $summary['total_bills'], 2) ?>/บิล
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Bills table -->
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-receipt me-2"></i>รายการบิล
          <span class="badge bg-secondary ms-1"><?= count($sales) ?></span>
        </h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>เลขบิล</th>
                <th>เวลา</th>
                <th>พนักงาน</th>
                <th class="text-center">รายการ</th>
                <th class="text-end">ยอดรวม</th>
                <th class="text-end">เงินทอน</th>
                <th class="text-center">ดู</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($sales)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    ไม่มียอดขายวันที่ <?= date('d/m/Y', strtotime($date)) ?>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($sales as $sale): ?>
                  <tr>
                    <td>
                      <a href="<?= site_url('/receipt/' . $sale['id']) ?>" target="_blank"
                         class="fw-semibold text-decoration-none">
                        <?= esc($sale['bill_number']) ?>
                      </a>
                    </td>
                    <td class="text-muted"><?= date('H:i', strtotime($sale['created_at'])) ?></td>
                    <td><?= esc($sale['cashier']) ?></td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark"><?= count($sale['items']) ?> รายการ</span>
                    </td>
                    <td class="text-end fw-bold">฿<?= number_format($sale['total_amount'], 2) ?></td>
                    <td class="text-end text-muted">฿<?= number_format($sale['change_amount'], 2) ?></td>
                    <td class="text-center">
                      <a href="<?= site_url('/receipt/' . $sale['id']) ?>" target="_blank"
                         class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-printer"></i>
                      </a>
                    </td>
                  </tr>
                  <!-- Expandable items -->
                  <?php if (!empty($sale['items'])): ?>
                    <tr class="table-light">
                      <td colspan="7" class="py-1 ps-4">
                        <small class="text-muted">
                          <?= implode(' &bull; ', array_map(fn($i) =>
                            esc($i['product_name']) . ' x' . $i['quantity'],
                            $sale['items']
                          )) ?>
                        </small>
                      </td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <?php if (!empty($sales)): ?>
              <tfoot>
                <tr class="table-light">
                  <td colspan="4" class="fw-bold text-end">รวมทั้งหมด</td>
                  <td class="text-end fw-bold text-success">
                    ฿<?= number_format($summary['total_revenue'] ?? 0, 2) ?>
                  </td>
                  <td colspan="2"></td>
                </tr>
              </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Top products -->
  <div class="col-lg-4">
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-trophy me-2 text-warning"></i>สินค้าขายดี</h6>
      </div>
      <div class="card-body p-0">
        <?php if (empty($topProducts)): ?>
          <div class="text-center text-muted py-4">ไม่มีข้อมูล</div>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($topProducts as $i => $tp): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge <?= $i === 0 ? 'bg-warning text-dark' : 'bg-secondary' ?> rounded-circle"
                        style="width:22px;height:22px;display:flex;align-items:center;justify-content:center">
                    <?= $i + 1 ?>
                  </span>
                  <div>
                    <div class="fw-semibold small"><?= esc($tp['product_name']) ?></div>
                    <div class="text-muted" style="font-size:.75rem">
                      ฿<?= number_format($tp['total_revenue'], 2) ?>
                    </div>
                  </div>
                </div>
                <span class="badge bg-primary"><?= $tp['total_qty'] ?> ชิ้น</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Export + actions -->
    <div class="card shadow-sm border-0">
      <div class="card-body py-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-download me-2 text-success"></i>ส่งออกข้อมูล</h6>
        <div class="d-grid gap-2">
          <a href="<?= site_url('/reports/export?type=sales&from=' . $date . '&to=' . $date) ?>"
             class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export ยอดขาย CSV
          </a>
          <a href="<?= site_url('/reports/export?type=profit&from=' . $date . '&to=' . $date) ?>"
             class="btn btn-outline-info btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export กำไร CSV
          </a>
          <a href="<?= site_url('/reports/search?from=' . $date . '&to=' . $date) ?>"
             class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i>ค้นหาบิลขั้นสูง
          </a>
          <hr class="my-1">
          <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>พิมพ์หน้านี้
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
