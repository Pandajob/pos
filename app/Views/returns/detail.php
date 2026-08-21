<?= view('templates/header', ['title' => $title]) ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="mb-3">
      <a href="<?= site_url('/returns') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>กลับรายการคืน
      </a>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div class="fw-bold">
          <i class="bi bi-arrow-return-left me-2 text-warning"></i><?= esc($return['return_number']) ?>
        </div>
        <?php
          $badge = match($return['status']) {
            'pending'  => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default    => 'bg-secondary',
          };
          $label = match($return['status']) {
            'pending'  => 'รออนุมัติ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ปฏิเสธแล้ว',
            default    => $return['status'],
          };
        ?>
        <span class="badge <?= $badge ?> fs-6"><?= $label ?></span>
      </div>
      <div class="card-body">

        <!-- Meta -->
        <div class="row g-3 mb-4">
          <div class="col-sm-4">
            <div class="text-muted small">บิลเดิม</div>
            <a href="<?= site_url('/receipt/' . $sale['id']) ?>" target="_blank" class="fw-bold text-decoration-none">
              <?= esc($sale['bill_number']) ?> <i class="bi bi-box-arrow-up-right small"></i>
            </a>
          </div>
          <div class="col-sm-4">
            <div class="text-muted small">พนักงาน</div>
            <div class="fw-semibold"><?= esc($return['cashier']) ?></div>
          </div>
          <div class="col-sm-4">
            <div class="text-muted small">วันที่แจ้งคืน</div>
            <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($return['created_at'])) ?></div>
          </div>
        </div>

        <!-- เหตุผล -->
        <div class="alert alert-warning border-0 py-2 px-3 mb-4">
          <i class="bi bi-chat-left-text me-1"></i>
          <strong>เหตุผล:</strong> <?= esc($return['reason']) ?>
        </div>

        <!-- รายการสินค้า -->
        <table class="table mb-4">
          <thead>
            <tr>
              <th>สินค้า</th>
              <th class="text-center">จำนวนคืน</th>
              <th class="text-end">ราคา/ชิ้น</th>
              <th class="text-end">ยอดคืน</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td class="fw-semibold"><?= esc($item['product_name']) ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-end">฿<?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-end fw-bold text-danger">฿<?= number_format($item['refund_amount'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="table-light">
              <td colspan="3" class="fw-bold text-end">ยอดคืนรวม</td>
              <td class="text-end fw-bold text-danger fs-5">฿<?= number_format($return['total_refund'], 2) ?></td>
            </tr>
          </tfoot>
        </table>

        <!-- Actions -->
        <?php if ($return['status'] === 'pending'): ?>
          <div class="d-flex gap-2">
            <form method="post" action="<?= site_url('/returns/approve/' . $return['id']) ?>"
                  onsubmit="return confirm('อนุมัติการคืนสินค้า?\nสต๊อกจะถูกเพิ่มกลับเข้าระบบ')">
              <?= csrf_field() ?>
              <button class="btn btn-success px-4">
                <i class="bi bi-check-circle me-1"></i>อนุมัติ — คืนสต๊อก
              </button>
            </form>
            <form method="post" action="<?= site_url('/returns/reject/' . $return['id']) ?>"
                  onsubmit="return confirm('ปฏิเสธรายการคืนนี้?')">
              <?= csrf_field() ?>
              <button class="btn btn-outline-danger px-4">
                <i class="bi bi-x-circle me-1"></i>ปฏิเสธ
              </button>
            </form>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
