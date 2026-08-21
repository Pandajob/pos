<?php
$name        = esc($tier['name']        ?? '');
$discountPct = $tier['discount_pct']   ?? 0;
$isWholesale = $tier['is_wholesale']   ?? 0;
$badgeColor  = esc($tier['badge_color'] ?? 'secondary');
$description = esc($tier['description'] ?? '');
?>

<div class="mb-3">
  <label class="form-label fw-semibold">ชื่อระดับ <span class="text-danger">*</span></label>
  <input type="text" name="name" class="form-control" value="<?= $name ?>" required maxlength="100">
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">ส่วนลด (%)</label>
  <div class="input-group">
    <input type="number" name="discount_pct" class="form-control"
           value="<?= $discountPct ?>" min="0" max="100" step="0.01">
    <span class="input-group-text">%</span>
  </div>
  <div class="form-text">0 = ไม่มีส่วนลด</div>
</div>

<div class="mb-3">
  <div class="form-check">
    <input type="checkbox" name="is_wholesale" value="1" class="form-check-input"
           id="isWholesale<?= $tier['id'] ?? 'new' ?>"
           <?= $isWholesale ? 'checked' : '' ?>>
    <label class="form-check-label" for="isWholesale<?= $tier['id'] ?? 'new' ?>">
      ใช้ราคาส่ง (wholesale_price)
    </label>
  </div>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">สี Badge</label>
  <select name="badge_color" class="form-select">
    <?php foreach (['secondary','primary','success','danger','warning','info','dark'] as $c): ?>
      <option value="<?= $c ?>" <?= $badgeColor === $c ? 'selected' : '' ?>><?= $c ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-0">
  <label class="form-label fw-semibold">หมายเหตุ</label>
  <input type="text" name="description" class="form-control" value="<?= $description ?>" maxlength="255">
</div>
