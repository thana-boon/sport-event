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
    'max'  => is_null($c['max_per_student']) ? 0 : (int)$c['max_per_student']
  ];
}

// -------- ฟิลเตอร์ --------
$q = trim($_GET['q'] ?? '');
$grade = trim($_GET['grade'] ?? '');

$gs = $pdo->prepare("SELECT DISTINCT class_level FROM students WHERE year_id=? AND color=? ORDER BY class_level");
$gs->execute([$yearId, $staffColor]);
$gradeOptions = $gs->fetchAll(PDO::FETCH_COLUMN);

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

$perCatCounts = [];
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

// Color themes
$colorThemes = [
  'เขียว' => ['bg' => '#d4edda', 'hex' => '#28a745', 'light' => '#e8f5e9'],
  'ฟ้า'   => ['bg' => '#d1ecf1', 'hex' => '#17a2b8', 'light' => '#e1f5fe'],
  'ชมพู'  => ['bg' => '#f8d7da', 'hex' => '#e83e8c', 'light' => '#fce4ec'],
  'ส้ม'   => ['bg' => '#fff3cd', 'hex' => '#fd7e14', 'light' => '#fff8e1'],
];
$currentTheme = $colorThemes[$staffColor] ?? ['bg' => '#f8f9fa', 'hex' => '#6c757d', 'light' => '#f8f9fa'];

$pageTitle = 'รายชื่อนักเรียน - สี' . $staffColor;
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/navbar.php';
?>

<style>
  body {
    background: linear-gradient(135deg, <?php echo $currentTheme['light']; ?> 0%, #ffffff 100%);
  }
  .stat-card {
    border-radius: 1rem;
    border: none;
    transition: transform 0.2s;
  }
  .stat-card:hover {
    transform: translateY(-2px);
  }
  .stat-icon {
    font-size: 2.5rem;
    opacity: 0.7;
  }
  .color-badge-big {
    background: linear-gradient(135deg, <?php echo $currentTheme['hex']; ?>, <?php echo $currentTheme['hex']; ?>dd);
    color: white;
    padding: 0.5rem 1.25rem;
    border-radius: 2rem;
    font-weight: 600;
    box-shadow: 0 4px 12px <?php echo $currentTheme['hex']; ?>33;
  }
  .search-card {
    border-radius: 1rem;
    border: 2px solid <?php echo $currentTheme['hex']; ?>33;
    background: white;
  }
  .student-row {
    transition: all 0.2s;
  }
  .student-row:hover {
    background: <?php echo $currentTheme['light']; ?> !important;
    transform: scale(1.01);
  }
  .status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 1rem;
    font-weight: 500;
    font-size: 0.85rem;
  }
  .empty-state {
    padding: 3rem;
    text-align: center;
    color: #6c757d;
  }
  .page-header {
    background: linear-gradient(135deg, <?php echo $currentTheme['hex']; ?>, <?php echo $currentTheme['hex']; ?>dd);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 24px <?php echo $currentTheme['hex']; ?>33;
  }
</style>

