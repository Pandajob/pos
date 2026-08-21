<?= view('templates/header', ['title' => 'รายการใบเสนอราคา']) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>รายการใบเสนอราคา</h5>
  <a href="<?= site_url('/wholesale') ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>สร้างใบเสนอราคาใหม่
  </a>
</div>

<?php
$statusLabel = ['pending' => 'รอยืนยัน', 'confirmed' => 'ยืนยันแล้ว', 'cancelled' => 'ยกเลิก'];
$statusClass = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'danger'];
?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>เลขที่</th>
            <th>วันที่</th>
            <th>ลูกค้า</th>
            <th class="text-end">ยอดรวม</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($quotes)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>ยังไม่มีใบเสนอราคา
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($quotes as $q): ?>
              <tr>
                <td class="fw-bold"><?= esc($q['quote_number']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?></td>
                <td><?= esc($q['customer_name'] ?: '-') ?>
                  <?php if ($q['customer_phone']): ?>
                    <br><small class="text-muted"><?= esc($q['customer_phone']) ?></small>
                  <?php endif; ?>
                </td>
                <td class="text-end fw-semibold">฿<?= number_format($q['total_amount'], 2) ?></td>
                <td class="text-center">
                  <span class="badge bg-<?= $statusClass[$q['status']] ?? 'secondary' ?>">
                    <?= $statusLabel[$q['status']] ?? $q['status'] ?>
                  </span>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('/wholesale/quotation/' . $q['id']) ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($q['status'] === 'confirmed' && $q['sale_id']): ?>
                    <a href="<?= site_url('/wholesale/delivery/' . $q['sale_id'] . '?quote=' . $q['id']) ?>"
                       class="btn btn-sm btn-outline-success" title="พิมพ์ใบส่งของ+ใบเสร็จ">
                      <i class="bi bi-printer"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
