<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-upc-scan me-2 text-success"></i>รับสินค้าเข้าสต๊อก</h5>
    <small class="text-muted">สแกนบาร์โค้ดหรือพิมพ์เพื่อค้นหาสินค้า</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('/stock') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-table me-1"></i>ดูสต๊อกทั้งหมด
    </a>
    <a href="<?= site_url('/stock/log') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-clock-history me-1"></i>ประวัติ
    </a>
  </div>
</div>

<!-- Scan input -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <label class="form-label fw-semibold">สแกนบาร์โค้ดสินค้า</label>
    <div class="input-group input-group-lg">
      <span class="input-group-text bg-success text-white"><i class="bi bi-upc-scan"></i></span>
      <input type="text" id="barcode-input" class="form-control form-control-lg font-monospace"
             placeholder="สแกน หรือ พิมพ์บาร์โค้ด / ชื่อสินค้า..."
             autocomplete="off" autofocus>
      <button class="btn btn-success" id="search-btn" type="button">
        <i class="bi bi-search me-1"></i>ค้นหา
      </button>
    </div>
    <div class="form-text">กด Enter หรือคลิก "ค้นหา" — บาร์โค้ดจากเครื่องสแกนจะทำงานอัตโนมัติ</div>
  </div>
</div>

<!-- Product result card -->
<div id="product-card" class="d-none">
  <div class="card shadow-sm border-success border-2 mb-4">
    <div class="card-body">
      <div class="row align-items-center g-3">
        <div class="col-md-6">
          <div class="d-flex align-items-start gap-3">
            <div class="bg-success bg-opacity-10 rounded p-3">
              <i class="bi bi-box-seam text-success fs-3"></i>
            </div>
            <div>
              <h5 class="mb-1 fw-bold" id="p-name">—</h5>
              <div class="text-muted small font-monospace" id="p-barcode"></div>
              <div class="text-muted small" id="p-category"></div>
            </div>
          </div>
        </div>
        <div class="col-md-2 text-center">
          <div class="text-muted small mb-1">สต๊อกปัจจุบัน</div>
          <span id="p-stock" class="badge fs-4 bg-secondary">—</span>
        </div>
        <div class="col-md-4">
          <div class="d-flex align-items-center gap-2">
            <div class="flex-fill">
              <label class="form-label fw-semibold small mb-1">จำนวนที่รับเข้า</label>
              <input type="number" id="qty-input" class="form-control form-control-lg text-center fw-bold"
                     value="1" min="1">
            </div>
            <div class="pt-4">
              <button class="btn btn-success btn-lg" id="add-btn">
                <i class="bi bi-plus-lg me-1"></i>รับเข้า
              </button>
            </div>
          </div>
          <div class="mt-2">
            <label class="form-label small mb-1">หมายเหตุ</label>
            <input type="text" id="note-input" class="form-control form-control-sm"
                   placeholder="เช่น รับสินค้าล็อตใหม่">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Not found message -->
<div id="not-found" class="d-none">
  <div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <span>ไม่พบสินค้า กรุณาตรวจสอบบาร์โค้ดหรือชื่อสินค้า</span>
  </div>
</div>

<!-- Search results (multiple matches) -->
<div id="search-results" class="d-none">
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-list-ul me-2"></i>ผลการค้นหา — เลือกสินค้า
    </div>
    <div class="list-group list-group-flush" id="results-list"></div>
  </div>
</div>

<!-- Recent log -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between">
    <span><i class="bi bi-clock-history me-2"></i>รับสินค้าล่าสุดในเซสชันนี้</span>
    <span id="session-count" class="badge bg-success">0 รายการ</span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0" id="session-log">
      <thead>
        <tr>
          <th>สินค้า</th>
          <th class="text-center">จำนวนที่รับ</th>
          <th class="text-center">สต๊อกใหม่</th>
          <th>หมายเหตุ</th>
          <th>เวลา</th>
        </tr>
      </thead>
      <tbody id="session-log-body">
        <tr><td colspan="5" class="text-center text-muted py-3">ยังไม่มีรายการ</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- BASE ต้องประกาศนอก nowdoc เพราะ <<<'JS' ไม่ประมวลผล PHP -->
<script>window.__POS_BASE = '<?= base_url() ?>';</script>
<?php $extraScript = <<<'JS'
<script>
const BASE = window.__POS_BASE;
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const barcodeInput  = document.getElementById('barcode-input');
const productCard   = document.getElementById('product-card');
const notFound      = document.getElementById('not-found');
const searchResults = document.getElementById('search-results');
const resultsList   = document.getElementById('results-list');
const addBtn        = document.getElementById('add-btn');
const qtyInput      = document.getElementById('qty-input');
const sessionLogBody = document.getElementById('session-log-body');

let currentProduct = null;
let sessionLog = [];

