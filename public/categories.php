<?php
// public/categories.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$pdo = db();
$errors = [];
$messages = [];

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ---- หา year ปัจจุบัน + ปีที่แล้ว ----
$yearId     = active_year_id($pdo);
$prevYearId = previous_year_id($pdo);

if (!$yearId) {
    // ยังไม่ได้ตั้งปีการศึกษาเป็น Active
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/navbar.php';
    echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้เป็น Active กรุณาไปที่ <a href="'.BASE_URL.'/years.php">กำหนดปีการศึกษา</a> แล้วตั้งค่า Active ก่อน</div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

/* =========================================================
   ACTIONS
   ========================================================= */

/* CREATE: เพิ่มประเภทกีฬาใหม่ (default ในตารางแม่) + สร้างค่า per-year ให้ปีปัจจุบันทันที */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $max  = (int)($_POST['max_per_student'] ?? 1);
  $max  = max(0, min(99, $max)); // 0=ไม่จำกัด

  if ($name === '') $errors[] = 'กรุณากรอกชื่อประเภทกีฬา';

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      // เพิ่มประเภทในตารางแม่ (ค่า default)
      $stmt = $pdo->prepare("INSERT INTO sport_categories (name, description, max_per_student, is_active) VALUES (?,?,?,1)");
      $stmt->execute([$name, $desc, $max]);
      $catId = (int)$pdo->lastInsertId();

      // สร้างค่าต่อปีให้ปีปัจจุบัน (ให้มีผลทันที)
      $st2 = $pdo->prepare("INSERT INTO category_year_settings (year_id, category_id, max_per_student, is_active)
                            VALUES(?,?,?,1)
                            ON DUPLICATE KEY UPDATE max_per_student=VALUES(max_per_student), is_active=VALUES(is_active)");
      $st2->execute([$yearId, $catId, $max]);

      $pdo->commit();
      
      // 🔥 LOG: เพิ่มประเภทกีฬาสำเร็จ
      log_activity('CREATE', 'sport_categories', $catId, 
        sprintf("เพิ่มประเภทกีฬา: %s | คำอธิบาย: %s | จำกัด/คน: %s | ปีการศึกษา ID:%d", 
          $name, 
          $desc ?: '-', 
          $max === 0 ? 'ไม่จำกัด' : $max,
          $yearId));
      
      $messages[] = 'เพิ่มประเภทกีฬาเรียบร้อย';
    } catch (Throwable $e) {
      $pdo->rollBack();
      
      // 🔥 LOG: เพิ่มประเภทกีฬาไม่สำเร็จ
      log_activity('ERROR', 'sport_categories', null, 
        sprintf("เพิ่มประเภทกีฬาไม่สำเร็จ: %s | ชื่อ: %s", $e->getMessage(), $name));
      
      $errors[] = 'เพิ่มไม่สำเร็จ (อาจชื่อซ้ำกัน): '.e($e->getMessage());
    }
  }
}

