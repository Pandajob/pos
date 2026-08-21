<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="mb-0 fw-bold">จัดการสต๊อกสินค้า</h5>
    <small class="text-muted"><?= count($products) ?> รายการ</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('/stock/receive') ?>" class="btn btn-success btn-sm">
      <i class="bi bi-upc-scan me-1"></i>รับสินค้าเข้าสต๊อก
    </a>
    <a href="<?= site_url('/stock/log') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-clock-history me-1"></i>ประวัติการปรับสต๊อก
    </a>
  </div>
</div>

<!-- Search + Filter -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <form method="get" action="<?= site_url('/stock') ?>" class="row g-2">
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="search" value="<?= esc($search) ?>"
                 class="form-control" placeholder="ค้นหาชื่อสินค้าหรือบาร์โค้ด...">
        </div>
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select">
          <option value="">-- ทุกหมวดหมู่ --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= esc($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary flex-fill" type="submit"><i class="bi bi-search me-1"></i>ค้นหา</button>
        <a href="<?= site_url('/stock') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Product List -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>สินค้า</th>
            <th>หมวดหมู่</th>
            <th class="text-center">สต๊อกปัจจุบัน</th>
            <th class="text-center" style="width:260px">ปรับสต๊อก</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="4" class="text-center text-muted py-5">ไม่พบสินค้า</td></tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
              <tr id="row-<?= $p['id'] ?>">
                <td>
                  <div class="fw-semibold"><?= esc($p['name']) ?></div>
                  <?php if ($p['barcode']): ?>
                    <small class="text-muted font-monospace"><?= esc($p['barcode']) ?></small>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-light text-dark"><?= esc($p['category'] ?? '-') ?></span></td>
                <td class="text-center">
                  <span id="stock-<?= $p['id'] ?>"
                        class="badge fs-6 <?= $p['stock'] == 0 ? 'bg-danger' : ($p['stock'] <= $threshold ? 'bg-warning text-dark' : 'bg-success') ?>">
                    <?= $p['stock'] ?>
                  </span>
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-success"
                          onclick="openAdjust(<?= $p['id'] ?>, '<?= esc($p['name'], 'js') ?>', <?= $p['stock'] ?>, 'in')">
                    <i class="bi bi-plus-lg"></i> รับเข้า
                  </button>
                  <button class="btn btn-sm btn-outline-warning"
                          onclick="openAdjust(<?= $p['id'] ?>, '<?= esc($p['name'], 'js') ?>', <?= $p['stock'] ?>, 'out')">
                    <i class="bi bi-dash-lg"></i> ตัดออก
                  </button>
                  <button class="btn btn-sm btn-outline-secondary"
                          onclick="openAdjust(<?= $p['id'] ?>, '<?= esc($p['name'], 'js') ?>', <?= $p['stock'] ?>, 'adjust')">
                    <i class="bi bi-pencil"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header" id="adj-header">
        <h6 class="modal-title" id="adj-title">ปรับสต๊อก</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 text-muted small" id="adj-product-name"></div>
        <div class="mb-3">
          <label class="form-label fw-semibold small" id="adj-qty-label">จำนวน</label>
          <input type="number" id="adj-qty" class="form-control form-control-lg text-center fw-bold"
                 value="1" min="0">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold small">หมายเหตุ</label>
          <input type="text" id="adj-note" class="form-control" placeholder="เช่น รับสินค้าล็อตใหม่">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="adj-confirm">
          <i class="bi bi-check-lg me-1"></i>ยืนยัน
        </button>
      </div>
    </div>
  </div>
</div>

<!-- BASE ต้องประกาศนอก nowdoc เพราะ <<<'JS' ไม่ประมวลผล PHP -->
<script>window.__POS_BASE = '<?= base_url() ?>';</script>
<?php $extraScript = <<<'JS'
<script>
const BASE = window.__POS_BASE;
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let adjData = {};
const adjModal = new bootstrap.Modal(document.getElementById('adjustModal'));

const typeConfig = {
  in:     { title:'รับสินค้าเข้า', label:'จำนวนที่รับเข้า', color:'success', icon:'bi-plus-lg', btnClass:'btn-success' },
  out:    { title:'ตัดสต๊อกออก',   label:'จำนวนที่ตัดออก',   color:'warning', icon:'bi-dash-lg', btnClass:'btn-warning' },
  adjust: { title:'ปรับสต๊อก',     label:'สต๊อกใหม่ (ตั้งค่าตรง)', color:'secondary', icon:'bi-pencil', btnClass:'btn-primary' },
};

function openAdjust(id, name, currentStock, type) {
  adjData = { id, name, currentStock, type };
  const cfg = typeConfig[type];
  document.getElementById('adj-title').textContent = cfg.title;
  document.getElementById('adj-product-name').innerHTML =
    `<strong>${name}</strong> &mdash; สต๊อกปัจจุบัน: <span class="badge bg-secondary">${currentStock}</span>`;
  document.getElementById('adj-qty-label').textContent = cfg.label;
  document.getElementById('adj-qty').value = type === 'adjust' ? currentStock : 1;
  document.getElementById('adj-qty').min   = type === 'adjust' ? 0 : 1;
  document.getElementById('adj-note').value = '';
  document.getElementById('adj-header').className = `modal-header bg-${cfg.color} ${cfg.color === 'warning' ? 'text-dark' : 'text-white'}`;
  const btn = document.getElementById('adj-confirm');
  btn.className = `btn ${cfg.btnClass}`;
  adjModal.show();
  setTimeout(() => document.getElementById('adj-qty').select(), 300);
}

document.getElementById('adj-confirm').addEventListener('click', function () {
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังบันทึก...';

  const qty  = parseInt(document.getElementById('adj-qty').value) || 0;
  const note = document.getElementById('adj-note').value.trim();

  fetch(BASE + 'stock/adjust', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF() },
    body: `product_id=${adjData.id}&type=${adjData.type}&qty=${qty}&note=${encodeURIComponent(note)}&csrf_token=${CSRF()}`,
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // อัปเดต badge ในตาราง
      const badge = document.getElementById('stock-' + adjData.id);
      if (badge) {
        badge.textContent = data.new_stock;
        badge.className = 'badge fs-6 ' + (data.new_stock == 0 ? 'bg-danger' : (data.new_stock <= 10 ? 'bg-warning text-dark' : 'bg-success'));
      }
      adjModal.hide();
      // flash
      const toast = document.createElement('div');
      toast.className = 'alert alert-success position-fixed top-0 end-0 m-3 shadow';
      toast.style.zIndex = 9999;
      toast.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.message}`;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    } else {
      alert(data.message);
    }
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-check-lg me-1"></i>ยืนยัน';
  });
});
</script>
JS; ?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
