<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

if (!function_exists('e')) { function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }

$pdo = db();
$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php'; exit;
}

// 1) นักเรียนทั้งหมด (ปีนี้)
$stTotal = $pdo->prepare("SELECT COUNT(*) FROM students WHERE year_id=?");
$stTotal->execute([$yearId]);
$totalStudents = (int)$stTotal->fetchColumn();

// 2) แยกตามสี
$byColor = ['เขียว'=>0,'ฟ้า'=>0,'ชมพู'=>0,'ส้ม'=>0];
$stColor = $pdo->prepare("SELECT color, COUNT(*) AS c FROM students WHERE year_id=? GROUP BY color");
$stColor->execute([$yearId]);
foreach($stColor->fetchAll(PDO::FETCH_ASSOC) as $row){
  $c = $row['color'];
  if (isset($byColor[$c])) $byColor[$c] = (int)$row['c'];
}

// 3) คนที่ลงทะเบียนแล้ว / ยังไม่ลง
$stReg = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM registrations WHERE year_id=?");
$stReg->execute([$yearId]);
$registeredCount = (int)$stReg->fetchColumn();
$notRegisteredCount = max(0, $totalStudents - $registeredCount);

// 4) แจ้งเตือน: ลงทะเบียนเกินโควต้า (ต่อหมวดกีฬา)
$stOver = $pdo->prepare("
  SELECT
    s.id AS student_id,
    s.color,
    s.first_name,
    s.last_name,
    sc.id AS category_id,
    sc.name AS category_name,
    sc.max_per_student,
    COUNT(*) AS registered_in_category
  FROM registrations r
  JOIN students s ON s.id = r.student_id
  JOIN sports sp ON sp.id = r.sport_id
  JOIN sport_categories sc ON sc.id = sp.category_id
  WHERE r.year_id = ?
  GROUP BY s.id, s.color, s.first_name, s.last_name, sc.id, sc.name, sc.max_per_student
  HAVING COUNT(*) > sc.max_per_student
  ORDER BY (COUNT(*) - sc.max_per_student) DESC, s.id ASC
");
$stOver->execute([$yearId]);
$overRows = $stOver->fetchAll(PDO::FETCH_ASSOC);
$overCount = count($overRows);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <div class="text-muted small">นักเรียนทั้งหมด (ปีนี้)</div>
          <div class="display-6 fw-semibold"><?php echo number_format($totalStudents); ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <div class="text-muted small">ลงทะเบียนแล้ว</div>
          <div class="h3 fw-semibold mb-0"><?php echo number_format($registeredCount); ?></div>
          <div class="small text-success">คิดเป็น <?php echo $totalStudents>0? number_format(($registeredCount/$totalStudents)*100,1):'0.0'; ?>%</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <div class="text-muted small">ยังไม่ได้ลงทะเบียน</div>
          <div class="h3 fw-semibold mb-0"><?php echo number_format($notRegisteredCount); ?></div>
          <div class="small text-danger">คิดเป็น <?php echo $totalStudents>0? number_format(($notRegisteredCount/$totalStudents)*100,1):'0.0'; ?>%</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body">
          <div class="text-muted small">แจ้งเตือนเกินกำหนด</div>
          <div class="h3 fw-semibold mb-0"><?php echo number_format($overCount); ?></div>
          <div class="small text-muted">คนที่ลงเกินโควต้าในหมวดกีฬา</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <h6 class="mb-2">จำนวนนักเรียนแยกตามสี</h6>
      <div class="row g-2">
        <?php
          $colorBg = [
            'เขียว' => '#d4edda',
            'ฟ้า'   => '#d1ecf1',
            'ชมพู'  => '#f8d7da',
            'ส้ม'   => '#fff3cd',
          ];
          foreach (['เขียว','ฟ้า','ชมพู','ส้ม'] as $c):
        ?>
        <div class="col-6 col-md-3">
          <div class="p-3 rounded-3 border" style="background: <?php echo $colorBg[$c]; ?>;">
            <div class="small text-muted">สี<?php echo e($c); ?></div>
            <div class="h4 mb-0 fw-semibold"><?php echo number_format($byColor[$c] ?? 0); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0">นักเรียนที่ลงทะเบียนเกินกำหนด (อิง max_per_student ของหมวดกีฬา)</h6>
        <?php if ($overCount > 0): ?>
          <span class="badge bg-danger"><?php echo $overCount; ?> รายการ</span>
        <?php else: ?>
          <span class="badge bg-success">ไม่มีการเกินกำหนด</span>
        <?php endif; ?>
      </div>
      <?php if ($overCount === 0): ?>
        <div class="text-muted">— ไม่มีข้อมูล —</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>นักเรียน</th>
                <th>สี</th>
                <th>หมวดกีฬา</th>
                <th class="text-end">ลงไป</th>
                <th class="text-end">อนุญาต</th>
                <th class="text-end">เกิน</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($overRows as $r):
                $nm = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                $allowed = (int)$r['max_per_student'];
                $cnt = (int)$r['registered_in_category'];
                $over = max(0, $cnt - $allowed);
                $bg = ['เขียว'=>'#d4edda','ฟ้า'=>'#d1ecf1','ชมพู'=>'#f8d7da','ส้ม'=>'#fff3cd'][$r['color']] ?? '#f8f9fa';
              ?>
              <tr>
                <td><?php echo e($nm ?: '—'); ?></td>
                <td><span class="px-2 py-1 rounded-3 border" style="background: <?php echo $bg; ?>">สี<?php echo e($r['color']); ?></span></td>
                <td><?php echo e($r['category_name']); ?></td>
                <td class="text-end"><?php echo number_format($cnt); ?></td>
                <td class="text-end"><?php echo number_format($allowed); ?></td>
                <td class="text-end text-danger fw-semibold"><?php echo number_format($over); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
