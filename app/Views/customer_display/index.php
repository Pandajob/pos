<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Display — <?= esc($shopName) ?></title>
<link href="<?= base_url('assets/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= base_url('assets/bootstrap-icons.min.css') ?>" rel="stylesheet">
<style>
  :root {
    --bg: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --accent: #3b82f6;
    --green: #22c55e;
    --text: #f1f5f9;
    --muted: #94a3b8;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; overflow: hidden; font-family: 'Sarabun', Arial, sans-serif; }
  body { background: var(--bg); color: var(--text); display: flex; flex-direction: column; }

  /* ── Header ── */
  #cd-header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: .75rem 1.5rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
  }
  #cd-header .shop-name { font-size: 1.5rem; font-weight: 800; letter-spacing: .02em; }
  #cd-header .clock { font-size: 1.1rem; color: var(--muted); font-variant-numeric: tabular-nums; }

  /* ── Body ── */
  #cd-body { flex: 1; display: flex; overflow: hidden; }

  /* ── Left: items list ── */
  #cd-items-panel {
    flex: 1; display: flex; flex-direction: column; overflow: hidden;
    padding: 1rem 1.25rem;
  }
  #cd-items-title {
    font-size: clamp(.85rem, 1.6vh, 1.1rem); text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted); margin-bottom: .5rem; font-weight: 700;
  }
  #cd-items-scroll { flex: 1; overflow-y: auto; }
  /* รายการสินค้า — ตัวใหญ่อ่านชัดบนจอ Full HD ระยะลูกค้ายืนมอง */
  .cd-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: clamp(.6rem, 1.6vh, 1.1rem) 1rem;
    border-radius: .75rem;
    border-bottom: 1px solid var(--border);
    transition: background .2s;
  }
  .cd-item:last-child { border-bottom: none; }
  .cd-item.new-item { animation: flash-in .4s ease-out; }
  @keyframes flash-in {
    from { background: #1d4ed830; }
    to   { background: transparent; }
  }
  .cd-item-name { font-weight: 700; font-size: clamp(1.15rem, 3vh, 2.1rem); line-height: 1.25; }
  .cd-item-unit { font-size: clamp(.85rem, 1.9vh, 1.3rem); color: var(--muted); margin-top: .15rem; }
  .cd-item-qty  {
    background: var(--accent); color: #fff;
    width: clamp(2.2rem, 4.8vh, 3.4rem); height: clamp(2.2rem, 4.8vh, 3.4rem); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: clamp(1rem, 2.3vh, 1.6rem); flex-shrink: 0;
  }
  .cd-item-sub { font-weight: 800; font-size: clamp(1.15rem, 3vh, 2.1rem); min-width: 160px; text-align: right; color: #4ade80; }

  /* ── Right: summary + QR ── */
  /* กว้างพอให้ QR ขนาด 50% ของจอแสดงเต็ม (Full HD: ~864px) */
  #cd-right {
    width: min(45vw, 900px); flex-shrink: 0;
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex; flex-direction: column;
    padding: 1.25rem 1.5rem;
    gap: .75rem;
  }

  /* member strip */
  #cd-member-strip {
    background: #1e3a5f; border: 1px solid #3b82f6;
    border-radius: .5rem; padding: .6rem 1rem;
    font-size: clamp(.95rem, 2vh, 1.35rem); display: none;
  }
  #cd-member-strip.show { display: block; }

  /* summary rows */
  .cd-sum-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: clamp(1rem, 2.2vh, 1.5rem); color: var(--muted);
  }
  .cd-sum-row.discount { color: #4ade80; }
  .cd-sum-row.total {
    color: var(--text); font-size: clamp(2rem, 5.5vh, 4rem); font-weight: 800;
    border-top: 2px solid var(--border); padding-top: .75rem; margin-top: .25rem;
  }
  .cd-sum-row.total .val { color: #4ade80; }

  /* QR section — รูป QR ขนาด 50% ของความสูงจอ (Full HD = 540px) */
  #cd-qr-section {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    border-top: 1px solid var(--border); padding-top: .75rem;
    display: none;
  }
  #cd-qr-section.show { display: flex; }
  #cd-qr-section img {
    width: min(50vh, 40vw);
    height: min(50vh, 40vw);
    max-width: none; max-height: none;
    object-fit: contain;
    background: #fff;            /* ขอบขาวรอบ QR ช่วยให้มือถือสแกนติดง่าย */
    padding: clamp(.5rem, 1.5vh, 1rem);
    border-radius: .75rem;
  }
  #cd-qr-section .qr-label { font-size: clamp(1rem, 2.2vh, 1.5rem); color: var(--muted); margin-top: .6rem; font-weight: 600; }

  /* ── Empty / Welcome screen ── */
  #cd-welcome {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: var(--bg); z-index: 10;
    transition: opacity .5s;
  }
  #cd-welcome.hidden { opacity: 0; pointer-events: none; }
  #cd-welcome .w-shop { font-size: 3.5rem; font-weight: 900; color: var(--text); }
  #cd-welcome .w-sub  { font-size: 1.2rem; color: var(--muted); margin-top: .5rem; }
  #cd-welcome .w-icon { font-size: 5rem; color: var(--accent); margin-bottom: 1rem; animation: float 3s ease-in-out infinite; }
  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-12px); }
  }
  #cd-welcome .w-time { font-size: 4rem; font-weight: 300; color: var(--muted); margin-top: 1.5rem; font-variant-numeric: tabular-nums; }

  /* ── Thank you screen ── */
  #cd-thankyou {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: #052e16; z-index: 10; opacity: 0;
    pointer-events: none; transition: opacity .4s;
  }
  #cd-thankyou.show { opacity: 1; pointer-events: auto; }
  #cd-thankyou .ty-icon { font-size: 6rem; color: #4ade80; margin-bottom: 1rem; }
  #cd-thankyou .ty-text { font-size: 3rem; font-weight: 800; color: #4ade80; }
  #cd-thankyou .ty-sub  { font-size: 1.4rem; color: #86efac; margin-top: .5rem; }

  /* scrollbar */
  #cd-items-scroll::-webkit-scrollbar { width: 4px; }
  #cd-items-scroll::-webkit-scrollbar-track { background: transparent; }
  #cd-items-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
