<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ — <?= esc(setting('shop_name', 'POS System')) ?></title>
  <link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">
  <style>
    body {
      background: #0f172a;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Sarabun', sans-serif;
    }
    .login-card {
      background: #1e293b;
      border-radius: 1rem;
      border: 1px solid #334155;
      padding: 2.5rem;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 60px rgba(0,0,0,.5);
    }
    .login-logo {
      text-align: center;
      margin-bottom: 2rem;
    }
    .login-logo .icon {
      width: 64px; height: 64px;
      background: #2563eb;
      border-radius: 1rem;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 2rem; color: #fff; margin-bottom: .75rem;
    }
    .login-logo h5 { color: #e2e8f0; font-weight: 700; margin: 0; }
    .login-logo small { color: #64748b; }
    .form-control {
      background: #0f172a; border: 1px solid #334155;
      color: #e2e8f0; border-radius: .5rem;
    }
    .form-control:focus {
      background: #0f172a; color: #e2e8f0;
      border-color: #2563eb; box-shadow: 0 0 0 3px #2563eb33;
    }
    .form-label { color: #94a3b8; font-size: .875rem; }
    .btn-login {
      background: #2563eb; border: none; color: #fff;
      border-radius: .5rem; padding: .75rem;
      font-weight: 700; font-size: 1rem;
      width: 100%; transition: background .2s;
    }
    .btn-login:hover { background: #1d4ed8; }
    .form-control::placeholder { color: #475569; }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-logo">
    <div class="icon"><i class="bi bi-bag-check-fill"></i></div>
    <h5><?= esc(setting('shop_name', 'POS System')) ?></h5>
    <small>ระบบจัดการร้านค้า</small>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2 mb-3" style="background:#3b0f0f;border-color:#ef4444;color:#fca5a5">
      <i class="bi bi-exclamation-triangle me-1"></i>
      <?= esc(session()->getFlashdata('error')) ?>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2 mb-3">
      <?= esc(session()->getFlashdata('success')) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('/login') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label">ชื่อผู้ใช้</label>
      <div class="input-group">
        <span class="input-group-text" style="background:#0f172a;border-color:#334155;color:#64748b">
          <i class="bi bi-person"></i>
        </span>
        <input type="text" name="username" class="form-control"
               placeholder="username" autocomplete="username"
               value="<?= old('username') ?>" required autofocus>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label">รหัสผ่าน</label>
      <div class="input-group">
        <span class="input-group-text" style="background:#0f172a;border-color:#334155;color:#64748b">
          <i class="bi bi-lock"></i>
        </span>
        <input type="password" name="password" class="form-control"
               placeholder="••••••••" autocomplete="current-password" required>
      </div>
    </div>

    <button type="submit" class="btn-login">
      <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
    </button>
  </form>
</div>

<script src="<?= base_url('assets/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
