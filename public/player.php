<?php
// filepath: c:\xampp\htdocs\sport-event\public\player.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ตรวจสอบว่าต้องเป็น admin เท่านั้น
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

// เช็ค session timeout (30 นาที)
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
  log_activity('LOGOUT', 'users', $_SESSION['admin']['id'] ?? null, 
    'ออกจากระบบอัตโนมัติ (session timeout 30 นาที) | Username: ' . ($_SESSION['admin']['username'] ?? 'unknown'));
  
  session_unset();
  session_destroy();
  header('Location: ' . BASE_URL . '/login.php?timeout=1');
  exit;
}
$_SESSION['last_activity'] = time();

$pdo = db();
$yearId = active_year_id($pdo);

// สร้างตาราง player_substitutions (ถ้ายังไม่มี)
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS player_substitutions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      year_id INT NOT NULL,
      sport_id INT NOT NULL,
      registration_id INT NOT NULL COMMENT 'ID ของการลงทะเบียนที่ถูกเปลี่ยน',
      old_student_id INT NOT NULL COMMENT 'นักเรียนเก่า (ก่อนเปลี่ยน)',
      new_student_id INT NOT NULL COMMENT 'นักเรียนใหม่ (หลังเปลี่ยน)',
      color VARCHAR(20) NOT NULL,
      substitution_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      created_by INT DEFAULT NULL,
      reason TEXT DEFAULT NULL,
      INDEX idx_year_sport (year_id, sport_id),
      INDEX idx_registration (registration_id),
      INDEX idx_students (old_student_id, new_student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
} catch (PDOException $e) {
  // ตารางมีอยู่แล้ว หรือ error อื่นๆ
  error_log("Create table error: " . $e->getMessage());
}

