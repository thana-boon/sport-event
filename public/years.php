<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$pdo = db();
$errors = [];
$messages = [];

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $year_be = (int)($_POST['year_be'] ?? 0);
    $title   = trim($_POST['title'] ?? '');

    if ($year_be < 2400 || $year_be > 2800) {
        $errors[] = 'ปีการศึกษาควรเป็น พ.ศ. ตัวเลขระหว่าง 2400–2800';
    }
    if ($title === '') {
        $title = 'ปีการศึกษา ' . $year_be;
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO academic_years (year_be, title) VALUES (?, ?)');
            $stmt->execute([$year_be, $title]);
            $messages[] = 'เพิ่มปีการศึกษาเรียบร้อย';
        } catch (Throwable $e) {
            $errors[] = 'ไม่สามารถเพิ่มปีการศึกษาได้ (อาจซ้ำกัน): ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id      = (int)($_POST['id'] ?? 0);
    $year_be = (int)($_POST['year_be'] ?? 0);
    $title   = trim($_POST['title'] ?? '');

    if ($id <= 0) $errors[] = 'ไม่พบรายการที่ต้องการแก้ไข';
    if ($year_be < 2400 || $year_be > 2800) $errors[] = 'ปีการศึกษาควรเป็น พ.ศ. 2400–2800';
    if ($title === '') $title = 'ปีการศึกษา ' . $year_be;

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('UPDATE academic_years SET year_be = ?, title = ? WHERE id = ?');
            $stmt->execute([$year_be, $title, $id]);
            $messages[] = 'แก้ไขปีการศึกษาเรียบร้อย';
        } catch (Throwable $e) {
            $errors[] = 'ไม่สามารถแก้ไขได้: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $errors[] = 'ไม่พบรายการที่ต้องการลบ';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM academic_years WHERE id = ?');
            $stmt->execute([$id]);
            $messages[] = 'ลบปีการศึกษาเรียบร้อย';
        } catch (Throwable $e) {
            $errors[] = 'ไม่สามารถลบได้ (อาจมีข้อมูลเชื่อมโยงภายหลัง): ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// SET ACTIVE (ให้มีได้ทีละปี)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'activate') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $errors[] = 'ไม่พบรายการที่ต้องการตั้งค่า Active';
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->exec('UPDATE academic_years SET is_active = 0'); // ปิดทั้งหมด
            $stmt = $pdo->prepare('UPDATE academic_years SET is_active = 1 WHERE id = ?');
            $stmt->execute([$id]);
            $pdo->commit();
            $messages[] = 'ตั้งปีการศึกษาปัจจุบันเรียบร้อย';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'ไม่สามารถตั้งค่า Active ได้: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// ดึงรายการทั้งหมดเรียงจากใหม่ไปเก่า โดย active มาก่อน
$stmt = $pdo->query('SELECT id, year_be, title, is_active, created_at FROM academic_years ORDER BY is_active DESC, year_be DESC, id DESC');
$years = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <div class="row">
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
          <h5 class="card-title mb-3">เพิ่มปีการศึกษา</h5>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <?php echo implode('<br>', array_map(fn($x)=>htmlspecialchars($x,ENT_QUOTES,'UTF-8'), $errors)); ?>
            </div>
          <?php endif; ?>

          <?php if ($messages): ?>
            <div class="alert alert-success">
              <?php echo implode('<br>', array_map(fn($x)=>htmlspecialchars($x,ENT_QUOTES,'UTF-8'), $messages)); ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?php echo BASE_URL; ?>/years.php" class="row g-3">
            <input type="hidden" name="action" value="create">
            <div class="col-12">
              <label class="form-label">ปีการศึกษา (พ.ศ.)</label>
              <input type="number" class="form-control" name="year_be" placeholder="เช่น 2568" required>
            </div>
            <div class="col-12">
              <label class="form-label">ชื่อ/คำอธิบาย (ไม่ใส่ก็ได้)</label>
              <input type="text" class="form-control" name="title" placeholder="เช่น ปีการศึกษา 2568 เทอม 1">
            </div>
            <div class="col-12 d-grid">
              <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
          </form>
        </div>
      </div>
      <a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">&larr; กลับแดชบอร์ด</a>
    </div>

    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <h5 class="card-title mb-3">รายการปีการศึกษา</h5>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th style="width: 120px;">ปี (พ.ศ.)</th>
                  <th>ชื่อ/คำอธิบาย</th>
                  <th style="width: 140px;">สถานะ</th>
                  <th style="width: 220px;">การทำงาน</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$years): ?>
                  <tr><td colspan="4" class="text-muted">ยังไม่มีข้อมูล</td></tr>
                <?php else: foreach ($years as $y): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($y['year_be'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($y['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <?php if ((int)$y['is_active'] === 1): ?>
                        <span class="badge bg-success">กำลังใช้งาน</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">ปิด</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <?php if ((int)$y['is_active'] !== 1): ?>
                          <form method="post" action="<?php echo BASE_URL; ?>/years.php" onsubmit="return confirm('ตั้งปีการศึกษานี้เป็นปีที่ใช้งานอยู่?');">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="id" value="<?php echo (int)$y['id']; ?>">
                            <button class="btn btn-sm btn-outline-success">ตั้งเป็น Active</button>
                          </form>
                        <?php endif; ?>

                        <!-- ปุ่มแก้ไข เปิด modal -->
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?php echo (int)$y['id']; ?>"
                                data-year="<?php echo (int)$y['year_be']; ?>"
                                data-title="<?php echo htmlspecialchars($y['title'], ENT_QUOTES, 'UTF-8'); ?>">
                          แก้ไข
                        </button>

                        <form method="post" action="<?php echo BASE_URL; ?>/years.php" onsubmit="return confirm('ต้องการลบรายการนี้?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo (int)$y['id']; ?>">
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
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/years.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขปีการศึกษา</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">ปีการศึกษา (พ.ศ.)</label>
          <input type="number" class="form-control" name="year_be" id="edit-year" required>
        </div>
        <div class="mb-3">
          <label class="form-label">ชื่อ/คำอธิบาย</label>
          <input type="text" class="form-control" name="title" id="edit-title" placeholder="เช่น ปีการศึกษา 2568 เทอม 1">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// เติมค่าเข้า modal ตอนกดแก้ไข
const editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('edit-id').value    = button.getAttribute('data-id');
    document.getElementById('edit-year').value  = button.getAttribute('data-year');
    document.getElementById('edit-title').value = button.getAttribute('data-title');
  });
}
</script>
