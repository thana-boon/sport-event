<?php
// public/summary.php - สรุปสถานะการบันทึกผลการแข่งขัน
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

if (!function_exists('e')) { 
  function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } 
}

$pdo = db();
$yearId = active_year_id($pdo);

if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ดึงข้อมูลปีการศึกษาที่ active
$yearStmt = $pdo->prepare("SELECT year_name FROM years WHERE id = ? AND is_active = 1");
$yearStmt->execute([$yearId]);
$yearInfo = $yearStmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลสรุปตามประเภทกีฬา
$summaryStmt = $pdo->prepare("
  SELECT 
    sc.id AS category_id,
    sc.name AS category_name,
    COUNT(DISTINCT s.id) AS total_sports,
    sc.name AS cat_type
  FROM sport_categories sc
  LEFT JOIN sports s ON s.category_id = sc.id AND s.year_id = :year_id AND s.is_active = 1
  WHERE sc.year_id = :year_id
  GROUP BY sc.id, sc.name
  ORDER BY sc.name
");
$summaryStmt->execute(['year_id' => $yearId]);
$categories = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

// สำหรับแต่ละประเภท ดึงข้อมูลการบันทึกผล
$summaryData = [];
foreach ($categories as $cat) {
  $catId = $cat['category_id'];
  $catName = $cat['category_name'];
  $totalSports = (int)$cat['total_sports'];
  
  // ตรวจสอบว่าเป็นกรีฑาหรือกีฬาสากล
  $isAthletics = stripos($catName, 'กรีฑ') !== false;
  
  if ($isAthletics) {
    // กรีฑา: ดูจาก track_heats และ track_lane_assignments
    $stmt = $pdo->prepare("
      SELECT 
        COUNT(DISTINCT s.id) AS total_events,
        COUNT(DISTINCT CASE 
          WHEN EXISTS (
            SELECT 1 FROM track_heats th
            JOIN track_lane_assignments tla ON tla.heat_id = th.id
            WHERE th.sport_id = s.id 
              AND th.year_id = :year_id
              AND (tla.result_time IS NOT NULL OR tla.result_distance IS NOT NULL OR tla.result_height IS NOT NULL)
          ) THEN s.id 
        END) AS recorded_events
      FROM sports s
      WHERE s.category_id = :cat_id 
        AND s.year_id = :year_id 
        AND s.is_active = 1
    ");
    $stmt->execute(['cat_id' => $catId, 'year_id' => $yearId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = (int)$result['total_events'];
    $recorded = (int)$result['recorded_events'];
    
  } else {
    // กีฬาสากล: ดูจาก match_pairs
    $stmt = $pdo->prepare("
      SELECT 
        COUNT(DISTINCT s.id) AS total_events,
        COUNT(DISTINCT CASE 
          WHEN EXISTS (
            SELECT 1 FROM match_pairs mp
            WHERE mp.sport_id = s.id 
              AND mp.year_id = :year_id
              AND mp.status IN ('completed', 'finished')
              AND mp.winner IS NOT NULL
          ) THEN s.id 
        END) AS recorded_events
      FROM sports s
      WHERE s.category_id = :cat_id 
        AND s.year_id = :year_id 
        AND s.is_active = 1
    ");
    $stmt->execute(['cat_id' => $catId, 'year_id' => $yearId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = (int)$result['total_events'];
    $recorded = (int)$result['recorded_events'];
  }
  
  $percentage = $total > 0 ? round(($recorded / $total) * 100, 1) : 0;
  
  $summaryData[] = [
    'category_name' => $catName,
    'total' => $total,
    'recorded' => $recorded,
    'pending' => $total - $recorded,
    'percentage' => $percentage,
    'is_athletics' => $isAthletics
  ];
}

// หาเปอร์เซ็นต์รวมทั้งหมด
$grandTotal = array_sum(array_column($summaryData, 'total'));
$grandRecorded = array_sum(array_column($summaryData, 'recorded'));
$grandPercentage = $grandTotal > 0 ? round(($grandRecorded / $grandTotal) * 100, 1) : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">
        <i class="bi bi-bar-chart-fill"></i> สรุปสถานะการบันทึกผล
      </h2>
      <?php if ($yearInfo): ?>
        <p class="text-muted mb-0">ปีการศึกษา: <?= e($yearInfo['year_name']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- สรุปภาพรวม -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card border-primary shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-graph-up"></i> ภาพรวมทั้งหมด</h5>
        </div>
        <div class="card-body">
          <div class="row text-center">
            <div class="col-md-3">
              <h3 class="text-primary mb-0"><?= $grandTotal ?></h3>
              <p class="text-muted mb-0">รายการทั้งหมด</p>
            </div>
            <div class="col-md-3">
              <h3 class="text-success mb-0"><?= $grandRecorded ?></h3>
              <p class="text-muted mb-0">บันทึกแล้ว</p>
            </div>
            <div class="col-md-3">
              <h3 class="text-warning mb-0"><?= $grandTotal - $grandRecorded ?></h3>
              <p class="text-muted mb-0">ยังไม่บันทึก</p>
            </div>
            <div class="col-md-3">
              <h3 class="<?= $grandPercentage >= 100 ? 'text-success' : 'text-info' ?> mb-0">
                <?= $grandPercentage ?>%
              </h3>
              <p class="text-muted mb-0">ความสำเร็จ</p>
            </div>
          </div>
          
          <!-- Progress Bar รวม -->
          <div class="mt-3">
            <div class="progress" style="height: 30px;">
              <div class="progress-bar bg-success" role="progressbar" 
                   style="width: <?= $grandPercentage ?>%;" 
                   aria-valuenow="<?= $grandPercentage ?>" 
                   aria-valuemin="0" 
                   aria-valuemax="100">
                <strong><?= $grandPercentage ?>%</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- สรุปแยกตามประเภทกีฬา -->
  <div class="card shadow-sm">
    <div class="card-header bg-light">
      <h5 class="mb-0"><i class="bi bi-list-check"></i> รายละเอียดตามประเภทกีฬา</h5>
    </div>
    <div class="card-body">
      <?php if (empty($summaryData)): ?>
        <div class="alert alert-info">ยังไม่มีข้อมูลกีฬา</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 30%;">ประเภทกีฬา</th>
                <th class="text-center" style="width: 12%;">ทั้งหมด</th>
                <th class="text-center" style="width: 12%;">บันทึกแล้ว</th>
                <th class="text-center" style="width: 12%;">ค้างอยู่</th>
                <th class="text-center" style="width: 12%;">เปอร์เซ็นต์</th>
                <th style="width: 22%;">ความคืบหน้า</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($summaryData as $data): ?>
                <tr>
                  <td>
                    <strong>
                      <?php if ($data['is_athletics']): ?>
                        <i class="bi bi-trophy text-warning"></i>
                      <?php else: ?>
                        <i class="bi bi-basketball text-info"></i>
                      <?php endif; ?>
                      <?= e($data['category_name']) ?>
                    </strong>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-secondary"><?= $data['total'] ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-success"><?= $data['recorded'] ?></span>
                  </td>
                  <td class="text-center">
                    <?php if ($data['pending'] > 0): ?>
                      <span class="badge bg-warning text-dark"><?= $data['pending'] ?></span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted">0</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <strong class="<?= $data['percentage'] >= 100 ? 'text-success' : ($data['percentage'] >= 50 ? 'text-info' : 'text-warning') ?>">
                      <?= $data['percentage'] ?>%
                    </strong>
                  </td>
                  <td>
                    <div class="progress" style="height: 25px;">
                      <?php
                        $progressClass = 'bg-warning';
                        if ($data['percentage'] >= 100) {
                          $progressClass = 'bg-success';
                        } elseif ($data['percentage'] >= 75) {
                          $progressClass = 'bg-info';
                        } elseif ($data['percentage'] >= 50) {
                          $progressClass = 'bg-primary';
                        }
                      ?>
                      <div class="progress-bar <?= $progressClass ?>" 
                           role="progressbar" 
                           style="width: <?= $data['percentage'] ?>%;" 
                           aria-valuenow="<?= $data['percentage'] ?>" 
                           aria-valuemin="0" 
                           aria-valuemax="100">
                        <?= $data['percentage'] ?>%
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- คำอธิบาย -->
  <div class="card mt-4 border-info">
    <div class="card-header bg-info text-white">
      <h6 class="mb-0"><i class="bi bi-info-circle"></i> หมายเหตุ</h6>
    </div>
    <div class="card-body">
      <ul class="mb-0">
        <li><strong>กีฬาสากล:</strong> นับจากจำนวนรายการที่มีการบันทึกผลแพ้-ชนะในตาราง match_pairs (สถานะ completed/finished)</li>
        <li><strong>กรีฑา:</strong> นับจากจำนวนรายการที่มีการบันทึกผลลัพธ์ (เวลา/ระยะทาง/ความสูง) ในตาราง track_lane_assignments</li>
        <li><strong>เปอร์เซ็นต์:</strong> คำนวณจาก (รายการที่บันทึกแล้ว / รายการทั้งหมด) × 100</li>
      </ul>
    </div>
  </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
