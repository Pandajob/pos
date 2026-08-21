<?= view('templates/header', ['title' => $title]) ?>

<?php
  $isOpen       = !empty($session);
  $userId       = $authUser['id']    ?? 0;
  $userRole     = $authUser['role']  ?? 'cashier';
  $canClose     = $isOpen && ($userRole === 'admin' || ($session['opened_by'] ?? 0) == $userId);
  $agentToken   = setting('agent_token', '');
  $agentPort    = setting('agent_port', '9991');
  $agentEnabled = !empty($agentToken);

  $typeLabel = [
    'cash_in'  => ['label' => 'เพิ่มเงิน',   'class' => 'success', 'icon' => 'plus-circle-fill'],
    'cash_out' => ['label' => 'นำออก',        'class' => 'danger',  'icon' => 'dash-circle-fill'],
    'sale'     => ['label' => 'ขาย',           'class' => 'primary', 'icon' => 'cart-fill'],
    'refund'   => ['label' => 'คืนสินค้า',    'class' => 'warning', 'icon' => 'arrow-return-left'],
    'void'     => ['label' => 'ยกเลิกบิล',    'class' => 'secondary','icon'=> 'x-octagon-fill'],
  ];
?>

<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
  <i class="bi bi-safe2-fill fs-4 text-success"></i>
  <h5 class="mb-0 fw-bold">ลิ้นชักเงิน</h5>
  <?php if ($isOpen): ?>
    <span class="badge bg-success ms-1"><i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i>เปิดอยู่</span>
  <?php else: ?>
    <span class="badge bg-secondary ms-1">ปิด</span>
  <?php endif; ?>
  <!-- Agent status indicator -->
  <?php if ($agentEnabled): ?>
    <span id="agentBadge" class="badge bg-secondary" title="Local Agent">
      <i class="bi bi-usb-symbol me-1"></i>Agent: <span id="agentState">...</span>
    </span>
    <button id="popDrawerBtn" class="btn btn-sm btn-warning fw-semibold" title="สั่งเปิดลิ้นชักจริงผ่าน Agent">
      <i class="bi bi-safe2 me-1"></i>Pop Drawer
    </button>
  <?php else: ?>
    <span class="badge bg-light text-muted border" title="ตั้งค่า Agent ใน Settings">
      <i class="bi bi-usb-symbol me-1"></i>Agent ยังไม่ตั้งค่า
    </span>
  <?php endif; ?>
  <?php if ($userRole === 'admin'): ?>
    <a href="<?= site_url('/cash-drawer/log') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
      <i class="bi bi-journal-text me-1"></i>Log ทั้งหมด
    </a>
  <?php endif; ?>
</div>