/* UPDATE: อัปเดตชื่อ/คำอธิบายในตารางแม่ + ค่าต่อปีในปีปัจจุบัน */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $id   = (int)($_POST['id'] ?? 0);              // category_id
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $max  = max(0, min(99, (int)($_POST['max_per_student'] ?? 1)));
  $active = isset($_POST['is_active']) ? 1 : 0;

  if ($id<=0) $errors[] = 'ไม่พบรายการ';
  if ($name==='') $errors[] = 'กรุณากรอกชื่อประเภทกีฬา';

  if (!$errors) {
    try {
      // ดึงข้อมูลเดิมก่อนแก้ไข
      $oldStmt = $pdo->prepare("
        SELECT sc.name, sc.description,
               COALESCE(cys.max_per_student, sc.max_per_student) AS old_max,
               COALESCE(cys.is_active, sc.is_active) AS old_active
        FROM sport_categories sc
        LEFT JOIN category_year_settings cys ON cys.category_id = sc.id AND cys.year_id = ?
        WHERE sc.id = ?
      ");
      $oldStmt->execute([$yearId, $id]);
      $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
      
      $pdo->beginTransaction();

      // อัปเดตชื่อ/คำอธิบายในตารางแม่ (ค่า default)
      $st1 = $pdo->prepare("UPDATE sport_categories SET name=?, description=? WHERE id=?");
      $st1->execute([$name, $desc, $id]);

      // upsert ค่าต่อปี (มีผลเฉพาะปีปัจจุบัน)
      $st2 = $pdo->prepare("
        INSERT INTO category_year_settings(year_id, category_id, max_per_student, is_active)
        VALUES(?,?,?,?)
        ON DUPLICATE KEY UPDATE max_per_student=VALUES(max_per_student), is_active=VALUES(is_active)
      ");
      $st2->execute([$yearId, $id, $max, $active]);

      $pdo->commit();
      
      // 🔥 LOG: แก้ไขประเภทกีฬาสำเร็จ
      if ($oldData) {
        $changes = [];
        if ($oldData['name'] !== $name) $changes[] = "ชื่อ: {$oldData['name']} → {$name}";
        if ($oldData['description'] !== $desc) $changes[] = "คำอธิบาย: " . ($oldData['description'] ?: '-') . " → " . ($desc ?: '-');
        if ((int)$oldData['old_max'] !== $max) {
          $oldMaxText = (int)$oldData['old_max'] === 0 ? 'ไม่จำกัด' : (int)$oldData['old_max'];
          $newMaxText = $max === 0 ? 'ไม่จำกัด' : $max;
          $changes[] = "จำกัด/คน: {$oldMaxText} → {$newMaxText}";
        }
        if ((int)$oldData['old_active'] !== $active) {
          $changes[] = "สถานะ: " . ((int)$oldData['old_active'] ? 'เปิด' : 'ปิด') . " → " . ($active ? 'เปิด' : 'ปิด');
        }
        
        log_activity('UPDATE', 'sport_categories', $id, 
          sprintf("แก้ไขประเภทกีฬา: %s | %s | ปีการศึกษา ID:%d", 
            $name,
            !empty($changes) ? implode(' | ', $changes) : 'ไม่มีการเปลี่ยนแปลง',
            $yearId));
      } else {
        log_activity('UPDATE', 'sport_categories', $id, 
          sprintf("แก้ไขประเภทกีฬา ID:%d → %s | ปีการศึกษา ID:%d", $id, $name, $yearId));
      }
      
      $messages[] = 'บันทึกค่าประเภทกีฬา (ปีนี้) เรียบร้อย';
    } catch (Throwable $e) {
      $pdo->rollBack();
      
      // 🔥 LOG: แก้ไขประเภทกีฬาไม่สำเร็จ
      log_activity('ERROR', 'sport_categories', $id, 
        sprintf("แก้ไขประเภทกีฬาไม่สำเร็จ: %s | ID:%d | ชื่อ: %s", 
          $e->getMessage(), $id, $name));
      
      $errors[] = 'แก้ไขไม่สำเร็จ: '.e($e->getMessage());
    }
  }
}

/* DELETE: ลบประเภทกีฬา (หากมี sports ผูกไว้จะลบไม่ได้ตาม FK) */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) {
    $errors[] = 'ไม่พบรายการที่ต้องการลบ';
  } else {
    try {
      // ดึงข้อมูลก่อนลบ
      $oldStmt = $pdo->prepare("SELECT name, description FROM sport_categories WHERE id=?");
      $oldStmt->execute([$id]);
      $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
      
      $stmt = $pdo->prepare("DELETE FROM sport_categories WHERE id=?");
      $stmt->execute([$id]);
      
      // 🔥 LOG: ลบประเภทกีฬาสำเร็จ
      if ($oldData) {
        log_activity('DELETE', 'sport_categories', $id, 
          sprintf("ลบประเภทกีฬา: %s | คำอธิบาย: %s", 
            $oldData['name'], 
            $oldData['description'] ?: '-'));
      } else {
        log_activity('DELETE', 'sport_categories', $id, 
          sprintf("ลบประเภทกีฬา ID:%d", $id));
      }
      
      $messages[] = 'ลบประเภทกีฬาเรียบร้อย';
    } catch (Throwable $e) {
      // 🔥 LOG: ลบประเภทกีฬาไม่สำเร็จ
      log_activity('ERROR', 'sport_categories', $id, 
        sprintf("ลบประเภทกีฬาไม่สำเร็จ: %s | ID:%d", $e->getMessage(), $id));
      
      $errors[] = 'ลบไม่สำเร็จ (อาจมีรายการกีฬาที่ผูกอยู่): '.e($e->getMessage());
    }
  }
}

