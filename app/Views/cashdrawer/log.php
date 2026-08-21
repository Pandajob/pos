<?= view('templates/header', ['title' => $title]) ?>

<?php
  $typeLabel = [
    'cash_in'  => ['label' => 'เพิ่มเงิน',   'class' => 'success'],
    'cash_out' => ['label' => 'นำออก',        'class' => 'danger'],
    'sale'     => ['label' => 'ขาย',           'class' => 'primary'],
    'refund'   => ['label' => 'คืนสินค้า',    'class' => 'warning'],
    'void'     => ['label' => 'ยกเลิกบิล',    'class' => 'secondary'],
  ];
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="<?= site_url('/cash-drawer') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <i class="bi bi-journal-text fs-4 text-secondary"></i>
  <h5 class="mb-0 fw-bold">Log ลิ้นชักเงิน</h5>
</div>

<!-- ── Sessions ────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold py-3">
    <i class="bi bi-safe2 me-2 text-success"></i>ประวัติกะ (50 ล่าสุด)
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>เปิดโดย</th>
            <th>เปิดเมื่อ</th>
            <th>เงินเริ่ม</th>
            <th>ปิดโดย</th>
            <th>ปิดเมื่อ</th>
            <th>คาดหวัง</th>
            <th>นับได้</th>
            <th>ผลต่าง</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sessions as $s): ?>
            <?php
              $opener = $userMap[$s['opened_by']] ?? null;
              $closer = $s['closed_by'] ? ($userMap[$s['closed_by']] ?? null) : null;
              $diff   = $s['difference'];
            ?>
            <tr>
              <td class="text-muted"><?= $s['id'] ?></td>
              <td><?= esc($opener ? $opener['full_name'] : '—') ?><br>
                  <small class="text-muted"><?= esc($opener['username'] ?? '') ?></small></td>
              <td class="small"><?= date('d/m/Y H:i', strtotime($s['opened_at'])) ?></td>
              <td><?= number_format($s['starting_cash'], 2) ?></td>
              <td><?= esc($closer ? $closer['full_name'] : '—') ?></td>
              <td class="small"><?= $s['closed_at'] ? date('d/m/Y H:i', strtotime($s['closed_at'])) : '—' ?></td>
              <td><?= $s['expected_cash'] !== null ? number_format($s['expected_cash'], 2) : '—' ?></td>
              <td><?= $s['closing_cash'] !== null ? number_format($s['closing_cash'], 2) : '—' ?></td>
              <td class="fw-semibold <?= $diff === null ? '' : ($diff == 0 ? 'text-success' : ($diff > 0 ? 'text-primary' : 'text-danger')) ?>">
                <?= $diff !== null ? sprintf('%+.2f', $diff) : '—' ?>
              </td>
              <td>
                <?php if ($s['status'] === 'open'): ?>
                  <span class="badge bg-success">เปิด</span>
                <?php else: ?>
                  <span class="badge bg-secondary">ปิด</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($s['note']): ?>
            <tr class="table-light">
              <td colspan="10" class="small text-muted ps-4">
                <i class="bi bi-chat-left-text me-1"></i><?= esc($s['note']) ?>
              </td>
            </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── Movement log ────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
    <span><i class="bi bi-list-ul me-2 text-primary"></i>รายการเคลื่อนไหว (200 ล่าสุด)</span>
    <span class="badge bg-secondary"><?= count($movements) ?> รายการ</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive" style="max-height:600px; overflow-y:auto">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light sticky-top">
          <tr>
            <th>เวลา</th>
            <th>กะ #</th>
            <th>ประเภท</th>
            <th>หมายเหตุ</th>
            <th class="text-end">จำนวน (฿)</th>
            <th>โดย</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($movements as $m): ?>
            <?php
              $t     = $typeLabel[$m['type']] ?? ['label'=>$m['type'],'class'=>'secondary'];
              $isOut = in_array($m['type'], ['cash_out','refund','void']);
            ?>
            <tr>
              <td class="small text-muted text-nowrap">
                <?= date('d/m H:i:s', strtotime($m['created_at'])) ?>
              </td>
              <td class="text-muted"><?= $m['session_id'] ?></td>
              <td><span class="badge bg-<?= $t['class'] ?> bg-opacity-75"><?= $t['label'] ?></span></td>
              <td class="small"><?= esc($m['note'] ?? '—') ?></td>
              <td class="text-end fw-semibold <?= $isOut ? 'text-danger' : 'text-success' ?>">
                <?= $isOut ? '−' : '+' ?><?= number_format($m['amount'], 2) ?>
              </td>
              <td class="small">
                <?= esc($m['full_name'] ?? '—') ?><br>
                <span class="text-muted"><?= esc($m['username'] ?? '') ?></span>
              </td>
              <td class="small text-muted"><?= esc($m['ip_address'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= view('templates/footer') ?>
