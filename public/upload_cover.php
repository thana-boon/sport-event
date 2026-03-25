<?php
// public/upload_cover.php — อัปโหลดหน้าปก PDF สำหรับกีฬาหลักแต่ละประเภท
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo = db();
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$yearId = active_year_id($pdo);
$messages = []; $errors = [];

// สร้างโฟลเดอร์สำหรับเก็บไฟล์หน้าปก
$uploadDir = __DIR__ . '/uploads/covers/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

// ตรวจสอบว่ามีตาราง sport_covers หรือยัง
try {
  $checkTable = $pdo->query("SHOW TABLES LIKE 'sport_covers'")->fetch();
  if (!$checkTable) {
    // สร้างตารางใหม่สำหรับเก็บหน้าปกแยกตามชื่อกีฬาหลัก
    $pdo->exec("
      CREATE TABLE sport_covers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sport_name VARCHAR(100) NOT NULL,
        cover_pdf VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_sport_name (sport_name)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  }
} catch (Exception $e) {
  // ถ้ามี error อาจจะมีตารางอยู่แล้ว
}

// จัดการการอัปโหลด
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_cover') {
  $sportName = trim($_POST['sport_name'] ?? '');
  
  if ($sportName === '') {
    $errors[] = '❌ ไม่พบชื่อกีฬา';
  } else {
    // ตรวจสอบว่ามีไฟล์อัปโหลดหรือไม่
    if (empty($_FILES['cover_file']) || $_FILES['cover_file']['error'] === UPLOAD_ERR_NO_FILE) {
      $errors[] = '❌ กรุณาเลือกไฟล์ JPG';
    } elseif ($_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = '❌ เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    } else {
      $file = $_FILES['cover_file'];
      $fileName = $file['name'];
      $fileTmp = $file['tmp_name'];
      $fileSize = $file['size'];
      $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
      
      // ตรวจสอบนามสกุลไฟล์ (รับเฉพาะรูปภาพ)
      $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      if (!in_array($fileExt, $allowedExts)) {
        $errors[] = '❌ กรุณาอัปโหลดไฟล์รูปภาพ (JPG, PNG, GIF, WEBP) เท่านั้น';
      } elseif ($fileSize > 10 * 1024 * 1024) { // จำกัดที่ 10MB
        $errors[] = '❌ ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 10MB)';
      } else {
        // สร้างชื่อไฟล์ใหม่ (ใช้ MD5 hash เพื่อหลีกเลี่ยงปัญหาอักษรไทย)
        $safeSportName = md5($sportName);
        $newFileName = 'cover_' . $safeSportName . '_' . time() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;
        
        // ลบไฟล์เก่าถ้ามี
        $oldFileStmt = $pdo->prepare("SELECT cover_pdf FROM sport_covers WHERE sport_name = ?");
        $oldFileStmt->execute([$sportName]);
        $oldFile = $oldFileStmt->fetchColumn();
        
        if ($oldFile && file_exists($uploadDir . $oldFile)) {
          unlink($uploadDir . $oldFile);
        }
        
        // อัปโหลดไฟล์ใหม่
        if (move_uploaded_file($fileTmp, $destination)) {
          // บันทึกข้อมูลในฐานข้อมูล (INSERT หรือ UPDATE)
          if ($oldFile) {
            // อัปเดตไฟล์เดิม
            $updateStmt = $pdo->prepare("UPDATE sport_covers SET cover_pdf = ? WHERE sport_name = ?");
            $updateStmt->execute([$newFileName, $sportName]);
          } else {
            // เพิ่มใหม่
            $insertStmt = $pdo->prepare("INSERT INTO sport_covers (sport_name, cover_pdf) VALUES (?, ?)");
            $insertStmt->execute([$sportName, $newFileName]);
          }
          
          // LOG
          log_activity('UPLOAD', 'sport_covers', null, 
            sprintf("อัปโหลดหน้าปกรูปภาพ | กีฬา: %s | ไฟล์: %s", $sportName, $newFileName));
          
          $messages[] = '✅ อัปโหลดหน้าปกสำหรับ ' . e($sportName) . ' เรียบร้อยแล้ว';
        } else {
          $errors[] = '❌ ไม่สามารถบันทึกไฟล์ได้';
        }
      }
    }
  }
}

