<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">

    <!-- User info card -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body text-center py-4">
        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
             style="width:72px;height:72px">
          <i class="bi bi-person-fill fs-2 text-white"></i>
        </div>
        <h5 class="fw-bold mb-0"><?= esc($user['full_name']) ?></h5>
        <div class="text-muted small">@<?= esc($user['username']) ?></div>
        <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> mt-2">
          <?= $user['role'] === 'admin' ? 'Admin' : 'Cashier' ?>
        </span>
      </div>
    </div>

    <!-- Change password card -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-key-fill me-2 text-warning"></i>เปลี่ยนรหัสผ่าน</h6>
      </div>
      <div class="card-body">
        <form action="<?= site_url('/profile/save') ?>" method="post">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">รหัสผ่านเดิม</label>
            <input type="password" name="old_password" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">รหัสผ่านใหม่</label>
            <input type="password" name="new_password" class="form-control"
                   minlength="6" required placeholder="อย่างน้อย 6 ตัวอักษร">
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" name="confirm" class="form-control" required>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle me-1"></i>บันทึก
            </button>
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?= view('templates/footer') ?>
