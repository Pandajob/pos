<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="d-flex align-items-center gap-2 mb-4">
      <a href="<?= site_url('/products') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h5 class="mb-0 fw-bold">เพิ่มสินค้าใหม่</h5>
    </div>

    <?php if (isset($validation)): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= $validation->listErrors() ?>
      </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= $error ?>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <form method="post" action="<?= site_url('/products/store') ?>">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">ชื่อสินค้า <span class="text-danger">*</span></label>
            <input type="text" name="name" value="<?= esc($old['name'] ?? '') ?>"
                   class="form-control" placeholder="เช่น น้ำเปล่า 600ml" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">บาร์โค้ด</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
              <input type="text" name="barcode" id="barcodeInput"
                     value="<?= esc($old['barcode'] ?? '') ?>"
                     class="form-control" placeholder="สแกนหรือพิมพ์บาร์โค้ด (ถ้ามี)"
                     autocomplete="off">
              <button type="button" class="btn btn-outline-secondary" id="genBarcode"
                      title="สร้างบาร์โค้ดอัตโนมัติ">
                <i class="bi bi-magic"></i>
              </button>
            </div>
            <!-- สถานะการตรวจสอบบาร์โค้ด -->
            <div id="barcodeStatus" class="mt-1" style="min-height:1.4rem"></div>
            <div class="form-text">ใช้ USB barcode scanner สแกนได้เลย หรือเว้นว่างไว้</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label fw-semibold">ราคาขาย (฿) <span class="text-danger">*</span></label>
              <input type="number" name="price" value="<?= esc($old['price'] ?? '') ?>"
                     class="form-control" min="0" step="0.01" placeholder="0.00" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">จำนวนสต๊อก <span class="text-danger">*</span></label>
              <input type="number" name="stock" value="<?= esc($old['stock'] ?? '0') ?>"
                     class="form-control" min="0" placeholder="0" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">ราคาส่ง (฿)</label>
              <input type="number" name="wholesale_price" value="<?= esc($old['wholesale_price'] ?? '') ?>"
                     class="form-control" min="0" step="0.01" placeholder="0.00 (ถ้ามี)">
              <div class="form-text">สำหรับลูกค้าระดับ "ราคาส่ง"</div>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">ต้นทุน (฿)</label>
              <input type="number" name="cost" value="<?= esc($old['cost'] ?? '') ?>"
                     class="form-control" min="0" step="0.01" placeholder="0.00 (ถ้ามี)">
              <div class="form-text">ใช้คำนวณกำไร (ไม่แสดงหน้าขาย)</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">หมวดหมู่</label>
            <input type="text" name="category" value="<?= esc($old['category'] ?? '') ?>"
                   class="form-control" list="categoryList" placeholder="เช่น เครื่องดื่ม, อาหาร, ของใช้">
            <datalist id="categoryList">
              <option value="เครื่องดื่ม">
              <option value="อาหาร">
              <option value="ของใช้">
              <option value="ขนม">
              <option value="อื่นๆ">
            </datalist>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">คำอธิบาย</label>
            <textarea name="description" class="form-control" rows="2"
                      placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)"><?= esc($old['description'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" id="submitBtn" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i>บันทึกสินค้า
            </button>
            <a href="<?= site_url('/products') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php $extraScript = <<<'JS'
<script>
// Auto-generate barcode
document.getElementById('genBarcode').addEventListener('click', function () {
  const ts = Date.now().toString().slice(-8);
  const input = document.getElementById('barcodeInput');
  input.value = '999' + ts;
  input.dispatchEvent(new Event('input')); // trigger check
});

// ── Barcode duplicate check ──────────────────────────────────────
const barcodeInput = document.getElementById('barcodeInput');
const statusEl     = document.getElementById('barcodeStatus');
const submitBtn    = document.getElementById('submitBtn');
const CHECK_URL    = barcodeInput.closest('form').action.replace('/store', '') + '/../products/barcode';

let checkTimer = null;
let isDuplicate = false;

function setStatus(type, html) {
  // type: 'ok' | 'error' | 'checking' | ''
  const icons = { ok: 'check-circle-fill text-success', error: 'x-circle-fill text-danger', checking: 'hourglass-split text-secondary' };
  if (!type) { statusEl.innerHTML = ''; return; }
  statusEl.innerHTML = `<small class="d-flex align-items-center gap-1">
    <i class="bi bi-${icons[type]}"></i>${html}</small>`;
}

function checkBarcode(barcode) {
  if (!barcode) {
    setStatus('', '');
    isDuplicate = false;
    submitBtn.disabled = false;
    return;
  }
  setStatus('checking', 'กำลังตรวจสอบ...');
  fetch(`<?= site_url('/products/barcode') ?>?barcode=${encodeURIComponent(barcode)}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // barcode exists
        isDuplicate = true;
        submitBtn.disabled = true;
        const p = data.product;
        setStatus('error',
          `<span class="text-danger fw-semibold">บาร์โค้ดนี้มีอยู่แล้ว</span>
           — <strong>${p.name}</strong>
           ราคา ${parseFloat(p.price).toFixed(2)} ฿ | สต๊อก ${p.stock}
           <a href="<?= site_url('/products/edit/') ?>${p.id}" class="ms-1 small">แก้ไขสินค้านี้</a>`
        );
      } else {
        // barcode free
        isDuplicate = false;
        submitBtn.disabled = false;
        setStatus('ok', '<span class="text-success">บาร์โค้ดพร้อมใช้งาน</span>');
      }
    })
    .catch(() => {
      isDuplicate = false;
      submitBtn.disabled = false;
      setStatus('', '');
    });
}

barcodeInput.addEventListener('input', function () {
  clearTimeout(checkTimer);
  const val = this.value.trim();
  if (!val) { setStatus('', ''); submitBtn.disabled = false; isDuplicate = false; return; }
  setStatus('checking', 'กำลังตรวจสอบ...');
  checkTimer = setTimeout(() => checkBarcode(val), 400); // debounce 400ms
});

// กรณีสแกนบาร์โค้ด (วาง/ป้อนเร็ว) — ตรวจทันทีหลัง blur
barcodeInput.addEventListener('blur', function () {
  clearTimeout(checkTimer);
  const val = this.value.trim();
  if (val) checkBarcode(val);
});

// ป้องกัน submit ถ้าบาร์โค้ดซ้ำ (กัน bypass)
barcodeInput.closest('form').addEventListener('submit', function (e) {
  if (isDuplicate) {
    e.preventDefault();
    statusEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});

// ตรวจทันทีถ้ามีค่าเดิม (กรณี validation error วนกลับมา)
if (barcodeInput.value.trim()) checkBarcode(barcodeInput.value.trim());
</script>
JS; ?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
