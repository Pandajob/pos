<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <div class="mb-3">
      <a href="<?= site_url('/receipt/' . $sale['id']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="bi bi-arrow-left me-1"></i>กลับไปดูใบเสร็จ
      </a>
    </div>

    <!-- Sale info -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-bold py-3">
        <i class="bi bi-receipt me-2 text-primary"></i>
        ข้อมูลบิลเดิม — <span class="text-primary"><?= esc($sale['bill_number']) ?></span>
      </div>
      <div class="card-body">
        <div class="row g-3 text-sm">
          <div class="col-sm-4">
            <div class="text-muted small">วันที่</div>
            <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></div>
          </div>
          <div class="col-sm-4">
            <div class="text-muted small">ยอดรวม</div>
            <div class="fw-bold text-primary">฿<?= number_format($sale['total_amount'], 2) ?></div>
          </div>
          <div class="col-sm-4">
            <div class="text-muted small">พนักงาน</div>
            <div class="fw-semibold"><?= esc($sale['cashier']) ?></div>
          </div>
        </div>
      </div>
    </div>

    <form method="post" action="<?= site_url('/returns/store') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">

      <!-- เลือกสินค้าที่คืน -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="bi bi-box-seam me-2 text-warning"></i>เลือกสินค้าที่ต้องการคืน
        </div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <thead>
              <tr>
                <th style="width:40px">
                  <input type="checkbox" id="checkAll" class="form-check-input" title="เลือกทั้งหมด (คืนเต็มจำนวน)">
                </th>
                <th>สินค้า</th>
                <th class="text-center">ซื้อมา</th>
                <th class="text-end">ราคา/ชิ้น</th>
                <th class="text-center" style="width:130px">จำนวนคืน</th>
                <th class="text-end">ยอดคืน</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr class="item-row">
                  <td class="align-middle">
                    <input type="checkbox" class="form-check-input item-check"
                           data-id="<?= $item['id'] ?>"
                           data-max="<?= $item['quantity'] ?>">
                  </td>
                  <td class="align-middle">
                    <div class="fw-semibold"><?= esc($item['product_name']) ?></div>
                    <?php if ($item['barcode']): ?>
                      <small class="text-muted"><?= esc($item['barcode']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-center align-middle"><?= $item['quantity'] ?></td>
                  <td class="text-end align-middle">฿<?= number_format($item['price'], 2) ?></td>
                  <td class="text-center align-middle">
                    <input type="number"
                           name="quantities[<?= $item['id'] ?>]"
                           class="form-control form-control-sm text-center qty-input"
                           value="0" min="0" max="<?= $item['quantity'] ?>"
                           data-price="<?= $item['price'] ?>"
                           data-id="<?= $item['id'] ?>"
                           style="width:80px; margin:0 auto">
                  </td>
                  <td class="text-end fw-bold align-middle refund-cell" id="refund-<?= $item['id'] ?>">
                    <span class="text-muted">฿0.00</span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- เหตุผล -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="bi bi-chat-left-text me-2 text-danger"></i>เหตุผลการคืนสินค้า <span class="text-danger">*</span>
        </div>
        <div class="card-body">
          <textarea name="reason" class="form-control" rows="3" required
                    placeholder="ระบุเหตุผล เช่น สินค้าชำรุด, ได้รับผิดรายการ, ลูกค้าเปลี่ยนใจ..."><?= old('reason') ?></textarea>
        </div>
      </div>

      <!-- สรุปยอดคืน -->
      <div class="card border-0 shadow-sm mb-4 border-start border-danger border-4">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
          <div class="fw-bold fs-5">ยอดคืนทั้งสิ้น</div>
          <div class="fw-bold fs-3 text-danger">฿<span id="total-refund">0.00</span></div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning btn-lg px-4 fw-bold" id="submit-btn" disabled>
          <i class="bi bi-arrow-return-left me-2"></i>ส่งรายการคืน (รอการอนุมัติ)
        </button>
        <a href="<?= site_url('/returns') ?>" class="btn btn-outline-secondary btn-lg">ยกเลิก</a>
      </div>

    </form>
  </div>
</div>

<?php $extraScript = <<<'JS'
<script>
const checks   = document.querySelectorAll('.item-check');
const checkAll = document.getElementById('checkAll');

// checkbox หัวตาราง → เซตจำนวนคืนเต็มหรือ 0
checkAll.addEventListener('change', function() {
  checks.forEach(cb => {
    cb.checked = this.checked;
    const input = document.querySelector(`.qty-input[data-id="${cb.dataset.id}"]`);
    input.value = this.checked ? input.max : 0;
  });
  recalc();
});

// checkbox แต่ละแถว → เซตจำนวนคืนเต็มหรือ 0
checks.forEach(cb => {
  cb.addEventListener('change', function() {
    const input = document.querySelector(`.qty-input[data-id="${this.dataset.id}"]`);
    input.value = this.checked ? input.max : 0;
    recalc();
    updateCheckAll();
  });
});

function updateCheckAll() {
  const all = [...checks];
  checkAll.checked       = all.every(c => c.checked);
  checkAll.indeterminate = !checkAll.checked && all.some(c => c.checked);
}

// พิมพ์จำนวนเอง → คำนวณใหม่ + sync checkbox
document.querySelectorAll('.qty-input').forEach(input => {
  input.addEventListener('input', function() {
    const qty = parseInt(this.value) || 0;
    const cb  = document.querySelector(`.item-check[data-id="${this.dataset.id}"]`);
    if (cb) cb.checked = qty > 0;
    // จำกัดไม่เกิน max
    if (qty > parseInt(this.max)) this.value = this.max;
    recalc();
    updateCheckAll();
  });
});

function recalc() {
  let total = 0;
  document.querySelectorAll('.qty-input').forEach(input => {
    const qty    = parseInt(input.value) || 0;
    const price  = parseFloat(input.dataset.price) || 0;
    const id     = input.dataset.id;
    const refund = qty * price;
    const cell   = document.getElementById('refund-' + id);
    cell.innerHTML = qty > 0
      ? `<span class="text-danger">฿${refund.toFixed(2)}</span>`
      : `<span class="text-muted">฿0.00</span>`;
    total += refund;
  });
  document.getElementById('total-refund').textContent = total.toFixed(2);
  document.getElementById('submit-btn').disabled = (total === 0);
}
</script>
JS; ?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