<main class="container py-4">
  <!-- Page Header -->
  <div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h3 class="mb-1">🏆 ระบบจัดการนักกีฬา</h3>
        <p class="mb-0 opacity-75">รายชื่อนักเรียน สี<?php echo e($staffColor); ?></p>
      </div>
      <div class="text-end">
        <div class="stat-icon">🎨</div>
      </div>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card stat-card shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="small opacity-75">👨‍🎓 นักเรียนทั้งหมด</div>
              <div class="h2 mb-0 fw-bold"><?php echo number_format($totalStudents); ?></div>
              <small class="opacity-75">คน</small>
            </div>
            <div class="stat-icon">🏫</div>
          </div>
          <div class="color-badge-big d-inline-block mt-2">
            สี<?php echo e($staffColor); ?>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card stat-card shadow-sm" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="small opacity-75">✅ ลงทะเบียนแล้ว</div>
              <div class="h2 mb-0 fw-bold"><?php echo number_format($registeredStudents); ?></div>
              <small class="opacity-75">คิดเป็น <?php echo $totalStudents>0? number_format(($registeredStudents/$totalStudents)*100,1):'0'; ?>%</small>
            </div>
            <div class="stat-icon">✍️</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card stat-card shadow-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="small opacity-75">⏳ ยังไม่ลงทะเบียน</div>
              <div class="h2 mb-0 fw-bold"><?php echo number_format($notRegistered); ?></div>
              <small class="opacity-75">คิดเป็น <?php echo $totalStudents>0? number_format(($notRegistered/$totalStudents)*100,1):'0'; ?>%</small>
            </div>
            <div class="stat-icon">📝</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters & Table -->
  <div class="card search-card shadow">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="mb-0">
          <span style="color: <?php echo $currentTheme['hex']; ?>;">📋</span> 
          รายชื่อนักเรียน
        </h5>
        <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/staff/index.php">
          <div class="col-auto">
            <label class="form-label small text-muted mb-1">🎓 ชั้น</label>
            <select class="form-select" name="grade" style="border-color: <?php echo $currentTheme['hex']; ?>66;">
              <option value="">ทั้งหมด</option>
              <?php foreach ($gradeOptions as $g): ?>
                <option value="<?php echo e($g); ?>" <?php echo $grade===$g?'selected':''; ?>><?php echo e($g); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <label class="form-label small text-muted mb-1">🔍 ค้นหา</label>
            <input type="text" class="form-control" name="q" placeholder="รหัส / ชื่อ / นามสกุล" value="<?php echo e($q); ?>" style="border-color: <?php echo $currentTheme['hex']; ?>66;">
          </div>
          <div class="col-auto">
            <button class="btn text-white" style="background: <?php echo $currentTheme['hex']; ?>;">
              ค้นหา 🔎
            </button>
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead style="background: <?php echo $currentTheme['light']; ?>;">
            <tr>
              <th class="border-0" style="width:110px;">📌 รหัส</th>
              <th class="border-0">👤 ชื่อ-สกุล</th>
              <th class="border-0" style="width:80px;">🎓 ชั้น</th>
              <th class="border-0" style="width:70px;">🚪 ห้อง</th>
              <th class="border-0" style="width:80px;">🔢 เลขที่</th>
              <th class="border-0 text-center" style="min-width:240px;">📊 สถานะลงทะเบียน</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$students): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                  <p class="mb-0">ไม่พบนักเรียนตามเงื่อนไขที่ค้นหา</p>
                </div>
              </td>
            </tr>
          <?php else: 
            $rowIndex = 0;
            foreach ($students as $s):
              $sid = (int)$s['id'];
              $hasAny = !empty($perCatCounts[$sid]);
              $rowClass = ($rowIndex % 2 === 0) ? '' : 'table-light';
              $rowIndex++;
          ?>
            <tr class="student-row <?php echo $rowClass; ?>">
              <td class="fw-semibold" style="color: <?php echo $currentTheme['hex']; ?>;">
                <?php echo e($s['student_code']); ?>
              </td>
              <td><?php echo e($s['fullname']); ?></td>
              <td><span class="badge bg-light text-dark"><?php echo e($s['grade']); ?></span></td>
              <td><span class="badge bg-light text-dark"><?php echo e($s['room']); ?></span></td>
              <td><span class="badge bg-light text-dark"><?php echo e($s['number']); ?></span></td>
              <td class="text-center">
                <?php if (!$hasAny): ?>
                  <span class="status-badge" style="background: #6c757d33; color: #6c757d;">
                    ⏳ ยังไม่ลง
                  </span>
                <?php else: ?>
                  <?php foreach ($perCatCounts[$sid] as $cid => $cnt):
                    $catName = $catInfo[$cid]['name'] ?? ('หมวด#'.$cid);
                    $limit   = $catInfo[$cid]['max'] ?? 0;
                    $ok = ($limit === 0) || ($cnt <= $limit);
                    $bgColor = $ok ? '#28a74533' : '#dc354533';
                    $textColor = $ok ? '#28a745' : '#dc3545';
                    $icon = $ok ? '✅' : '⚠️';
                  ?>
                    <span class="status-badge me-1 mb-1" style="background: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>;">
                      <?php echo $icon; ?> <?php echo e($cnt); ?> <?php echo e($catName); ?>
                    </span>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($students): ?>
        <div class="mt-3 text-muted small text-center">
          📊 แสดง <?php echo number_format(count($students)); ?> คน จากทั้งหมด <?php echo number_format($totalStudents); ?> คน
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
