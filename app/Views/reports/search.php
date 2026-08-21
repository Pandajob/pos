<?= view('templates/header', ['title' => $title]) ?>

<!-- Search form -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <form method="get" action="<?= site_url('/reports/search') ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold small">วันที่เริ่ม</label>
        <input type="date" name="from" value="<?= esc($from) ?>" class="form-control" max="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold small">วันที่สิ้นสุด</label>
        <input type="date" name="to" value="<?= esc($to) ?>" class="form-control" max="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small">ค้นหา (เลขบิล / สินค้า / สมาชิก / พนักงาน)</label>
        <input type="text" name="keyword" value="<?= esc($keyword) ?>" class="form-control" placeholder="พิมพ์คำค้นหา...">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-fill" type="submit"><i class="bi bi-search me-1"></i>ค้นหา</button>
        <a href="<?= site_url('/reports/search') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Results -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-receipt me-2"></i>ผลการค้นหา
      <span class="badge bg-secondary ms-1"><?= count($sales) ?> บิล</span>
    </h6>
    <div class="d-flex gap-2">
      <a href="<?= site_url('/reports/export?type=sales&from=' . $from . '&to=' . $to . '&keyword=' . urlencode($keyword)) ?>"
         class="btn btn-sm btn-outline-success">
        <i class="bi bi-download me-1"></i>Export CSV
      </a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>เลขบิล</th>
            <th>วันที่ / เวลา</th>
            <th>พนักงาน</th>
            <th>สมาชิก</th>
            <th>รายการสินค้า</th>
            <th class="text-end">ยอดรวม</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">ดู</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sales)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>ไม่พบรายการที่ตรงกัน
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sales as $s): ?>
              <tr class="<?= !empty($s['voided_at']) ? 'table-danger opacity-75' : '' ?>">
                <td>
                  <a href="<?= site_url('/receipt/' . $s['id']) ?>" target="_blank"
                     class="fw-semibold text-decoration-none <?= !empty($s['voided_at']) ? 'text-danger text-decoration-line-through' : '' ?>">
                    <?= esc($s['bill_number']) ?>
                  </a>
                </td>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                <td><?= esc($s['cashier']) ?></td>
                <td><?= $s['member_name'] ? '<span class="badge bg-light text-dark">' . esc($s['member_name']) . '</span>' : '<span class="text-muted">-</span>' ?></td>
                <td>
                  <small class="text-muted"><?= esc(mb_strimwidth($s['product_list'] ?? '', 0, 60, '...')) ?></small>
                </td>
                <td class="text-end fw-bold <?= !empty($s['voided_at']) ? 'text-decoration-line-through text-muted' : '' ?>">
                  ฿<?= number_format($s['total_amount'], 2) ?>
                </td>
                <td class="text-center">
                  <?php if (!empty($s['voided_at'])): ?>
                    <span class="badge bg-danger">ยกเลิก</span>
                  <?php else: ?>
                    <span class="badge bg-success">ปกติ</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('/receipt/' . $s['id']) ?>" target="_blank"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($sales)): ?>
          <?php $activeTotal = array_sum(array_column(array_filter($sales, fn($r) => empty($r['voided_at'])), 'total_amount')); ?>
          <tfoot>
            <tr class="table-light fw-bold">
              <td colspan="5" class="text-end">ยอดรวม (เฉพาะบิลปกติ)</td>
              <td class="text-end text-success">฿<?= number_format($activeTotal, 2) ?></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