/* COPY FROM PREVIOUS YEAR: คัดลอกค่าต่อปีจากปีที่แล้ว -> ปีนี้ */
if ($action === 'copy_prev_year' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$prevYearId) {
    $errors[] = 'ไม่พบปีการศึกษาก่อนหน้า';
  } else {
    try {
      $pdo->beginTransaction();

      // นับจำนวนก่อนคัดลอก
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM category_year_settings WHERE year_id = ?");
      $countStmt->execute([$prevYearId]);
      $totalCopied = $countStmt->fetchColumn();

      // 1) คัดลอกจากปีที่แล้ว (ถ้ามีอยู่แล้วให้อัปเดต)
      $sql = "
        INSERT INTO category_year_settings (year_id, category_id, max_per_student, is_active)
        SELECT :cur, cys.category_id, cys.max_per_student, cys.is_active
        FROM category_year_settings cys
        WHERE cys.year_id = :prev
        ON DUPLICATE KEY UPDATE
          max_per_student = VALUES(max_per_student),
          is_active = VALUES(is_active)";
      $st = $pdo->prepare($sql);
      $st->execute([':cur'=>$yearId, ':prev'=>$prevYearId]);

      // 2) เผื่อปีที่แล้วไม่มีค่า/ประเภทใหม่ → เติมจาก default ของแม่
      $sql2 = "
        INSERT IGNORE INTO category_year_settings (year_id, category_id, max_per_student, is_active)
        SELECT :cur, sc.id, sc.max_per_student, sc.is_active
        FROM sport_categories sc";
      $pdo->prepare($sql2)->execute([':cur'=>$yearId]);

      $pdo->commit();
      
      // 🔥 LOG: คัดลอกจากปีที่แล้วสำเร็จ
      log_activity('COPY', 'category_year_settings', null, 
        sprintf("คัดลอกค่าประเภทกีฬาจากปีที่แล้ว: %d รายการ | จาก ปี ID:%d → ปี ID:%d", 
          $totalCopied, $prevYearId, $yearId));
      
      $messages[] = 'คัดลอกค่าจากปีการศึกษาที่แล้วเรียบร้อย';
    } catch (Throwable $e) {
      $pdo->rollBack();
      
      // 🔥 LOG: คัดลอกจากปีที่แล้วไม่สำเร็จ
      log_activity('ERROR', 'category_year_settings', null, 
        sprintf("คัดลอกค่าประเภทกีฬาไม่สำเร็จ: %s | จาก ปี ID:%d → ปี ID:%d", 
          $e->getMessage(), $prevYearId, $yearId));
      
      $errors[] = 'คัดลอกไม่สำเร็จ: '.e($e->getMessage());
    }
  }
}

/* =========================================================
   LIST + FILTER (ค่าที่มีผลในปีปัจจุบัน)
   ========================================================= */

$q = trim($_GET['q'] ?? '');
$onlyActive = isset($_GET['only_active']) ? 1 : 0;