</style>
</head>
<body>

<!-- Welcome screen -->
<div id="cd-welcome">
  <div class="w-icon"><i class="bi bi-bag-check-fill"></i></div>
  <div class="w-shop"><?= esc($shopName) ?></div>
  <div class="w-sub">ยินดีต้อนรับ</div>
  <div class="w-time" id="welcome-clock"></div>
</div>

<!-- Thank you screen -->
<div id="cd-thankyou">
  <div class="ty-icon"><i class="bi bi-check-circle-fill"></i></div>
  <div class="ty-text">ขอบคุณที่ใช้บริการ</div>
  <div class="ty-sub">Thank you for your purchase!</div>
</div>

<!-- Header -->
<div id="cd-header">
  <div class="shop-name"><i class="bi bi-bag-check-fill text-primary me-2"></i><?= esc($shopName) ?></div>
  <div class="clock" id="header-clock"></div>
</div>

<!-- Body -->
<div id="cd-body">

  <!-- Items panel -->
  <div id="cd-items-panel">
    <div id="cd-items-title">รายการสินค้า</div>
    <div id="cd-items-scroll">
      <div id="cd-items-list"></div>
    </div>
  </div>

  <!-- Right summary panel -->
  <div id="cd-right">

    <!-- Member strip -->
    <div id="cd-member-strip">
      <i class="bi bi-person-check-fill text-primary me-1"></i>
      <strong id="cd-member-name"></strong>
      <span class="badge bg-warning text-dark ms-2" id="cd-member-pts" style="display:none">
        <i class="bi bi-star-fill"></i> <span id="cd-pts-val"></span> แต้ม
      </span>
    </div>

    <!-- Discount row -->
    <div class="cd-sum-row discount" id="cd-discount-row" style="display:none">
      <span><i class="bi bi-tag-fill me-1"></i>ส่วนลด <span id="cd-disc-pct"></span>%</span>
      <span>-฿<span id="cd-disc-amt"></span></span>
    </div>

    <!-- Points row -->
    <div class="cd-sum-row discount" id="cd-points-row" style="display:none">
      <span><i class="bi bi-star-fill me-1"></i>ใช้แต้ม</span>
      <span>-฿<span id="cd-pts-disc"></span></span>
    </div>

    <!-- Total -->
    <div class="cd-sum-row total">
      <span>ยอดรวม</span>
      <span class="val">฿<span id="cd-total">0.00</span></span>
    </div>

    <!-- QR section -->
    <div id="cd-qr-section">
      <div class="qr-label mb-2">ชำระเงินด้วย QR Code</div>
      <img id="cd-qr-img" src="" alt="QR Payment">
      <div class="qr-label">สแกนเพื่อชำระเงิน</div>
    </div>

  </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