function showProduct(p) {
  p.stock = Number(p.stock);
  p.id    = Number(p.id);
  currentProduct = p;
  document.getElementById('p-name').textContent     = p.name;
  document.getElementById('p-barcode').textContent  = p.barcode ?? '';
  document.getElementById('p-category').textContent = p.category ? 'หมวด: ' + p.category : '';
  const stockBadge = document.getElementById('p-stock');
  stockBadge.textContent  = p.stock;
  stockBadge.className    = 'badge fs-4 ' + (p.stock === 0 ? 'bg-danger' : (p.stock <= 10 ? 'bg-warning text-dark' : 'bg-success'));
  productCard.classList.remove('d-none');
  notFound.classList.add('d-none');
  searchResults.classList.add('d-none');
  qtyInput.value = 1;
  document.getElementById('note-input').value = '';
  setTimeout(() => qtyInput.focus(), 100);
}

function doSearch() {
  const q = barcodeInput.value.trim();
  if (!q) return;

  productCard.classList.add('d-none');
  notFound.classList.add('d-none');
  searchResults.classList.add('d-none');

  // ลองค้นหาแบบ exact barcode ก่อน
  fetch(BASE + 'products/barcode?barcode=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showProduct(data.product);
      } else {
        // ถ้าไม่เจอ barcode ให้ค้นหาด้วยชื่อ
        fetch(BASE + 'products/search?q=' + encodeURIComponent(q))
          .then(r => r.json())
          .then(results => {
            if (!results || results.length === 0) {
              notFound.classList.remove('d-none');
            } else if (results.length === 1) {
              showProduct(results[0]);
            } else {
              // แสดงรายการให้เลือก
              results.forEach(p => { p.stock = Number(p.stock); });
              resultsList.innerHTML = results.map(p => `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        onclick="selectFromList(${p.id})">
                  <div>
                    <div class="fw-semibold">${p.name}</div>
                    <small class="text-muted font-monospace">${p.barcode ?? ''}</small>
                  </div>
                  <span class="badge ${p.stock === 0 ? 'bg-danger' : (p.stock <= 10 ? 'bg-warning text-dark' : 'bg-success')}">${p.stock} ชิ้น</span>
                </button>
              `).join('');
              searchResults.classList.remove('d-none');
            }
          });
      }
    });
}

function selectFromList(productId) {
  fetch(BASE + 'stock/product/' + productId)
    .then(r => r.json())
    .then(data => {
      if (data.success) showProduct(data.product);
    });
}

barcodeInput.addEventListener('keydown', e => {
  if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
});
document.getElementById('search-btn').addEventListener('click', doSearch);

addBtn.addEventListener('click', function () {
  if (!currentProduct) return;
  const qty  = parseInt(qtyInput.value) || 0;
  const note = document.getElementById('note-input').value.trim();
  if (qty <= 0) { alert('จำนวนต้องมากกว่า 0'); return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังบันทึก...';

  fetch(BASE + 'stock/adjust', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF() },
    body: `product_id=${currentProduct.id}&type=in&qty=${qty}&note=${encodeURIComponent(note)}&csrf_token=${CSRF()}`,
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // อัปเดต stock badge
      const stockBadge = document.getElementById('p-stock');
      stockBadge.textContent = data.new_stock;
      stockBadge.className   = 'badge fs-4 ' + (data.new_stock === 0 ? 'bg-danger' : (data.new_stock <= 10 ? 'bg-warning text-dark' : 'bg-success'));
      currentProduct.stock = data.new_stock;

      // เพิ่มใน session log
      const now = new Date().toLocaleTimeString('th-TH', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
      sessionLog.unshift({ name: currentProduct.name, qty, newStock: data.new_stock, note, time: now });
      renderLog();

      // flash
      const toast = document.createElement('div');
      toast.className = 'alert alert-success position-fixed top-0 end-0 m-3 shadow';
      toast.style.zIndex = 9999;
      toast.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.message}`;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 2500);

      // reset & focus barcode
      barcodeInput.value = '';
      productCard.classList.add('d-none');
      currentProduct = null;
      barcodeInput.focus();
    } else {
      alert(data.message);
    }
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-plus-lg me-1"></i>รับเข้า';
  });
});

function renderLog() {
  document.getElementById('session-count').textContent = sessionLog.length + ' รายการ';
  if (sessionLog.length === 0) {
    sessionLogBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">ยังไม่มีรายการ</td></tr>';
    return;
  }
  sessionLogBody.innerHTML = sessionLog.map(r => `
    <tr>
      <td class="fw-semibold">${r.name}</td>
      <td class="text-center text-success fw-bold">+${r.qty}</td>
      <td class="text-center"><span class="badge ${r.newStock <= 10 ? 'bg-warning text-dark' : 'bg-success'}">${r.newStock}</span></td>
      <td class="text-muted small">${r.note || '-'}</td>
      <td class="text-muted small">${r.time}</td>
    </tr>
  `).join('');
}

// กด Enter บน qtyInput ให้ trigger รับเข้า
qtyInput.addEventListener('keydown', e => {
  if (e.key === 'Enter') { e.preventDefault(); addBtn.click(); }
});
</script>
JS; ?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
