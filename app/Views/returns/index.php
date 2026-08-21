<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-0">
      <i class="bi bi-arrow-return-left me-2 text-warning"></i>รายการคืนสินค้า
    </h5>
    <?php if ($pendingCount > 0): ?>
      <span class="badge bg-danger ms-1"><?= $pendingCount ?> รอดำเนินการ</span>
    <?php endif; ?>
  </div>
</div>

<!-- Filter tabs -->
<ul class="nav nav-pills mb-3">
  <?php
    $tabs = [
      'pending'  => ['label' => 'รออนุมัติ',  'color' => 'warning'],
      'approved' => ['label' => 'อนุมัติแล้ว', 'color' => 'success'],
      'rejected' => ['label' => 'ปฏิเสธแล้ว',  'color' => 'danger'],
      ''         => ['label' => 'ทั้งหมด',     'color' => 'secondary'],
    ];
    foreach ($tabs as $val => $tab):
  ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $val ? 'active' : '' ?>"
         href="<?= site_url('/returns?status=' . $val) ?>">
        <?= $tab['label'] ?>
        <?php if ($val === 'pending' && $pendingCount > 0): ?>
          <span class="badge bg-danger ms-1"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>เลขคืน</th>
          <th>เลขบิล</th>
          <th>พนักงาน</th>
          <th>เหตุผล</th>
          <th class="text-end">ยอดคืน</th>
          <th class="text-center">สถานะ</th>
          <th class="text-center">วันที่</th>
          <th class="text-center" style="width:160px">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($returns)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-5">
              <i class="bi bi-inbox fs-2 d-block mb-2"></i>ไม่มีรายการ
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($returns as $r): ?>
            <tr>
              <td><code><?= esc($r['return_number']) ?></code></td>
              <td>
                <a href="<?= site_url('/receipt/' . $r['sale_id']) ?>" target="_blank"
                   class="badge bg-light text-dark text-decoration-none">
                  <?= esc($r['bill_number']) ?>
                </a>
              </td>
              <td><?= esc($r['cashier']) ?></td>
              <td class="text-muted small" style="max-width:200px">
                <?= esc(mb_strimwidth($r['reason'], 0, 60, '...')) ?>
              </td>
              <td class="text-end fw-bold text-danger">฿<?= number_format($r['total_refund'], 2) ?></td>
              <td class="text-center">
                <?php
                  $badge = match($r['status']) {
                    'pending'  => 'bg-warning text-dark',
                    'approved' => 'bg-success',
                    'rejected' => 'bg-danger',
                    default    => 'bg-secondary',
                  };
                  $label = match($r['status']) {
                    'pending'  => 'รออนุมัติ',
                    'approved' => 'อนุมัติแล้ว',
                    'rejected' => 'ปฏิเสธแล้ว',
                    default    => $r['status'],
                  };
                ?>
                <span class="badge <?= $badge ?>"><?= $label ?></span>
              </td>
              <td class="text-center text-muted small">
                <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
              </td>
              <td class="text-center">
                <a href="<?= site_url('/returns/detail/' . $r['id']) ?>"
                   class="btn btn-sm btn-outline-secondary me-1" title="รายละเอียด">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if ($r['status'] === 'pending'): ?>
                  <form method="post" action="<?= site_url('/returns/approve/' . $r['id']) ?>"
                        class="d-inline"
                        onsubmit="return confirm('อนุมัติการคืนสินค้า <?= esc($r['return_number']) ?>?\nสต๊อกจะถูกเพิ่มกลับเข้าระบบ')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-success me-1" title="อนุมัติ">
                      <i class="bi bi-check-lg"></i>
                    </button>
                  </form>
                  <form method="post" action="<?= site_url('/returns/reject/' . $r['id']) ?>"
                        class="d-inline"
                        onsubmit="return confirm('ปฏิเสธรายการคืนสินค้า <?= esc($r['return_number']) ?>?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" title="ปฏิเสธ">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= view('templates/footer') ?>
