<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="d-flex align-items-center gap-2 mb-4">
      <a href="<?= site_url('/products') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h5 class="mb-0 fw-bold">แก้ไขสินค้า</h5>
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
        <form method="post" action="<?= site_url('/products/update/' . $product['id']) ?>">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">ชื่อสินค้า <span class="text-danger">*</span></label>
            <input type="text" name="name" value="<?= esc($product['name']) ?>"
                   class="form-control" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">บาร์โค้ด</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
              <input type="text" name="barcode" id="barcodeInput"
                     value="<?= esc($product['barcode'] ?? '') ?>"
                     class="form-control" placeholder="สแกนหรือพิมพ์บาร์โค้ด">
              <button type="button" class="btn btn-outline-secondary" id="genBarcode">
                <i class="bi bi-magic"></i>
              </button>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label fw-semibold">ราคาขาย (฿) <span class="text-danger">*</span></label>
              <input type="number" name="price" value="<?= esc($product['price']) ?>"
                     class="form-control" min="0" step="0.01" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">จำนวนสต๊อก <span class="text-danger">*</span></label>
              <input type="number" name="stock" value="<?= esc($product['stock']) ?>"
                     class="form-control" min="0" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">ราคาส่ง (฿)</label>
              <input type="number" name="wholesale_price" value="<?= esc($product['wholesale_price'] ?? '') ?>"
                     class="form-control" min="0" step="0.01" placeholder="0.00 (ถ้ามี)">
              <div class="form-text">สำหรับลูกค้าระดับ "ราคาส่ง"</div>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">ต้นทุน (฿)</label>
              <input type="number" name="cost" value="<?= esc($product['cost'] ?? '') ?>"
                     class="form-control" min="0" step="0.01" placeholder="0.00 (ถ้ามี)">
              <div class="form-text">ใช้คำนวณกำไร (ไม่แสดงหน้าขาย)</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">หมวดหมู่</label>
            <input type="text" name="category" value="<?= esc($product['category'] ?? '') ?>"
                   class="form-control" list="categoryList">
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
            <textarea name="description" class="form-control" rows="2"><?= esc($product['description'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข
            </button>
            <a href="<?= site_url('/products') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Info footer -->
    <div class="text-muted small mt-3 text-end">
      สร้างเมื่อ: <?= date('d/m/Y H:i', strtotime($product['created_at'])) ?>
      <?php if ($product['updated_at']): ?>
        | แก้ไขล่าสุด: <?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php $extraScript = <<<'JS'
<script>
document.getElementById('genBarcode').addEventListener('click', function () {
  const ts = Date.now().toString().slice(-8);
  document.getElementById('barcodeInput').value = '999' + ts;
});
</script>
JS; ?>
<?= view('templates/footer') ?>
