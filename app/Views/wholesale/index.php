<?php
$baseUrl   = base_url();
$customer  = $customer ?? [];
$discPct   = $disc_pct ?? 0;

$extraHead = <<<CSS
<style>
  body { background: #0f172a; }
  #main  { background: #0f172a; }
  #topbar { background: #1e293b; border-color: #334155; }
  #topbar .page-title { color: #e2e8f0; }
  .content-wrapper { padding: 1rem; }

  #ws-left {
    background: #1e293b; border-radius: .75rem;
    height: calc(100vh - 100px);
    display: flex; flex-direction: column; overflow: hidden;
  }
  #ws-right {
    background: #fff; border-radius: .75rem;
    height: calc(100vh - 100px);
    display: flex; flex-direction: column; overflow: hidden;
  }

  /* Product grid */
  #product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .5rem; padding: .75rem; overflow-y: auto; flex: 1;
  }
  .product-card {
    background: #0f172a; border: 1px solid #334155;
    border-radius: .5rem; padding: .75rem .5rem;
    text-align: center; cursor: pointer;
    transition: all .15s; color: #e2e8f0;
  }
  .product-card:hover { background: #0369a1; border-color: #38bdf8; transform: scale(1.02); }
  .product-card .p-name  { font-size: .8rem; font-weight: 600; line-height: 1.2; margin-bottom: .25rem; }
  .product-card .p-price { font-size: .95rem; font-weight: 700; color: #38bdf8; }
  .product-card .p-retail { font-size: .7rem; color: #64748b; text-decoration: line-through; }
  .product-card .p-stock { font-size: .7rem; color: #94a3b8; }
  .product-card.out-of-stock { opacity: .4; pointer-events: none; }

  /* Cart */
  #cart-body { flex: 1; overflow-y: auto; }
  .cart-row { border-bottom: 1px solid #f1f5f9; padding: .5rem .75rem; }
  .cart-row:last-child { border-bottom: none; }
  .cart-row .item-name  { font-weight: 600; font-size: .9rem; }
  .cart-row .item-price { font-size: .8rem; color: #64748b; }
  .qty-btn {
    width: 28px; height: 28px; border-radius: 50%;
    border: 1px solid #e2e8f0; background: #f8fafc;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-weight: 700; transition: all .1s;
  }
  .qty-btn:hover { background: #dbeafe; border-color: #3b82f6; color: #3b82f6; }
  .qty-input {
    width: 40px; text-align: center; border: 1px solid #e2e8f0;
    border-radius: .25rem; font-weight: 700; font-size: .95rem;
  }
  #summary-panel { border-top: 2px solid #f1f5f9; padding: .75rem 1rem; background: #fafbff; }
  #total-display { font-size: 1.8rem; font-weight: 800; color: #0369a1; }

  #barcode-field {
    background: #0f172a !important; color: #38bdf8 !important;
    border-color: #334155 !important; font-family: monospace;
    font-size: 1.1rem;
  }
  #barcode-field:focus { box-shadow: 0 0 0 2px #38bdf850 !important; }
  #barcode-field::placeholder { color: #475569 !important; }

  .customer-panel { padding: .75rem; border-bottom: 1px solid #f1f5f9; background: #f0f9ff; }
</style>
CSS;
?>
<?= view('templates/header', ['title' => 'ขายส่ง / ใบเสนอราคา', 'extraHead' => $extraHead]) ?>

<div class="row g-3">
  <!-- ══ LEFT: ค้นหาสินค้า ══════════════════════════════════════════════════ -->
  <div class="col-lg-7">
    <div id="ws-left">

      <div class="p-3 border-bottom" style="border-color:#334155 !important">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-0 text-info">
            <i class="bi bi-upc-scan fs-5"></i>
          </span>
          <input type="text" id="barcode-field" class="form-control"
                 placeholder="สแกนบาร์โค้ด หรือพิมพ์ชื่อสินค้า..."
                 autocomplete="off" autofocus>
          <button class="btn btn-outline-secondary border-0 text-muted" id="clearSearch" type="button">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div id="search-status" class="mt-1 ms-1" style="font-size:.8rem; color:#475569"></div>
      </div>

      <div id="product-grid">
        <div class="text-center py-5 w-100" id="grid-placeholder" style="grid-column:1/-1; color:#475569">
          <i class="bi bi-search fs-2 d-block mb-2" style="color:#334155"></i>
          พิมพ์ชื่อสินค้า หรือสแกนบาร์โค้ด
        </div>
      </div>

    </div>
  </div>

  <!-- ══ RIGHT: ตะกร้า + ข้อมูลลูกค้า ════════════════════════════════════ -->
  <div class="col-lg-5">
    <div id="ws-right">

      <!-- Customer info -->
      <div class="customer-panel">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold text-primary" style="font-size:.9rem">
            <i class="bi bi-building me-1"></i>ข้อมูลลูกค้า
          </span>
          <span class="badge bg-info text-white">ราคาส่ง</span>
        </div>
        <div class="row g-1">
          <div class="col-12">
            <input type="text" id="cust-name" class="form-control form-control-sm"
                   placeholder="ชื่อลูกค้า / บริษัท *"
                   value="<?= esc($customer['name'] ?? '') ?>">
          </div>
          <div class="col-12">
            <textarea id="cust-address" class="form-control form-control-sm" rows="2"
                      placeholder="ที่อยู่จัดส่ง"><?= esc($customer['address'] ?? '') ?></textarea>
          </div>
          <div class="col-6">
            <input type="text" id="cust-tax" class="form-control form-control-sm"
                   placeholder="เลขประจำตัวผู้เสียภาษี"
                   value="<?= esc($customer['tax_id'] ?? '') ?>">
          </div>
          <div class="col-6">
            <input type="text" id="cust-phone" class="form-control form-control-sm"
                   placeholder="เบอร์โทรศัพท์"
                   value="<?= esc($customer['phone'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Cart header -->
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
        <span class="fw-bold" style="font-size:.9rem">
          รายการสินค้า <span id="cart-count" class="badge bg-primary"><?= count($cart) ?></span>
        </span>
        <button class="btn btn-sm btn-outline-danger" onclick="clearCart()" title="ล้างตะกร้า">
          <i class="bi bi-trash3"></i>
        </button>
      </div>

      <!-- Cart items -->
      <div id="cart-body">
        <?php if (empty($cart)): ?>
          <div id="cart-empty" class="text-center text-muted py-5">
            <i class="bi bi-cart3 fs-2 d-block mb-2"></i>ยังไม่มีสินค้า
          </div>
        <?php else: ?>
          <div id="cart-empty" class="text-center text-muted py-5 d-none">
            <i class="bi bi-cart3 fs-2 d-block mb-2"></i>ยังไม่มีสินค้า
          </div>
        <?php endif; ?>
      </div>

      <!-- Summary + actions -->
      <div id="summary-panel">
        <!-- Discount -->
        <div class="d-flex align-items-center gap-2 mb-2">
          <label class="text-muted small mb-0" style="white-space:nowrap">ส่วนลด %</label>
          <div class="input-group input-group-sm" style="max-width:130px">
            <input type="number" id="disc-pct" class="form-control text-center fw-bold"
                   min="0" max="100" step="0.5" value="<?= $discPct ?>">
            <span class="input-group-text">%</span>
          </div>
          <div class="ms-auto text-end">
            <div class="text-muted small">ก่อนลด: <span id="subtotal-display">฿<?= number_format($subtotal, 2) ?></span></div>
            <div class="text-danger small">ส่วนลด: -<span id="discamt-display">฿<?= number_format($disc_amt, 2) ?></span></div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold text-dark">ยอดรวมสุทธิ</span>
          <span id="total-display">฿<?= number_format($total, 2) ?></span>
        </div>

        <!-- Note -->
        <div class="mb-2">
          <input type="text" id="note" class="form-control form-control-sm"
                 placeholder="หมายเหตุ (ถ้ามี)">
        </div>

        <button id="btn-save-quote" class="btn btn-primary w-100 fw-bold"
                onclick="saveQuote()">
          <i class="bi bi-file-earmark-text me-2"></i>บันทึกใบเสนอราคา
        </button>

        <div class="d-flex gap-2 mt-2">
          <a href="<?= site_url('/wholesale/list') ?>" class="btn btn-outline-secondary btn-sm flex-fill">
            <i class="bi bi-list-ul me-1"></i>รายการใบเสนอราคา
          </a>
          <a href="<?= site_url('/sales') ?>" class="btn btn-outline-dark btn-sm flex-fill">
            <i class="bi bi-cart3 me-1"></i>ไปหน้าขาย POS
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
  <div id="toast-msg" class="toast align-items-center border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-semibold" id="toast-text"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const BASE = '<?= base_url() ?>';

// ── กล่องตะกร้าที่เหลือจาก PHP ─────────────────────────────────────────────
let cartData = <?= json_encode(array_values($cart), JSON_UNESCAPED_UNICODE) ?>;
renderCart(cartData);

// ── Barcode / search ──────────────────────────────────────────────────────
let searchTimer = null;
const barcodeField = document.getElementById('barcode-field');

barcodeField.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    const val = barcodeField.value.trim();
    if (val) lookupBarcode(val);
  }
});

barcodeField.addEventListener('input', () => {
  clearTimeout(searchTimer);
  const q = barcodeField.value.trim();
  if (q.length < 2) {
    setGrid([]);
    document.getElementById('search-status').textContent = '';
    return;
  }
  searchTimer = setTimeout(() => searchProducts(q), 250);
});

document.getElementById('clearSearch').addEventListener('click', () => {
  barcodeField.value = '';
  setGrid([]);
  document.getElementById('search-status').textContent = '';
  barcodeField.focus();
});

function lookupBarcode(code) {
  fetch(`${BASE}wholesale/barcode?barcode=${encodeURIComponent(code)}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        addToCart(data.product.id, 1);
        barcodeField.value = '';
        setGrid([]);
      } else {
        showToast(data.message, 'danger');
      }
    });
}

function searchProducts(q) {
  document.getElementById('search-status').textContent = 'กำลังค้นหา...';
  fetch(`${BASE}wholesale/search?q=${encodeURIComponent(q)}`)
    .then(r => r.json())
    .then(products => {
      document.getElementById('search-status').textContent =
        products.length ? `พบ ${products.length} รายการ` : 'ไม่พบสินค้า';
      setGrid(products);
    });
}

function setGrid(products) {
  const grid = document.getElementById('product-grid');
  const ph   = document.getElementById('grid-placeholder');
  if (!products.length) {
    grid.innerHTML = '';
    grid.appendChild(ph);
    ph.classList.remove('d-none');
    return;
  }
  ph.classList.add('d-none');
  grid.innerHTML = '';
  products.forEach(p => {
    const wsPrice = p.wholesale_price && parseFloat(p.wholesale_price) > 0
      ? parseFloat(p.wholesale_price) : parseFloat(p.price);
    const outOfStock = parseInt(p.stock) <= 0;
    const card = document.createElement('div');
    card.className = 'product-card' + (outOfStock ? ' out-of-stock' : '');
    card.innerHTML = `
      <div class="p-name">${esc(p.name)}</div>
      <div class="p-price">฿${fmt(wsPrice)}</div>
      ${wsPrice !== parseFloat(p.price) ? `<div class="p-retail">฿${fmt(p.price)}</div>` : ''}
      <div class="p-stock">${outOfStock ? 'หมด' : 'คงเหลือ: ' + p.stock}</div>`;
    card.addEventListener('click', () => addToCart(p.id, 1));
    grid.appendChild(card);
  });
}

// ── Cart operations ───────────────────────────────────────────────────────
function addToCart(productId, qty) {
  post('wholesale/add-to-cart', { product_id: productId, quantity: qty })
    .then(data => {
      if (data.success) { updateCartUI(data); }
      else { showToast(data.message, 'danger'); }
    });
}

function updateQty(productId, qty) {
  post('wholesale/update-cart', { product_id: productId, quantity: qty })
    .then(data => {
      if (data.success) updateCartUI(data);
      else showToast(data.message, 'danger');
    });
}

function removeItem(productId) {
  post('wholesale/remove-from-cart', { product_id: productId })
    .then(data => { if (data.success) updateCartUI(data); });
}

function clearCart() {
  if (!confirm('ล้างตะกร้าทั้งหมด?')) return;
  location.href = '<?= site_url('/wholesale/clear-cart') ?>';
}

function updateCartUI(data) {
  cartData = data.items ?? [];
  renderCart(cartData);
  document.getElementById('subtotal-display').textContent = '฿' + fmt(data.subtotal);
  document.getElementById('discamt-display').textContent  = '฿' + fmt(data.disc_amt);
  document.getElementById('total-display').textContent    = '฿' + fmt(data.total);
  document.getElementById('cart-count').textContent       = data.count ?? cartData.length;
}

function renderCart(items) {
  const body  = document.getElementById('cart-body');
  const empty = document.getElementById('cart-empty');
  // ล้างรายการเก่า (ยกเว้น empty div)
  [...body.children].forEach(el => { if (el.id !== 'cart-empty') el.remove(); });

  if (!items.length) {
    empty.classList.remove('d-none');
    return;
  }
  empty.classList.add('d-none');

  items.forEach(item => {
    const row = document.createElement('div');
    row.className = 'cart-row';
    row.innerHTML = `
      <div class="d-flex justify-content-between align-items-start">
        <div style="flex:1;min-width:0">
          <div class="item-name text-truncate">${esc(item.name)}</div>
          <div class="item-price">ราคาส่ง ฿${fmt(item.price)}/ชิ้น</div>
        </div>
        <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="removeItem(${item.product_id})">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
      <div class="d-flex align-items-center justify-content-between mt-1">
        <div class="d-flex align-items-center gap-1">
          <button class="qty-btn" onclick="updateQty(${item.product_id}, ${item.quantity - 1})">−</button>
          <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${item.stock}"
                 onchange="updateQty(${item.product_id}, +this.value)">
          <button class="qty-btn" onclick="updateQty(${item.product_id}, ${item.quantity + 1})">+</button>
        </div>
        <span class="fw-bold text-primary">฿${fmt(item.subtotal)}</span>
      </div>`;
    body.appendChild(row);
  });
}

// ── Discount % ────────────────────────────────────────────────────────────
let discTimer = null;
document.getElementById('disc-pct').addEventListener('input', function() {
  clearTimeout(discTimer);
  discTimer = setTimeout(() => {
    post('wholesale/set-discount', { discount_pct: this.value })
      .then(data => { if (data.success) updateCartUI(data); });
  }, 400);
});

// ── Save quote ────────────────────────────────────────────────────────────
function saveQuote() {
  if (!cartData.length) { showToast('ยังไม่มีสินค้าในตะกร้า', 'warning'); return; }
  const custName = document.getElementById('cust-name').value.trim();
  if (!custName) { showToast('กรุณาใส่ชื่อลูกค้า', 'warning'); document.getElementById('cust-name').focus(); return; }

  const btn = document.getElementById('btn-save-quote');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังบันทึก...';

  post('wholesale/save-quote', {
    customer_name:    custName,
    customer_address: document.getElementById('cust-address').value,
    customer_tax_id:  document.getElementById('cust-tax').value,
    customer_phone:   document.getElementById('cust-phone').value,
    discount_pct:     document.getElementById('disc-pct').value,
    note:             document.getElementById('note').value,
  }).then(data => {
    if (data.success) {
      location.href = data.redirect;
    } else {
      showToast(data.message, 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i>บันทึกใบเสนอราคา';
    }
  });
}

// ── Utils ─────────────────────────────────────────────────────────────────
function post(path, data) {
  const body = new URLSearchParams({ ...data, csrf_token: CSRF });
  return fetch(`${BASE}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF },
    body,
  }).then(r => r.json());
}

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmt(n) {
  return parseFloat(n||0).toLocaleString('th-TH', { minimumFractionDigits:2, maximumFractionDigits:2 });
}
function showToast(msg, type = 'success') {
  const toast = document.getElementById('toast-msg');
  const text  = document.getElementById('toast-text');
  toast.className = `toast align-items-center border-0 text-bg-${type}`;
  text.textContent = msg;
  new bootstrap.Toast(toast, { delay: 3000 }).show();
}
</script>
