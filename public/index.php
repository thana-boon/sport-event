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
  echo '<main class="container py-5"><div class="alert alert-warning">⚠️ ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
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

// 4) แจ้งเตือน: ลงทะเบียนเกินโควต้า (ต่อหมวดกีฬา) - resolve max_per_student จาก category_year_settings
$stOver = $pdo->prepare("
  SELECT
    s.id AS student_id,
    s.color,
    s.first_name,
    s.last_name,
    sc.id AS category_id,
    sc.name AS category_name,
    cat_limits.max_per_student,
    COUNT(*) AS registered_in_category
  FROM registrations r
  JOIN students s ON s.id = r.student_id
  JOIN sports sp ON sp.id = r.sport_id
  JOIN sport_categories sc ON sc.id = sp.category_id
  JOIN (
    SELECT 
      sc2.id AS category_id,
      COALESCE(cys2.max_per_student, sc2.max_per_student) AS max_per_student
    FROM sport_categories sc2
    LEFT JOIN category_year_settings cys2 ON cys2.category_id = sc2.id AND cys2.year_id = :y
  ) AS cat_limits ON cat_limits.category_id = sc.id
  WHERE r.year_id = :y
  GROUP BY s.id, s.color, s.first_name, s.last_name, sc.id, sc.name, cat_limits.max_per_student
  HAVING COUNT(*) > cat_limits.max_per_student
  ORDER BY (COUNT(*) - cat_limits.max_per_student) DESC, s.id ASC
");
$stOver->execute([':y' => $yearId]);
$overRows = $stOver->fetchAll(PDO::FETCH_ASSOC);
$overCount = count($overRows);

// 5) สถิติการลงทะเบียนแต่ละสี
// นับจำนวนคนที่ต้องลงทะเบียนทั้งหมด (รวม team_size ของทุกกีฬา)
$totalSlotsStmt = $pdo->prepare("
  SELECT COALESCE(SUM(CASE WHEN team_size > 0 THEN team_size ELSE 1 END), 0) 
  FROM sports 
  WHERE year_id = ? AND is_active = 1
");
$totalSlotsStmt->execute([$yearId]);
$totalSlots = (int)$totalSlotsStmt->fetchColumn();

// จำนวนคนที่แต่ละสีลงทะเบียนแล้ว
$colorRegistration = [];
foreach (['เขียว','ฟ้า','ชมพู','ส้ม'] as $color) {
  $stmt = $pdo->prepare("SELECT COUNT(r.id) 
                         FROM registrations r 
                         JOIN students s ON s.id = r.student_id 
                         WHERE r.year_id=? AND s.color=?");
  $stmt->execute([$yearId, $color]);
  $registered = (int)$stmt->fetchColumn();
  $percent = $totalSlots > 0 ? round(($registered / $totalSlots) * 100, 1) : 0;
  $colorRegistration[$color] = [
    'registered' => $registered,
    'total' => $totalSlots,
    'percent' => $percent
  ];
}

$pageTitle = 'แดชบอร์ด';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
  .stat-card {
    border-radius: 0.75rem;
    border: 1px solid rgba(0,0,0,0.06);
  }
  .stat-icon {
    font-size: 2rem;
    opacity: 0.6;
  }
  .color-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
  }
  .color-dot {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
  }
</style>

<main class="container py-4">
  <!-- Header -->
  <div class="mb-4">
    <h4 class="fw-bold mb-1">📊 แดชบอร์ด</h4>
    <p class="text-muted small mb-0">ภาพรวมระบบจัดการกีฬาสี</p>
  </div>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between mb-2">
            <span class="text-muted small">👨‍🎓 นักเรียนทั้งหมด</span>
            <span class="stat-icon">🏫</span>
          </div>
          <div class="h3 fw-bold mb-0"><?php echo number_format($totalStudents); ?></div>
          <small class="text-muted">คน (ปีนี้)</small>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between mb-2">
            <span class="text-muted small">✅ ลงทะเบียนแล้ว</span>
            <span class="stat-icon">✍️</span>
          </div>
          <div class="h3 fw-bold mb-0 text-success"><?php echo number_format($registeredCount); ?></div>
          <small class="text-muted">คิดเป็น <?php echo $totalStudents>0? number_format(($registeredCount/$totalStudents)*100,1):'0.0'; ?>%</small>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between mb-2">
            <span class="text-muted small">⏳ ยังไม่ลงทะเบียน</span>
            <span class="stat-icon">📝</span>
          </div>
          <div class="h3 fw-bold mb-0 text-warning"><?php echo number_format($notRegisteredCount); ?></div>
          <small class="text-muted">คิดเป็น <?php echo $totalStudents>0? number_format(($notRegisteredCount/$totalStudents)*100,1):'0.0'; ?>%</small>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card stat-card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between mb-2">
            <span class="text-muted small">⚠️ เกินกำหนด</span>
            <span class="stat-icon">🚨</span>
          </div>
          <div class="h3 fw-bold mb-0 text-danger"><?php echo number_format($overCount); ?></div>
          <small class="text-muted">รายการเกินโควต้า</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Registration Progress by Color -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3">📋 สถิติการลงทะเบียนแต่ละสี</h6>
      <div class="row g-3">
        <?php
          $colorInfo = [
            'เขียว' => ['bg' => '#d4edda', 'hex' => '#28a745', 'dark' => '#155724'],
            'ฟ้า'   => ['bg' => '#d1ecf1', 'hex' => '#17a2b8', 'dark' => '#0c5460'],
            'ชมพู'  => ['bg' => '#f8d7da', 'hex' => '#e83e8c', 'dark' => '#721c24'],
            'ส้ม'   => ['bg' => '#fff3cd', 'hex' => '#fd7e14', 'dark' => '#856404'],
          ];
          foreach (['เขียว','ฟ้า','ชมพู','ส้ม'] as $c):
            $data = $colorRegistration[$c];
        ?>
        <div class="col-6 col-md-3">
          <div class="p-3 rounded-3" style="background: <?php echo $colorInfo[$c]['bg']; ?>;">
            <div class="color-badge mx-auto mb-2" style="background: white; color: #333;">
              <div class="color-dot" style="background: <?php echo $colorInfo[$c]['hex']; ?>;"></div>
              <span>สี<?php echo e($c); ?></span>
            </div>
            <div class="text-center mb-2">
              <div class="h5 fw-bold mb-0" style="color: <?php echo $colorInfo[$c]['dark']; ?>;">
                <?php echo $data['registered']; ?> / <?php echo $data['total']; ?>
              </div>
              <small class="text-muted">คนที่ลงทะเบียน</small>
            </div>
            <div class="progress" style="height: 8px; background: rgba(255,255,255,0.6);">
              <div class="progress-bar" role="progressbar" 
                   style="width: <?php echo $data['percent']; ?>%; background: <?php echo $colorInfo[$c]['hex']; ?>;"
                   aria-valuenow="<?php echo $data['percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="text-center mt-2">
              <span class="badge" style="background: <?php echo $colorInfo[$c]['hex']; ?>; color: white;">
                <?php echo $data['percent']; ?>%
              </span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Color Distribution -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3">🎨 จำนวนนักเรียนแยกตามสี</h6>
      <div class="row g-3">
        <?php
          foreach (['เขียว','ฟ้า','ชมพู','ส้ม'] as $c):
        ?>
        <div class="col-6 col-md-3">
          <div class="p-3 rounded-3 text-center" style="background: <?php echo $colorInfo[$c]['bg']; ?>;">
            <div class="color-badge mx-auto mb-2" style="background: white; color: #333;">
              <div class="color-dot" style="background: <?php echo $colorInfo[$c]['hex']; ?>;"></div>
              <span>สี<?php echo e($c); ?></span>
            </div>
            <div class="h4 fw-bold mb-0" style="color: <?php echo $colorInfo[$c]['hex']; ?>;">
              <?php echo number_format($byColor[$c] ?? 0); ?>
            </div>
            <small class="text-muted">คน</small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Over Limit Table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">🚨 นักเรียนที่ลงทะเบียนเกินกำหนด</h6>
        <?php if ($overCount > 0): ?>
          <span class="badge bg-danger rounded-pill"><?php echo $overCount; ?> รายการ</span>
        <?php else: ?>
          <span class="badge bg-success rounded-pill">✅ ไม่มีการเกินกำหนด</span>
        <?php endif; ?>
      </div>

      <?php if ($overCount === 0): ?>
        <div class="text-center py-4 text-muted">
          <div class="mb-2" style="font-size: 2.5rem;">🎉</div>
          <p class="mb-0">ไม่มีข้อมูลการลงทะเบียนเกินกำหนด</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>👤 นักเรียน</th>
                <th>🎨 สี</th>
                <th>🏅 หมวดกีฬา</th>
                <th class="text-center">ลงไป</th>
                <th class="text-center">อนุญาต</th>
                <th class="text-center">⚠️ เกิน</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($overRows as $r):
                $nm = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                $allowed = (int)$r['max_per_student'];
                $cnt = (int)$r['registered_in_category'];
                $over = max(0, $cnt - $allowed);
                $color = $colorInfo[$r['color']] ?? ['bg' => '#f8f9fa', 'hex' => '#6c757d'];
              ?>
              <tr>
                <td><?php echo e($nm ?: '—'); ?></td>
                <td>
                  <span class="color-badge" style="background: <?php echo $color['bg']; ?>;">
                    <div class="color-dot" style="background: <?php echo $color['hex']; ?>;"></div>
                    <span>สี<?php echo e($r['color']); ?></span>
                  </span>
                </td>
                <td><?php echo e($r['category_name']); ?></td>
                <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo number_format($cnt); ?></span></td>
                <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo number_format($allowed); ?></span></td>
                <td class="text-center"><span class="badge bg-danger rounded-pill">+<?php echo number_format($over); ?></span></td>
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