$where = [];
$params = [':year_id' => $yearId];
if ($q !== '') { $where[] = "(sc.name LIKE :q OR sc.description LIKE :q)"; $params[':q'] = '%'.$q.'%'; }
if ($onlyActive) { $where[] = "COALESCE(cys.is_active, sc.is_active) = 1"; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

$sqlList = "
  SELECT sc.id, sc.name, sc.description,
         COALESCE(cys.max_per_student, sc.max_per_student) AS eff_max,
         COALESCE(cys.is_active, sc.is_active)             AS eff_active,
         cys.id AS setting_id
  FROM sport_categories sc
  LEFT JOIN category_year_settings cys
    ON cys.category_id = sc.id AND cys.year_id = :year_id
  $whereSql
  ORDER BY eff_active DESC, sc.name ASC";
$stmt = $pdo->prepare($sqlList);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   VIEW
   ========================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <div class="row g-3">
    <!-- LEFT: CREATE -->
    <div class="col-lg-4">
      <div class="card rounded-4 shadow-sm border-0">
        <div class="card-body">
          <h5 class="card-title mb-3">สร้างประเภทกีฬา</h5>

          <?php if ($errors): ?>
            <div class="alert alert-danger"><?php echo implode('<br>', array_map('e',$errors)); ?></div>
          <?php endif; ?>
          <?php if ($messages): ?>
            <div class="alert alert-success"><?php echo implode('<br>', array_map('e',$messages)); ?></div>
          <?php endif; ?>

          <form method="post" action="<?php echo BASE_URL; ?>/categories.php" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-12">
              <label class="form-label">ชื่อประเภทกีฬา</label>
              <input type="text" class="form-control" name="name" placeholder="เช่น กรีฑา, กีฬาไทย, กีฬาในร่ม" required>
            </div>
            <div class="col-12">
              <label class="form-label">คำอธิบาย (ถ้ามี)</label>
              <input type="text" class="form-control" name="description" placeholder="รายละเอียดสั้น ๆ">
            </div>
            <div class="col-12">
              <label class="form-label">จำกัดจำนวนต่อคนในประเภทนี้ (ปีนี้)</label>
              <div class="input-group">
                <input type="number" class="form-control" name="max_per_student" min="0" value="1" required>
                <span class="input-group-text">รายการ</span>
              </div>
              <div class="form-text">ใส่ 0 = ไม่จำกัด (เพียงแจ้งเตือนในหน้าลงทะเบียน)</div>
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-primary">บันทึก</button>
            </div>
          </form>
        </div>
      </div>

    </div>

    <!-- RIGHT: LIST -->
    <div class="col-lg-8">
      <div class="card rounded-4 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
            <h5 class="card-title mb-0">รายการประเภทกีฬา</h5>

            <div class="d-flex flex-wrap gap-2">
              <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/categories.php">
                <div class="col-auto">
                  <label class="form-label">ค้นหา</label>
                  <input type="text" class="form-control" name="q" value="<?php echo e($q); ?>" placeholder="ชื่อประเภท / คำอธิบาย">
                </div>
                <div class="col-auto">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="only_active" name="only_active" <?php echo $onlyActive?'checked':''; ?>>
                    <label class="form-check-label" for="only_active">แสดงเฉพาะที่เปิดใช้งาน</label>
                  </div>
                </div>
                <div class="col-auto">
                  <button class="btn btn-primary">ค้นหา</button>
                </div>
              </form>

              <?php if ($prevYearId): ?>
              <form method="post" action="<?php echo BASE_URL; ?>/categories.php" onsubmit="return confirm('คัดลอกค่าจากปีที่แล้วมาปีนี้?');">
                <input type="hidden" name="action" value="copy_prev_year">
                <button class="btn btn-outline-secondary" type="submit">คัดลอกจากปีที่แล้ว</button>
              </form>
              <?php endif; ?>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>ชื่อ</th>
                  <th>คำอธิบาย</th>
                  <th class="text-center" style="width:160px;">จำกัด/คน (ปีนี้)</th>
                  <th class="text-center" style="width:120px;">สถานะ (ปีนี้)</th>
                  <th style="width:210px;">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$rows): ?>
                  <tr><td colspan="5" class="text-muted">ยังไม่มีข้อมูล</td></tr>
                <?php else: foreach ($rows as $r): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo e($r['name']); ?></td>
                    <td><?php echo e($r['description']); ?></td>
                    <td class="text-center">
                      <?php echo ((int)$r['eff_max'] === 0) ? '<span class="badge bg-info">ไม่จำกัด</span>' : (int)$r['eff_max']; ?>
                    </td>
                    <td class="text-center">
                      <?php echo ((int)$r['eff_active']===1) ? '<span class="badge bg-success">เปิด</span>' : '<span class="badge bg-secondary">ปิด</span>'; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?php echo (int)$r['id']; ?>"
                                data-name="<?php echo e($r['name']); ?>"
                                data-description="<?php echo e($r['description']); ?>"
                                data-max="<?php echo (int)$r['eff_max']; ?>"
                                data-active="<?php echo (int)$r['eff_active']; ?>">
                          แก้ไข
                        </button>
                        <form method="post" action="<?php echo BASE_URL; ?>/categories.php" onsubmit="return confirm('ต้องการลบประเภทนี้?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger">ลบ</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/categories.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขประเภทกีฬา (ปีปัจจุบัน)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-12">
          <label class="form-label">ชื่อประเภทกีฬา</label>
          <input type="text" class="form-control" id="edit-name" name="name" required>
        </div>
        <div class="col-12">
          <label class="form-label">คำอธิบาย</label>
          <input type="text" class="form-control" id="edit-description" name="description">
        </div>
        <div class="col-12">
          <label class="form-label">จำกัดจำนวนต่อคนในประเภทนี้ (ปีนี้)</label>
          <div class="input-group">
            <input type="number" class="form-control" id="edit-max" name="max_per_student" min="0" required>
            <span class="input-group-text">รายการ</span>
          </div>
          <div class="form-text">0 = ไม่จำกัด (จะแจ้งเตือนตอนลงทะเบียนแต่ยังอนุญาตให้ลงได้)</div>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="edit-active" name="is_active" value="1">
            <label class="form-check-label" for="edit-active">เปิดใช้งาน (ปีนี้)</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
// เติมค่าจากปุ่ม "แก้ไข" ลงใน modal
const editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('edit-id').value          = b.getAttribute('data-id');
    document.getElementById('edit-name').value        = b.getAttribute('data-name');
    document.getElementById('edit-description').value = b.getAttribute('data-description') || '';
    document.getElementById('edit-max').value         = b.getAttribute('data-max');
    document.getElementById('edit-active').checked    = (b.getAttribute('data-active') === '1');
  });
}
</script>
