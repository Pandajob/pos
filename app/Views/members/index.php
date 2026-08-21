<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>จัดการสมาชิก</h5>
  <a href="<?= site_url('/members/create') ?>" class="btn btn-primary">
    <i class="bi bi-person-plus me-1"></i>เพิ่มสมาชิก
  </a>
</div>

<form method="get" class="mb-3">
  <div class="input-group" style="max-width:420px">
    <input type="text" name="q" class="form-control" placeholder="ค้นหาชื่อ, เบอร์โทร, รหัสสมาชิก..."
           value="<?= esc($q) ?>" autocomplete="off">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
    <?php if ($q): ?>
      <a href="<?= site_url('/members') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
    <?php endif; ?>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>รหัส</th>
          <th>ชื่อ-นามสกุล</th>
          <th>เบอร์โทร</th>
          <th class="text-center">ระดับ</th>
          <th class="text-center">แต้มสะสม</th>
          <th class="text-center">วันที่สมัคร</th>
          <th class="text-center">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($members)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              <i class="bi bi-people fs-2 d-block mb-2"></i>
              <?= $q ? 'ไม่พบสมาชิกที่ตรงกัน' : 'ยังไม่มีสมาชิก' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($members as $m): ?>
            <tr>
              <td><span class="badge bg-secondary"><?= esc($m['code']) ?></span></td>
              <td class="fw-semibold"><?= esc($m['name']) ?></td>
              <td><?= esc($m['phone'] ?? '-') ?></td>
              <td class="text-center">
                <?php if (!empty($m['tier_name'])): ?>
                  <span class="badge bg-<?= esc($m['badge_color'] ?? 'secondary') ?>">
                    <?= esc($m['tier_name']) ?>
                    <?php if (($m['tier_discount'] ?? 0) > 0): ?>
                      -<?= $m['tier_discount'] ?>%
                    <?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge bg-warning text-dark">
                  <i class="bi bi-star-fill me-1"></i><?= number_format($m['points']) ?>
                </span>
              </td>
              <td class="text-center text-muted small">
                <?= date('d/m/Y', strtotime($m['created_at'])) ?>
              </td>
              <td class="text-center">
                <a href="<?= site_url('/members/history/' . $m['id']) ?>"
                   class="btn btn-sm btn-outline-info me-1" title="ประวัติการซื้อ">
                  <i class="bi bi-clock-history"></i>
                </a>
                <a href="<?= site_url('/members/edit/' . $m['id']) ?>"
                   class="btn btn-sm btn-outline-primary me-1">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" action="<?= site_url('/members/delete/' . $m['id']) ?>"
                      class="d-inline"
                      onsubmit="return confirm('ลบสมาชิก <?= esc($m['name']) ?>?')">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer text-muted small">
    ทั้งหมด <?= number_format(count($members)) ?> รายการ
  </div>
</div>

<?= view('templates/footer') ?>