// จัดการการลบหน้าปก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_cover') {
  $sportName = trim($_POST['sport_name'] ?? '');
  
  if ($sportName === '') {
    $errors[] = '❌ ไม่พบชื่อกีฬา';
  } else {
    $coverStmt = $pdo->prepare("SELECT cover_pdf FROM sport_covers WHERE sport_name = ?");
    $coverStmt->execute([$sportName]);
    $oldFile = $coverStmt->fetchColumn();
    
    // ลบไฟล์
    if ($oldFile && file_exists($uploadDir . $oldFile)) {
      unlink($uploadDir . $oldFile);
    }
    
    // ลบข้อมูลจากฐานข้อมูล
    $deleteStmt = $pdo->prepare("DELETE FROM sport_covers WHERE sport_name = ?");
    $deleteStmt->execute([$sportName]);
    
    // LOG
    log_activity('DELETE', 'sport_covers', null, 
      sprintf("ลบหน้าปกรูปภาพ | กีฬา: %s | ไฟล์เก่า: %s", $sportName, $oldFile ?? '-'));
    
    $messages[] = '✅ ลบหน้าปกสำหรับ ' . e($sportName) . ' เรียบร้อยแล้ว';
  }
}

// โหลดรายการกีฬาหลักทั้งหมด (ดึงชื่อกีฬาหลักจาก sports แทน sport_categories)
// โดยใช้ SUBSTRING_INDEX เพื่อตัดเอาคำแรกของชื่อกีฬา (เช่น "ฟุตบอล ประถม" -> "ฟุตบอล")
$categoriesStmt = $pdo->prepare("
  SELECT DISTINCT 
    SUBSTRING_INDEX(s.name, ' ', 1) AS main_sport_name
  FROM sports s
  WHERE s.year_id = :y AND s.is_active = 1
  ORDER BY main_sport_name
");
$categoriesStmt->execute([':y' => $yearId]);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลหน้าปกที่มีอยู่แล้ว
$coverStmt = $pdo->query("SELECT sport_name, cover_pdf FROM sport_covers");
$covers = [];
while ($row = $coverStmt->fetch(PDO::FETCH_ASSOC)) {
  $covers[$row['sport_name']] = $row['cover_pdf'];
}

$pageTitle = 'อัปโหลดหน้าปกกีฬา';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
  body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
  }
  .page-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
  }
  .category-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s;
  }
  .category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  }
  .category-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 1rem;
  }
  .upload-form {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }
  .file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
  }
  .file-input-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
  }
  .file-input-label {
    background: #667eea;
    color: white;
    padding: 0.5rem 1.25rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-block;
    font-weight: 500;
  }
  .file-input-label:hover {
    background: #5568d3;
  }
  .file-name {
    color: #666;
    font-style: italic;
  }
  .btn-upload {
    background: #28a745;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;
  }
  .btn-upload:hover {
    background: #218838;
    transform: translateY(-1px);
  }
  .btn-delete {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;
  }
  .btn-delete:hover {
    background: #c82333;
    transform: translateY(-1px);
  }
  .current-cover {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border-left: 4px solid #28a745;
    margin-bottom: 1rem;
  }
  .no-cover {
    background: #fff3cd;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border-left: 4px solid #ffc107;
    margin-bottom: 1rem;
  }
  .container-main {
    background: white;
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  }
</style>

