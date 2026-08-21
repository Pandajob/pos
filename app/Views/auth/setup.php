<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ตั้งค่าเริ่มต้น — POS System</title>
  <link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <style>
    body { background:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Sarabun',sans-serif; }
    .setup-card { background:#1e293b; border-radius:1rem; border:1px solid #334155; padding:2.5rem; width:100%; max-width:440px; box-shadow:0 20px 60px rgba(0,0,0,.5); }
    .form-control { background:#0f172a; border:1px solid #334155; color:#e2e8f0; }
    .form-control:focus { background:#0f172a; color:#e2e8f0; border-color:#2563eb; box-shadow:0 0 0 3px #2563eb33; }
    .form-label { color:#94a3b8; font-size:.875rem; }
    .form-control::placeholder { color:#475569; }
  </style>
</head>
<body>
<div class="setup-card">
  <div class="text-center mb-4">
    <div class="mb-2" style="font-size:3rem">🛒</div>
    <h5 class="text-white fw-bold mb-1">ตั้งค่าระบบครั้งแรก</h5>
    <small class="text-muted">สร้างบัญชีผู้ดูแลระบบ (Admin)</small>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2 mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('/setup') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">ชื่อ-นามสกุล</label>
      <input type="text" name="full_name" class="form-control" placeholder="เช่น สมชาย ใจดี" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">ชื่อผู้ใช้ (username)</label>
      <input type="text" name="username" class="form-control" placeholder="admin" required>
    </div>
    <div class="mb-3">
      <label class="form-label">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <div class="mb-4">
      <label class="form-label">ยืนยันรหัสผ่าน</label>
      <input type="password" name="confirm" class="form-control" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
      <i class="bi bi-shield-check me-2"></i>สร้างบัญชีและเริ่มใช้งาน
    </button>
  </form>
</div>
</body>
</html>
