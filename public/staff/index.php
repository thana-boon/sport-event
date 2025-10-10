<?php
// public/staff/index.php  (status badges per category with limit colors)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['staff'])) {
  header('Location: ' . BASE_URL . '/staff/login.php');
  exit;
}
$staff = $_SESSION['staff'];
$staffColor = $staff['color'] ?? null;

$pdo = db();
$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../../includes/header.php';
  include __DIR__ . '/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้เป็น Active กรุณาติดต่อผู้ดูแลระบบ</div></main>';
  include __DIR__ . '/../../includes/footer.php';
  exit;
}
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// -------- โหลดประเภทกีฬา + เพดานต่อคน (resolve ด้วย category_year_settings) --------
$catStmt = $pdo->prepare("
  SELECT sc.id, sc.name,
         COALESCE(cys.max_per_student, sc.max_per_student) AS max_per_student
  FROM sport_categories sc
  LEFT JOIN category_year_settings cys
    ON cys.category_id = sc.id AND cys.year_id = :y
  ORDER BY sc.name
");
$catStmt->execute([':y'=>$yearId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
$catInfo = [];
foreach ($categories as $c) {
  $catInfo[(int)$c['id']] = [
    'name' => $c['name'],
    'max'  => is_null($c['max_per_student']) ? 0 : (int)$c['max_per_student'] // 0 = ไม่จำกัด
  ];
}

// -------- ฟิลเตอร์ --------
$q = trim($_GET['q'] ?? '');
$grade = trim($_GET['grade'] ?? '');

// ดึงรายการชั้นเรียน (class_level) ของสีนี้ในปีนี้
$gs = $pdo->prepare("SELECT DISTINCT class_level FROM students WHERE year_id=? AND color=? ORDER BY class_level");
$gs->execute([$yearId, $staffColor]);
$gradeOptions = $gs->fetchAll(PDO::FETCH_COLUMN);

// นับสรุป (รวม)
$countAllStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE year_id=? AND color=?");
$countRegStmt = $pdo->prepare("
  SELECT COUNT(DISTINCT s.id)
  FROM students s
  JOIN registrations r ON r.student_id = s.id AND r.year_id = s.year_id
  WHERE s.year_id=? AND s.color=?
");
$countAllStmt->execute([$yearId, $staffColor]);
$totalStudents = (int)$countAllStmt->fetchColumn();
$countRegStmt->execute([$yearId, $staffColor]);
$registeredStudents = (int)$countRegStmt->fetchColumn();
$notRegistered = max(0, $totalStudents - $registeredStudents);

// ดึงรายชื่อนักเรียน (ตามฟิลเตอร์)
$where = ["s.year_id=:y", "s.color=:c"];
$params = [":y"=>$yearId, ":c"=>$staffColor];
if ($q !== '') {
  $where[] = "(s.student_code LIKE :q OR s.first_name LIKE :q OR s.last_name LIKE :q)";
  $params[":q"] = '%'.$q.'%';
}
if ($grade !== '') {
  $where[] = "s.class_level=:g";
  $params[":g"] = $grade;
}

$sqlStudents = "
  SELECT s.id, s.student_code,
         CONCAT(s.first_name, ' ', s.last_name) AS fullname,
         s.class_level AS grade,
         s.class_room AS room,
         s.number_in_room AS number
  FROM students s
  WHERE ".implode(' AND ', $where)."
  ORDER BY
    CASE WHEN s.class_level LIKE 'ป%' THEN 1
         WHEN s.class_level LIKE 'ม%' THEN 2
         ELSE 3 END,
    CAST(SUBSTRING(s.class_level, 2) AS UNSIGNED),
    s.class_room, s.number_in_room, s.first_name, s.last_name
";
$st = $pdo->prepare($sqlStudents);
$st->execute($params);
$students = $st->fetchAll(PDO::FETCH_ASSOC);

// -------- โหลดจำนวนที่ลงทะเบียนต่อ "นักเรียน x หมวด" เพื่อทำ badge --------
$perCatCounts = []; // [student_id][category_id] = cnt
if ($students) {
  $ids = array_map(fn($r)=> (int)$r['id'], $students);
  $in = implode(',', array_fill(0, count($ids), '?'));
  $bind = array_merge([$yearId, $staffColor], $ids);
  $qCnt = $pdo->prepare("
    SELECT r.student_id, sx.category_id, COUNT(*) AS cnt
    FROM registrations r
    JOIN sports sx ON sx.id = r.sport_id AND sx.year_id = r.year_id
    JOIN students s ON s.id = r.student_id AND s.year_id = r.year_id
    WHERE r.year_id = ? AND s.color = ? AND r.student_id IN ($in)
    GROUP BY r.student_id, sx.category_id
  ");
  $qCnt->execute($bind);
  while ($row = $qCnt->fetch(PDO::FETCH_ASSOC)) {
    $sid = (int)$row['student_id'];
    $cid = (int)$row['category_id'];
    $perCatCounts[$sid][$cid] = (int)$row['cnt'];
  }
}

// VIEW
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/navbar.php';
?>
<main class="container py-4">

  <!-- Summary cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">จำนวนนักเรียนสีของคุณ</div>
            <div class="h4 mb-0"><?php echo number_format($totalStudents); ?> คน</div>
          </div>
          <div class="badge bg-secondary fs-6">สี<?php echo e($staffColor); ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <div class="text-muted small">ลงทะเบียนอย่างน้อย 1 กีฬา</div>
          <div class="h4 mb-0 text-success"><?php echo number_format($registeredStudents); ?> คน</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <div class="text-muted small">ยังไม่ได้ลงทะเบียน</div>
          <div class="h4 mb-0 text-danger"><?php echo number_format($notRegistered); ?> คน</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <h5 class="card-title mb-0">รายชื่อนักเรียน (สี<?php echo e($staffColor); ?>)</h5>
        <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/staff/index.php">
          <div class="col-auto">
            <label class="form-label">ชั้น</label>
            <select class="form-select" name="grade">
              <option value="">ทั้งหมด</option>
              <?php foreach ($gradeOptions as $g): ?>
                <option value="<?php echo e($g); ?>" <?php echo $grade===$g?'selected':''; ?>><?php echo e($g); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label">ค้นหา</label>
            <input type="text" class="form-control" name="q" placeholder="รหัส / ชื่อ / นามสกุล" value="<?php echo e($q); ?>">
          </div>
          <div class="col-auto">
            <button class="btn btn-primary">ค้นหา</button>
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th style="width:110px;">รหัส</th>
              <th>ชื่อ-สกุล</th>
              <th style="width:80px;">ชั้น</th>
              <th style="width:70px;">ห้อง</th>
              <th style="width:80px;">เลขที่</th>
              <th class="text-center" style="min-width:240px;">สถานะลงทะเบียน (แยกตามประเภท)</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$students): ?>
            <tr><td colspan="6" class="text-muted">ไม่พบนักเรียนตามเงื่อนไข</td></tr>
          <?php else: foreach ($students as $s):
            $sid = (int)$s['id'];
            $hasAny = !empty($perCatCounts[$sid]);
          ?>
            <tr>
              <td class="fw-semibold"><?php echo e($s['student_code']); ?></td>
              <td><?php echo e($s['fullname']); ?></td>
              <td><?php echo e($s['grade']); ?></td>
              <td><?php echo e($s['room']); ?></td>
              <td><?php echo e($s['number']); ?></td>
              <td class="text-center">
                <?php if (!$hasAny): ?>
                  <span class="badge bg-secondary">ยังไม่ลง</span>
                <?php else: ?>
                  <?php foreach ($perCatCounts[$sid] as $cid => $cnt):
                    $catName = $catInfo[$cid]['name'] ?? ('หมวด#'.$cid);
                    $limit   = $catInfo[$cid]['max'] ?? 0;
                    $ok = ($limit === 0) || ($cnt <= $limit);
                    $cls = $ok ? 'bg-success' : 'bg-danger';
                  ?>
                    <span class="badge <?php echo $cls; ?> me-1 mb-1">
                      <?php echo e($cnt); ?> <?php echo e($catName); ?>
                    </span>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
