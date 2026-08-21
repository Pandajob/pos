<?= view('templates/header', ['title' => $title]) ?>

<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
  <i class="bi bi-journal-text fs-4 text-warning"></i>
  <h5 class="mb-0 fw-bold">ค้างชำระ / เงินเชื่อ</h5>
  <span class="text-muted small">บิลที่ขายเชื่อไว้และยังเก็บเงินไม่ครบ</span>
</div>

<!-- สรุปยอด -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body py-3">
        <div class="text-muted small">ยอดค้างชำระรวม</div>
        <div class="fs-4 fw-bold text-danger">฿<?= number_format($totalOutstanding, 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body py-3">
        <div class="text-muted small">บิลที่ค้างอยู่</div>
        <div class="fs-4 fw-bold"><?= count($bills) ?> <span class="fs-6 fw-normal text-muted">บิล</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body py-3">
        <div class="text-muted small">จำนวนลูกหนี้</div>
        <div class="fs-4 fw-bold"><?= count($byMember) ?> <span class="fs-6 fw-normal text-muted">คน</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 bg-light">
      <div class="card-body py-3 small text-muted">
        <i class="bi bi-info-circle me-1"></i>ขายเชื่อได้จากหน้าขาย —
        เลือกสมาชิกแล้วกดช่องทางชำระ <span class="badge bg-warning text-dark">เชื่อ/ค้างชำระ</span>
      </div>
    </div>
  </div>
</div>

<?php if (! empty($byMember)): ?>
<!-- สรุปรายลูกหนี้ -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold"><i class="bi bi-people-fill me-2 text-warning"></i>ยอดค้างรายลูกหนี้</div>
  <div class="card-body py-2">
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($byMember as $m): ?>
        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-3 py-2">
          <i class="bi bi-person-fill me-1 text-warning"></i><?= esc($m['name']) ?>
          <span class="text-muted mx-1">(<?= $m['bills'] ?> บิล)</span>
          <span class="fw-bold text-danger">฿<?= number_format($m['outstanding'], 2) ?></span>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ตารางบิลค้าง -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex align-items-center gap-2 flex-wrap">
    <span class="fw-semibold"><i class="bi bi-hourglass-split me-2 text-danger"></i>บิลที่ยังค้างชำระ</span>
    <input type="text" id="filter-input" class="form-control form-control-sm ms-auto" style="max-width:240px"
           placeholder="ค้นหาชื่อลูกค้า / เลขบิล...">
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="credit-table">
      <thead class="table-light">
        <tr>
          <th>เลขบิล</th>
          <th>วันที่ขาย</th>
          <th>ลูกค้า</th>
          <th class="text-end">ยอดบิล</th>
          <th class="text-end">ชำระแล้ว</th>
          <th class="text-end">คงค้าง</th>
          <th class="text-center" style="width:220px">จัดการ</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($bills)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">
          <i class="bi bi-emoji-smile me-1"></i>ไม่มีบิลค้างชำระ
        </td></tr>
      <?php endif; ?>
      <?php foreach ($bills as $b): $out = (float) $b['outstanding']; ?>
        <tr data-filter="<?= esc(mb_strtolower(($b['member_name'] ?? '') . ' ' . $b['bill_number'])) ?>">
          <td><code><?= esc($b['bill_number']) ?></code></td>
          <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
          <td class="fw-semibold"><i class="bi bi-person-fill text-warning me-1"></i><?= esc($b['member_name'] ?? '-') ?></td>
          <td class="text-end">฿<?= number_format($b['total_amount'], 2) ?></td>
          <td class="text-end text-success">฿<?= number_format($b['paid_amount'], 2) ?></td>
          <td class="text-end fw-bold text-danger">฿<?= number_format($out, 2) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-success pay-btn"
                    data-id="<?= $b['id'] ?>" data-bill="<?= esc($b['bill_number']) ?>"
                    data-name="<?= esc($b['member_name'] ?? '-') ?>" data-out="<?= $out ?>">
              <i class="bi bi-cash-coin me-1"></i>รับชำระ
            </button>
            <button class="btn btn-sm btn-outline-secondary history-btn" data-id="<?= $b['id'] ?>" title="ประวัติการชำระ">
              <i class="bi bi-clock-history"></i>
            </button>
            <a href="<?= site_url('/receipt/' . $b['id']) ?>" class="btn btn-sm btn-outline-secondary" title="ดูบิล" target="_blank">
              <i class="bi bi-receipt"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- บิลที่ชำระครบแล้ว -->
<?php if (! empty($settled)): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white">
    <a class="text-decoration-none text-dark fw-semibold" data-bs-toggle="collapse" href="#settled-panel" role="button">
      <i class="bi bi-check-circle-fill me-2 text-success"></i>ชำระครบแล้วล่าสุด (<?= count($settled) ?>)
      <i class="bi bi-chevron-down small ms-1"></i>
    </a>
  </div>
  <div class="collapse" id="settled-panel">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr><th>เลขบิล</th><th>ลูกค้า</th><th class="text-end">ยอดบิล</th><th>ชำระครบเมื่อ</th><th class="text-center">ประวัติ</th></tr>
        </thead>
        <tbody>
        <?php foreach ($settled as $s): ?>
          <tr>
            <td><code><?= esc($s['bill_number']) ?></code></td>
            <td><?= esc($s['member_name'] ?? '-') ?></td>
            <td class="text-end">฿<?= number_format($s['total_amount'], 2) ?></td>
            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($s['credit_settled_at'])) ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-secondary history-btn" data-id="<?= $s['id'] ?>">
                <i class="bi bi-clock-history"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal: รับชำระ -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="payForm" action="">
        <div class="modal-header bg-success text-white py-2">
          <h6 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>รับชำระหนี้</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">บิล</span><code id="pm-bill"></code>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">ลูกค้า</span><span class="fw-semibold" id="pm-name"></span>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">ยอดคงค้าง</span>
            <span class="fw-bold text-danger fs-5">฿<span id="pm-out"></span></span>
          </div>

          <label class="form-label fw-semibold small mb-1">จำนวนเงินที่รับ (บาท)</label>
          <div class="input-group mb-2">
            <input type="number" name="amount" id="pm-amount" class="form-control form-control-lg text-end fw-bold"
                   min="0.01" step="0.01" required placeholder="0.00">
            <button type="button" class="btn btn-outline-success" id="pm-full">เต็มจำนวน</button>
          </div>

          <label class="form-label fw-semibold small mb-1">ช่องทางรับเงิน</label>
          <div class="btn-group w-100 mb-2" role="group">
            <input type="radio" class="btn-check" name="method" id="m-cash" value="cash" checked>
            <label class="btn btn-outline-secondary btn-sm" for="m-cash"><i class="bi bi-cash-coin me-1"></i>เงินสด</label>
            <input type="radio" class="btn-check" name="method" id="m-qr" value="qr">
            <label class="btn btn-outline-primary btn-sm" for="m-qr"><i class="bi bi-qr-code me-1"></i>QR</label>
            <input type="radio" class="btn-check" name="method" id="m-transfer" value="transfer">
            <label class="btn btn-outline-info btn-sm" for="m-transfer"><i class="bi bi-bank me-1"></i>โอน</label>
          </div>

          <label class="form-label fw-semibold small mb-1">หมายเหตุ (ถ้ามี)</label>
          <input type="text" name="note" class="form-control form-control-sm" maxlength="255" placeholder="เช่น งวดที่ 1">
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i>บันทึกรับชำระ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: ประวัติการชำระ -->
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white py-2">
        <h6 class="modal-title"><i class="bi bi-clock-history me-2"></i>ประวัติการชำระ <code class="text-white" id="h-bill"></code></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="h-body">
        <div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลด...</div>
      </div>
    </div>
  </div>
