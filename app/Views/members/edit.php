<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold">
        <i class="bi bi-pencil me-2 text-primary"></i>แก้ไขสมาชิก
        <span class="badge bg-secondary ms-2"><?= esc($member['code']) ?></span>
      </div>
      <div class="card-body">

        <?php if (session()->getFlashdata('errors')): ?>
          <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $e): ?>
              <div><?= esc($e) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('/members/update/' . $member['id']) ?>">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="<?= old('name', $member['name']) ?>" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control"
                   value="<?= old('phone', $member['phone']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">ที่อยู่</label>
            <textarea name="address" class="form-control" rows="3"
                      placeholder="บ้านเลขที่ ถนน ตำบล อำเภอ จังหวัด"><?= old('address', $member['address']) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              เลขประจำตัวผู้เสียภาษี (VAT)
              <span class="badge bg-light text-secondary fw-normal ms-1">สมาชิก</span>
            </label>
            <input type="text" name="tax_id" class="form-control"
                   value="<?= old('tax_id', $member['tax_id'] ?? '') ?>"
                   placeholder="0-0000-00000-00-0" maxlength="20">
            <div class="form-text">ไม่บังคับ — สำหรับสมาชิกที่ต้องการออกใบกำกับภาษี</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">ระดับสมาชิก</label>
            <select name="tier_id" class="form-select">
              <option value="">— ไม่มีระดับ —</option>
              <?php foreach ($tiers as $tier): ?>
                <option value="<?= $tier['id'] ?>"
                  <?= (old('tier_id', $member['tier_id'] ?? '') == $tier['id']) ? 'selected' : '' ?>>
                  <?= esc($tier['name']) ?>
                  <?php if ($tier['discount_pct'] > 0): ?>
                    (ลด <?= $tier['discount_pct'] ?>%<?= $tier['is_wholesale'] ? ' — ราคาส่ง' : '' ?>)
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">แต้มสะสม</label>
            <input type="text" class="form-control" value="<?= number_format($member['points']) ?> แต้ม" disabled>
            <div class="form-text">แต้มเพิ่มอัตโนมัติเมื่อซื้อสินค้า</div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle me-1"></i>บันทึก
            </button>
            <a href="<?= site_url('/members') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
