<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ ตรวจสอบว่าต้องเป็น admin เท่านั้น
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

// ✅ เช็ค session timeout (30 นาที)
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

// ===== จัดการ AJAX Requests =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  header('Content-Type: application/json; charset=utf-8');
  
  try {
    $action = $_GET['action'];
    
    // สร้าง Backup
    if ($action === 'create') {
      $host = DB_HOST;
      $user = DB_USER;
      $pass = DB_PASS;
      $name = DB_NAME;
      
      $backupDir = __DIR__ . '/backups';
      if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
      }
      
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "backup_{$timestamp}.sql";
      $filepath = $backupDir . '/' . $filename;
      
      // ใช้ PHP เพื่อสำรองข้อมูล
      $pdo = db();
      $tables = [];
      
      $stmt = $pdo->query("SHOW TABLES");
      while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
      }
      
      $sql = "-- Backup Database: {$name}\n";
      $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
      $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
      $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
      $sql .= "SET time_zone = \"+07:00\";\n\n";
      
      foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $row[1] . ";\n\n";
        
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        
        if (!empty($rows)) {
          foreach ($rows as $row) {
            $values = array_map(function($value) use ($pdo) {
              return $value === null ? 'NULL' : $pdo->quote($value);
            }, $row);
            $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
          }
          $sql .= "\n";
        }
      }
      
      $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
      
      file_put_contents($filepath, $sql);
      
      if (!file_exists($filepath) || filesize($filepath) === 0) {
        throw new Exception('ไม่สามารถสร้างไฟล์สำรองข้อมูลได้');
      }
      
      log_activity('BACKUP', 'database', null, 
        'สร้างไฟล์สำรองข้อมูล | ไฟล์: ' . $filename . ' | ขนาด: ' . filesize($filepath) . ' bytes');
      
      echo json_encode([
        'success' => true,
        'filename' => $filename,
        'size' => filesize($filepath)
      ]);
    }
    
    // อัพโหลดไฟล์ Backup
    elseif ($action === 'upload') {
      if (!isset($_FILES['backup_file'])) {
        throw new Exception('ไม่พบไฟล์ที่อัพโหลด');
      }
      
      $file = $_FILES['backup_file'];
      
      // ตรวจสอบ error
      if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
      }
      
      // ตรวจสอบนามสกุลไฟล์
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if ($ext !== 'sql') {
        throw new Exception('กรุณาอัพโหลดไฟล์ .sql เท่านั้น');
      }
      
      // ตรวจสอบขนาดไฟล์ (สูงสุด 50MB)
      $maxSize = 50 * 1024 * 1024; // 50MB
      if ($file['size'] > $maxSize) {
        throw new Exception('ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 50MB)');
      }
      
      $backupDir = __DIR__ . '/backups';
      if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
      }
      
      // สร้างชื่อไฟล์ใหม่ (เพิ่ม timestamp ถ้าชื่อซ้ำ)
      $filename = $file['name'];
      $filepath = $backupDir . '/' . $filename;
      
      if (file_exists($filepath)) {
        $timestamp = date('Y-m-d_H-i-s');
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $filename = $name . '_' . $timestamp . '.sql';
        $filepath = $backupDir . '/' . $filename;
      }
      
      // ย้ายไฟล์
      if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('ไม่สามารถบันทึกไฟล์ได้');
      }
      
      log_activity('UPLOAD', 'database', null, 
        'อัพโหลดไฟล์สำรองข้อมูล | ไฟล์: ' . $filename . ' | ขนาด: ' . $file['size'] . ' bytes');
      
      echo json_encode([
        'success' => true,
        'filename' => $filename,
        'size' => filesize($filepath)
      ]);
      exit;
    }
    
    // คืนค่า Backup
    elseif ($action === 'restore') {
      $input = json_decode(file_get_contents('php://input'), true);
      $filename = $input['filename'] ?? '';
      
      if (empty($filename)) {
        throw new Exception('ไม่พบชื่อไฟล์');
      }
      
      $backupDir = __DIR__ . '/backups';
      $filepath = $backupDir . '/' . basename($filename);
      
      if (!file_exists($filepath)) {
        throw new Exception('ไม่พบไฟล์สำรอง');
      }
      
      $pdo = db();
      $sql = file_get_contents($filepath);
      
      if (empty($sql)) {
        throw new Exception('ไฟล์สำรองว่างเปล่า');
      }
      
      try {
        // ปิด autocommit และ foreign key checks
        $pdo->exec("SET autocommit=0");
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        
        // แยก SQL โดยเก็บ multi-line statements
        $lines = explode("\n", $sql);
        $tempLine = '';
        
        foreach ($lines as $line) {
          $line = trim($line);
          
          // ข้าม comment และบรรทัดว่าง
          if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
          }
          
          $tempLine .= ' ' . $line;
          
          // ถ้าจบด้วย ; แสดงว่าเป็น statement สมบูรณ์
          if (substr(trim($line), -1) === ';') {
            $statement = trim($tempLine);
            $statement = rtrim($statement, ';');
            
            if (!empty($statement)) {
              // ข้าม SET statements ที่ซ้ำซ้อน
              $upperStmt = strtoupper(substr($statement, 0, 30));
              if (strpos($upperStmt, 'SET FOREIGN_KEY') === false && 
                  strpos($upperStmt, 'SET SQL_MODE') === false &&
                  strpos($upperStmt, 'SET AUTOCOMMIT') === false &&
                  strpos($upperStmt, 'SET TIME_ZONE') === false) {
                $pdo->exec($statement);
              }
            }
            
            $tempLine = '';
          }
        }
        
        // เปิด foreign key checks และ autocommit กลับ
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        $pdo->exec("SET autocommit=1");
        
        log_activity('RESTORE', 'database', null, 
          'คืนค่าข้อมูลจากไฟล์สำรอง | ไฟล์: ' . $filename);
        
        echo json_encode(['success' => true]);
        
      } catch (Exception $e) {
        // เปิด foreign key checks และ autocommit กลับในกรณีเกิดข้อผิดพลาด
        try {
          $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
          $pdo->exec("SET autocommit=1");
        } catch (Exception $cleanupEx) {
          // Ignore cleanup errors
        }
        throw $e;
      }
    }
    
    // ลบ Backup
    elseif ($action === 'delete') {
      $input = json_decode(file_get_contents('php://input'), true);
      $filename = $input['filename'] ?? '';
      
      if (empty($filename)) {
        throw new Exception('ไม่พบชื่อไฟล์');
      }
      
      $backupDir = __DIR__ . '/backups';
      $filepath = $backupDir . '/' . basename($filename);
      
      if (!file_exists($filepath)) {
        throw new Exception('ไม่พบไฟล์สำรอง');
      }
      
      if (!unlink($filepath)) {
        throw new Exception('ไม่สามารถลบไฟล์ได้');
      }
      
      log_activity('DELETE', 'database', null, 
        'ลบไฟล์สำรองข้อมูล | ไฟล์: ' . $filename);
      
      echo json_encode(['success' => true]);
    }
    
  } catch (Exception $e) {
    echo json_encode([
      'success' => false,
      'error' => $e->getMessage()
    ]);
  }
  exit;
}

