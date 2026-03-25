<?php
// reports_calendar.php — Export ปฏิทินกีฬารายเดือน

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo = db();
$yearId = active_year_id($pdo);
$yearName = function_exists('active_year_name') ? active_year_name($pdo) : 'ปีการศึกษา';

// ดึงกีฬาหลักทั้งหมดที่มีคู่แข่งขัน (จับกลุ่มตามชื่อหลัก)
$sqlMainSports = "
  SELECT DISTINCT 
    SUBSTRING_INDEX(s.name, ' ', 1) AS main_sport_name
  FROM sports s
  INNER JOIN match_pairs mp ON s.id = mp.sport_id AND mp.year_id = :y
  WHERE s.year_id = :y
  ORDER BY SUBSTRING_INDEX(s.name, ' ', 1)
";
$stMainSports = $pdo->prepare($sqlMainSports);
$stMainSports->execute([':y' => $yearId]);
$mainSports = $stMainSports->fetchAll(PDO::FETCH_COLUMN);

// ดึงช่วงวันที่ของการแข่งขัน
$sqlDateRange = "
  SELECT MIN(match_date) AS min_date, MAX(match_date) AS max_date
  FROM match_pairs
  WHERE year_id = :y AND match_date IS NOT NULL
";
$stDateRange = $pdo->prepare($sqlDateRange);
$stDateRange->execute([':y' => $yearId]);
$dateRange = $stDateRange->fetch(PDO::FETCH_ASSOC);

$minDate = $dateRange['min_date'] ?? date('Y-m-01');
$maxDate = $dateRange['max_date'] ?? date('Y-m-t');

// สร้างรายการเดือนที่มีการแข่งขัน
$months = [];
if ($minDate && $maxDate) {
  $start = new DateTime($minDate);
  $end = new DateTime($maxDate);
  
  while ($start <= $end) {
    $monthKey = $start->format('Y-m');
    $monthLabel = $start->format('m/Y');
    $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
                   'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $month = (int)$start->format('n');
    $year = (int)$start->format('Y') + 543;
    $monthLabel = $thaiMonths[$month] . ' ' . $year;
    
    if (!isset($months[$monthKey])) {
      $months[$monthKey] = $monthLabel;
    }
    
    $start->modify('+1 month');
  }
}

$pageTitle = 'ปฏิทินกีฬา';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
  .sport-btn {
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    user-select: none;
    font-size: 1rem;
  }
  .sport-btn:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .sport-btn.selected {
    background: #3b82f6;
    color: white;
    border-color: #2563eb;
    font-weight: 600;
  }
  .sport-btn.selected:hover {
    background: #2563eb;
  }
  
  .month-btn {
    padding: 0.6rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    user-select: none;
    font-size: 0.95rem;
  }
  .month-btn:hover {
    border-color: #10b981;
    background: #ecfdf5;
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .month-btn.selected {
    background: #10b981;
    color: white;
    border-color: #059669;
    font-weight: 600;
  }
  .month-btn.selected:hover {
    background: #059669;
  }
</style>

<main class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1">📅 ปฏิทินกีฬา</h4>
      <p class="text-muted mb-0 small">Export ปฏิทินรายเดือนแสดงกำหนดการแข่งขันกีฬา</p>
    </div>
    <a href="<?= BASE_URL ?>/reports.php" class="btn btn-outline-secondary">← กลับ</a>
  </div>

  <?php if (empty($mainSports)): ?>
    <div class="alert alert-warning">
      ⚠️ ยังไม่มีรายการแข่งขันในระบบ กรุณาสร้างคู่แข่งขันที่หน้า <a href="<?= BASE_URL ?>/matches.php">จัดการคู่แข่งขัน</a>
    </div>
  <?php else: ?>

  <form method="post" action="<?= BASE_URL ?>/reports_calendar_export.php" target="_blank">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">🏅 เลือกกีฬาที่ต้องการแสดง</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllSports()">✓ เลือกทั้งหมด</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="unselectAllSports()">✗ ยกเลิกทั้งหมด</button>
        </div>
        <div class="row g-3">
          <?php foreach ($mainSports as $sport): ?>
            <div class="col-md-3">
              <div class="sport-btn" onclick="toggleSport('<?= htmlspecialchars($sport) ?>')" data-sport="<?= htmlspecialchars($sport) ?>">
                🏆 <?= htmlspecialchars($sport) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="sportInputs"></div>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0">📆 เลือกเดือนที่ต้องการ Export</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllMonths()">✓ เลือกทั้งหมด</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="unselectAllMonths()">✗ ยกเลิกทั้งหมด</button>
        </div>
        <?php if (empty($months)): ?>
          <div class="alert alert-info">ยังไม่มีข้อมูลวันที่แข่งขันในระบบ</div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($months as $monthKey => $monthLabel): ?>
              <div class="col-md-3">
                <div class="month-btn" onclick="toggleMonth('<?= htmlspecialchars($monthKey) ?>')" data-month="<?= htmlspecialchars($monthKey) ?>">
                  📅 <?= htmlspecialchars($monthLabel) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div id="monthInputs"></div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body text-center">
        <button type="submit" class="btn btn-lg btn-primary">
          📥 Export PDF ปฏิทินกีฬา
        </button>
      </div>
    </div>
  </form>

  <?php endif; ?>
</main>

<script>
  function toggleSport(sport) {
    const btn = document.querySelector(`.sport-btn[data-sport="${sport}"]`);
    btn.classList.toggle('selected');
    updateSportInputs();
  }
  
  function toggleMonth(month) {
    const btn = document.querySelector(`.month-btn[data-month="${month}"]`);
    btn.classList.toggle('selected');
    updateMonthInputs();
  }
  
  function updateSportInputs() {
    const container = document.getElementById('sportInputs');
    container.innerHTML = '';
    document.querySelectorAll('.sport-btn.selected').forEach(btn => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'sports[]';
      input.value = btn.dataset.sport;
      container.appendChild(input);
    });
  }
  
  function updateMonthInputs() {
    const container = document.getElementById('monthInputs');
    container.innerHTML = '';
    document.querySelectorAll('.month-btn.selected').forEach(btn => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'months[]';
      input.value = btn.dataset.month;
      container.appendChild(input);
    });
  }
  
  function selectAllSports() {
    document.querySelectorAll('.sport-btn').forEach(btn => btn.classList.add('selected'));
    updateSportInputs();
  }
  
  function unselectAllSports() {
    document.querySelectorAll('.sport-btn').forEach(btn => btn.classList.remove('selected'));
    updateSportInputs();
  }
  
  function selectAllMonths() {
    document.querySelectorAll('.month-btn').forEach(btn => btn.classList.add('selected'));
    updateMonthInputs();
  }
  
  function unselectAllMonths() {
    document.querySelectorAll('.month-btn').forEach(btn => btn.classList.remove('selected'));
    updateMonthInputs();
  }
  
  // ป้องกันการ submit ถ้าไม่ได้เลือกอะไรเลย
  document.querySelector('form').addEventListener('submit', function(e) {
    const selectedSports = document.querySelectorAll('.sport-btn.selected');
    const selectedMonths = document.querySelectorAll('.month-btn.selected');
    
    if (selectedSports.length === 0) {
      e.preventDefault();
      alert('⚠️ กรุณาเลือกกีฬาอย่างน้อย 1 รายการ');
      return;
    }
    
    if (selectedMonths.length === 0) {
      e.preventDefault();
      alert('⚠️ กรุณาเลือกเดือนอย่างน้อย 1 เดือน');
      return;
    }
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
