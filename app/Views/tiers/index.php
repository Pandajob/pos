<?= view('templates/header', ['title' => $title]) ?>

<div class="row g-4">

  <!-- Tiers table -->
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>ระดับสมาชิกทั้งหมด</h6>
        <span class="badge bg-secondary"><?= count($tiers) ?> ระดับ</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>ชื่อระดับ</th>
                <th class="text-center">ส่วนลด %</th>
                <th class="text-center">ราคาส่ง</th>
                <th>สี Badge</th>
                <th>หมายเหตุ</th>
                <th class="text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tiers)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>ยังไม่มีระดับสมาชิก
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($tiers as $tier): ?>
                  <tr>
                    <td class="text-muted"><?= $tier['id'] ?></td>
                    <td>
                      <span class="badge bg-<?= esc($tier['badge_color']) ?> fs-6 px-3">
                        <?= esc($tier['name']) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <?php if ($tier['discount_pct'] > 0): ?>
                        <span class="badge bg-success"><?= $tier['discount_pct'] ?>%</span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if ($tier['is_wholesale']): ?>
                        <span class="badge bg-danger">ใช้ราคาส่ง</span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <code class="small"><?= esc($tier['badge_color']) ?></code>
                    </td>
                    <td class="text-muted small"><?= esc($tier['description'] ?? '') ?></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary me-1"
                              data-bs-toggle="modal" data-bs-target="#editModal<?= $tier['id'] ?>">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="post" action="<?= site_url('/tiers/delete/' . $tier['id']) ?>"
                            class="d-inline"
                            onsubmit="return confirm('ลบระดับ <?= esc($tier['name'], 'js') ?> ?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>

                  <!-- Edit Modal -->
                  <div class="modal fade" id="editModal<?= $tier['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form method="post" action="<?= site_url('/tiers/update/' . $tier['id']) ?>">
                          <?= csrf_field() ?>
                          <div class="modal-header">
                            <h6 class="modal-title fw-bold">แก้ไขระดับสมาชิก</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <?= view('tiers/_form', ['tier' => $tier]) ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">
                              <i class="bi bi-check-circle me-1"></i>บันทึก
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add form -->
  <div class="col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>เพิ่มระดับใหม่</h6>
      </div>
      <div class="card-body">
        <form method="post" action="<?= site_url('/tiers/store') ?>">
          <?= csrf_field() ?>
          <?= view('tiers/_form', ['tier' => null]) ?>
          <button type="submit" class="btn btn-success w-100 mt-3">
            <i class="bi bi-plus-circle me-1"></i>เพิ่มระดับ
          </button>
        </form>
      </div>
    </div>

    <!-- Badge color hint -->
    <div class="card shadow-sm border-0 mt-3">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-muted small">ตัวอย่างสี Badge (Bootstrap)</h6>
      </div>
      <div class="card-body py-2">
        <?php foreach (['primary','secondary','success','danger','warning','info','dark'] as $c): ?>
          <span class="badge bg-<?= $c ?> me-1 mb-1"><?= $c ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<?= view('templates/footer') ?>