// ===== ดาวน์โหลด Backup =====
if (isset($_GET['download'])) {
  $filename = $_GET['download'] ?? '';
  
  if (empty($filename)) {
    die('ไม่พบชื่อไฟล์');
  }
  
  $backupDir = __DIR__ . '/backups';
  $filepath = $backupDir . '/' . basename($filename);
  
  if (!file_exists($filepath)) {
    die('ไม่พบไฟล์สำรอง');
  }
  
  log_activity('DOWNLOAD', 'database', null, 
    'ดาวน์โหลดไฟล์สำรองข้อมูล | ไฟล์: ' . $filename);
  
  header('Content-Type: application/sql');
  header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
  header('Content-Length: ' . filesize($filepath));
  header('Pragma: no-cache');
  header('Expires: 0');
  
  readfile($filepath);
  exit;
}

$pageTitle = 'สำรองข้อมูล';

// ดึงรายการไฟล์ backup ที่มีอยู่
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
  mkdir($backupDir, 0755, true);
}

$backupFiles = [];
if ($handle = opendir($backupDir)) {
  while (false !== ($file = readdir($handle))) {
    if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
      $filepath = $backupDir . '/' . $file;
      $backupFiles[] = [
        'name' => $file,
        'size' => filesize($filepath),
        'date' => filemtime($filepath)
      ];
    }
  }
  closedir($handle);
}

