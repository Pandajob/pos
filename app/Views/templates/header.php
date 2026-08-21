<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title><?= esc($title ?? 'POS System') ?> - ร้านค้า</title>

  <!-- Bootstrap 5 CDN -->
  <link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">

  <style>
    :root {
      --sidebar-w: 240px;
      --primary:   #2563eb;
      --sidebar-bg:#1e293b;
      --sidebar-hover:#334155;
    }
    body { background:#f1f5f9; font-family:'Sarabun', sans-serif; }

    /* ── Sidebar ── */
    #sidebar {
      width: var(--sidebar-w);
      height: 100vh;              /* สูงเท่าจอพอดี (เดิม min-height ทำให้เมนูทะลุจอ กดไม่ได้) */
      height: 100dvh;
      overflow-y: auto;           /* เมนูยาวกว่าจอ = เลื่อนในแถบเมนูได้ ไม่ต้อง zoom out */
      overflow-x: hidden;
      background: var(--sidebar-bg);
      position: fixed;
      top: 0; left: 0;
      z-index: 1000;
      transition: width .25s;
      padding-bottom: 1rem;       /* กันรายการสุดท้ายชนขอบจอ */
      scrollbar-width: thin;
      scrollbar-color: #475569 transparent;
    }
    #sidebar::-webkit-scrollbar { width: 6px; }
    #sidebar::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
    #sidebar::-webkit-scrollbar-track { background: transparent; }
    #sidebar .brand {
      padding: 1rem;
      color: #fff;
      font-size: 1.15rem;
      font-weight: 700;
      border-bottom: 1px solid #334155;
      display: flex; align-items: center; gap: .5rem;
    }
    #sidebar .nav-link {
      color: #94a3b8;
      padding: .5rem 1rem;
      display: flex; align-items: center; gap: .6rem;
      border-radius: .375rem;
      margin: .1rem .5rem;
      transition: all .15s;
      font-size: .95rem;
      white-space: nowrap;
    }
    #sidebar .nav-link:hover,
    #sidebar .nav-link.active {
      background: var(--sidebar-hover);
      color: #fff;
    }
    #sidebar .nav-link i { font-size: 1.1rem; width: 1.4rem; text-align: center; }
    #sidebar .section-label {
      font-size: .7rem;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: #475569;
      padding: .5rem 1.5rem .2rem;
    }

    /* ── Main area ── */
    #main { margin-left: var(--sidebar-w); min-height: 100vh; }

    /* ── Topbar ── */
    #topbar {
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      padding: .75rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 900;
    }
    #topbar .page-title { font-weight: 600; font-size: 1.1rem; color: #1e293b; }

    /* ── Content wrapper ── */
    .content-wrapper { padding: 1.5rem; }

    /* ── Cards ── */
    .stat-card { border: none; border-radius: .75rem; }
    .stat-card .card-body { padding: 1.25rem; }

    /* ── Alert flash ── */
    .flash-container { position: fixed; top: 1rem; right: 1rem; z-index: 9999; width: 320px; }

    /* ── Table styles ── */
    .table th { background: #f8fafc; font-weight: 600; font-size: .85rem; }
    .table-hover tbody tr:hover { background: #f0f9ff; }

    /* ── Barcode indicator ── */
    #barcode-indicator {
      position: fixed;
      bottom: 1.5rem; right: 1.5rem;
      background: #1e293b;
      color: #fff;
      padding: .5rem 1rem;
      border-radius: 2rem;
      font-size: .8rem;
      display: none;
      z-index: 5000;
    }
    #barcode-indicator.active { display: block; }

    /* ── ปุ่มเปิด/ปิดเมนู (โชว์เฉพาะจอแคบ) ── */
    #sidebar-toggle { display: none; }

    @media (max-width: 768px) {
      #sidebar { width: 0; overflow: hidden; padding-bottom: 0; }
      #sidebar.open { width: var(--sidebar-w); overflow-y: auto; padding-bottom: 1rem; box-shadow: 0 0 0 100vmax rgba(0,0,0,.35); }
      #main    { margin-left: 0; }
      #sidebar-toggle { display: inline-flex; }
    }
  </style>

  <?= $extraHead ?? '' ?>
</head>
<body>

<!-- ══════════════════════════════ SIDEBAR ══════════════════════════════ -->
<?php $authUser = session()->get('auth_user'); $isAdmin = $authUser && $authUser['role'] === 'admin'; ?>
<nav id="sidebar">
  <div class="brand">
    <i class="bi bi-bag-check-fill text-primary"></i>
    <span><?= esc(setting('shop_name', 'POS System')) ?></span>
  </div>

  <div class="section-label mt-3">เมนูหลัก</div>

  <a href="<?= site_url('/') ?>"
     class="nav-link <?= (uri_string() === '' || uri_string() === 'dashboard') ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> แดชบอร์ด
  </a>

  <a href="<?= site_url('/sales') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'sales') ? 'active' : '' ?>">
    <i class="bi bi-cart3"></i> หน้าขาย (POS)
  </a>

  <a href="<?= site_url('/wholesale') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'wholesale') ? 'active' : '' ?>">
    <i class="bi bi-file-earmark-text"></i> ขายส่ง / ใบเสนอราคา
  </a>

  <div class="section-label">จัดการ</div>

  <?php if ($isAdmin): ?>
  <a href="<?= site_url('/products') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'products') ? 'active' : '' ?>">
    <i class="bi bi-box-seam"></i> สินค้า
  </a>
  <?php endif; ?>

  <a href="<?= site_url('/members') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'members') ? 'active' : '' ?>">
    <i class="bi bi-people-fill"></i> สมาชิก
  </a>

  <?php if ($isAdmin): ?>
  <a href="<?= site_url('/reports/daily') ?>"
     class="nav-link <?= uri_string() === 'reports/daily' ? 'active' : '' ?>">
    <i class="bi bi-bar-chart-line"></i> รายงานยอดขาย
  </a>
  <a href="<?= site_url('/reports/profit') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'reports/profit') ? 'active' : '' ?>">
    <i class="bi bi-graph-up-arrow"></i> รายงานกำไร
  </a>
  <a href="<?= site_url('/reports/monthly') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'reports/monthly') ? 'active' : '' ?>">
    <i class="bi bi-calendar3"></i> รายงานรายเดือน
  </a>
  <a href="<?= site_url('/reports/search') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'reports/search') ? 'active' : '' ?>">
    <i class="bi bi-search"></i> ค้นหาบิล
  </a>
  <a href="<?= site_url('/stock') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'stock') ? 'active' : '' ?>">
    <i class="bi bi-boxes"></i> จัดการสต๊อก
  </a>
  <?php endif; ?>

  <?php $pendingRtn = pendingReturnsCount(); ?>
  <a href="<?= site_url('/returns') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'returns') ? 'active' : '' ?>">
    <i class="bi bi-arrow-return-left"></i> คืนสินค้า
    <?php if ($pendingRtn > 0): ?>
      <span class="badge bg-danger ms-auto"><?= $pendingRtn ?></span>
    <?php endif; ?>
  </a>

  <?php $creditCnt = outstandingCreditCount(); ?>
  <a href="<?= site_url('/credit') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'credit') ? 'active' : '' ?>">
    <i class="bi bi-journal-text"></i> ค้างชำระ / เงินเชื่อ
    <?php if ($creditCnt > 0): ?>
      <span class="badge bg-warning text-dark ms-auto"><?= $creditCnt ?></span>
    <?php endif; ?>
  </a>

  <a href="<?= site_url('/cash-drawer') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'cash-drawer') ? 'active' : '' ?>">
    <i class="bi bi-safe2-fill"></i> ลิ้นชักเงิน
  </a>

  <?php if ($isAdmin): ?>
  <div class="section-label">ระบบ</div>

  <a href="<?= site_url('/settings') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'settings') ? 'active' : '' ?>">
    <i class="bi bi-gear-fill"></i> ตั้งค่าระบบ
  </a>

  <a href="<?= site_url('/users') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'users') ? 'active' : '' ?>">
    <i class="bi bi-people"></i> จัดการผู้ใช้
  </a>
  <a href="<?= site_url('/tiers') ?>"
     class="nav-link <?= str_starts_with(uri_string(), 'tiers') ? 'active' : '' ?>">
    <i class="bi bi-award-fill"></i> ระดับสมาชิก
  </a>
  <?php endif; ?>

  <a href="<?= site_url('/customer-display') ?>" target="_blank"
     class="nav-link">
    <i class="bi bi-display"></i> หน้าจอลูกค้า
    <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.7rem;opacity:.5"></i>
  </a>

  <div class="section-label">ข้อมูล</div>
  <div class="nav-link" style="cursor:default;color:#475569;font-size:.8rem;">
    <i class="bi bi-clock"></i>
    <span id="sidebar-clock"></span>
  </div>
</nav>

<!-- ══════════════════════════════ MAIN ══════════════════════════════ -->
<div id="main">
  <div id="topbar">
    <div class="d-flex align-items-center gap-2">
      <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary" type="button"
              onclick="document.getElementById('sidebar').classList.toggle('open')"
              title="เปิด/ปิดเมนู">
        <i class="bi bi-list"></i>
      </button>
      <span class="page-title"><?= esc($title ?? '') ?></span>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="badge bg-success">
        <i class="bi bi-circle-fill" style="font-size:.5rem"></i> ONLINE
      </span>
      <span class="text-muted small" id="topbar-date"></span>
      <?php if ($authUser): ?>
        <span class="badge <?= $isAdmin ? 'bg-danger' : 'bg-primary' ?> text-white">
          <?= $isAdmin ? 'Admin' : 'Cashier' ?>
        </span>
        <span class="fw-semibold text-dark small d-none d-md-inline"><?= esc($authUser['full_name']) ?></span>
        <?php if ($isAdmin): ?>
        <a href="<?= site_url('/users') ?>" class="btn btn-sm btn-outline-secondary" title="จัดการผู้ใช้">
          <i class="bi bi-person-gear"></i>
        </a>
        <?php else: ?>
        <a href="<?= site_url('/profile') ?>" class="btn btn-sm btn-outline-secondary" title="โปรไฟล์">
          <i class="bi bi-person-gear"></i>
        </a>
        <?php endif; ?>
        <a href="<?= site_url('/logout') ?>" class="btn btn-sm btn-outline-danger" title="ออกจากระบบ">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flash messages -->
  <div class="flash-container">
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Barcode scanner status indicator -->
  <div id="barcode-indicator">
    <i class="bi bi-upc-scan me-1"></i> กำลังอ่านบาร์โค้ด...
  </div>

  <div class="content-wrapper">