let lastTs = 0;
let lastCount = 0;
let thankYouTimer = null;

// ── Clock ─────────────────────────────────────────────────────────────────
function updateClock() {
  const now = new Date();
  const t = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  const d = now.toLocaleDateString('th-TH', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
  const el = document.getElementById('header-clock');
  const wc = document.getElementById('welcome-clock');
  if (el) el.textContent = d + '  ' + t;
  if (wc) wc.textContent = t;
}
setInterval(updateClock, 1000);
updateClock();

// ── Escape HTML ───────────────────────────────────────────────────────────
function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmt(n) { return parseFloat(n || 0).toLocaleString('th-TH', {minimumFractionDigits:2, maximumFractionDigits:2}); }

// ── Elements ──────────────────────────────────────────────────────────────
const welcome   = document.getElementById('cd-welcome');
const thankyou  = document.getElementById('cd-thankyou');
const itemsList = document.getElementById('cd-items-list');

// ── Poll ──────────────────────────────────────────────────────────────────
async function poll() {
  try {
    const res  = await fetch(BASE + 'customer-display/data?_=' + Date.now());
    const data = await res.json();

    if (data.ts === lastTs) return false; // ไม่มีการเปลี่ยนแปลง
    lastTs = data.ts || 0;

    if (data.status === 'clear' || !data.items || data.items.length === 0) {
      if (lastCount > 0) {
        // เพิ่งผ่าน checkout → แสดง thank you
        showThankYou();
      } else {
        showWelcome();
      }
      lastCount = 0;
      return true;
    }

    // มีสินค้าในตะกร้า
    cancelThankYou();
    welcome.classList.add('hidden');
    thankyou.classList.remove('show');

    renderItems(data.items, data.count !== lastCount);
    renderSummary(data);
    lastCount = data.count || data.items.length;
    return true;

  } catch(e) { /* ignore */ }
  return false;
}

function showWelcome() {
  welcome.classList.remove('hidden');
  thankyou.classList.remove('show');
  itemsList.innerHTML = '';
  document.getElementById('cd-total').textContent = '0.00';
}

function showThankYou() {
  thankyou.classList.add('show');
  welcome.classList.add('hidden');
  if (thankYouTimer) clearTimeout(thankYouTimer);
  thankYouTimer = setTimeout(() => {
    thankyou.classList.remove('show');
    welcome.classList.remove('hidden');
  }, 5000);
}

function cancelThankYou() {
  if (thankYouTimer) clearTimeout(thankYouTimer);
  thankyou.classList.remove('show');
}

function renderItems(items, hasNew) {
  itemsList.innerHTML = items.map((item, i) => `
    <div class="cd-item${hasNew && i === items.length - 1 ? ' new-item' : ''}">
      <div style="flex:1">
        <div class="cd-item-name">${esc(item.name)}</div>
        <div class="cd-item-unit">฿${fmt(item.price)} × ${item.quantity}</div>
      </div>
      <div class="cd-item-qty">${item.quantity}</div>
      <div class="cd-item-sub ms-3">฿${fmt(item.subtotal)}</div>
    </div>
  `).join('');
}

function renderSummary(data) {
  // member
  const mStrip = document.getElementById('cd-member-strip');
  const mName  = document.getElementById('cd-member-name');
  const mPts   = document.getElementById('cd-member-pts');
  const mPtsV  = document.getElementById('cd-pts-val');
  if (data.member && data.member.name) {
    mName.textContent = data.member.name;
    if (data.member.points > 0) {
      mPtsV.textContent = data.member.points.toLocaleString('th-TH');
      mPts.style.display = '';
    } else {
      mPts.style.display = 'none';
    }
    mStrip.classList.add('show');
  } else {
    mStrip.classList.remove('show');
  }

  // discount
  const discRow = document.getElementById('cd-discount-row');
  if (data.discount_pct > 0) {
    document.getElementById('cd-disc-pct').textContent = data.discount_pct;
    document.getElementById('cd-disc-amt').textContent = fmt(data.discount_amt);
    discRow.style.display = '';
  } else {
    discRow.style.display = 'none';
  }

  // points discount
  const ptsRow = document.getElementById('cd-points-row');
  if (data.points_disc > 0) {
    document.getElementById('cd-pts-disc').textContent = fmt(data.points_disc);
    ptsRow.style.display = '';
  } else {
    ptsRow.style.display = 'none';
  }

  // total
  document.getElementById('cd-total').textContent = fmt(data.total);

  // QR
  const qrSec = document.getElementById('cd-qr-section');
  const qrImg = document.getElementById('cd-qr-img');
  if (data.qr_image && data.total > 0) {
    // ใส่ ?v=mtime กันแคช — รูปถูกบันทึกทับชื่อเดิม ถ้าไม่ใส่หน้าจอลูกค้าจะค้างรูปเก่า
    const newSrc = BASE + 'uploads/' + data.qr_image + '?v=' + (data.qr_ver || 0);
    if (qrImg.src !== newSrc) qrImg.src = newSrc;
    qrSec.classList.add('show');
  } else {
    qrSec.classList.remove('show');
  }
}

// ── จังหวะการถามข้อมูล (polling) ──────────────────────────────────────────
// เดิมยิงทุก 1.5 วิ ตลอดเวลา = ~2,400 คำขอ/ชม. ทั้งวัน แม้ไม่มีลูกค้า
// เครื่องสเปคน้อยรับไม่ไหว เพราะทุกคำขอคือการบูต PHP + ยิง DB ใหม่ทั้งชุด
// ใหม่: มีความเคลื่อนไหว = ถามถี่ / นิ่งเกิน 30 วิ = ถามห่างขึ้น / จอถูกซ่อน = หยุดถาม
const POLL_FAST  = 1200;   // ระหว่างขายอยู่ — ต้องตอบสนองไว
const POLL_SLOW  = 5000;   // ร้านว่าง — ประหยัดเครื่อง
const IDLE_AFTER = 30000;  // นิ่งนานเท่านี้ถือว่าว่าง

let idleSince = Date.now();
let pollTimer = null;

async function pollLoop() {
  if (!document.hidden) {
    if (await poll()) idleSince = Date.now();   // มีอะไรเปลี่ยน = กลับไปถามถี่
  }
  const delay = (Date.now() - idleSince > IDLE_AFTER) ? POLL_SLOW : POLL_FAST;
  pollTimer = setTimeout(pollLoop, delay);
}

// กลับมาดูจออีกครั้ง = ถามทันที ไม่ต้องรอรอบถัดไป
document.addEventListener('visibilitychange', () => {
  if (document.hidden) return;
  idleSince = Date.now();
  clearTimeout(pollTimer);
  pollLoop();
});

pollLoop();
</script>
</body>
</html>
