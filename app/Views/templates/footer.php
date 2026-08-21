  </div><!-- end content-wrapper -->
</div><!-- end #main -->

<!-- Bootstrap 5 JS -->
<script src="<?= base_url('assets/bootstrap.bundle.min.js') ?>"></script>

<script>
// ── Clock ─────────────────────────────────────────────────────────────────
function updateClock() {
  const now = new Date();
  const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
  document.getElementById('topbar-date').textContent =
    now.toLocaleDateString('th-TH', opts);
  const timeStr = now.toLocaleTimeString('th-TH');
  const el = document.getElementById('sidebar-clock');
  if (el) el.textContent = timeStr;
}
updateClock();
setInterval(updateClock, 1000);

// ── CSRF helper for fetch() ────────────────────────────────────────────────
function csrfHeaders() {
  return {
    'Content-Type': 'application/x-www-form-urlencoded',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
  };
}
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ── ปิดเมนูเมื่อแตะนอกแถบเมนู (จอแคบ) ───────────────────────────────────────
document.addEventListener('click', (e) => {
  const sb = document.getElementById('sidebar');
  if (!sb || !sb.classList.contains('open')) return;
  if (sb.contains(e.target) || e.target.closest('#sidebar-toggle')) return;
  sb.classList.remove('open');
});

// ── Auto-dismiss alerts ────────────────────────────────────────────────────
setTimeout(() => {
  document.querySelectorAll('.flash-container .alert').forEach(el => {
    bootstrap.Alert.getOrCreateInstance(el)?.close();
  });
}, 4000);
</script>

<?= $extraScript ?? '' ?>

</body>
</html>
