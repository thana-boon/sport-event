<?php
// public/regis.php — หน้าผู้ดูแลสำหรับลงทะเบียน (admin)
// จุดสำคัญที่แก้: ใช้จำนวน placeholder ให้ตรงกับ execute() เสมอ และหุ้มด้วยทรานแซกชันอย่างปลอดภัย

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php'); exit;
}

if (!function_exists('e')) { function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
// flash message สั้น ๆ
function flash($k,$v=null){
  if ($v===null) { $x=$_SESSION['__flash'][$k]??null; unset($_SESSION['__flash'][$k]); return $x; }
  $_SESSION['__flash'][$k]=$v;
}

$pdo = db();
$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php'; exit;
}

// ---------- helpers ----------
function safeCommit(PDO $pdo){ if ($pdo->inTransaction()) $pdo->commit(); }
function safeRollback(PDO $pdo){ if ($pdo->inTransaction()) $pdo->rollBack(); }

// โหลดรายชื่อกีฬา (ปีนี้)
$stSports = $pdo->prepare("
  SELECT sp.id, sp.name, sc.name AS category_name
  FROM sports sp
  JOIN sport_categories sc ON sc.id = sp.category_id
  WHERE sp.year_id = ? AND sp.is_active = 1
  ORDER BY sc.name, sp.name
");
$stSports->execute([$yearId]);
$sports = $stSports->fetchAll(PDO::FETCH_ASSOC);

// ---------- handle POST: ลงทะเบียนนักเรียน ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='add_regs') {
  $sportId   = (int)($_POST['sport_id'] ?? 0);
  // รับ student_ids ได้ทั้งแบบหลาย select name="student_ids[]" หรือกล่องข้อความคั่นด้วยจุลภาค
  $studentIds = [];
  if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
    foreach ($_POST['student_ids'] as $sid) {
      $sid = (int)$sid;
      if ($sid>0) $studentIds[]=$sid;
    }
  } else {
    // เผื่อรับจาก input text ชนิด "1,2,3"
    $line = trim((string)($_POST['student_ids_text'] ?? ''));
    if ($line!=='') {
      foreach (explode(',', $line) as $sid) {
        $sid = (int)trim($sid);
        if ($sid>0) $studentIds[]=$sid;
      }
    }
  }
  $studentIds = array_values(array_unique($studentIds));

  if (!$sportId || !$studentIds) {
    flash('err', 'กรุณาเลือกกีฬาและรายชื่อนักเรียนอย่างน้อย 1 คน');
    header('Location: '.BASE_URL.'/regis.php'); exit;
  }

  try{
    if (!$pdo->inTransaction()) $pdo->beginTransaction();

    // ตรวจซ้ำ: มีอยู่แล้วหรือยัง (ปีนี้/กีฬาเดียวกัน/คนเดียวกัน)
    $sel = $pdo->prepare("
      SELECT student_id FROM registrations
      WHERE year_id = ? AND sport_id = ? AND student_id IN (%s)
    ");
    $ph = implode(',', array_fill(0, count($studentIds), '?')); // จำนวน ? ให้ตรงกับ studentIds
    // rebuild statement with correct placeholders
    $sqlSel = sprintf("
      SELECT student_id FROM registrations
      WHERE year_id = ? AND sport_id = ? AND student_id IN (%s)
    ", $ph);
    $sel = $pdo->prepare($sqlSel);
    // bind: yearId, sportId, ...studentIds
    $params = array_merge([$yearId, $sportId], $studentIds);
    $sel->execute($params);
    $exists = $sel->fetchAll(PDO::FETCH_COLUMN);
    $exists = array_map('intval', $exists);

    // เตรียม insert
    $ins = $pdo->prepare("
      INSERT INTO registrations (year_id, sport_id, student_id, created_at)
      VALUES (?,?,?,NOW())
    "); // มี 3 ? -> execute ต้องส่ง 3 ค่าเท่านั้น

    $ok = 0; $skip = 0;
    foreach ($studentIds as $sid) {
      if (in_array($sid, $exists, true)) { $skip++; continue; }
      // execute ให้จำนวนพารามิเตอร์ตรงกับจำนวน ? (3 ค่า)
      $ins->execute([$yearId, $sportId, $sid]);
      $ok++;
    }

    safeCommit($pdo);
    $msg = "บันทึกลงทะเบียนสำเร็จ {$ok} รายการ";
    if ($skip>0) $msg .= " (ข้าม {$skip} รายการที่มีอยู่แล้ว)";
    flash('ok', $msg);
  }catch(Throwable $e){
    safeRollback($pdo);
    flash('err', 'บันทึกไม่สำเร็จ: '.$e->getMessage());
  }
  header('Location: '.BASE_URL.'/regis.php'); exit;
}

// ---------- สรุปตัวเลขเล็กน้อยไว้ดู ----------
$stTotalStudents = $pdo->prepare("SELECT COUNT(*) FROM students WHERE year_id=?");
$stTotalStudents->execute([$yearId]);
$totalStudents = (int)$stTotalStudents->fetchColumn();

$stRegCount = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE year_id=?");
$stRegCount->execute([$yearId]);
$totalRegs = (int)$stRegCount->fetchColumn();

// ---------- page ----------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
$ok = flash('ok'); $err = flash('err');
?>
<main class="container py-4">
  <?php if($ok): ?><div class="alert alert-success"><?php echo e($ok); ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <h5 class="mb-3">ลงทะเบียนนักเรียน (ผู้ดูแล)</h5>
          <form method="post" class="row g-3">
            <input type="hidden" name="action" value="add_regs">
            <div class="col-12">
              <label class="form-label">กีฬา</label>
              <select name="sport_id" class="form-select" required>
                <option value="">— เลือกกีฬา —</option>
                <?php foreach($sports as $sp): ?>
                  <option value="<?php echo (int)$sp['id']; ?>">
                    <?php echo e($sp['category_name'].' — '.$sp['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">รหัสนักเรียน (หลายคนคั่นด้วย ,)</label>
              <input type="text" class="form-control" name="student_ids_text" placeholder="เช่น 101,102,103">
              <div class="form-text">หรือใช้ select แบบหลายตัวเลือกด้านล่างแทนก็ได้</div>
            </div>
            <div class="col-12">
              <label class="form-label">เลือกนักเรียน (กด Ctrl/Shift เพื่อเลือกหลายคน)</label>
              <?php
                // โหลดรายชื่อนักเรียนปีนี้ (ย่อ)
                $st = $pdo->prepare("SELECT id, student_code, first_name, last_name, color FROM students WHERE year_id=? ORDER BY color, student_code");
                $st->execute([$yearId]);
                $stds = $st->fetchAll(PDO::FETCH_ASSOC);
              ?>
              <select name="student_ids[]" class="form-select" size="10" multiple>
                <?php foreach($stds as $s): ?>
                  <option value="<?php echo (int)$s['id']; ?>">
                    <?php echo e('['.$s['color'].'] '.$s['student_code'].' - '.$s['first_name'].' '.$s['last_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <button class="btn btn-primary">บันทึกลงทะเบียน</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <h6 class="mb-2">ภาพรวม (ปีนี้)</h6>
          <div class="d-flex flex-column gap-2">
            <div><span class="text-muted">จำนวนนักเรียนทั้งหมด:</span> <span class="fw-semibold"><?php echo number_format($totalStudents); ?></span></div>
            <div><span class="text-muted">รายการลงทะเบียนทั้งหมด:</span> <span class="fw-semibold"><?php echo number_format($totalRegs); ?></span></div>
          </div>
          <hr>
          <div class="text-muted small">หมายเหตุ: ระบบจะข้ามรายการที่ซ้ำ (ปี/กีฬา/นักเรียนเดิม) ให้อัตโนมัติ</div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