<main class="container py-4">
  <div class="container-main">
    <!-- Page Header -->
    <div class="page-header">
      <h3 class="mb-1">📄 อัปโหลดหน้าปกกีฬา</h3>
      <p class="mb-0 opacity-75">จัดการไฟล์รูปภาพหน้าปกสำหรับกีฬาหลักแต่ละประเภท</p>
    </div>

    <!-- Alerts -->
    <?php if ($messages): ?>
      <div class="alert alert-success border-0 shadow-sm">
        <?php echo implode('<br>', array_map('e', $messages)); ?>
      </div>
    <?php endif; ?>
    
    <?php if ($errors): ?>
      <div class="alert alert-danger border-0 shadow-sm">
        <?php echo implode('<br>', array_map('e', $errors)); ?>
      </div>
    <?php endif; ?>

    <!-- รายการกีฬาหลัก -->
    <?php if (empty($categories)): ?>
      <div class="alert alert-info">
        ⚠️ ยังไม่มีกีฬาในระบบ
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($categories as $cat): ?>
          <?php 
            $sportName = $cat['main_sport_name'];
            $coverPdf = $covers[$sportName] ?? null;
            $safeSportId = md5($sportName); // ใช้ MD5 เพื่อให้แน่ใจว่า ID ไม่ซ้ำกัน
          ?>
          <div class="col-12 col-lg-6">
            <div class="category-card">
              <div class="category-name">
                🏅 <?php echo e($sportName); ?>
              </div>

              <?php if (!empty($coverPdf)): ?>
                <div class="current-cover">
                  <strong>✅ มีหน้าปกแล้ว:</strong> 
                  <a href="<?php echo BASE_URL; ?>/uploads/covers/<?php echo e($coverPdf); ?>" 
                     target="_blank" 
                     class="text-decoration-none">
                    📄 <?php echo e($coverPdf); ?>
                  </a>
                </div>
                
                <!-- ฟอร์มลบ -->
                <form method="POST" onsubmit="return confirm('ต้องการลบหน้าปกนี้หรือไม่?');" style="margin-bottom: 1rem;">
                  <input type="hidden" name="action" value="delete_cover">
                  <input type="hidden" name="sport_name" value="<?php echo e($sportName); ?>">
                  <button type="submit" class="btn-delete">
                    🗑️ ลบหน้าปก
                  </button>
                </form>
              <?php else: ?>
                <div class="no-cover">
                  ⚠️ ยังไม่มีหน้าปก
                </div>
              <?php endif; ?>

              <!-- ฟอร์มอัปโหลด -->
              <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="upload_cover">
                <input type="hidden" name="sport_name" value="<?php echo e($sportName); ?>">
                
                <div class="file-input-wrapper">
                  <input type="file" 
                         name="cover_file" 
                         id="file_<?php echo e($safeSportId); ?>" 
                         accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
                         onchange="updateFileName('<?php echo e($safeSportId); ?>')">
                  <label for="file_<?php echo e($safeSportId); ?>" class="file-input-label">
                    📁 เลือกรูปภาพ
                  </label>
                </div>
                
                <span class="file-name" id="filename_<?php echo e($safeSportId); ?>">
                  ยังไม่ได้เลือกไฟล์
                </span>
                
                <button type="submit" class="btn-upload">
                  ⬆️ อัปโหลด
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- คำแนะนำ -->
    <div class="alert alert-info border-0 shadow-sm mt-4">
      <strong>📌 คำแนะนำ:</strong>
      <ul class="mb-0 mt-2">
        <li>อัปโหลดไฟล์ <strong>รูปภาพเท่านั้น</strong> (JPG, PNG, GIF, WEBP)</li>
        <li>รูปภาพจะถูกสเกลให้เต็มหน้าอัตโนมัติเมื่อ Export รวมเล่ม</li>
        <li>ขนาดไฟล์สูงสุด 10MB</li>
        <li>ไฟล์จะถูกเก็บไว้ที่ <code>/public/uploads/covers/</code></li>
        <li>เมื่อเลือกไฟล์แล้ว กดปุ่ม "อัปโหลด" เพื่อบันทึก</li>
        <li>หากมีไฟล์เก่าอยู่แล้ว จะถูกแทนที่ด้วยไฟล์ใหม่</li>
        <li><strong>รายการกีฬา</strong>จะแสดงเฉพาะชื่อกีฬาหลัก (เช่น "ฟุตบอล" ไม่แยกประถม/มัธยม)</li>
      </ul>
    </div>
  </div>
</main>

<script>
function updateFileName(sportId) {
  const fileInput = document.getElementById('file_' + sportId);
  const fileNameSpan = document.getElementById('filename_' + sportId);
  
  if (fileInput && fileInput.files.length > 0) {
    fileNameSpan.textContent = '📄 ' + fileInput.files[0].name;
    fileNameSpan.style.color = '#28a745';
    fileNameSpan.style.fontStyle = 'normal';
  } else {
    fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
    fileNameSpan.style.color = '#666';
    fileNameSpan.style.fontStyle = 'italic';
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