</div>

<?php $extraScript = '<script>
const BASE = ' . json_encode(base_url()) . ';

// ── รับชำระ ──
const payModal = new bootstrap.Modal(document.getElementById("payModal"));
let payOut = 0;
document.querySelectorAll(".pay-btn").forEach(btn => btn.addEventListener("click", function () {
  payOut = parseFloat(this.dataset.out) || 0;
  document.getElementById("pm-bill").textContent = this.dataset.bill;
  document.getElementById("pm-name").textContent = this.dataset.name;
  document.getElementById("pm-out").textContent  = payOut.toLocaleString(undefined, {minimumFractionDigits:2});
  const amt = document.getElementById("pm-amount");
  amt.value = ""; amt.max = payOut.toFixed(2);
  document.getElementById("payForm").action = BASE + "credit/pay/" + this.dataset.id;
  payModal.show();
  setTimeout(() => amt.focus(), 350);
}));
document.getElementById("pm-full").addEventListener("click", () => {
  document.getElementById("pm-amount").value = payOut.toFixed(2);
});

// ── ประวัติ ──
const historyModal = new bootstrap.Modal(document.getElementById("historyModal"));
const mLabel = { cash: "เงินสด", qr: "QR", transfer: "โอน" };
document.querySelectorAll(".history-btn").forEach(btn => btn.addEventListener("click", async function () {
  document.getElementById("h-bill").textContent = "";
  document.getElementById("h-body").innerHTML =
    \'<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลด...</div>\';
  historyModal.show();
  try {
    const r = await fetch(BASE + "credit/payments/" + this.dataset.id);
    const d = await r.json();
    if (!d.success) throw new Error(d.message || "โหลดไม่สำเร็จ");
    document.getElementById("h-bill").textContent = d.bill_number;
    const fmt = n => Number(n).toLocaleString(undefined, {minimumFractionDigits:2});
    let rows = d.payments.map(p => `<tr>
        <td class="small text-muted">${p.created_at ?? "-"}</td>
        <td class="text-end fw-semibold text-success">฿${fmt(p.amount)}</td>
        <td>${mLabel[p.method] ?? p.method}</td>
        <td class="small">${(p.note ?? "")}</td>
        <td class="small text-muted">${(p.received_by ?? "")}</td>
      </tr>`).join("");
    if (!rows) rows = \'<tr><td colspan="5" class="text-center text-muted py-3">ยังไม่มีการชำระหลังวันขาย</td></tr>\';
    document.getElementById("h-body").innerHTML = `
      <div class="d-flex justify-content-between small mb-2">
        <span>ยอดบิล ฿${fmt(d.total)} · ชำระแล้ว ฿${fmt(d.paid)}</span>
        <span class="${d.settled ? "text-success" : "text-danger"} fw-bold">
          ${d.settled ? "ชำระครบแล้ว ✓" : "คงค้าง ฿" + fmt(d.outstanding)}
        </span>
      </div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th>วันที่</th><th class="text-end">จำนวน</th><th>ช่องทาง</th><th>หมายเหตุ</th><th>ผู้รับ</th></tr></thead>
        <tbody>${rows}</tbody>
      </table></div>`;
  } catch (e) {
    document.getElementById("h-body").innerHTML =
      \'<div class="alert alert-danger mb-0">\' + e.message + \'</div>\';
  }
}));

// ── ค้นหาในตาราง ──
document.getElementById("filter-input").addEventListener("input", function () {
  const q = this.value.trim().toLowerCase();
  document.querySelectorAll("#credit-table tbody tr[data-filter]").forEach(tr => {
    tr.style.display = !q || tr.dataset.filter.includes(q) ? "" : "none";
  });
});
</script>'; ?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
