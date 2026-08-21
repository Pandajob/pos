<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white fw-bold py-3">
        <i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>นำเข้าสินค้าจาก CSV
      </div>
      <div class="card-body">

        <!-- Error list -->
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <!-- Format description -->
        <div class="alert alert-info border-0 mb-4">
          <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i>รูปแบบไฟล์ CSV</h6>
          <p class="mb-2 small">ไฟล์ต้องมีคอลัมน์ตามลำดับดังนี้ (แถวแรกเป็น header ระบบจะข้ามให้อัตโนมัติ):</p>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-2">
              <thead class="table-light">
                <tr>
                  <th>คอลัมน์ A</th><th>คอลัมน์ B</th><th>คอลัมน์ C</th>
                  <th>คอลัมน์ D</th><th>คอลัมน์ E</th><th>คอลัมน์ F</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>ชื่อสินค้า <span class="text-danger">*</span></td>
                  <td>บาร์โค้ด</td>
                  <td>ราคาขาย <span class="text-danger">*</span></td>
                  <td>ต้นทุน</td>
                  <td>จำนวนสต๊อก</td>
                  <td>หมวดหมู่</td>
                </tr>
                <tr class="text-muted small">
                  <td>ข้าวผัด</td><td>8851111234567</td><td>65</td><td>30</td><td>100</td><td>อาหาร</td>
                </tr>
              </tbody>
            </table>
          </div>
          <small class="text-muted">
            <i class="bi bi-lightbulb me-1 text-warning"></i>
            ถ้ามีบาร์โค้ดซ้ำกับสินค้าที่มีอยู่แล้ว ระบบจะ <strong>อัปเดต</strong> ข้อมูลแทน
          </small>
        </div>

        <!-- Download template -->
        <div class="mb-4">
          <a href="<?= site_url('/products/import-template') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i>ดาวน์โหลด Template CSV
          </a>
        </div>

        <!-- Upload form -->
        <form method="post" action="<?= site_url('/products/import') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">เลือกไฟล์ CSV <span class="text-danger">*</span></label>
            <input type="file" name="csv_file" accept=".csv,.txt" class="form-control" required>
            <div class="form-text">รองรับ .csv หรือ .txt ขนาดไม่เกิน 5MB</div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-upload me-1"></i>นำเข้าสินค้า
            </button>
            <a href="<?= site_url('/products') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>

<?= view('templates/footer') ?>