// จัดการ AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  
  try {
    $action = $_GET['action'];
    
    // ดึงข้อมูลกีฬาและนักกีฬา
    if ($action === 'load_sport') {
      $sportId = $_POST['sport_id'] ?? 0;
      
      // ดึงข้อมูลกีฬา
      $stmt = $pdo->prepare("
        SELECT s.*, sc.name as category_name
        FROM sports s
        LEFT JOIN sport_categories sc ON s.category_id = sc.id
        WHERE s.id = ? AND s.year_id = ?
      ");
      $stmt->execute([$sportId, $yearId]);
      $sport = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if (!$sport) {
        throw new Exception('ไม่พบข้อมูลกีฬา');
      }
      
      // ดึงรายชื่อนักกีฬาที่ลงทะเบียนแล้ว (แยกตามสี)
      $colors = ['ส้ม', 'เขียว', 'ชมพู', 'ฟ้า'];
      $players = [];
      
      foreach ($colors as $color) {
        $stmt = $pdo->prepare("
          SELECT 
            r.id as registration_id,
            r.student_id,
            s.student_code,
            s.first_name,
            s.last_name,
            s.class_level,
            s.class_room,
            s.color
          FROM registrations r
          JOIN students s ON r.student_id = s.id
          WHERE r.sport_id = ? 
            AND r.year_id = ? 
            AND s.color = ?
          ORDER BY s.class_level, s.class_room, CAST(s.number_in_room AS UNSIGNED)
        ");
        $stmt->execute([$sportId, $yearId, $color]);
        $players[$color] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
      
      echo json_encode([
        'success' => true,
        'sport' => $sport,
        'players' => $players
      ]);
      exit;
    }
    
    // ดึงรายชื่อนักเรียนที่สามารถเลือกได้
    if ($action === 'load_students') {
      $sportId = $_POST['sport_id'] ?? 0;
      $color = $_POST['color'] ?? '';
      $currentStudentId = $_POST['current_student_id'] ?? 0;
      
      error_log("=== LOAD STUDENTS DEBUG ===");
      error_log("Sport ID: " . $sportId);
      error_log("Color: " . $color);
      error_log("Current Student ID: " . $currentStudentId);
      
      // ดึงข้อมูลกีฬา
      $stmt = $pdo->prepare("
        SELECT * FROM sports WHERE id = ? AND year_id = ?
      ");
      $stmt->execute([$sportId, $yearId]);
      $sport = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if (!$sport) {
        throw new Exception('ไม่พบข้อมูลกีฬา');
      }
      
      error_log("Sport: " . json_encode($sport));
      
      // เริ่มสร้าง SQL (ไม่กรองอะไรเลยก่อน)
      $sql = "
        SELECT 
          id, student_code, first_name, last_name, 
          class_level, class_room, color
        FROM students 
        WHERE year_id = ? AND color = ?
      ";
      $params = [$yearId, $color];
      
      // กรองตามระดับชั้น (ถ้ามี)
      if (!empty($sport['grade_levels'])) {
        $gradeLevels = array_map('trim', explode(',', $sport['grade_levels']));
        
        // ปรับ format ให้ตรงกับฐานข้อมูล (เพิ่มจุด ป.1, ป.2)
        $adjustedLevels = [];
        foreach ($gradeLevels as $level) {
          $adjustedLevels[] = $level;
          // ถ้าเป็น ป1, ป2 ให้เพิ่ม ป.1, ป.2 ด้วย
          if (preg_match('/^ป(\d)$/', $level, $matches)) {
            $adjustedLevels[] = 'ป.' . $matches[1];
          }
          // ถ้าเป็น ป.1, ป.2 ให้เพิ่ม ป1, ป2 ด้วย
          if (preg_match('/^ป\.(\d)$/', $level, $matches)) {
            $adjustedLevels[] = 'ป' . $matches[1];
          }
          // เหมือนกันกับ ม.
          if (preg_match('/^ม(\d)$/', $level, $matches)) {
            $adjustedLevels[] = 'ม.' . $matches[1];
          }
          if (preg_match('/^ม\.(\d)$/', $level, $matches)) {
            $adjustedLevels[] = 'ม' . $matches[1];
          }
        }
        
        $adjustedLevels = array_unique($adjustedLevels);
        error_log("Adjusted grade levels: " . json_encode($adjustedLevels));
        
        $placeholders = implode(',', array_fill(0, count($adjustedLevels), '?'));
        $sql .= " AND class_level IN ($placeholders)";
        $params = array_merge($params, $adjustedLevels);
      }
      
      $sql .= " ORDER BY class_level, class_room, CAST(number_in_room AS UNSIGNED)";
      
      error_log("SQL: " . $sql);
      error_log("Params: " . json_encode($params));
      
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      error_log("Students found: " . count($students));
      
      if (count($students) > 0) {
        error_log("First student: " . json_encode($students[0]));
      }
      
      // นับจำนวนที่ลงทะเบียนไปแล้ว
      $countStmt = $pdo->prepare("
        SELECT student_id, COUNT(*) as count
        FROM registrations
        WHERE year_id = ?
        GROUP BY student_id
      ");
      $countStmt->execute([$yearId]);
      $counts = [];
      while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        $counts[$row['student_id']] = (int)$row['count'];
      }
      
      // เพิ่มข้อมูลจำนวนลงทะเบียน
      foreach ($students as &$student) {
        $student['registration_count'] = $counts[$student['id']] ?? 0;
      }
      
      error_log("Final count: " . count($students));
      
      echo json_encode([
        'success' => true,
        'students' => $students,
        'sport' => $sport
      ]);
      exit;
    }
    
    // เปลี่ยนตัวนักกีฬา
    if ($action === 'substitute') {
      $registrationId = $_POST['registration_id'] ?? 0;
      $newStudentId = $_POST['new_student_id'] ?? 0;
      $reason = trim($_POST['reason'] ?? '');
      
      if (!$registrationId || !$newStudentId) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
      }
      
      // ดึงข้อมูลเก่า
      $stmt = $pdo->prepare("
        SELECT r.*, s.color, sp.id as sport_id
        FROM registrations r
        JOIN students s ON r.student_id = s.id
        JOIN sports sp ON r.sport_id = sp.id
        WHERE r.id = ? AND r.year_id = ?
      ");
      $stmt->execute([$registrationId, $yearId]);
      $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if (!$oldData) {
        throw new Exception('ไม่พบข้อมูลการลงทะเบียนเดิม');
      }
      
      // เช็คว่านักเรียนใหม่ลงทะเบียนกีฬานี้แล้วหรือยัง
      $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM registrations 
        WHERE year_id = ? AND sport_id = ? AND student_id = ?
      ");
      $stmt->execute([$yearId, $oldData['sport_id'], $newStudentId]);
      $exists = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($exists['count'] > 0) {
        throw new Exception('นักเรียนคนนี้ลงทะเบียนกีฬานี้แล้ว กรุณาเลือกคนอื่น');
      }
      
      // เริ่ม transaction
      $pdo->beginTransaction();
      
      try {
        // บันทึกประวัติการเปลี่ยนตัว
        $stmt = $pdo->prepare("
          INSERT INTO player_substitutions 
          (year_id, sport_id, registration_id, old_student_id, new_student_id, color, created_by, reason)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
          $yearId,
          $oldData['sport_id'],
          $registrationId,
          $oldData['student_id'],
          $newStudentId,
          $oldData['color'],
          $_SESSION['admin']['id'] ?? null,
          $reason
        ]);
        
        // อัพเดทการลงทะเบียน
        $stmt = $pdo->prepare("
          UPDATE registrations 
          SET student_id = ?
          WHERE id = ?
        ");
        $stmt->execute([$newStudentId, $registrationId]);
        
        // Log activity
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
        $stmt->execute([$oldData['student_id']]);
        $oldStudent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt->execute([$newStudentId]);
        $newStudent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        log_activity('SUBSTITUTE', 'registrations', $registrationId,
          sprintf('เปลี่ยนตัวนักกีฬา | จาก: %s %s → %s %s | เหตุผล: %s',
            $oldStudent['first_name'], $oldStudent['last_name'],
            $newStudent['first_name'], $newStudent['last_name'],
            $reason ?: '-'
          )
        );
        
        $pdo->commit();
        
        echo json_encode(['success' => true]);
        
      } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
      }
      exit;
    }
    
    // ดึงประวัติการเปลี่ยนตัว
    if ($action === 'load_history') {
      $sportId = $_POST['sport_id'] ?? 0;
      
      $stmt = $pdo->prepare("
        SELECT 
          ps.*,
          sp.name as sport_name,
          old_s.student_code as old_code,
          old_s.first_name as old_fname,
          old_s.last_name as old_lname,
          new_s.student_code as new_code,
          new_s.first_name as new_fname,
          new_s.last_name as new_lname,
          u.username as created_by_name
        FROM player_substitutions ps
        JOIN sports sp ON ps.sport_id = sp.id
        JOIN students old_s ON ps.old_student_id = old_s.id
        JOIN students new_s ON ps.new_student_id = new_s.id
        LEFT JOIN users u ON ps.created_by = u.id
        WHERE ps.year_id = ? AND ps.sport_id = ?
        ORDER BY ps.substitution_date DESC
      ");
      $stmt->execute([$yearId, $sportId]);
      $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      echo json_encode([
        'success' => true,
        'history' => $history
      ]);
      exit;
    }
    
    // ดึงสถิติ Dashboard
    if ($action === 'load_dashboard') {
      // จำนวนการเปลี่ยนตัวทั้งหมด
      $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM player_substitutions WHERE year_id = ?
      ");
      $stmt->execute([$yearId]);
      $totalSubstitutions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
      
      // ประวัติการเปลี่ยนตัวทั้งหมด (เรียงตามชื่อนักเรียนใหม่ให้ซ้ำกันอยู่ติดกัน)
      $stmt = $pdo->prepare("
        SELECT 
          ps.*,
          sp.name as sport_name,
          old_s.student_code as old_code,
          old_s.first_name as old_fname,
          old_s.last_name as old_lname,
          new_s.student_code as new_code,
          new_s.first_name as new_fname,
          new_s.last_name as new_lname,
          u.username as created_by_name
        FROM player_substitutions ps
        JOIN sports sp ON ps.sport_id = sp.id
        JOIN students old_s ON ps.old_student_id = old_s.id
        JOIN students new_s ON ps.new_student_id = new_s.id
        LEFT JOIN users u ON ps.created_by = u.id
        WHERE ps.year_id = ?
        ORDER BY new_s.first_name, new_s.last_name, sp.name, ps.substitution_date DESC
      ");
      $stmt->execute([$yearId]);
      $allSubstitutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      echo json_encode([
        'success' => true,
        'dashboard' => [
          'total' => $totalSubstitutions,
          'allSubstitutions' => $allSubstitutions
        ]
      ]);
      exit;
    }
    
  } catch (Exception $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    echo json_encode([
      'success' => false,
      'error' => $e->getMessage()
    ]);
  }
  exit;
}

$pageTitle = 'จัดการการเปลี่ยนตัวนักกีฬา';

// ดึงกีฬาทั้งหมด
$stmt = $pdo->prepare("
  SELECT s.*, sc.name as category_name
  FROM sports s
  LEFT JOIN sport_categories sc ON s.category_id = sc.id
  WHERE s.year_id = ? 
  ORDER BY sc.name, s.name
");
$stmt->execute([$yearId]);
$sports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงประเภทกีฬา
$stmt = $pdo->prepare("
  SELECT DISTINCT sc.name 
  FROM sport_categories sc
  JOIN sports s ON s.category_id = sc.id
  WHERE s.year_id = ?
  ORDER BY sc.name
");
$stmt->execute([$yearId]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | ระบบจัดการกีฬาสี</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
      min-height: 100vh;
      padding-bottom: 2rem;
    }
    
    .dashboard-card {
      background: white;
      border-radius: 1rem;
      padding: 1.5rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 1.5rem;
    }
    
    .stat-card {
      text-align: center;
      padding: 2rem;
      background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
      color: white;
      border-radius: 1rem;
      box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
    }
    
    .stat-number {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-size: 1.1rem;
      opacity: 0.9;
    }
    
    .filter-section {
      background: white;
      padding: 1.5rem;
      border-radius: 1rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .color-badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 0.4rem;
      font-weight: 600;
      color: white;
      font-size: 0.85rem;
    }
    
    .color-orange { background: #ff9800; }
    .color-green { background: #4caf50; }
    .color-pink { background: #e91e63; }
    .color-blue { background: #2196f3; }
    
    .player-card {
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 0.75rem;
      padding: 1rem;
      margin-bottom: 0.75rem;
      transition: all 0.3s;
    }
    
    .player-card:hover {
      border-color: #1976d2;
      transform: translateX(4px);
      box-shadow: 0 4px 15px rgba(25, 118, 210, 0.15);
    }
    
    .student-option {
      padding: 1rem;
      border: 2px solid #e0e0e0;
      border-radius: 0.5rem;
      margin-bottom: 0.5rem;
      cursor: pointer;
      transition: all 0.2s;
      background: white;
    }
    
    .student-option:hover {
      border-color: #1976d2;
      background: #e3f2fd;
    }
    
    .student-option.selected {
      border-color: #1976d2;
      background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    }
    
    .badge-count {
      background: #fff3e0;
      color: #e65100;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.85rem;
    }
    
    .badge-count.warning {
      background: #ffebee;
      color: #c62828;
    }
    
    .table-substitutions {
      font-size: 0.95rem;
    }
    
    .table-substitutions th {
      background: #f5f5f5;
      font-weight: 600;
      color: #424242;
      border-bottom: 2px solid #e0e0e0;
    }
    
    .table-substitutions td {
      vertical-align: middle;
    }
    
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    
    .loading-overlay.active {
      display: flex;
    }
    
    .spinner {
      width: 3rem;
      height: 3rem;
      border: 4px solid #e0e0e0;
      border-top-color: #1976d2;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #e0e0e0;
    }
    
    .pagination {
      margin: 0;
    }
    
    .pagination .page-link {
      color: #1976d2;
      border-color: #e0e0e0;
    }
    
    .pagination .page-item.active .page-link {
      background-color: #1976d2;
      border-color: #1976d2;
    }
    
    .pagination .page-link:hover {
      background-color: #e3f2fd;
      border-color: #1976d2;
    }
    
    .page-size-selector {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .page-size-selector select {
      width: auto;
      padding: 0.25rem 0.5rem;
    }
    
    .table-container {
      max-height: 500px;
      overflow-y: auto;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
      <div class="spinner mx-auto mb-3"></div>
      <div class="text-white fw-semibold">กำลังดำเนินการ...</div>
    </div>
  </div>

  <main class="container py-4">
    <h2 class="text-dark fw-bold mb-4">
      <i class="bi bi-arrow-left-right"></i> จัดการการเปลี่ยนตัวนักกีฬา
    </h2>

    <div id="alertContainer"></div>

    <!-- Dashboard -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number" id="totalSubstitutions">0</div>
          <div class="stat-label">การเปลี่ยนตัวทั้งหมด</div>
        </div>
      </div>
      
      <div class="col-md-9">
        <div class="dashboard-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
              <i class="bi bi-table"></i> ประวัติการเปลี่ยนตัวทั้งหมด
            </h5>
            <div class="d-flex gap-2">
              <input type="text" id="searchAllSubstitutions" class="form-control form-control-sm" 
                     style="width: 250px;" placeholder="🔍 ค้นหาชื่อ, กีฬา...">
              <button class="btn btn-sm btn-outline-secondary" onclick="clearSearch()">
                <i class="bi bi-x-circle"></i> ล้าง
              </button>
            </div>
          </div>
          
          <div class="table-container">
            <table class="table table-hover table-substitutions" id="allSubstitutionsTable">
              <thead class="sticky-top">
                <tr>
                  <th width="5%">#</th>
                  <th width="20%">นักเรียนใหม่</th>
                  <th width="20%">กีฬา</th>
                  <th width="20%">แทนนักเรียน</th>
                  <th width="10%">สี</th>
                  <th width="15%">วันที่</th>
                  <th width="10%">ผู้บันทึก</th>
                </tr>
              </thead>
              <tbody id="allSubstitutionsBody">
                <tr>
                  <td colspan="7" class="text-center text-muted">กำลังโหลดข้อมูล...</td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="pagination-container">
            <div class="page-size-selector">
              <label class="mb-0 small text-muted">แสดง:</label>
              <select id="pageSizeSelect" class="form-select form-select-sm" onchange="changePageSize()">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">ทั้งหมด</option>
              </select>
              <span class="small text-muted">รายการ</span>
            </div>
            
            <div class="small text-muted" id="pageInfo">
              แสดง 0-0 จาก 0 รายการ
            </div>
            
            <nav>
              <ul class="pagination pagination-sm mb-0" id="pagination">
                <!-- จะถูกสร้างด้วย JavaScript -->
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-section">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">
            <i class="bi bi-funnel"></i> ประเภทกีฬา
          </label>
          <select id="categoryFilter" class="form-select">
            <option value="">ทั้งหมด</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-6">
          <label class="form-label fw-semibold">
            <i class="bi bi-search"></i> ค้นหากีฬา
          </label>
          <input type="text" id="sportSearch" class="form-control" placeholder="พิมพ์ชื่อกีฬา...">
        </div>
      </div>
    </div>

    <!-- Select Sport -->
    <div class="dashboard-card">
      <h5 class="fw-bold mb-3">
        <i class="bi bi-calendar-event"></i> เลือกรายการกีฬา
      </h5>
      <select id="sportSelect" class="form-select form-select-lg">
        <option value="">-- เลือกรายการกีฬา --</option>
        <?php foreach ($sports as $sport): ?>
          <option value="<?= $sport['id'] ?>" 
                  data-category="<?= htmlspecialchars($sport['category_name'] ?? '') ?>"
                  data-name="<?= htmlspecialchars($sport['name']) ?>">
            <?= htmlspecialchars($sport['name']) ?> 
            (<?= htmlspecialchars($sport['participant_type']) ?>)
            <?php if ($sport['category_name']): ?>
              - <?= htmlspecialchars($sport['category_name']) ?>
            <?php endif; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Players List -->
    <div id="playersContainer" style="display: none;">
      <div class="row">
        <?php 
        $colors = ['ส้ม' => 'orange', 'เขียว' => 'green', 'ชมพู' => 'pink', 'ฟ้า' => 'blue'];
        foreach ($colors as $colorTh => $colorEn): 
        ?>
          <div class="col-lg-6 mb-4">
            <div class="dashboard-card">
              <h5 class="mb-3">
                <span class="color-badge color-<?= $colorEn ?>"><?= $colorTh ?></span>
              </h5>
              <div id="players-<?= $colorEn ?>"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- History -->
      <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0">
            <i class="bi bi-journal-text"></i> ประวัติการเปลี่ยนตัวของกีฬานี้
          </h5>
          <button class="btn btn-sm btn-outline-primary" onclick="loadHistory()">
            <i class="bi bi-arrow-clockwise"></i> รีเฟรช
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-substitutions">
            <thead>
              <tr>
                <th width="5%">#</th>
                <th width="25%">นักเรียนใหม่</th>
                <th width="25%">แทนนักเรียน</th>
                <th width="10%">สี</th>
                <th width="20%">วันที่</th>
                <th width="15%">ผู้บันทึก</th>
              </tr>
            </thead>
            <tbody id="historyContainer">
              <tr>
                <td colspan="6" class="text-center text-muted">ยังไม่มีประวัติการเปลี่ยนตัว</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal: Substitute Player -->
  <div class="modal fade" id="substituteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            <i class="bi bi-arrow-left-right"></i> เปลี่ยนตัวนักกีฬา
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">นักกีฬาเดิม:</label>
            <div id="oldPlayerInfo" class="alert alert-info"></div>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-semibold">เลือกนักเรียนใหม่:</label>
            <input type="text" id="searchStudent" class="form-control mb-2" 
                   placeholder="🔍 ค้นหาชื่อ หรือรหัสนักเรียน...">
            <div id="studentsList" style="max-height: 400px; overflow-y: auto;"></div>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-semibold">เหตุผลในการเปลี่ยนตัว:</label>
            <textarea id="substituteReason" class="form-control" rows="3" 
                      placeholder="ระบุเหตุผล (ไม่บังคับ)..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="button" class="btn btn-primary" onclick="confirmSubstitute()">
            <i class="bi bi-check-lg"></i> ยืนยันการเปลี่ยนตัว
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    let currentSportId = null;
    let currentRegistrationId = null;
    let currentStudentId = null;
    let currentColor = null;
    let selectedStudentId = null;
    let allStudents = [];
    let allSubstitutionsData = []; // เก็บข้อมูลทั้งหมด
    let filteredSubstitutions = []; // เก็บข้อมูลที่กรองแล้ว
    let currentPage = 1;
    let pageSize = 20;
    let substituteModal = null;

    document.addEventListener('DOMContentLoaded', function() {
      substituteModal = new bootstrap.Modal(document.getElementById('substituteModal'));
      
      // Load dashboard
      loadDashboard();
      
      // Filter events
      document.getElementById('categoryFilter').addEventListener('change', filterSports);
      document.getElementById('sportSearch').addEventListener('input', filterSports);
      
      document.getElementById('sportSelect').addEventListener('change', function() {
        if (this.value) {
          currentSportId = this.value;
          loadSport(this.value);
        } else {
          document.getElementById('playersContainer').style.display = 'none';
        }
      });
      
      document.getElementById('searchStudent').addEventListener('input', function() {
        filterStudents(this.value);
      });
      
      // Search all substitutions
      document.getElementById('searchAllSubstitutions').addEventListener('input', function() {
        filterAllSubstitutions(this.value);
      });
    });

    function filterSports() {
      const category = document.getElementById('categoryFilter').value;
      const search = document.getElementById('sportSearch').value.toLowerCase();
      const select = document.getElementById('sportSelect');
      const options = select.querySelectorAll('option[value]');
      
      options.forEach(option => {
        if (!option.value) return;
        
        const optCategory = option.dataset.category || '';
        const optName = (option.dataset.name || '').toLowerCase();
        
        let show = true;
        if (category && optCategory !== category) show = false;
        if (search && !optName.includes(search)) show = false;
        
        option.style.display = show ? '' : 'none';
      });
    }

    async function showAlert(message, type = 'success') {
      const iconMap = {
        'success': 'success',
        'danger': 'error',
        'warning': 'warning',
        'info': 'info'
      };
      
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
      });
      
      Toast.fire({
        icon: iconMap[type] || 'info',
        title: message.replace(/^(✅|❌|⚠️|ℹ️)\s*/, '')
      });
    }

    function showLoading() {
      document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoading() {
      document.getElementById('loadingOverlay').classList.remove('active');
    }

    async function loadDashboard() {
      try {
        const response = await fetch('player.php?action=load_dashboard', {
          method: 'POST'
        });
        const result = await response.json();
        
        if (result.success) {
          const { total, allSubstitutions } = result.dashboard;
          
          // Total
          document.getElementById('totalSubstitutions').textContent = total;
          
          // เก็บข้อมูลไว้ใน global variable
          allSubstitutionsData = allSubstitutions;
          filteredSubstitutions = allSubstitutions;
          
          // แสดงข้อมูลหน้าแรก
          currentPage = 1;
          displayAllSubstitutions();
        }
      } catch (error) {
        console.error('Error loading dashboard:', error);
      }
    }

    function displayAllSubstitutions() {
      const tbody = document.getElementById('allSubstitutionsBody');
      
      if (filteredSubstitutions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">ไม่พบข้อมูล</td></tr>';
        updatePageInfo(0, 0, 0);
        updatePagination(0);
        return;
      }
      
      // คำนวณข้อมูลที่จะแสดงในหน้านี้
      const startIndex = (currentPage - 1) * pageSize;
      const endIndex = pageSize === 'all' ? filteredSubstitutions.length : Math.min(startIndex + pageSize, filteredSubstitutions.length);
      const pageData = pageSize === 'all' ? filteredSubstitutions : filteredSubstitutions.slice(startIndex, endIndex);
      
      tbody.innerHTML = pageData.map((item, i) => {
        const date = new Date(item.substitution_date);
        const colorClass = {
          'ส้ม': 'orange',
          'เขียว': 'green',
          'ชมพู': 'pink',
          'ฟ้า': 'blue'
        }[item.color];
        
        const rowNumber = startIndex + i + 1;
        
        return `
          <tr>
            <td>${rowNumber}</td>
            <td>
              <strong>${item.new_fname} ${item.new_lname}</strong>
              <br><small class="text-muted">${item.new_code}</small>
            </td>
            <td>${item.sport_name}</td>
            <td>
              <strong>${item.old_fname} ${item.old_lname}</strong>
              <br><small class="text-muted">${item.old_code}</small>
            </td>
            <td><span class="color-badge color-${colorClass}">${item.color}</span></td>
            <td><small>${date.toLocaleString('th-TH', { 
              year: 'numeric', 
              month: 'short', 
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            })}</small></td>
            <td><small>${item.created_by_name || 'ระบบ'}</small></td>
          </tr>
        `;
      }).join('');
      
      // อัพเดท pagination
      updatePageInfo(startIndex + 1, endIndex, filteredSubstitutions.length);
      updatePagination(filteredSubstitutions.length);
    }

    function updatePageInfo(start, end, total) {
      document.getElementById('pageInfo').textContent = `แสดง ${start}-${end} จาก ${total} รายการ`;
    }

    function updatePagination(totalItems) {
      const pagination = document.getElementById('pagination');
      
      if (pageSize === 'all' || totalItems === 0) {
        pagination.innerHTML = '';
        return;
      }
      
      const totalPages = Math.ceil(totalItems / pageSize);
      
      if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
      }
      
      let html = '';
      
      // Previous button
      html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
      `;
      
      // Page numbers
      const maxButtons = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
      let endPage = Math.min(totalPages, startPage + maxButtons - 1);
      
      if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
      }
      
      // First page
      if (startPage > 1) {
        html += `
          <li class="page-item">
            <a class="page-link" href="#" onclick="goToPage(1); return false;">1</a>
          </li>
        `;
        if (startPage > 2) {
          html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
      }
      
      // Page buttons
      for (let i = startPage; i <= endPage; i++) {
        html += `
          <li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
          </li>
        `;
      }
      
      // Last page
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        html += `
          <li class="page-item">
            <a class="page-link" href="#" onclick="goToPage(${totalPages}); return false;">${totalPages}</a>
          </li>
        `;
      }
      
      // Next button
      html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      `;
      
      pagination.innerHTML = html;
    }

    function goToPage(page) {
      const totalPages = Math.ceil(filteredSubstitutions.length / pageSize);
      if (page < 1 || page > totalPages) return;
      
      currentPage = page;
      displayAllSubstitutions();
      
      // Scroll to top of table
      document.getElementById('allSubstitutionsTable').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function changePageSize() {
      const select = document.getElementById('pageSizeSelect');
      pageSize = select.value === 'all' ? 'all' : parseInt(select.value);
      currentPage = 1;
      displayAllSubstitutions();
    }

    function filterAllSubstitutions(query) {
      query = query.toLowerCase().trim();
      
      if (!query) {
        filteredSubstitutions = allSubstitutionsData;
      } else {
        filteredSubstitutions = allSubstitutionsData.filter(item => {
          const newName = `${item.new_fname} ${item.new_lname}`.toLowerCase();
          const newCode = (item.new_code || '').toLowerCase();
          const oldName = `${item.old_fname} ${item.old_lname}`.toLowerCase();
          const oldCode = (item.old_code || '').toLowerCase();
          const sportName = (item.sport_name || '').toLowerCase();
          const color = (item.color || '').toLowerCase();
          
          return newName.includes(query) || 
                 newCode.includes(query) || 
                 oldName.includes(query) || 
                 oldCode.includes(query) || 
                 sportName.includes(query) ||
                 color.includes(query);
        });
      }
      
      currentPage = 1;
      displayAllSubstitutions();
    }

    function clearSearch() {
      document.getElementById('searchAllSubstitutions').value = '';
      filteredSubstitutions = allSubstitutionsData;
      currentPage = 1;
      displayAllSubstitutions();
    }

    async function loadSport(sportId) {
      showLoading();
      
      try {
        const formData = new FormData();
        formData.append('sport_id', sportId);
        
        const response = await fetch('player.php?action=load_sport', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
          displayPlayers(result.players);
          loadHistory();
          document.getElementById('playersContainer').style.display = 'block';
        } else {
          showAlert('❌ ' + (result.error || 'ไม่สามารถโหลดข้อมูลได้'), 'danger');
        }
      } catch (error) {
        hideLoading();
        showAlert('❌ เกิดข้อผิดพลาด', 'danger');
      }
    }

    function displayPlayers(playersByColor) {
      const colorMap = {
        'ส้ม': 'orange',
        'เขียว': 'green',
        'ชมพู': 'pink',
        'ฟ้า': 'blue'
      };
      
      for (const [colorTh, colorEn] of Object.entries(colorMap)) {
        const container = document.getElementById(`players-${colorEn}`);
        const players = playersByColor[colorTh] || [];
        
        if (players.length === 0) {
          container.innerHTML = '<p class="text-muted text-center">ยังไม่มีนักกีฬาลงทะเบียน</p>';
          continue;
        }
        
        container.innerHTML = players.map((player, i) => {
          // เก็บข้อมูลเป็น JSON แทนการส่งแยก
          const playerData = JSON.stringify({
            registration_id: player.registration_id,
            student_id: player.student_id,
            color: colorTh,
            name: `${player.first_name} ${player.last_name}`,
            code: player.student_code
          });
          
          return `
            <div class="player-card d-flex justify-content-between align-items-center">
              <div>
                <strong>${i + 1}. ${player.first_name} ${player.last_name}</strong>
                <div class="small text-muted">
                  รหัส: ${player.student_code} | 
                  ชั้น: ${player.class_level}/${player.class_room}
                </div>
              </div>
              <button class="btn btn-warning btn-sm" 
                      onclick='openSubstituteModal(${playerData})'>
                <i class="bi bi-arrow-left-right"></i> เปลี่ยนตัว
              </button>
            </div>
          `;
        }).join('');
      }
    }

    async function openSubstituteModal(playerData) {
      currentRegistrationId = playerData.registration_id;
      currentStudentId = playerData.student_id;
      currentColor = playerData.color;
      selectedStudentId = null;
      
      const colorClass = {
        'ส้ม': 'orange',
        'เขียว': 'green',
        'ชมพู': 'pink',
        'ฟ้า': 'blue'
      }[playerData.color];
      
      document.getElementById('oldPlayerInfo').innerHTML = `
        <strong><i class="bi bi-person"></i> ${playerData.name}</strong><br>
        รหัส: ${playerData.code} | สี: <span class="color-badge color-${colorClass}">${playerData.color}</span>
      `;
      
      document.getElementById('searchStudent').value = '';
      document.getElementById('substituteReason').value = '';
      document.getElementById('studentsList').innerHTML = '<p class="text-center text-muted">กำลังโหลด...</p>';
      
      substituteModal.show();
      
      await loadStudents();
    }

    async function loadStudents() {
      console.log('🔍 Loading students...', {
        sportId: currentSportId,
        color: currentColor,
        studentId: currentStudentId
      });
      
      try {
        const formData = new FormData();
        formData.append('sport_id', currentSportId);
        formData.append('color', currentColor);
        formData.append('current_student_id', currentStudentId);
        
        const response = await fetch('player.php?action=load_students', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        console.log('📦 Load students result:', result);
        
        if (result.success) {
          allStudents = result.students;
          console.log('✅ Students loaded:', allStudents.length, allStudents);
          displayStudents(allStudents);
        } else {
          console.error('❌ Load failed:', result.error);
          showAlert('❌ ' + (result.error || 'ไม่สามารถโหลดรายชื่อนักเรียนได้'), 'danger');
          document.getElementById('studentsList').innerHTML = '<p class="text-center text-danger">ไม่สามารถโหลดข้อมูลได้</p>';
        }
      } catch (error) {
        console.error('❌ Error:', error);
        showAlert('❌ เกิดข้อผิดพลาด', 'danger');
        document.getElementById('studentsList').innerHTML = '<p class="text-center text-danger">เกิดข้อผิดพลาด</p>';
      }
    }

    function displayStudents(students) {
      const container = document.getElementById('studentsList');
      
      console.log('🎨 Displaying students:', students.length);
      
      if (!students || students.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">ไม่พบนักเรียนที่ตรงเงื่อนไข</p>';
        return;
      }
      
      const html = students.map(student => {
        const count = student.registration_count || 0;
        const badgeClass = count >= 3 ? 'warning' : '';
        const badge = count > 0 ? `<span class="badge-count ${badgeClass}">ลงแล้ว ${count} รายการ</span>` : '';
        
        return `
          <div class="student-option" data-student-id="${student.id}">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <strong>${student.first_name} ${student.last_name}</strong>
                <div class="small text-muted">
                  รหัส: ${student.student_code} | 
                  ชั้น: ${student.class_level}/${student.class_room}
                </div>
              </div>
              ${badge}
            </div>
          </div>
        `;
      }).join('');
      
      container.innerHTML = html;
      
      // Add click event listeners
      container.querySelectorAll('.student-option').forEach(el => {
        el.addEventListener('click', function() {
          const studentId = parseInt(this.dataset.studentId);
          selectStudent(studentId, this);
        });
      });
      
      console.log('✅ Displayed', students.length, 'students');
    }

    function selectStudent(studentId, element) {
      selectedStudentId = studentId;
      document.querySelectorAll('.student-option').forEach(el => {
        el.classList.remove('selected');
      });
      element.classList.add('selected');
      console.log('✅ Selected student:', studentId);
    }

    function filterStudents(query) {
      query = query.toLowerCase().trim();
      
      console.log('🔎 Filtering with query:', query);
      console.log('📚 All students count:', allStudents.length);
      
      if (!query) {
        console.log('➡️ No query, showing all');
        displayStudents(allStudents);
        return;
      }
      
      const filtered = allStudents.filter(student => {
        const fullName = `${student.first_name} ${student.last_name}`.toLowerCase();
        const code = (student.student_code || '').toLowerCase();
        const match = fullName.includes(query) || code.includes(query);
        
        if (match) {
          console.log('✅ Match:', student.first_name, student.last_name);
        }
        
        return match;
      });
      
      console.log('🎯 Filtered count:', filtered.length);
      displayStudents(filtered);
    }

    async function confirmSubstitute() {
      if (!selectedStudentId) {
        Swal.fire({
          icon: 'warning',
          title: 'กรุณาเลือกนักเรียน',
          text: 'กรุณาเลือกนักเรียนที่ต้องการเปลี่ยนก่อน',
          confirmButtonText: 'ตรวจสอบ',
          confirmButtonColor: '#1976d2'
        });
        return;
      }
      
      // ใช้ SweetAlert2 แทน confirm
      const result = await Swal.fire({
        icon: 'question',
        title: 'ยืนยันการเปลี่ยนตัว?',
        text: 'คุณแน่ใจหรือไม่ที่จะเปลี่ยนตัวนักกีฬา',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#1976d2',
        cancelButtonColor: '#d33',
        reverseButtons: true
      });
      
      if (!result.isConfirmed) {
        return;
      }
      
      showLoading();
      
      try {
        const formData = new FormData();
        formData.append('registration_id', currentRegistrationId);
        formData.append('new_student_id', selectedStudentId);
        formData.append('reason', document.getElementById('substituteReason').value.trim());
        
        const response = await fetch('player.php?action=substitute', {
          method: 'POST',
          body: formData
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.success) {
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: 'เปลี่ยนตัวนักกีฬาเรียบร้อยแล้ว',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#1976d2',
            timer: 2000,
            timerProgressBar: true
          });
          
          substituteModal.hide();
          loadSport(currentSportId);
          loadDashboard();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: data.error || 'ไม่สามารถเปลี่ยนตัวได้',
            confirmButtonText: 'ตรวจสอบ',
            confirmButtonColor: '#d33'
          });
        }
      } catch (error) {
        hideLoading();
        Swal.fire({
          icon: 'error',
          title: 'เกิดข้อผิดพลาด!',
          text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
          confirmButtonText: 'ตรวจสอบ',
          confirmButtonColor: '#d33'
        });
      }
    }

    async function loadHistory() {
      if (!currentSportId) return;
      
      try {
        const formData = new FormData();
        formData.append('sport_id', currentSportId);
        
        const response = await fetch('player.php?action=load_history', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          displayHistory(result.history);
        }
      } catch (error) {
        console.error('Error loading history:', error);
      }
    }

    function displayHistory(history) {
      const container = document.getElementById('historyContainer');
      
      if (history.length === 0) {
        container.innerHTML = '<tr><td colspan="6" class="text-center text-muted">ยังไม่มีประวัติการเปลี่ยนตัว</td></tr>';
        return;
      }
      
      container.innerHTML = history.map((item, i) => {
        const date = new Date(item.substitution_date);
        const colorClass = {
          'ส้ม': 'orange',
          'เขียว': 'green',
          'ชมพู': 'pink',
          'ฟ้า': 'blue'
        }[item.color];
        
        return `
          <tr>
            <td>${i + 1}</td>
            <td>
              <strong>${item.new_fname} ${item.new_lname}</strong>
              <br><small class="text-muted">${item.new_code}</small>
            </td>
            <td>
              <strong>${item.old_fname} ${item.old_lname}</strong>
              <br><small class="text-muted">${item.old_code}</small>
            </td>
            <td><span class="color-badge color-${colorClass}">${item.color}</span></td>
            <td><small>${date.toLocaleString('th-TH', { 
              year: 'numeric', 
              month: 'short', 
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            })}</small></td>
            <td><small>${item.created_by_name || 'ระบบ'}</small></td>
          </tr>
        `;
      }).join('');
    }
  </script>
</body>
</html>