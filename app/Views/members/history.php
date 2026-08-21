<?= view('templates/header', ['title' => $title]) ?>

<!-- Member card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
           style="width:52px;height:52px;font-size:1.4rem">
        <?= mb_substr($member['name'], 0, 1) ?>
      </div>
      <div class="flex-fill">
        <div class="fw-bold fs-5"><?= esc($member['name']) ?></div>
        <div class="text-muted small">
          <i class="bi bi-telephone me-1"></i><?= esc($member['phone']) ?>
          &nbsp;·&nbsp;
          <i class="bi bi-hash me-1"></i><?= esc($member['code']) ?>
        </div>
      </div>
      <div class="text-center">
        <div class="fw-bold text-warning fs-4">
          <i class="bi bi-star-fill me-1"></i><?= number_format($member['points']) ?>
        </div>
        <div class="text-muted small">แต้มสะสม</div>
      </div>
      <div class="text-center">
        <div class="fw-bold text-success fs-4">฿<?= number_format($totalSpent, 2) ?></div>
        <div class="text-muted small">ยอดซื้อสะสม</div>
      </div>
      <div class="text-center">
        <div class="fw-bold text-primary fs-4"><?= count(array_filter($history, fn($r) => empty($r['voided_at']))) ?></div>
        <div class="text-muted small">ครั้งที่ซื้อ</div>
      </div>
    </div>
  </div>
</div>

<!-- History table -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-clock-history me-2"></i>ประวัติการซื้อ
      <span class="badge bg-secondary ms-1"><?= count($history) ?> รายการ</span>
    </h6>
    <a href="<?= site_url('/members') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>กลับ
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>เลขบิล</th>
            <th>วันที่</th>
            <th>รายการสินค้า</th>
            <th class="text-center">จำนวน</th>
            <th class="text-end">ยอดรวม</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">ดู</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($history)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-cart-x fs-2 d-block mb-2"></i>ยังไม่มีประวัติการซื้อ
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($history as $h): $voided = !empty($h['voided_at']); ?>
              <tr class="<?= $voided ? 'table-danger opacity-75' : '' ?>">
                <td>
                  <a href="<?= site_url('/receipt/' . $h['sale_id']) ?>" target="_blank"
                     class="fw-semibold text-decoration-none <?= $voided ? 'text-decoration-line-through text-muted' : '' ?>">
                    <?= esc($h['bill_number']) ?>
                  </a>
                </td>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                <td>
                  <small class="text-muted"><?= esc(mb_strimwidth($h['items_summary'] ?? '', 0, 70, '...')) ?></small>
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-dark"><?= $h['item_count'] ?> รายการ</span>
                </td>
                <td class="text-end fw-bold <?= $voided ? 'text-decoration-line-through text-muted' : 'text-success' ?>">
                  ฿<?= number_format($h['total_amount'], 2) ?>
                </td>
                <td class="text-center">
                  <?= $voided
                    ? '<span class="badge bg-danger">ยกเลิก</span>'
                    : '<span class="badge bg-success">ปกติ</span>' ?>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('/receipt/' . $h['sale_id']) ?>" target="_blank"
                     class="btn btn-sm btn-outline-primary">
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

<?= view('templates/footer') ?>
