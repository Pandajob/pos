<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>จัดการผู้ใช้งาน</h5>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
    <i class="bi bi-person-plus me-1"></i>เพิ่มผู้ใช้
  </button>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>ชื่อ</th>
              <th>Username</th>
              <th class="text-center">สิทธิ์</th>
              <th class="text-center">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td class="fw-semibold"><?= esc($u['full_name']) ?></td>
                <td class="text-muted"><?= esc($u['username']) ?></td>
                <td class="text-center">
                  <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                    <?= $u['role'] === 'admin' ? 'Admin' : 'Cashier' ?>
                  </span>
                </td>
                <td class="text-center">
                  <?php $auth = session()->get('auth_user'); ?>
                  <?php if ($u['id'] !== $auth['id']): ?>
                    <form method="post" action="<?= site_url('/users/delete/' . $u['id']) ?>"
                          class="d-inline" onsubmit="return confirm('ลบผู้ใช้ <?= esc($u['full_name']) ?>?')">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  <?php else: ?>
                    <span class="badge bg-light text-dark">ตัวเอง</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- เปลี่ยนรหัสผ่าน -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold py-3">
        <i class="bi bi-key me-2 text-warning"></i>เปลี่ยนรหัสผ่านของฉัน
      </div>
      <div class="card-body">
        <form method="post" action="<?= site_url('/users/change-password') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">รหัสผ่านเดิม</label>
            <input type="password" name="old_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">รหัสผ่านใหม่</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" name="confirm" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-warning w-100">
            <i class="bi bi-key me-1"></i>เปลี่ยนรหัสผ่าน
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal เพิ่ม User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>เพิ่มผู้ใช้งาน</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= site_url('/users/create') ?>">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">ชื่อ-นามสกุล</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">รหัสผ่าน (อย่างน้อย 6 ตัว)</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-0">
            <label class="form-label fw-semibold">สิทธิ์</label>
            <select name="role" class="form-select">
              <option value="cashier">Cashier — พนักงานแคชเชียร์</option>
              <option value="admin">Admin — ผู้ดูแลระบบ</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">เพิ่มผู้ใช้</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