<?php foreach (['success','warning','error'] as $t): ?>
  <?php if ($msg = session()->getFlashdata($t)): ?>
    <div class="alert alert-<?= $t === 'error' ? 'danger' : $t ?> alert-dismissible fade show">
      <i class="bi bi-<?= $t === 'success' ? 'check-circle' : ($t === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>-fill me-2"></i>
      <?= esc($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<div class="row g-4">

  <!-- ── ซ้าย: สถานะ + action ──────────────────────────────────────────── -->
  <div class="col-lg-5">

    <?php if ($isOpen): ?>
    <!-- สถานะลิ้นชักเปิด -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-success text-white fw-bold py-3">
        <i class="bi bi-safe2-fill me-2"></i>ลิ้นชักเปิดอยู่
      </div>
      <div class="card-body">
        <div class="row g-2 text-center mb-3">
          <div class="col-4">
            <div class="text-muted small">เงินเริ่มต้น</div>
            <div class="fw-bold fs-5"><?= number_format($session['starting_cash'], 2) ?></div>
          </div>
          <div class="col-4">
            <div class="text-muted small">คาดมีในลิ้นชัก</div>
            <div class="fw-bold fs-5 text-success"><?= number_format($expected, 2) ?></div>
          </div>
          <div class="col-4">
            <div class="text-muted small">เปิดโดย</div>
            <div class="fw-bold small"><?= esc($session['opened_by_name'] ?? '—') ?></div>
          </div>
        </div>
        <div class="text-muted small text-center">
          <i class="bi bi-clock me-1"></i>เปิดเมื่อ <?= date('d/m/Y H:i', strtotime($session['opened_at'])) ?>
        </div>

        <!-- summary badges -->
        <?php if ($summary): ?>
        <hr class="my-2">
        <div class="d-flex flex-wrap gap-1 justify-content-center">
          <?php foreach ($summary as $type => $row): ?>
            <?php $t = $typeLabel[$type] ?? ['label'=>$type,'class'=>'secondary','icon'=>'circle']; ?>
            <span class="badge bg-<?= $t['class'] ?> bg-opacity-75">
              <?= $t['label'] ?>: <?= $row['cnt'] ?> รายการ / <?= number_format($row['total'], 2) ?> ฿
            </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ปุ่ม Cash In / Out -->
    <div class="row g-3 mb-4">
      <div class="col-<?= $userRole === 'admin' ? '6' : '12' ?>">
        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#cashInModal">
          <i class="bi bi-plus-circle-fill me-1"></i>เพิ่มเงิน
        </button>
      </div>
      <?php if ($userRole === 'admin'): ?>
      <div class="col-6">
        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cashOutModal">
          <i class="bi bi-dash-circle-fill me-1"></i>นำเงินออก
        </button>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($canClose): ?>
    <!-- ปิดลิ้นชัก -->
    <div class="card border-danger border-2 shadow-sm">
      <div class="card-header bg-danger text-white fw-bold py-3">
        <i class="bi bi-lock-fill me-2"></i>ปิดลิ้นชัก
      </div>
      <div class="card-body">
        <form method="post" action="<?= site_url('/cash-drawer/close') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">จำนวนเงินที่นับได้จริง (฿) <span class="text-danger">*</span></label>
            <input type="number" name="closing_cash" class="form-control form-control-lg fw-bold text-center"
                   min="0" step="0.01" placeholder="0.00" required>
            <div class="form-text">
              ยอดที่ควรมี: <strong class="text-success"><?= number_format($expected, 2) ?> ฿</strong>
              — ผลต่างจะถูกบันทึกอัตโนมัติ
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">หมายเหตุ</label>
            <input type="text" name="note" class="form-control" placeholder="หมายเหตุ (ถ้ามี)">
          </div>
          <button type="submit" class="btn btn-danger w-100"
                  onclick="return confirm('ยืนยันปิดลิ้นชัก?')">
            <i class="bi bi-lock-fill me-1"></i>ยืนยันปิดลิ้นชัก
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ลิ้นชักปิด — เปิดใหม่ -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-secondary text-white fw-bold py-3">
        <i class="bi bi-safe2 me-2"></i>ลิ้นชักปิดอยู่
      </div>
      <div class="card-body">
        <form method="post" action="<?= site_url('/cash-drawer/open') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">เงินเริ่มต้นในลิ้นชัก (฿) <span class="text-danger">*</span></label>
            <input type="number" name="starting_cash" class="form-control form-control-lg fw-bold text-center"
                   min="0" step="0.01" placeholder="0.00" required autofocus>
            <div class="form-text">ใส่จำนวนเงินที่เตรียมไว้สำหรับทอน</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">หมายเหตุ</label>
            <input type="text" name="note" class="form-control" placeholder="หมายเหตุ (ไม่บังคับ)">
          </div>
          <button type="submit" class="btn btn-success w-100 btn-lg">
            <i class="bi bi-unlock-fill me-1"></i>เปิดลิ้นชัก
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- ── ขวา: รายการ movements + ประวัติ session ───────────────────────── -->
  <div class="col-lg-7">

    <?php if ($isOpen && $movements): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
        <span><i class="bi bi-list-ul me-2 text-primary"></i>รายการในกะนี้</span>
        <span class="badge bg-secondary"><?= count($movements) ?> รายการ</span>
      </div>
      <div class="card-body p-0">
        <div style="max-height:380px; overflow-y:auto">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th>เวลา</th>
                <th>ประเภท</th>
                <th>หมายเหตุ</th>
                <th class="text-end">จำนวน (฿)</th>
                <th>โดย</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($movements as $m): ?>
                <?php $t = $typeLabel[$m['type']] ?? ['label'=>$m['type'],'class'=>'secondary','icon'=>'circle']; ?>
                <?php $isOut = in_array($m['type'], ['cash_out','refund','void']); ?>
                <tr>
                  <td class="text-muted small"><?= date('H:i:s', strtotime($m['created_at'])) ?></td>
                  <td>
                    <span class="badge bg-<?= $t['class'] ?> bg-opacity-75">
                      <i class="bi bi-<?= $t['icon'] ?> me-1"></i><?= $t['label'] ?>
                    </span>
                  </td>
                  <td class="small"><?= esc($m['note'] ?? '—') ?></td>
                  <td class="text-end fw-semibold <?= $isOut ? 'text-danger' : 'text-success' ?>">
                    <?= $isOut ? '−' : '+' ?><?= number_format($m['amount'], 2) ?>
                  </td>
                  <td class="small text-muted"><?= esc($m['username'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ประวัติ session ล่าสุด -->
    <?php if ($history): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold py-3">
        <i class="bi bi-clock-history me-2 text-secondary"></i>ประวัติกะล่าสุด
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>เปิด</th><th>ปิด</th><th>เริ่มต้น</th>
              <th>คาดหวัง</th><th>นับได้</th><th>ผลต่าง</th><th>สถานะ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
              <?php $diff = $h['difference'] ?? null; ?>
              <tr>
                <td class="small"><?= date('d/m H:i', strtotime($h['opened_at'])) ?></td>
                <td class="small"><?= $h['closed_at'] ? date('d/m H:i', strtotime($h['closed_at'])) : '—' ?></td>
                <td class="small"><?= number_format($h['starting_cash'], 2) ?></td>
                <td class="small"><?= $h['expected_cash'] !== null ? number_format($h['expected_cash'], 2) : '—' ?></td>
                <td class="small"><?= $h['closing_cash'] !== null ? number_format($h['closing_cash'], 2) : '—' ?></td>
                <td class="small fw-semibold <?= $diff === null ? '' : ($diff == 0 ? 'text-success' : ($diff > 0 ? 'text-primary' : 'text-danger')) ?>">
                  <?= $diff !== null ? sprintf('%+.2f', $diff) : '—' ?>
                </td>
                <td>
                  <?php if ($h['status'] === 'open'): ?>
                    <span class="badge bg-success">เปิด</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">ปิด</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- ── Modal: Cash In ────────────────────────────────────────────────────── -->
<div class="modal fade" id="cashInModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>เพิ่มเงินในลิ้นชัก</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= site_url('/cash-drawer/cash-in') ?>">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">จำนวนเงิน (฿) <span class="text-danger">*</span></label>
            <input type="number" name="amount" class="form-control form-control-lg text-center fw-bold"
                   min="0.01" step="0.01" placeholder="0.00" required autofocus>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">เหตุผล <span class="text-danger">*</span></label>
            <input type="text" name="note" class="form-control" placeholder="เช่น รับเงินสดเพิ่ม, เปลี่ยนแบงก์" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>ยืนยัน</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Modal: Cash Out (admin เท่านั้น) ──────────────────────────────────── -->
<?php if ($userRole === 'admin'): ?>
<div class="modal fade" id="cashOutModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-dash-circle-fill me-2"></i>นำเงินออกจากลิ้นชัก</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= site_url('/cash-drawer/cash-out') ?>">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-warning p-2 small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            ยอดที่คาดว่ามีในลิ้นชัก: <strong><?= number_format($expected ?? 0, 2) ?> ฿</strong>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">จำนวนเงิน (฿) <span class="text-danger">*</span></label>
            <input type="number" name="amount" class="form-control form-control-lg text-center fw-bold"
                   min="0.01" step="0.01" max="<?= $expected ?? 0 ?>" placeholder="0.00" required>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">เหตุผล <span class="text-danger">*</span></label>
            <input type="text" name="note" class="form-control"
                   placeholder="เช่น ส่งเงินเข้าธนาคาร, ซื้อของ" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg me-1"></i>ยืนยัน</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($agentEnabled): ?>
<script>
const AGENT_BASE  = 'http://127.0.0.1:<?= esc($agentPort) ?>';
const AGENT_TOKEN = '<?= esc($agentToken) ?>';
const CASHIER     = '<?= esc($authUser['username'] ?? 'unknown') ?>';

async function agentFetch(path, opts = {}) {
  return fetch(AGENT_BASE + path, {
    ...opts,
    headers: { 'Content-Type': 'application/json', 'X-Agent-Token': AGENT_TOKEN, ...(opts.headers || {}) },
    signal: AbortSignal.timeout(3000),
  });
}

async function checkAgent() {
  const badge = document.getElementById('agentBadge');
  const state = document.getElementById('agentState');
  try {
    const r = await fetch(AGENT_BASE + '/status', { signal: AbortSignal.timeout(2000) });
    const d = await r.json();
    if (d.ok) {
      badge.className = 'badge bg-success';
      state.textContent = 'Online';
    } else throw '';
  } catch {
    badge.className = 'badge bg-danger';
    state.textContent = 'Offline';
  }
}

async function popDrawer(reason = 'manual') {
  const btn = document.getElementById('popDrawerBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>กำลังเปิด...'; }
  try {
    const r = await agentFetch('/open-drawer', {
      method: 'POST',
      body: JSON.stringify({ triggeredBy: CASHIER, reason }),
    });
    const d = await r.json();
    if (d.ok) {
      showToast('เปิดลิ้นชักสำเร็จ ✓', 'success');
    } else {
      showToast('Error: ' + d.error, 'danger');
    }
  } catch (e) {
    showToast('เชื่อมต่อ Agent ไม่ได้ — ' + e.message, 'danger');
  }
  if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-safe2 me-1"></i>Pop Drawer'; }
}

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `alert alert-${type} alert-dismissible position-fixed bottom-0 end-0 m-3 shadow`;
  t.style.zIndex = 9999;
  t.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

document.getElementById('popDrawerBtn')?.addEventListener('click', () => popDrawer('manual'));
checkAgent();
setInterval(checkAgent, 15000);
</script>
<?php endif; ?>

<?= view('templates/footer') ?>
