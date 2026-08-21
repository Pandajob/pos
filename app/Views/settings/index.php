<?= view('templates/header', ['title' => $title]) ?>

<div class="row g-4">

  <div class="col-12">
    <form method="post" action="<?= site_url('/settings/save') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row g-4">

        <!-- ── ข้อมูลร้านค้า ─────────────────────────────────────────── -->
        <div class="col-md-7">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3">
              <i class="bi bi-shop me-2 text-primary"></i>ข้อมูลร้านค้า
            </div>
            <div class="card-body">

              <div class="mb-3">
                <label class="form-label fw-semibold">ชื่อร้านค้า <span class="text-danger">*</span></label>
                <input type="text" name="shop_name" class="form-control"
                       value="<?= esc($settings['shop_name'] ?? 'ร้านของเรา') ?>"
                       placeholder="ชื่อร้านค้า" required>
                <div class="form-text">แสดงบนหัวใบเสร็จและ Sidebar</div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">ที่อยู่ร้านค้า</label>
                <textarea name="shop_address" class="form-control" rows="3"
                          placeholder="บ้านเลขที่ ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์"><?= esc($settings['shop_address'] ?? '') ?></textarea>
              </div>

              <div class="mb-0">
                <label class="form-label fw-semibold">
                  เลขประจำตัวผู้เสียภาษี (VAT)
                  <span class="badge bg-light text-secondary fw-normal ms-1">ร้านค้า</span>
                </label>
                <input type="text" name="shop_tax_id" class="form-control"
                       value="<?= esc($settings['shop_tax_id'] ?? '') ?>"
                       placeholder="0-0000-00000-00-0" maxlength="20">
                <div class="form-text">แสดงบนใบเสร็จ (ถ้ามี)</div>
              </div>

            </div>
          </div>
        </div>

        <!-- ── ตั้งค่าพนักงาน + แต้มสะสม ────────────────────────────────── -->
        <div class="col-md-5">

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3">
              <i class="bi bi-person-badge me-2 text-success"></i>ตั้งค่าพนักงานขาย
            </div>
            <div class="card-body">

              <div class="mb-0">
                <label class="form-label fw-semibold">ชื่อพนักงานขาย (Default)</label>
                <input type="text" name="cashier_name" class="form-control"
                       value="<?= esc($settings['cashier_name'] ?? 'Admin') ?>"
                       placeholder="ชื่อพนักงาน" required>
                <div class="form-text">ชื่อที่แสดงบนใบเสร็จในช่อง "พนักงาน"</div>
              </div>

            </div>
          </div>

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3">
              <i class="bi bi-star-fill me-2 text-warning"></i>ระบบแต้มสะสม
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">อัตราสะสมแต้ม</label>
                <div class="input-group">
                  <span class="input-group-text bg-white">1 แต้ม ต่อทุก</span>
                  <input type="number" name="points_rate" class="form-control text-center fw-bold"
                         value="<?= esc($settings['points_rate'] ?? '10') ?>"
                         min="1" max="99999" required style="max-width:90px">
                  <span class="input-group-text bg-white">บาท</span>
                </div>
                <div class="form-text">
                  ตัวอย่าง: ซื้อ <?= esc($settings['points_rate'] ?? '10') ?> บาท ได้ 1 แต้ม
                </div>
              </div>
              <div class="mb-0">
                <label class="form-label fw-semibold">มูลค่าต่อแต้ม (แลกแต้ม)</label>
                <div class="input-group">
                  <span class="input-group-text bg-white">1 แต้ม =</span>
                  <input type="number" name="points_value" class="form-control text-center fw-bold"
                         value="<?= esc($settings['points_value'] ?? '1') ?>"
                         min="0.01" step="0.01" required style="max-width:90px">
                  <span class="input-group-text bg-white">บาท</span>
                </div>
                <div class="form-text">มูลค่าส่วนลดเมื่อลูกค้าแลกแต้ม (ค่าเริ่มต้น 1 แต้ม = 1 บาท)</div>
              </div>
            </div>
          </div>

          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold py-3">
              <i class="bi bi-exclamation-triangle me-2 text-warning"></i>แจ้งเตือนสต๊อก
            </div>
            <div class="card-body">
              <div class="mb-0">
                <label class="form-label fw-semibold">แจ้งเตือนสต๊อกต่ำกว่า</label>
                <div class="input-group">
                  <input type="number" name="low_stock_threshold" class="form-control text-center fw-bold"
                         value="<?= esc($settings['low_stock_threshold'] ?? '10') ?>"
                         min="0" max="9999" style="max-width:90px">
                  <span class="input-group-text bg-white">ชิ้น</span>
                </div>
                <div class="form-text">แสดงแจ้งเตือนบน Dashboard เมื่อสต๊อกน้อยกว่าค่านี้</div>
              </div>
            </div>
          </div>

        </div>

      </div><!-- /.row -->

      <!-- ── Printer Settings ────────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="bi bi-printer-fill me-2 text-dark"></i>ตั้งค่าเครื่องพิมพ์
        </div>
        <div class="card-body">
          <div class="row g-4">

            <div class="col-md-5">
              <label class="form-label fw-semibold">ประเภทเครื่องพิมพ์หลัก</label>
              <?php $ptype = $settings['printer_type'] ?? 'slip80'; ?>
              <div class="d-flex flex-column gap-2">
                <div class="form-check border rounded p-3 <?= $ptype === 'slip80' ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                  <input class="form-check-input" type="radio" name="printer_type" value="slip80"
                         id="pt_slip80" <?= $ptype === 'slip80' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="pt_slip80">
                    <i class="bi bi-receipt me-2 text-primary"></i>เครื่องพิมพ์สลิป 80mm
                    <span class="badge bg-primary ms-1">แนะนำ</span>
                  </label>
                  <div class="text-muted small mt-1">พิมพ์ใบเสร็จแบบย่อ 80×80 มม. เหมาะกับร้านค้าทั่วไป</div>
                </div>
                <div class="form-check border rounded p-3 <?= $ptype === 'a4' ? 'border-success bg-success bg-opacity-10' : '' ?>">
                  <input class="form-check-input" type="radio" name="printer_type" value="a4"
                         id="pt_a4" <?= $ptype === 'a4' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="pt_a4">
                    <i class="bi bi-file-text me-2 text-success"></i>เครื่องพิมพ์ A4
                  </label>
                  <div class="text-muted small mt-1">พิมพ์ใบเสร็จแบบเต็มหน้า A4 / ใบกำกับภาษี</div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">วิธีสั่งพิมพ์</label>
              <?php $pmode = $settings['print_mode'] ?? 'browser'; ?>
              <div class="d-flex flex-column gap-2 mb-3">
                <div class="form-check border rounded p-2 ps-4 <?= $pmode === 'agent' ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                  <input class="form-check-input" type="radio" name="print_mode" value="agent"
                         id="pm_agent" <?= $pmode === 'agent' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold small" for="pm_agent">
                    <i class="bi bi-hdd-network me-1 text-primary"></i>พิมพ์ผ่านโปรแกรม (Agent)
                    <span class="badge bg-primary ms-1">แนะนำ</span>
                  </label>
                  <div class="text-muted small">พิมพ์ตรงไปเครื่องปริ้นทันที ไม่มีหน้าต่างเบราว์เซอร์ให้กดเลือก</div>
                </div>
                <div class="form-check border rounded p-2 ps-4 <?= $pmode === 'browser' ? 'border-secondary bg-secondary bg-opacity-10' : '' ?>">
                  <input class="form-check-input" type="radio" name="print_mode" value="browser"
                         id="pm_browser" <?= $pmode === 'browser' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold small" for="pm_browser">
                    <i class="bi bi-window me-1"></i>พิมพ์ผ่านเบราว์เซอร์
                  </label>
                  <div class="text-muted small">เปิดหน้าต่างพิมพ์ของ Chrome/Edge ให้เลือกเครื่องเอง</div>
                </div>
              </div>

              <label class="form-label fw-semibold">เครื่องพิมพ์ใบเสร็จ (เมื่อพิมพ์ผ่านโปรแกรม)</label>
              <?php $savedPrinter = $settings['receipt_printer'] ?? ''; ?>
              <div class="input-group mb-3">
                <select name="receipt_printer" id="receipt_printer" class="form-select">
                  <option value="<?= esc($savedPrinter) ?>" selected>
                    <?= $savedPrinter ? esc($savedPrinter) : '-- กด ⟳ เพื่อโหลดรายชื่อจาก Agent --' ?>
                  </option>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="refreshPrintersBtn" title="โหลดรายชื่อเครื่องพิมพ์">
                  <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn btn-outline-primary" id="testPrintBtn" title="พิมพ์สลิปทดสอบไปเครื่องที่เลือก">
                  <i class="bi bi-printer"></i> ทดสอบพิมพ์
                </button>
              </div>
              <div id="testPrintResult" class="small mb-3"></div>

              <label class="form-label fw-semibold">การพิมพ์อัตโนมัติ</label>
              <?php $autoPrint = $settings['auto_print'] ?? '0'; ?>
              <div class="form-check form-switch mb-3">
                <input type="hidden" name="auto_print" value="0">
                <input class="form-check-input" type="checkbox" name="auto_print" value="1"
                       id="auto_print" style="width:3rem;height:1.5rem"
                       <?= $autoPrint === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold ms-2" for="auto_print">
                  พิมพ์ใบเสร็จอัตโนมัติหลัง Checkout
                </label>
              </div>
              <div class="alert alert-info border-0 small p-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                เมื่อเปิด: หลัง checkout จะเปิดหน้าพิมพ์ใบเสร็จอัตโนมัติตามประเภทเครื่องพิมพ์ที่เลือก
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold">ชื่อเครื่องพิมพ์ <small class="text-muted">(อ้างอิง)</small></label>
              <input type="text" name="printer_paper" class="form-control"
                     value="<?= esc($settings['printer_paper'] ?? '') ?>"
                     placeholder="เช่น EPSON TM-T82">
              <div class="form-text">ชื่อเพื่อใช้อ้างอิงเท่านั้น ไม่ได้เชื่อมต่อโดยตรง</div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── QR Payment ──────────────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="bi bi-qr-code me-2 text-primary"></i>QR Code ชำระเงิน
          <small class="text-muted fw-normal ms-2">แสดงบน Customer Display และใบเสร็จ</small>
        </div>
        <div class="card-body">
          <div class="row align-items-start g-3">
            <div class="col-md-4">
              <?php $qrImg = $settings['qr_payment_image'] ?? ''; ?>
              <?php if ($qrImg): ?>
                <?php $qrVer = is_file(FCPATH . 'uploads/' . $qrImg) ? filemtime(FCPATH . 'uploads/' . $qrImg) : 0; ?>
                <div class="mb-2">
                  <img src="<?= site_url('/uploads/' . esc($qrImg)) . '?v=' . $qrVer ?>"
                       alt="QR Payment" class="img-thumbnail" style="max-width:180px">
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="delete_qr" value="1" id="delete_qr">
                  <label class="form-check-label text-danger small" for="delete_qr">
                    <i class="bi bi-trash me-1"></i>ลบรูป QR นี้
                  </label>
                </div>
              <?php else: ?>
                <div class="text-muted text-center p-4 border rounded bg-light" style="max-width:180px">
                  <i class="bi bi-qr-code fs-1 d-block mb-1"></i>
                  <small>ยังไม่มีรูป QR</small>
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-8">
              <label class="form-label fw-semibold"><?= $qrImg ? 'เปลี่ยนรูป QR' : 'อัปโหลดรูป QR' ?></label>
              <input type="file" name="qr_payment_file" accept="image/*" class="form-control mb-2">
              <div class="form-text">
                รองรับ JPG, PNG, GIF, WebP · ขนาดแนะนำ 400×400 px ขึ้นไป<br>
                รูปจะแสดงบน <strong>Customer Display</strong> เมื่อมีสินค้าในตะกร้า
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Preview ──────────────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="bi bi-receipt me-2 text-warning"></i>ตัวอย่างหัวใบเสร็จ
        </div>
        <div class="card-body">
          <div class="row align-items-start g-3">

            <!-- Color picker -->
            <div class="col-md-4">
              <label class="form-label fw-semibold">
                <i class="bi bi-palette me-1 text-primary"></i>สีตัวอักษรใบเสร็จ
              </label>
              <?php $billColor = $settings['bill_font_color'] ?? '#000000'; ?>
              <div class="d-flex align-items-center gap-2">
                <input type="color" name="bill_font_color" id="bill_font_color"
                       class="form-control form-control-color"
                       value="<?= esc($billColor) ?>"
                       title="เลือกสีตัวอักษร" style="width:3rem;height:2.5rem;cursor:pointer">
                <input type="text" id="bill_font_color_hex" class="form-control form-control-sm"
                       value="<?= esc($billColor) ?>" maxlength="7"
                       placeholder="#000000" style="max-width:90px;font-family:monospace">
              </div>
              <div class="form-text">ใช้กับข้อความทั้งหมดบนใบเสร็จ</div>
              <!-- Preset swatches -->
              <div class="d-flex flex-wrap gap-1 mt-2">
                <?php foreach (['#000000','#1e3a5f','#7c3aed','#b45309','#dc2626','#166534'] as $c): ?>
                  <button type="button" class="color-swatch border rounded"
                          data-color="<?= $c ?>"
                          style="width:1.5rem;height:1.5rem;background:<?= $c ?>;cursor:pointer"
                          title="<?= $c ?>"></button>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Font sizes per section -->
            <?php
              $fsMap = [
                'bill_fs_shopname' => ['label'=>'ชื่อร้าน',     'icon'=>'shop',          'default'=>22, 'min'=>12, 'max'=>40, 'prev'=>'prev-name'],
                'bill_fs_meta'     => ['label'=>'ที่อยู่/เลขบิล','icon'=>'info-circle',   'default'=>13, 'min'=>8,  'max'=>20, 'prev'=>'prev-address'],
                'bill_fs_items'    => ['label'=>'รายการสินค้า',  'icon'=>'list-ul',       'default'=>14, 'min'=>8,  'max'=>22, 'prev'=>'prev-items'],
                'bill_fs_total'    => ['label'=>'ยอดรวม',        'icon'=>'cash-stack',    'default'=>16, 'min'=>10, 'max'=>28, 'prev'=>'prev-total'],
                'bill_fs_footer'   => ['label'=>'ข้อความท้ายบิล','icon'=>'chat-left-text','default'=>12, 'min'=>8,  'max'=>18, 'prev'=>'prev-footer'],
              ];
            ?>
            <div class="col-md-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-type me-1 text-secondary"></i>ขนาดตัวอักษรแต่ละส่วน
              </label>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($fsMap as $key => $cfg): ?>
                  <?php $val = (int)($settings[$key] ?? $cfg['default']); ?>
                  <div class="border rounded p-2">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <small class="fw-semibold text-secondary">
                        <i class="bi bi-<?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
                      </small>
                      <span class="badge bg-secondary fs-size-label" id="lbl_<?= $key ?>"
                            style="min-width:2.2rem"><?= $val ?>px</span>
                    </div>
                    <input type="range" name="<?= $key ?>" id="<?= $key ?>"
                           class="form-range fs-slider"
                           min="<?= $cfg['min'] ?>" max="<?= $cfg['max'] ?>" step="1"
                           value="<?= $val ?>"
                           data-label="lbl_<?= $key ?>"
                           data-prev="<?= $cfg['prev'] ?>">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Live Preview -->
            <div class="col-md-5">
              <label class="form-label fw-semibold">ตัวอย่างใบเสร็จ</label>
              <?php
                $pvColor    = esc($billColor ?? '#000000');
                $pvShopname = (int)($settings['bill_fs_shopname'] ?? 22);
                $pvMeta     = (int)($settings['bill_fs_meta']     ?? 13);
                $pvItems    = (int)($settings['bill_fs_items']    ?? 14);
                $pvTotal    = (int)($settings['bill_fs_total']    ?? 16);
                $pvFooter   = (int)($settings['bill_fs_footer']   ?? 12);
              ?>
              <div id="bill-preview" class="border rounded p-3 bg-white"
                   style="font-family:'Sarabun',sans-serif; max-width:320px; color:<?= $pvColor ?>">
                <!-- ชื่อร้าน -->
                <div id="prev-name" class="fw-bold text-center"
                     style="font-size:<?= $pvShopname ?>px"><?= esc($settings['shop_name'] ?? 'ร้านของเรา') ?></div>
                <!-- ที่อยู่/เลขบิล -->
                <div id="prev-address" class="text-center mt-1"
                     style="font-size:<?= $pvMeta ?>px; white-space:pre-line; color:#64748b"><?= esc($settings['shop_address'] ?? '') ?></div>
                <?php if (!empty($settings['shop_tax_id'])): ?>
                  <div id="prev-taxid" class="text-center"
                       style="font-size:<?= $pvMeta ?>px; color:#64748b">เลขภาษี: <?= esc($settings['shop_tax_id']) ?></div>
                <?php else: ?>
                  <div id="prev-taxid" style="display:none"></div>
                <?php endif; ?>
                <hr class="my-1" style="border-top:1px dashed #ccc">
                <!-- รายการสินค้า -->
                <div id="prev-items" style="font-size:<?= $pvItems ?>px">
                  <div class="d-flex justify-content-between py-1 border-bottom" style="color:#64748b; font-size:<?= max(8, $pvItems-2) ?>px">
                    <span>สินค้า</span><span>จำนวน</span><span>รวม</span>
                  </div>
                  <div class="d-flex justify-content-between py-1">
                    <span>เบรกเกอร์ช้าง 10A</span><span>2</span><span>130.00</span>
                  </div>
                </div>
                <hr class="my-1" style="border-top:1px dashed #ccc">
                <!-- ยอดรวม -->
                <div id="prev-total" class="d-flex justify-content-between fw-bold"
                     style="font-size:<?= $pvTotal ?>px">
                  <span>ยอดรวม</span><span>130.00</span>
                </div>
                <div class="d-flex justify-content-between" style="font-size:<?= $pvItems ?>px; color:#64748b">
                  <span>รับเงิน</span><span>150.00</span>
                </div>
                <div class="d-flex justify-content-between" style="font-size:<?= $pvItems ?>px; color:#16a34a">
                  <span>ทอนเงิน</span><span>20.00</span>
                </div>
                <hr class="my-1" style="border-top:1px dashed #ccc">
                <!-- ท้ายบิล -->
                <div id="prev-footer" class="text-center"
                     style="font-size:<?= $pvFooter ?>px; color:#94a3b8">ขอบคุณที่ใช้บริการ</div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Cash Drawer Agent ─────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-bold py-3 d-flex align-items-center gap-2">
          <i class="bi bi-safe2-fill text-success"></i>Cash Drawer / Print Agent
          <span id="agentStatusBadge" class="badge bg-secondary ms-1">กำลังตรวจสอบ...</span>
          <button type="button" class="btn btn-primary btn-sm ms-auto" id="autoSetupBtn">
            <i class="bi bi-magic me-1"></i>ตั้งค่าอัตโนมัติ
          </button>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Token <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" name="agent_token" id="agent_token"
                       class="form-control font-monospace"
                       value="<?= esc($settings['agent_token'] ?? '') ?>"
                       placeholder="pos-xxxxxxxxxxxxxxxxxxxx">
                <button type="button" class="btn btn-outline-secondary" id="testAgentBtn"
                        title="ทดสอบการเชื่อมต่อ">
                  <i class="bi bi-plug-fill"></i> ทดสอบ
                </button>
              </div>
              <div class="form-text">
                Token จาก <code>config.json</code> ในโฟลเดอร์ <code>local-agent/</code>
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Port</label>
              <input type="number" name="agent_port" class="form-control"
                     value="<?= esc($settings['agent_port'] ?? '9991') ?>"
                     min="1024" max="65535">
              <div class="form-text">default: 9991</div>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold d-block">ตัวเลือก</label>
              <div class="form-check form-switch mt-2">
                <input type="hidden" name="agent_open_on_checkout" value="0">
                <input class="form-check-input" type="checkbox"
                       name="agent_open_on_checkout" value="1" id="agent_open_on_checkout"
                       style="width:3rem;height:1.5rem"
                       <?= ($settings['agent_open_on_checkout'] ?? '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold ms-2" for="agent_open_on_checkout">
                  เปิดลิ้นชักอัตโนมัติหลัง Checkout
                </label>
              </div>
              <div class="alert alert-info border-0 small p-2 mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                ต้องรัน <strong>local-agent/start.bat</strong> บนเครื่อง POS ก่อน
              </div>
            </div>
          </div>
          <div id="agentTestResult" class="mt-2"></div>
        </div>
      </div>

      <!-- ── Actions ──────────────────────────────────────────────────── -->
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-check-circle me-1"></i>บันทึกการตั้งค่า
        </button>
        <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
      </div>

    </form>
  </div>

  <!-- ── อัพเดทฐานข้อมูล ──────────────────────────────────────────────── -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold py-3">
        <i class="bi bi-database-fill-gear me-2 text-info"></i>อัพเดทฐานข้อมูล
        <small class="text-muted fw-normal ms-2">สำหรับอัพเดทโครงสร้าง DB บนเครื่องจริง โดยไม่ลบข้อมูลเดิม</small>
      </div>
      <div class="card-body">
        <div class="row align-items-center g-3">

          <div class="col-md-7">
            <div class="alert alert-info border-0 small mb-3">
              <i class="bi bi-info-circle me-1"></i>
              กดปุ่มนี้บนเครื่องที่ใช้งานจริงเพื่อ <strong>ปรับโครงสร้างฐานข้อมูลให้ตรงกับต้นแบบครบทุกตาราง</strong>
              ระบบจะ <strong>สร้างตารางที่ขาด + เพิ่มคอลัมน์ที่ยังไม่มี</strong> ให้อัตโนมัติ
              (เหมาะกับกรณีก๊อปโปรแกรมไปแล้วฐานข้อมูลสร้างไม่ครบ) —
              ข้อมูลเดิม (สินค้า, บิล, สมาชิก ฯลฯ) <strong>ไม่ถูกลบหรือแก้ไข</strong> เพิ่มเฉพาะส่วนที่ขาดเท่านั้น
            </div>
            <div class="d-flex flex-wrap gap-2">
              <span class="badge bg-light text-dark border">
                <i class="bi bi-hdd-stack me-1"></i>เวอร์ชันปัจจุบัน: <strong class="ms-1"><?= esc($dbStatus['version'] ?? '-') ?></strong>
              </span>
              <span class="badge bg-light text-dark border">
                <i class="bi bi-check2-square me-1"></i>อัพเดทแล้ว: <?= (int)($dbStatus['applied'] ?? 0) ?>/<?= (int)($dbStatus['total'] ?? 0) ?> รายการ
              </span>
              <?php if (($dbStatus['pending'] ?? 0) > 0): ?>
                <span class="badge bg-warning text-dark">
                  <i class="bi bi-exclamation-circle me-1"></i>มีรายการรออัพเดท <?= (int)$dbStatus['pending'] ?> รายการ
                </span>
              <?php else: ?>
                <span class="badge bg-success">
                  <i class="bi bi-check-circle me-1"></i>เป็นเวอร์ชันล่าสุด
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-md-5 text-md-end">
            <form method="post" action="<?= site_url('/settings/update-database') ?>"
                  onsubmit="return confirm('ยืนยันอัพเดทโครงสร้างฐานข้อมูล?\n\nระบบจะเพิ่มตาราง/คอลัมน์ใหม่เท่านั้น ไม่ลบข้อมูลเดิม\nแนะนำให้สำรอง (backup) ฐานข้อมูลก่อนทุกครั้ง');">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-info text-white px-4">
                <i class="bi bi-arrow-repeat me-1"></i>อัพเดทฐานข้อมูลตอนนี้
              </button>
              <div class="form-text mt-2">
                <i class="bi bi-shield-check me-1"></i>ควร backup ฐานข้อมูลก่อนทุกครั้งเพื่อความปลอดภัย
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>

<script>
  // Live preview
  const bind = (inputName, elemId, prefix = '', suffix = '') => {
    const input = document.querySelector(`[name="${inputName}"]`);
    const elem  = document.getElementById(elemId);
    if (!input || !elem) return;
    input.addEventListener('input', () => {
      const val = input.value.trim();
      if (elemId === 'prev-taxid') {
        elem.textContent = val ? `VAT: ${val}` : '';
        elem.style.display = val ? '' : 'none';
      } else {
        elem.textContent = prefix + (val || input.placeholder) + suffix;
      }
    });
  };
  bind('shop_name',    'prev-name');
  bind('shop_address', 'prev-address');
  bind('shop_tax_id',  'prev-taxid');
  bind('cashier_name', 'prev-cashier');

  // Bill font color — color picker ↔ hex text input ↔ live preview
  const colorPicker = document.getElementById('bill_font_color');
  const colorHex    = document.getElementById('bill_font_color_hex');
  const preview     = document.getElementById('bill-preview');

  const applyColor = (hex) => {
    if (preview) preview.style.color = hex;
  };

  if (colorPicker) {
    colorPicker.addEventListener('input', () => {
      colorHex.value = colorPicker.value;
      applyColor(colorPicker.value);
    });
  }
  if (colorHex) {
    colorHex.addEventListener('input', () => {
      const hex = colorHex.value.trim();
      if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
        colorPicker.value = hex;
        applyColor(hex);
      }
    });
  }

  // Preset swatches
  document.querySelectorAll('.color-swatch').forEach(btn => {
    btn.addEventListener('click', () => {
      const hex = btn.dataset.color;
      colorPicker.value = hex;
      colorHex.value    = hex;
      applyColor(hex);
    });
  });

  // Per-section font size sliders → live preview
  // Maps slider data-prev value to which CSS property on the target element to update
  document.querySelectorAll('.fs-slider').forEach(slider => {
    slider.addEventListener('input', () => {
      const px      = slider.value;
      const labelEl = document.getElementById(slider.dataset.label);
      const prevEl  = document.getElementById(slider.dataset.prev);
      if (labelEl) labelEl.textContent = px + 'px';
      if (prevEl)  prevEl.style.fontSize = px + 'px';
    });
  });

  // ── Cash Drawer Agent ──────────────────────────────────────────────────────
  function getAgentBase() {
    const port = document.querySelector('[name="agent_port"]')?.value || '9991';
    return `http://127.0.0.1:${port}`;
  }

  async function checkAgentStatus() {
    const badge  = document.getElementById('agentStatusBadge');
    try {
      const r = await fetch(getAgentBase() + '/status', { signal: AbortSignal.timeout(2000) });
      const d = await r.json();
      if (d.ok) {
        badge.className = 'badge bg-success ms-1';
        badge.innerHTML = '<i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i>Online';
      } else throw new Error();
    } catch {
      badge.className = 'badge bg-danger ms-1';
      badge.innerHTML = '<i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i>Offline';
    }
  }

  document.getElementById('testAgentBtn')?.addEventListener('click', async function () {
    const result  = document.getElementById('agentTestResult');
    const token   = document.getElementById('agent_token')?.value;
    result.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split me-1"></i>กำลังทดสอบ...</span>';
    try {
      const r = await fetch(getAgentBase() + '/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Agent-Token': token },
        body: JSON.stringify({ triggeredBy: 'settings-test',
                               printerName: document.getElementById('receipt_printer')?.value || undefined }),
        signal: AbortSignal.timeout(4000),
      });
      const d = await r.json();
      if (d.ok) {
        result.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>เชื่อมต่อสำเร็จ! ปริ้นเตอร์ตอบสนอง</span>';
      } else {
        result.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Error: ${d.error}</span>`;
      }
    } catch (e) {
      result.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>เชื่อมต่อไม่ได้ — agent ทำงานอยู่ไหม? (${e.message})</span>`;
    }
    checkAgentStatus();
  });

  checkAgentStatus();
  setInterval(checkAgentStatus, 15000); // ตรวจทุก 15 วินาที

  // ── ตั้งค่าอัตโนมัติ: จับคู่ token + โหลดเครื่องพิมพ์ + เดาเครื่องสลิป ─────────
  function fillPrinterSelect(printers, suggested) {
    const sel = document.getElementById('receipt_printer');
    const saved = sel.value;
    sel.innerHTML = '<option value="">-- ไม่ระบุ (ใช้ค่าใน config ของ agent) --</option>';
    printers.forEach(name => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name + (name === suggested ? ' ★ (น่าจะเป็นปริ้นสลิป)' : '');
      sel.appendChild(opt);
    });
    // เลือกตามลำดับ: ค่าที่บันทึกไว้ > ตัวที่ agent เดา > ว่าง
    if (saved && printers.includes(saved)) sel.value = saved;
    else if (suggested)                    sel.value = suggested;
  }

  document.getElementById('autoSetupBtn')?.addEventListener('click', async function () {
    const result = document.getElementById('agentTestResult');
    this.disabled = true;
    result.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split me-1"></i>กำลังค้นหา Agent...</span>';
    try {
      const r = await fetch(getAgentBase() + '/pair', { signal: AbortSignal.timeout(10000) });
      const d = await r.json();
      if (!d.ok) throw new Error(d.error || 'pair failed');

      document.getElementById('agent_token').value = d.token;
      if (d.printers && d.printers.length) fillPrinterSelect(d.printers, d.suggested);

      result.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>'
        + 'จับคู่สำเร็จ! ใส่ Token ให้แล้ว'
        + (d.suggested ? ` — เดาเครื่องสลิป: <strong>${d.suggested}</strong>` : '')
        + ' — อย่าลืมกด <strong>บันทึกการตั้งค่า</strong> ด้านล่าง</span>';
      checkAgentStatus();
    } catch (e) {
      result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>'
        + 'หา Agent ไม่เจอ — เปิดโปรแกรม local-agent/start.bat บนเครื่องนี้ก่อน แล้วลองใหม่</span>';
    }
    this.disabled = false;
  });

  // ── โหลดรายชื่อเครื่องพิมพ์ (ปุ่ม ⟳) ─────────────────────────────────────────
  async function loadPrinters(silent) {
    const token = document.getElementById('agent_token')?.value;
    if (!token) { if (!silent) alert('กด "ตั้งค่าอัตโนมัติ" ก่อน เพื่อจับคู่กับ Agent'); return; }
    try {
      const r = await fetch(getAgentBase() + '/printers', {
        headers: { 'X-Agent-Token': token },
        signal: AbortSignal.timeout(10000),
      });
      const d = await r.json();
      if (d.ok) fillPrinterSelect(d.printers, d.suggested);
      else if (!silent) alert('โหลดรายชื่อไม่ได้: ' + d.error);
    } catch (e) {
      if (!silent) alert('เชื่อมต่อ Agent ไม่ได้ — agent ทำงานอยู่ไหม?');
    }
  }
  document.getElementById('refreshPrintersBtn')?.addEventListener('click', () => loadPrinters(false));
  loadPrinters(true); // โหลดอัตโนมัติตอนเปิดหน้า (เงียบ ๆ ถ้าไม่เจอ)

  // ── ทดสอบพิมพ์สลิป: ส่ง ESC/POS ตัวอักษรอังกฤษล้วนไปเครื่องที่เลือก ─────────
  // ถ้าพิมพ์ออกมาอ่านได้ = เครื่องนี้ใช้พิมพ์สลิปได้ / ถ้าเป็นตัวอักษรต่างดาว = ผิดเครื่อง
  // (เช่น เลือกเครื่องพิมพ์สติกเกอร์ที่พูดภาษา TSPL ไม่ใช่ ESC/POS)
  document.getElementById('testPrintBtn')?.addEventListener('click', async function () {
    const token   = document.getElementById('agent_token')?.value;
    const printer = document.getElementById('receipt_printer')?.value;
    const result  = document.getElementById('testPrintResult');
    if (!token)   { alert('กด "ตั้งค่าอัตโนมัติ" ก่อน เพื่อจับคู่กับ Agent'); return; }
    if (!printer) { alert('เลือกเครื่องพิมพ์ใบเสร็จก่อน'); return; }
    result.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split me-1"></i>กำลังส่งสลิปทดสอบ...</span>';
    const text = '\x1B\x40' // ESC @ init
      + '================================\n'
      + '       POS TEST PRINT OK\n'
      + '================================\n'
      + 'Printer: ' + printer.replace(/[^\x20-\x7E]/g, '?') + '\n'
      + 'If you can read this, this\n'
      + 'printer works for receipts.\n\n\n\n'
      + '\x1D\x56\x42\x00'; // partial cut
    const bytes = new Uint8Array([...text].map(c => c.charCodeAt(0) & 0xFF));
    let b64 = ''; bytes.forEach(b => b64 += String.fromCharCode(b)); b64 = btoa(b64);
    try {
      const r = await fetch(getAgentBase() + '/print', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Agent-Token': token },
        body: JSON.stringify({ printerName: printer, dataBase64: b64 }),
        signal: AbortSignal.timeout(15000),
      });
      const d = await r.json();
      if (d.ok) {
        result.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>'
          + 'ส่งแล้ว — ถ้าสลิปอ่านได้ = เครื่องถูกต้อง / ถ้าเป็นตัวอักษรต่างดาว = เครื่องนี้ไม่ใช่ปริ้นสลิป ให้เลือกเครื่องอื่น</span>';
      } else {
        result.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Error: ${d.error}</span>`;
      }
    } catch (e) {
      result.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>เชื่อมต่อ Agent ไม่ได้ (${e.message})</span>`;
    }
  });
</script>

<?= view('templates/footer') ?>
