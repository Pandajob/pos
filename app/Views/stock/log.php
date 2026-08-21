<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="mb-0 fw-bold">ประวัติการปรับสต๊อก</h5>
    <?php if ($product): ?>
      <small class="text-muted"><?= esc($product['name']) ?></small>
    <?php endif; ?>
  </div>
  <a href="<?= site_url('/stock') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>กลับ
  </a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>วันที่ / เวลา</th>
            <th>สินค้า</th>
            <th class="text-center">ประเภท</th>
            <th class="text-center">ก่อน</th>
            <th class="text-center">เปลี่ยน</th>
            <th class="text-center">หลัง</th>
            <th>หมายเหตุ</th>
            <th>โดย</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">ยังไม่มีประวัติ</td></tr>
          <?php else: ?>
            <?php foreach ($logs as $l):
              $cfg = [
                'in'     => ['bg-success', 'รับเข้า',   '+'],
                'out'    => ['bg-danger',  'ตัดออก',    '-'],
                'adjust' => ['bg-secondary','ปรับ',     '±'],
              ][$l['type']] ?? ['bg-light text-dark', $l['type'], ''];
              $change = ($l['qty_change'] > 0 ? '+' : '') . $l['qty_change'];
              $changeClass = $l['qty_change'] > 0 ? 'text-success fw-bold' : ($l['qty_change'] < 0 ? 'text-danger fw-bold' : 'text-muted');
            ?>
              <tr>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                <td>
                  <div class="fw-semibold small"><?= esc($l['product_name']) ?></div>
                </td>
                <td class="text-center">
                  <span class="badge <?= $cfg[0] ?>"><?= $cfg[1] ?></span>
                </td>
                <td class="text-center text-muted"><?= $l['qty_before'] ?></td>
                <td class="text-center <?= $changeClass ?>"><?= $change ?></td>
                <td class="text-center fw-bold"><?= $l['qty_after'] ?></td>
                <td><small class="text-muted"><?= esc($l['note'] ?? '') ?></small></td>
                <td><small class="text-muted"><?= esc($l['created_by'] ?? '') ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