// เรียงตามวันที่ล่าสุด
usort($backupFiles, function($a, $b) {
  return $b['date'] - $a['date'];
});

function formatBytes($bytes, $precision = 2) {
  $units = ['B', 'KB', 'MB', 'GB'];
  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);
  $bytes /= (1 << (10 * $pow));
  return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | ระบบจัดการกีฬาสี</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link rel="shortcut icon" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      min-height: 100vh;
    }
    
    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: all 0.3s;
    }
    
    .card:hover {
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .card-header {
      background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
      color: white;
      border: none;
      border-radius: 1rem 1rem 0 0 !important;
      padding: 1.25rem 1.5rem;
      font-weight: 600;
    }
    
    .btn-create-backup {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      border: none;
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 0.75rem;
      font-weight: 500;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    
    .btn-create-backup:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
      color: white;
    }
    
    .backup-item {
      background: white;
      border-radius: 0.75rem;
      padding: 1.25rem;
      margin-bottom: 1rem;
      border: 2px solid #e2e8f0;
      transition: all 0.3s;
    }
    
    .backup-item:hover {
      border-color: #0ea5e9;
      transform: translateX(4px);
      box-shadow: 0 4px 15px rgba(14, 165, 233, 0.15);
    }
    
    .backup-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .backup-icon {
      font-size: 2.5rem;
      color: #0ea5e9;
    }
    
    .backup-details {
      flex: 1;
      min-width: 200px;
    }
    
    .backup-name {
      font-weight: 600;
      color: #1e293b;
      font-size: 1.1rem;
      margin-bottom: 0.25rem;
    }
    
    .backup-meta {
      color: #64748b;
      font-size: 0.9rem;
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .backup-actions {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }
    
    .btn-restore, .btn-download, .btn-delete {
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.3s;
      border: none;
    }
    
    .btn-restore {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
    }
    
    .btn-restore:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
      color: white;
    }
    
    .btn-download {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      color: white;
    }
    
    .btn-download:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
      color: white;
    }
    
    .btn-delete {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
    }
    
    .btn-delete:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
      color: white;
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #94a3b8;
    }
    
    .empty-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .alert {
      border: none;
      border-radius: 0.75rem;
      padding: 1rem 1.25rem;
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
    
    .loading-content {
      background: white;
      padding: 2rem 3rem;
      border-radius: 1rem;
      text-align: center;
    }
    
    .spinner {
      width: 3rem;
      height: 3rem;
      border: 4px solid #e2e8f0;
      border-top-color: #0ea5e9;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 1rem;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    /* Custom Confirm Modal */
    .confirm-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.2s;
    }
    
    .confirm-modal.active {
      display: flex;
    }
    
    .confirm-content {
      background: white;
      border-radius: 1rem;
      padding: 2rem;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s;
    }
    
    .confirm-icon {
      font-size: 3rem;
      text-align: center;
      margin-bottom: 1rem;
    }
    
    .confirm-title {
      font-size: 1.5rem;
      font-weight: 600;
      text-align: center;
      margin-bottom: 1rem;
      color: #1e293b;
    }
    
    .confirm-message {
      text-align: center;
      color: #64748b;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }
    
    .confirm-buttons {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
    }
    
    .confirm-btn {
      padding: 0.75rem 2rem;
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
      font-family: 'Kanit', sans-serif;
    }
    
    .confirm-btn-primary {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
    }
    
    .confirm-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    
    .confirm-btn-danger {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
    }
    
    .confirm-btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    .confirm-btn-secondary {
      background: #e2e8f0;
      color: #475569;
    }
    
    .confirm-btn-secondary:hover {
      background: #cbd5e1;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes slideUp {
      from {
        transform: translateY(20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    
    @media (max-width: 768px) {
      .backup-info {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .backup-actions {
        width: 100%;
        flex-direction: column;
      }
      
      .btn-restore, .btn-download, .btn-delete {
        width: 100%;
      }
      
      .confirm-buttons {
        flex-direction: column;
      }
      
      .confirm-btn {
        width: 100%;
      }
    }
    
    /* Upload Area Styles */
    .upload-area {
      border: 3px dashed #cbd5e1;
      border-radius: 1rem;
      padding: 2rem;
      text-align: center;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 1.5rem;
    }
    
    .upload-area:hover {
      border-color: #0ea5e9;
      background: #e0f2fe;
    }
    
    .upload-area.dragover {
      border-color: #0ea5e9;
      background: #dbeafe;
      transform: scale(1.02);
    }
    
    .upload-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.7;
    }
    
    .upload-text {
      color: #64748b;
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }
    
    .upload-hint {
      color: #94a3b8;
      font-size: 0.9rem;
    }
    
    #backupFileInput {
      display: none;
    }
    
    .btn-group-custom {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
      .btn-group-custom {
        flex-direction: column;
      }
      
      .btn-group-custom .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
      <div class="spinner"></div>
      <div class="fw-semibold">กำลังดำเนินการ...</div>
      <div class="text-muted small">กรุณารอสักครู่</div>
    </div>
  </div>

  <!-- Custom Confirm Modal -->
  <div class="confirm-modal" id="confirmModal">
    <div class="confirm-content">
      <div class="confirm-icon" id="confirmIcon">❓</div>
      <div class="confirm-title" id="confirmTitle">ยืนยันการทำงาน</div>
      <div class="confirm-message" id="confirmMessage">คุณแน่ใจหรือไม่?</div>
      <div class="confirm-buttons" id="confirmButtons">
        <button class="confirm-btn confirm-btn-secondary" onclick="closeConfirm()">ยกเลิก</button>
        <button class="confirm-btn confirm-btn-primary" id="confirmOkBtn">ยืนยัน</button>
      </div>
    </div>
  </div>

  <main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <h2 class="fw-bold text-primary mb-0">💾 สำรองข้อมูล</h2>
      <div class="btn-group-custom">
        <button class="btn btn-create-backup" onclick="createBackup()">
          ➕ สร้างไฟล์สำรองใหม่
        </button>
        <button class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 500; border: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);" onclick="triggerUpload()">
          📤 อัพโหลดไฟล์สำรอง
        </button>
      </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Upload Area -->
    <div class="card mb-4" id="uploadCard" style="display: none;">
      <div class="card-header">
        📤 อัพโหลดไฟล์สำรองข้อมูล
      </div>
      <div class="card-body">
        <div class="upload-area" id="uploadArea" onclick="document.getElementById('backupFileInput').click()">
          <div class="upload-icon">📁</div>
          <div class="upload-text fw-semibold">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</div>
          <div class="upload-hint">รองรับไฟล์ .sql เท่านั้น (ขนาดสูงสุด 50MB)</div>
        </div>
        <input type="file" id="backupFileInput" accept=".sql" onchange="handleFileSelect(event)">
        <div class="text-center">
          <button class="btn btn-secondary" onclick="cancelUpload()">ยกเลิก</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        📦 ไฟล์สำรองข้อมูล
      </div>
      <div class="card-body">
        <div id="backupList">
          <?php if (empty($backupFiles)): ?>
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <div class="fw-semibold">ยังไม่มีไฟล์สำรองข้อมูล</div>
              <div class="text-muted small">คลิกปุ่มด้านบนเพื่อสร้างไฟล์สำรองข้อมูลใหม่</div>
            </div>
          <?php else: ?>
            <?php foreach ($backupFiles as $backup): ?>
              <div class="backup-item" data-filename="<?= htmlspecialchars($backup['name']) ?>">
                <div class="backup-info">
                  <div class="backup-icon">📄</div>
                  <div class="backup-details">
                    <div class="backup-name"><?= htmlspecialchars($backup['name']) ?></div>
                    <div class="backup-meta">
                      <span>📅 <?= date('d/m/Y H:i:s', $backup['date']) ?></span>
                      <span>💾 <?= formatBytes($backup['size']) ?></span>
                    </div>
                  </div>
                  <div class="backup-actions">
                    <button class="btn btn-restore" onclick="restoreBackup('<?= htmlspecialchars($backup['name']) ?>')">
                      🔄 คืนค่า
                    </button>
                    <a href="backup.php?download=<?= urlencode($backup['name']) ?>" class="btn btn-download">
                      ⬇️ ดาวน์โหลด
                    </a>
                    <button class="btn btn-delete" onclick="deleteBackup('<?= htmlspecialchars($backup['name']) ?>')">
                      🗑️ ลบ
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">
        ℹ️ คำแนะนำ
      </div>
      <div class="card-body">
        <ul class="mb-0">
          <li><strong>สร้างไฟล์สำรองข้อมูล:</strong> คลิกปุ่ม "สร้างไฟล์สำรองใหม่" เพื่อสำรองข้อมูลทั้งหมดในระบบ</li>
          <li><strong>อัพโหลดไฟล์สำรอง:</strong> คลิกปุ่ม "อัพโหลดไฟล์สำรอง" เพื่อนำไฟล์ .sql ที่ดาวน์โหลดไว้กลับมาใช้งาน</li>
          <li><strong>คืนค่าข้อมูล:</strong> คลิกปุ่ม "🔄 คืนค่า" เพื่อกลับไปใช้ข้อมูลจากไฟล์สำรอง (ข้อมูลปัจจุบันจะถูกแทนที่)</li>
          <li><strong>ดาวน์โหลด:</strong> คลิกปุ่ม "⬇️ ดาวน์โหลด" เพื่อบันทึกไฟล์สำรองลงเครื่องของคุณ</li>
          <li><strong>ลบไฟล์:</strong> คลิกปุ่ม "🗑️ ลบ" เพื่อลบไฟล์สำรองที่ไม่ต้องการ</li>
          <li class="text-danger"><strong>⚠️ คำเตือน:</strong> การคืนค่าข้อมูลจะลบข้อมูลปัจจุบันทั้งหมดและแทนที่ด้วยข้อมูลจากไฟล์สำรอง กรุณาตรวจสอบให้แน่ใจก่อนดำเนินการ</li>
        </ul>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Custom Confirm Dialog
    function showConfirm(icon, title, message, isDanger = false) {
      return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const iconEl = document.getElementById('confirmIcon');
        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const okBtn = document.getElementById('confirmOkBtn');
        
        iconEl.textContent = icon;
        titleEl.textContent = title;
        messageEl.innerHTML = message;
        
        // เปลี่ยนสีปุ่มตามประเภท
        okBtn.className = `confirm-btn confirm-btn-${isDanger ? 'danger' : 'primary'}`;
        
        modal.classList.add('active');
        
        // Handle OK button
        okBtn.onclick = () => {
          modal.classList.remove('active');
          resolve(true);
        };
        
        // Handle close on background click
        modal.onclick = (e) => {
          if (e.target === modal) {
            modal.classList.remove('active');
            resolve(false);
          }
        };
      });
    }
    
    function closeConfirm() {
      document.getElementById('confirmModal').classList.remove('active');
    }
    
    function showAlert(message, type = 'success') {
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
      alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.getElementById('alertContainer').appendChild(alertDiv);
      
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    }

    function showLoading() {
      document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoading() {
      document.getElementById('loadingOverlay').classList.remove('active');
    }

    async function createBackup() {
      const confirmed = await showConfirm(
        '🔄',
        'สร้างไฟล์สำรองข้อมูล',
        'คุณต้องการสร้างไฟล์สำรองข้อมูลใหม่ใช่หรือไม่?'
      );
      
      if (!confirmed) return;
      
      showLoading();
      
      try {
        const response = await fetch('backup.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' }
        });
        
        const result = await response.json();
        
        hideLoading();
        
        if (result.success) {
          showAlert('✅ สร้างไฟล์สำรองข้อมูลสำเร็จ', 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          showAlert('❌ เกิดข้อผิดพลาด: ' + (result.error || 'ไม่สามารถสร้างไฟล์สำรองได้'), 'danger');
        }
      } catch (error) {
        hideLoading();
        showAlert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
      }
    }

    async function restoreBackup(filename) {
      const confirmed1 = await showConfirm(
        '⚠️',
        'คืนค่าข้อมูล',
        `คุณแน่ใจหรือไม่ที่จะคืนค่าข้อมูลจากไฟล์นี้?<br><br>
        <strong class="text-danger">⚡ ข้อมูลปัจจุบันทั้งหมดจะถูกแทนที่!</strong><br><br>
        <strong>ไฟล์:</strong> ${filename}`,
        true
      );
      
      if (!confirmed1) return;
      
      const confirmed2 = await showConfirm(
        '🔴',
        'ยืนยันอีกครั้ง',
        '<strong>การกระทำนี้ไม่สามารถย้อนกลับได้!</strong><br><br>คุณแน่ใจ 100% หรือไม่?',
        true
      );
      
      if (!confirmed2) return;
      
      showLoading();
      
      try {
        const response = await fetch('backup.php?action=restore', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ filename: filename })
        });
        
        const result = await response.json();
        
        hideLoading();
        
        if (result.success) {
          showAlert('✅ คืนค่าข้อมูลสำเร็จ', 'success');
          setTimeout(() => location.reload(), 2000);
        } else {
          showAlert('❌ เกิดข้อผิดพลาด: ' + (result.error || 'ไม่สามารถคืนค่าข้อมูลได้'), 'danger');
        }
      } catch (error) {
        hideLoading();
        showAlert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
      }
    }

    async function deleteBackup(filename) {
      const confirmed = await showConfirm(
        '🗑️',
        'ลบไฟล์สำรอง',
        `คุณต้องการลบไฟล์สำรองนี้ใช่หรือไม่?<br><br><strong>${filename}</strong>`,
        true
      );
      
      if (!confirmed) return;
      
      showLoading();
      
      try {
        const response = await fetch('backup.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ filename: filename })
        });
        
        const result = await response.json();
        
        hideLoading();
        
        if (result.success) {
          showAlert('✅ ลบไฟล์สำรองสำเร็จ', 'success');
          document.querySelector(`[data-filename="${filename}"]`).remove();
          
          if (document.querySelectorAll('.backup-item').length === 0) {
            document.getElementById('backupList').innerHTML = `
              <div class="empty-state">
                <div class="empty-icon">📭</div>
                <div class="fw-semibold">ยังไม่มีไฟล์สำรองข้อมูล</div>
                <div class="text-muted small">คลิกปุ่มด้านบนเพื่อสร้างไฟล์สำรองข้อมูลใหม่</div>
              </div>
            `;
          }
        } else {
          showAlert('❌ เกิดข้อผิดพลาด: ' + (result.error || 'ไม่สามารถลบไฟล์ได้'), 'danger');
        }
      } catch (error) {
        hideLoading();
        showAlert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
      }
    }

    // Upload Functions
    function triggerUpload() {
      document.getElementById('uploadCard').style.display = 'block';
      document.getElementById('uploadCard').scrollIntoView({ behavior: 'smooth' });
    }

    function cancelUpload() {
      document.getElementById('uploadCard').style.display = 'none';
      document.getElementById('backupFileInput').value = '';
    }

    // Drag and Drop
    const uploadArea = document.getElementById('uploadArea');
    
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
      uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        handleFile(files[0]);
      }
    });

    async function handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        await handleFile(file);
      }
    }

    async function handleFile(file) {
      // ตรวจสอบนามสกุล
      if (!file.name.endsWith('.sql')) {
        showAlert('❌ กรุณาเลือกไฟล์ .sql เท่านั้น', 'danger');
        return;
      }
      
      // ตรวจสอบขนาด (50MB)
      const maxSize = 50 * 1024 * 1024;
      if (file.size > maxSize) {
        showAlert('❌ ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 50MB)', 'danger');
        return;
      }
      
      const confirmed = await showConfirm(
        '📤',
        'อัพโหลดไฟล์สำรอง',
        `คุณต้องการอัพโหลดไฟล์นี้ใช่หรือไม่?<br><br><strong>${file.name}</strong><br>ขนาด: ${formatBytes(file.size)}`
      );
      
      if (!confirmed) {
        document.getElementById('backupFileInput').value = '';
        return;
      }
      
      showLoading();
      
      try {
        const formData = new FormData();
        formData.append('backup_file', file);
        
        const response = await fetch('backup.php?action=upload', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        hideLoading();
        
        if (result.success) {
          showAlert('✅ อัพโหลดไฟล์สำรองสำเร็จ', 'success');
          document.getElementById('backupFileInput').value = '';
          cancelUpload();
          setTimeout(() => location.reload(), 1500);
        } else {
          showAlert('❌ เกิดข้อผิดพลาด: ' + (result.error || 'ไม่สามารถอัพโหลดได้'), 'danger');
        }
      } catch (error) {
        hideLoading();
        showAlert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
      }
    }

    function formatBytes(bytes) {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
  </script>
</body>
</html>