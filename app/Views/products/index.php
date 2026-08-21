<?= view('templates/header', ['title' => $title]) ?>
<script>const BASE_URL = '<?= base_url() ?>';</script>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="mb-0 fw-bold">รายการสินค้า</h5>
    <small class="text-muted"><?= count($products) ?> รายการ</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('/products/import') ?>" class="btn btn-outline-success">
      <i class="bi bi-upload me-1"></i>นำเข้า CSV
    </a>
    <a href="<?= site_url('/products/create') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i>เพิ่มสินค้า
    </a>
  </div>
</div>

<!-- Search + Filter -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <form method="get" action="<?= site_url('/products') ?>" class="row g-2">
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="search" value="<?= esc($search ?? '') ?>"
                 class="form-control" placeholder="ค้นหาชื่อสินค้าหรือบาร์โค้ด...">
        </div>
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select">
          <option value="">-- ทุกหมวดหมู่ --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= esc($cat) ?>" <?= ($category ?? '') === $cat ? 'selected' : '' ?>>
              <?= esc($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary flex-fill" type="submit">
          <i class="bi bi-search me-1"></i>ค้นหา
        </button>
        <a href="<?= site_url('/products') ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x-lg"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Product Table -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>ชื่อสินค้า</th>
            <th>บาร์โค้ด</th>
            <th>หมวดหมู่</th>
            <th class="text-end">ราคา (฿)</th>
            <th class="text-center">สต๊อก</th>
            <th class="text-center" style="width:130px">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>ไม่พบสินค้า
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $i => $p): ?>
              <tr>
                <td class="text-muted"><?= ($row_offset ?? 0) + $i + 1 ?></td>
                <td>
                  <div class="fw-semibold"><?= esc($p['name']) ?></div>
                  <?php if ($p['description']): ?>
                    <small class="text-muted"><?= esc(mb_strimwidth($p['description'], 0, 40, '...')) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['barcode']): ?>
                    <code class="bg-light px-2 py-1 rounded"><?= esc($p['barcode']) ?></code>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['category']): ?>
                    <span class="badge bg-light text-dark"><?= esc($p['category']) ?></span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-end fw-bold"><?= number_format($p['price'], 2) ?></td>
                <td class="text-center">
                  <span class="badge <?= $p['stock'] == 0 ? 'bg-danger' : ($p['stock'] <= 10 ? 'bg-warning text-dark' : 'bg-success') ?>">
                    <?= $p['stock'] ?>
                  </span>
                </td>
                <td class="text-center">
                  <a href="<?= site_url('/products/edit/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary me-1"
                     title="แก้ไข">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <?php if ($p['barcode']): ?>
                    <button type="button" class="btn btn-sm btn-outline-success me-1"
                            title="พิมพ์บาร์โค้ด"
                            onclick="openBarcodeModal(<?= $p['id'] ?>, '<?= esc($p['name'], 'js') ?>', '<?= esc($p['barcode'], 'js') ?>', <?= (float)$p['price'] ?>)">
                      <i class="bi bi-upc"></i>
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" disabled title="ไม่มีบาร์โค้ด">
                      <i class="bi bi-upc"></i>
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete(<?= $p['id'] ?>, '<?= esc($p['name'], 'js') ?>')"
                          title="ลบ">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php
    // ลิงก์ที่คงค่าค้นหา/หมวดหมู่เดิมไว้ — กดเปลี่ยนหน้าแล้วตัวกรองต้องไม่หาย
    $pageUrl = static function (array $extra) use ($search, $category) {
        $q = array_filter([
            'search'   => $search   ?? '',
            'category' => $category ?? '',
        ] + $extra, static fn ($v) => $v !== '' && $v !== null);

        return site_url('products') . ($q ? '?' . http_build_query($q) : '');
    };
  ?>

  <?php if (! empty($products) || ($total ?? 0) > 0): ?>
  <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
    <small class="text-muted">
      <?php if (! empty($show_all)): ?>
        แสดงทั้งหมด <?= number_format($total ?? 0) ?> รายการ
      <?php else: ?>
        แสดง <?= number_format(($row_offset ?? 0) + 1) ?>–<?= number_format(($row_offset ?? 0) + count($products)) ?>
        จาก <?= number_format($total ?? 0) ?> รายการ
      <?php endif; ?>
    </small>

    <div class="d-flex align-items-center gap-2">
      <?php if (! empty($show_all)): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= esc($pageUrl([])) ?>">
          <i class="bi bi-list-ol me-1"></i>แบ่งหน้า
        </a>
      <?php elseif (($total ?? 0) > ($per_page ?? 100)): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= esc($pageUrl(['per' => 'all'])) ?>"
           title="โหลดช้ากว่า แต่ใช้ Ctrl+F ค้นได้ทั้งหมด">
          <i class="bi bi-card-list me-1"></i>แสดงทั้งหมด
        </a>
      <?php endif; ?>

      <?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
        <?php
          $cur  = max(1, $pager->getCurrentPage());
          $last = $pager->getPageCount();
          // แสดงเลขหน้าแค่รอบ ๆ หน้าปัจจุบัน ไม่งั้นร้านที่มีสินค้าเยอะจะได้เลขหน้าเป็นพรืด
          $from = max(1, $cur - 2);
          $to   = min($last, $cur + 2);
        ?>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= esc($pageUrl(['page' => max(1, $cur - 1)])) ?>">&laquo;</a>
            </li>

            <?php if ($from > 1): ?>
              <li class="page-item"><a class="page-link" href="<?= esc($pageUrl(['page' => 1])) ?>">1</a></li>
              <?php if ($from > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($n = $from; $n <= $to; $n++): ?>
              <li class="page-item <?= $n === $cur ? 'active' : '' ?>">
                <a class="page-link" href="<?= esc($pageUrl(['page' => $n])) ?>"><?= $n ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($to < $last): ?>
              <?php if ($to < $last - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
              <?php endif; ?>
              <li class="page-item"><a class="page-link" href="<?= esc($pageUrl(['page' => $last])) ?>"><?= $last ?></a></li>
            <?php endif; ?>

            <li class="page-item <?= $cur >= $last ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= esc($pageUrl(['page' => min($last, $cur + 1)])) ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Delete confirm modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">ยืนยันการลบ</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>ลบสินค้า "<strong id="deleteProductName"></strong>" ?</p>
        <p class="text-muted small">ข้อมูลจะถูกลบออกจากระบบ</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <form id="deleteForm" method="post">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger">ลบสินค้า</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Barcode Modal -->
<div class="modal fade" id="barcodeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-upc me-2 text-success"></i>พิมพ์บาร์โค้ด</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Preview label -->
        <div class="text-center mb-3">
          <div id="label-preview" class="d-inline-block border rounded p-3 bg-white shadow-sm" style="min-width:220px">
            <div id="preview-name" class="fw-bold mb-1" style="font-size:.85rem; max-width:200px; margin:0 auto; line-height:1.3"></div>
            <svg id="preview-barcode" style="max-width:200px"></svg>
            <div id="preview-price" class="fw-bold mt-1" style="font-size:.9rem; color:#1e293b"></div>
          </div>
        </div>

        <!-- Label size -->
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold small">ขนาด Label</label>
            <select id="label-size" class="form-select form-select-sm">
              <optgroup label="🖨️ Thermal (เครื่องพิมพ์ความร้อน)">
                <option value="80x80">Thermal 80×80 มม.</option>
                <option value="80x60">Thermal 80×60 มม.</option>
                <option value="80x50">Thermal 80×50 มม.</option>
              </optgroup>
              <optgroup label="🏷️ ดวงสติ๊กเกอร์">
                <option value="60x40" selected>ดวงสติ๊กเกอร์ 60×40 มม.</option>
                <option value="50x30">ดวงสติ๊กเกอร์ 50×30 มม.</option>
                <option value="40x30">ดวงสติ๊กเกอร์ 40×30 มม.</option>
                <option value="40x25">ดวงสติ๊กเกอร์ 40×25 มม.</option>
                <option value="35x25">ดวงสติ๊กเกอร์ 35×25 มม.</option>
                <option value="32x25">ดวงสติ๊กเกอร์ 32×25 มม.</option>
                <option value="30x20">ดวงสติ๊กเกอร์ 30×20 มม.</option>
                <option value="25x15">ดวงสติ๊กเกอร์จิ๋ว 25×15 มม.</option>
                <option value="20x10">ดวงสติ๊กเกอร์จิ๋ว 20×10 มม.</option>
              </optgroup>
              <optgroup label="🏷️🏷️ สติ๊กเกอร์หลายดวงต่อแถว (ม้วน)">
                <option value="32x25-2col">32×25 มม. × 2 ดวง/แถว</option>
                <option value="30x20-2col">30×20 มม. × 2 ดวง/แถว</option>
                <option value="30x20-3col">30×20 มม. × 3 ดวง/แถว</option>
              </optgroup>
              <optgroup label="📄 แผ่น A4">
                <option value="38x21-5col">แผ่น A4 — 38×21 มม. (5 คอล × 13 แถว)</option>
                <option value="48x25-4col">แผ่น A4 — 48×25 มม. (4 คอล × 11 แถว)</option>
              </optgroup>
              <optgroup label="⚙️ กำหนดเอง">
                <option value="custom">กำหนดขนาดเอง...</option>
              </optgroup>
            </select>
            <div id="multi-col-hint" class="form-text small" style="display:none"></div>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold small">จำนวนที่พิมพ์</label>
            <div class="input-group input-group-sm">
              <button class="btn btn-outline-secondary" type="button" id="qty-minus">-</button>
              <input type="number" id="print-qty" class="form-control text-center fw-bold" value="1" min="1" max="200">
              <button class="btn btn-outline-secondary" type="button" id="qty-plus">+</button>
            </div>
          </div>
        </div>

        <!-- Custom size designer -->
        <div id="custom-size-panel" class="border rounded p-3 mb-3 bg-light" style="display:none">
          <div class="fw-semibold small mb-2"><i class="bi bi-rulers me-1"></i>ออกแบบดวงสติ๊กเกอร์</div>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label small mb-1">กว้าง (มม.)</label>
              <input type="number" id="cz-w" class="form-control form-control-sm" value="40" min="15" max="210" step="0.5">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">สูง (มม.)</label>
              <input type="number" id="cz-h" class="form-control form-control-sm" value="30" min="10" max="297" step="0.5">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">คอลัมน์/แถว</label>
              <input type="number" id="cz-cols" class="form-control form-control-sm" value="1" min="1" max="6">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">ช่องว่างระหว่างดวง (มม.)</label>
              <input type="number" id="cz-gap" class="form-control form-control-sm" value="2" min="0" max="10" step="0.5">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">ความสูงบาร์โค้ด (px)</label>
              <input type="number" id="cz-bch" class="form-control form-control-sm" value="20" min="8" max="80">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">ความหนาเส้น</label>
              <input type="number" id="cz-bcw" class="form-control form-control-sm" value="1.3" min="0.8" max="3" step="0.1">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">ฟอนต์ชื่อ (pt)</label>
              <input type="number" id="cz-fname" class="form-control form-control-sm" value="6" min="4" max="14" step="0.5">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">ฟอนต์ราคา (pt)</label>
              <input type="number" id="cz-fprice" class="form-control form-control-sm" value="7" min="4" max="16" step="0.5">
            </div>
            <div class="col-4">
              <label class="form-label small mb-1">ฟอนต์เลขบาร์โค้ด (pt)</label>
              <input type="number" id="cz-fbc" class="form-control form-control-sm" value="6" min="4" max="12" step="0.5">
            </div>
          </div>
          <div class="form-text mt-1">ค่าที่ตั้งจะถูกจำไว้ในเครื่องนี้อัตโนมัติ</div>
        </div>

        <!-- Options -->
        <div class="d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="show-name" checked>
            <label class="form-check-label small" for="show-name">แสดงชื่อสินค้า</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="show-price" checked>
            <label class="form-check-label small" for="show-price">แสดงราคา</label>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-success" id="print-barcode-btn">
          <i class="bi bi-printer me-1"></i>พิมพ์
        </button>
      </div>
    </div>
  </div>
</div>

<?php $extraScript = '<script src="' . base_url('assets/JsBarcode.all.min.js') . '"></script>' . <<<'JS'
<script>

// ── Delete confirm ────────────────────────────────────────────────────────
function confirmDelete(id, name) {
  document.getElementById('deleteProductName').textContent = name;
  document.getElementById('deleteForm').action = BASE_URL + 'products/delete/' + id;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// ── Barcode modal ─────────────────────────────────────────────────────────
let bcProduct = {};

function openBarcodeModal(id, name, barcode, price) {
  bcProduct = { id, name, barcode, price };
  document.getElementById('preview-name').textContent = name;
  document.getElementById('preview-price').textContent = '฿' + parseFloat(price).toFixed(2);
  renderPreview();
  new bootstrap.Modal(document.getElementById('barcodeModal')).show();
}

function renderPreview() {
  try {
    JsBarcode('#preview-barcode', bcProduct.barcode, {
      format: 'CODE128', width: 1.5, height: 40,
      displayValue: true, fontSize: 10, margin: 4, textMargin: 2,
    });
  } catch(e) {
    document.getElementById('preview-barcode').style.display = 'none';
  }
  document.getElementById('preview-name').style.display =
    document.getElementById('show-name').checked ? '' : 'none';
  document.getElementById('preview-price').style.display =
    document.getElementById('show-price').checked ? '' : 'none';
}

document.getElementById('show-name').addEventListener('change', renderPreview);
document.getElementById('show-price').addEventListener('change', renderPreview);

// ── Custom label designer: แสดง/ซ่อนแผง + จำค่าใน localStorage ───────────────
const CZ_KEY = 'pos_custom_label';
const czFields = ['cz-w','cz-h','cz-cols','cz-gap','cz-bch','cz-bcw','cz-fname','cz-fprice','cz-fbc'];

function loadCustomLabel() {
  try {
    const saved = JSON.parse(localStorage.getItem(CZ_KEY) || '{}');
    czFields.forEach(id => { if (saved[id] !== undefined) document.getElementById(id).value = saved[id]; });
    if (saved.sizeKey) document.getElementById('label-size').value = saved.sizeKey;
  } catch {}
}
function saveCustomLabel() {
  const data = { sizeKey: document.getElementById('label-size').value };
  czFields.forEach(id => { data[id] = document.getElementById(id).value; });
  localStorage.setItem(CZ_KEY, JSON.stringify(data));
}
function toggleCustomPanel() {
  document.getElementById('custom-size-panel').style.display =
    document.getElementById('label-size').value === 'custom' ? '' : 'none';
  updateMultiColHint();
  updateNoNameLock();
  if (bcProduct.barcode) renderPreview();
}
// ม้วนหลายดวงต่อแถว: บอกผู้ใช้ว่าจำนวนพิมพ์จะถูกปัดขึ้นให้เต็มแถว
const MULTI_COL_SIZES = { '32x25-2col': 2, '30x20-2col': 2, '30x20-3col': 3 };
function updateMultiColHint() {
  const hint = document.getElementById('multi-col-hint');
  const cols = MULTI_COL_SIZES[document.getElementById('label-size').value];
  if (cols) {
    hint.textContent = 'ม้วนนี้แถวละ ' + cols + ' ดวง — จำนวนพิมพ์จะถูกปัดขึ้นให้เต็มแถวอัตโนมัติ';
    hint.style.display = '';
  } else {
    hint.style.display = 'none';
  }
}
// ดวงเล็กมาก (20×10 มม.) ไม่พอใส่ชื่อสินค้า — ปิดช่องชื่อให้อัตโนมัติ เหลือแค่บาร์โค้ด+ราคา
const NO_NAME_SIZES = { '20x10': true };
function updateNoNameLock() {
  const showNameChk = document.getElementById('show-name');
  if (NO_NAME_SIZES[document.getElementById('label-size').value]) {
    showNameChk.checked = false;
    showNameChk.disabled = true;
  } else {
    showNameChk.disabled = false;
  }
}
document.getElementById('label-size').addEventListener('change', () => { toggleCustomPanel(); saveCustomLabel(); });
czFields.forEach(id => document.getElementById(id).addEventListener('input', saveCustomLabel));
loadCustomLabel();
toggleCustomPanel();

document.getElementById('qty-minus').addEventListener('click', () => {
  const el = document.getElementById('print-qty');
  if (parseInt(el.value) > 1) el.value = parseInt(el.value) - 1;
});
document.getElementById('qty-plus').addEventListener('click', () => {
  const el = document.getElementById('print-qty');
  if (parseInt(el.value) < 200) el.value = parseInt(el.value) + 1;
});

// ── Print ─────────────────────────────────────────────────────────────────
document.getElementById('print-barcode-btn').addEventListener('click', function() {
  const qty       = parseInt(document.getElementById('print-qty').value) || 1;
  const sizeKey   = document.getElementById('label-size').value;
  const showName  = document.getElementById('show-name').checked;
  const showPrice = document.getElementById('show-price').checked;

  // w,h = ขนาดดวงสติ๊กเกอร์ (มม.), cols = จำนวนคอลัมน์
  // pageW/pageH = ขนาดกระดาษจริง (null = ใช้ W×H), padX/padY = margin กระดาษ (สำหรับ A4 sheet)
  const sizeMap = {
    '80x80':      { w:80, h:80,  cols:1 },
    '80x60':      { w:80, h:60,  cols:1 },
    '80x50':      { w:80, h:50,  cols:1 },
    '60x40':      { w:60, h:40,  cols:1 },
    '50x30':      { w:50, h:30,  cols:1 },
    '40x30':      { w:40, h:30,  cols:1 },
    '40x25':      { w:40, h:25,  cols:1 },
    '35x25':      { w:35, h:25,  cols:1 },
    '32x25':      { w:32, h:25,  cols:1 },
    '30x20':      { w:30, h:20,  cols:1 },
    '25x15':      { w:25, h:15,  cols:1 },
    '20x10':      { w:20, h:10,  cols:1 },
    // ม้วนสติ๊กเกอร์หลายดวงต่อแถว — หน้ากระดาษ = ทุกดวงรวมกัน + ช่องว่างระหว่างดวง
    '32x25-2col': { w:32, h:25,  cols:2, gapMm:3 },
    '30x20-2col': { w:30, h:20,  cols:2, gapMm:2 },
    '30x20-3col': { w:30, h:20,  cols:3, gapMm:2 },
    '38x21-5col': { w:38, h:21,  cols:5, pageW:'210mm', pageH:'297mm', padX:'9.8mm', padY:'10.5mm', gap:'0mm' },
    '48x25-4col': { w:48, h:25,  cols:4, pageW:'210mm', pageH:'297mm', padX:'9mm',   padY:'10.5mm', gap:'0mm' },
  };
  // กำหนดเอง: อ่านค่าจากแผงออกแบบ
  if (sizeKey === 'custom') {
    const v = id => parseFloat(document.getElementById(id).value) || 0;
    sizeMap.custom = {
      w: v('cz-w') || 40, h: v('cz-h') || 30,
      cols: Math.max(1, Math.round(v('cz-cols'))),
      gap: `${v('cz-gap')}mm`,
    };
  }
  const cfg = sizeMap[sizeKey] || { w:60, h:40, cols:1 };
  const { w, h, cols } = cfg;
  const isA4Sheet = !!cfg.pageW;
  // หลายดวงต่อแถว (preset gapMm หรือ custom): หน้ากระดาษ = ทุกดวงรวมกัน + ช่องว่าง
  const gapMm  = sizeKey === 'custom'
                  ? (parseFloat(document.getElementById('cz-gap').value) || 0)
                  : (cfg.gapMm || 0);
  const pageW  = cfg.pageW  || (cols > 1
                  ? `${(w * cols + gapMm * (cols - 1)).toFixed(1)}mm`
                  : `${w}mm`);
  const pageH  = cfg.pageH  || `${h}mm`;
  const padX   = cfg.padX   || '0mm';
  const padY   = cfg.padY   || '0mm';
  const gap    = cfg.gap    !== undefined ? cfg.gap : `${gapMm}mm`;

  // ปรับ barcode ตามความกว้างดวง — หรือใช้ค่าที่ออกแบบเองทั้งหมด
  let bcBarW, bcH, bcFs, namePt, pricePt;
  if (sizeKey === 'custom') {
    const v = id => parseFloat(document.getElementById(id).value) || 0;
    bcBarW  = v('cz-bcw')    || 1.3;
    bcH     = v('cz-bch')    || 20;
    bcFs    = v('cz-fbc')    || 6;
    namePt  = v('cz-fname')  || 6;
    pricePt = v('cz-fprice') || 7;
  } else {
    bcBarW  = w >= 75 ? 2.0 : w >= 55 ? 1.6 : w >= 45 ? 1.3 : w >= 35 ? 1.1 : w >= 28 ? 1.0 : 0.9;
    bcH     = w >= 75 ? 42  : w >= 55 ? 26  : w >= 45 ? 18  : w >= 35 ? 13  : w >= 28 ? 11  : 9;
    bcFs    = w >= 75 ? 8   : w >= 55 ? 7   : w >= 45 ? 6   : w >= 35 ? 6   : 5;
    namePt  = w >= 75 ? 8   : w >= 55 ? 6.5 : w >= 45 ? 5.5 : w >= 35 ? 5   : 4.5;
    pricePt = w >= 75 ? 10  : w >= 55 ? 8   : w >= 45 ? 7   : w >= 35 ? 6   : 5.5;
  }
  const padYmm   = w >= 75 ? 2 : w >= 28 ? 0.8 : 0.5;
  const padXmm   = w >= 75 ? 3 : w >= 28 ? 1.5 : 1;
  const padLabel = `${padYmm}mm ${padXmm}mm`;

  // ── สัดส่วนบาร์โค้ด: ให้เต็มพื้นที่ที่เหลือของดวง ────────────────────────
  // เดิมปล่อยให้ JsBarcode คุมสัดส่วนเอง แล้วบีบด้วย width อย่างเดียว
  // → ดวงเล็กได้บาร์โค้ดเตี้ยจู๋ เหลือที่ว่างครึ่งดวง ดูไม่สมดุล
  // ใหม่: หักที่ของชื่อ+ราคาออกก่อน ที่เหลือยกให้บาร์โค้ดทั้งหมด (มีเพดานกันสูงเกินบนดวงใหญ่)
  const ptToMm   = pt => pt * 0.3528;
  const nameMm   = showName  ? ptToMm(namePt)  * 1.25 + 0.2 : 0;
  const priceMm  = showPrice ? ptToMm(pricePt) * 1.3  + 0.2 : 0;
  const bcTextMm = w >= 75 ? 3 : w >= 55 ? 2.4 : w >= 45 ? 2 : w >= 35 ? 1.8 : w >= 28 ? 1.6 : 1.3;
  const availMm  = h - padYmm * 2 - nameMm - priceMm - 0.4;
  const capMm    = w >= 35 ? w * 0.45 : 999;   // ดวงใหญ่ไม่ต้องยืดเต็มพื้นที่ ดูอึดอัด
  const bcBoxMm  = Math.max(4, Math.min(availMm, capMm));
  const autoFit  = sizeKey !== 'custom';       // กำหนดเอง = เชื่อค่าที่ผู้ใช้ออกแบบไว้

  const escHtml = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  // ม้วนหลายดวงต่อแถว: ปัดจำนวนขึ้นให้เต็มแถว (เช่น แถวละ 3 สั่ง 1 → พิมพ์ 3)
  // ไม่งั้นดวงที่เหลือในแถวจะออกเป็นสติ๊กเกอร์เปล่า เสียของฟรี
  const printQty = (cols > 1 && !isA4Sheet) ? Math.ceil(qty / cols) * cols : qty;

  let labels = '';
  for (let i = 0; i < printQty; i++) {
    labels += `<div class="label">
      ${showName  ? `<div class="lname">${escHtml(bcProduct.name)}</div>` : ''}
      <svg class="barcode" data-val="${escHtml(bcProduct.barcode)}"></svg>
      ${showPrice ? `<div class="lprice">฿${parseFloat(bcProduct.price).toFixed(2)}</div>` : ''}
    </div>`;
  }

  const gridCSS = isA4Sheet
    ? `display:grid; grid-template-columns:repeat(${cols},${w}mm); gap:${gap}; padding:${padY} ${padX};`
    : cols > 1
      ? `display:grid; grid-template-columns:repeat(${cols},${w}mm); gap:${gap};`
      : `display:block;`;

  const win = window.open('', '_blank', 'width=960,height=720');
  win.document.write(`<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>บาร์โค้ด</title>
<script src="${BASE_URL}assets/JsBarcode.all.min.js"><\/script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Sarabun',Arial,sans-serif; background:#fff; }
  .grid { ${gridCSS} }
  .label {
    width:${w}mm; height:${h}mm;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:${padLabel}; text-align:center;
    overflow:hidden; page-break-inside:avoid; break-inside:avoid;
  }
  .lname  { font-size:${namePt}pt; font-weight:700; line-height:1.2;
            max-width:${w-2}mm; word-break:break-word; margin-bottom:0.2mm; }
  .lprice { font-size:${pricePt}pt; font-weight:700; margin-top:0.2mm; }
  .barcode { width:${w-3}mm !important;
             height:${autoFit ? bcBoxMm.toFixed(2)+'mm' : 'auto'} !important; display:block; }
  @media screen {
    body { padding:8px; background:#f0f0f0; }
    .label { background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.2); margin:2px; }
    .notice { background:#fff3cd; border:1px solid #ffc107; border-radius:4px;
              padding:8px 12px; margin-bottom:10px; font-size:13px; }
  }
  @media print {
    @page { size:${pageW} ${pageH}; margin:0; }
    body  { margin:0; padding:0; background:#fff; }
    .label { background:#fff; box-shadow:none; margin:0; }
    .notice { display:none; }
  }
</style>
</head><body>
<div class="notice">
  ⚠️ <strong>ก่อนพิมพ์:</strong> ในหน้าต่างพิมพ์ ให้ตั้ง <strong>Margins → None</strong>
  และ <strong>Scale → 100%</strong> (ปิด Fit to page)
  &nbsp;|&nbsp; ขนาดกระดาษ: <strong>${pageW} × ${pageH}</strong>
  ${printQty !== qty ? ` | ปัดเป็น <strong>${printQty} ดวง</strong> ให้เต็มแถว (แถวละ ${cols})` : ''}
  ${cols > 1 && !isA4Sheet ? `<br>🖨️ ตั้งขนาดกระดาษใน driver เครื่องพิมพ์สติ๊กเกอร์เป็น <strong>${pageW} × ${pageH}</strong> ด้วย (ไม่ใช่ขนาดดวงเดี่ยว) ไม่งั้นจะพิมพ์ออกดวงเดียว/เพี้ยน` : ''}
</div>
<div class="grid">${labels}</div>
<script>
  const BC_W_MM    = ${(w - 3).toFixed(2)};      // ความกว้างบาร์โค้ดจริงบนดวง (มม.)
  const BC_BOX_MM  = ${bcBoxMm.toFixed(2)};      // ความสูงที่จองไว้ให้บาร์โค้ด (มม.)
  const BC_TEXT_MM = ${bcTextMm.toFixed(2)};     // ความสูงตัวเลขใต้บาร์โค้ด (มม.)
  const AUTO_FIT   = ${autoFit ? 'true' : 'false'};
  document.querySelectorAll('.barcode').forEach(el => {
    try {
      const base = { format:'CODE128', width:${bcBarW}, displayValue:true, margin:1, textMargin:1 };
      JsBarcode(el, el.dataset.val, { ...base, height:${bcH}, fontSize:${bcFs} });
      if (AUTO_FIT) {
        // รอบแรกเรนเดอร์เพื่อวัดความกว้างจริง (px) → ได้อัตรา px ต่อ มม.
        // แล้วเรนเดอร์ซ้ำโดยสั่งความสูงเป็น px ที่แปลงจาก มม. → ได้ขนาดจริงตรงตามที่จองไว้
        const ppm = (parseFloat(el.getAttribute('width')) || 0) / BC_W_MM;
        if (ppm > 0) {
          const fontPx = Math.max(4, BC_TEXT_MM * ppm);
          const tmPx   = 0.3 * ppm;
          JsBarcode(el, el.dataset.val, { ...base,
            fontSize:   fontPx,
            textMargin: tmPx,
            height:     Math.max(6, BC_BOX_MM * ppm - fontPx - tmPx - 2),
          });
        }
      }
      // ลบ width/height attribute ที่ JsBarcode ใส่มา เพื่อให้ CSS ควบคุมขนาดแทน
      el.removeAttribute('width');
      el.removeAttribute('height');
    } catch(e) {
      el.outerHTML = '<span style="font-size:6pt;color:red">บาร์โค้ดไม่ถูกต้อง</span>';
    }
  });
  setTimeout(() => { window.focus(); window.print(); }, 600);
<\/script>
</body></html>`);
  win.document.close();
});
</script>
JS; ?>
<?= view('templates/footer', ['extraScript' => $extraScript]) ?>
