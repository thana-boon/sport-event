<?php
// athletics_time_import.php — Import เวลาแข่งขันจาก CSV

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

// ถ้ามีการ upload ไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์'];
        header('Location: ' . BASE_URL . '/reports_athletics.php');
        exit;
    }
    
    // ตรวจสอบนามสกุลไฟล์
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'กรุณาอัพโหลดไฟล์ CSV เท่านั้น'];
        header('Location: ' . BASE_URL . '/reports_athletics.php');
        exit;
    }
    
    // อ่านไฟล์ CSV
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ไม่สามารถเปิดไฟล์ได้'];
        header('Location: ' . BASE_URL . '/reports_athletics.php');
        exit;
    }
    
    // ข้าม header
    fgetcsv($handle);
    
    $updated = 0;
    $skipped = 0;
    
    $updateStmt = $pdo->prepare("
        UPDATE athletics_events 
        SET competition_time = :time 
        WHERE id = :id AND year_id = :y
    ");
    
    while (($data = fgetcsv($handle)) !== false) {
        // ข้ามบรรทัดที่ไม่มีข้อมูล
        if (count($data) < 7) {
            $skipped++;
            continue;
        }
        
        $id = (int)($data[0] ?? 0);
        $time = trim($data[6] ?? ''); // คอลัมน์เวลาแข่งขัน
        
        if ($id <= 0) {
            $skipped++;
            continue;
        }
        
        // ปรับรูปแบบเวลาให้เป็น HH:MM:SS สำหรับ MySQL
        $normalizedTime = null;
        if ($time !== '') {
            // รองรับรูปแบบ: H:MM, HH:MM, H:MM:SS, HH:MM:SS
            if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $matches)) {
                $hour = (int)$matches[1];
                $minute = (int)$matches[2];
                $second = isset($matches[3]) ? (int)$matches[3] : 0;
                
                // ตรวจสอบความถูกต้อง
                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 && $second >= 0 && $second <= 59) {
                    $normalizedTime = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
                } else {
                    $skipped++;
                    continue;
                }
            } else {
                // รูปแบบไม่ถูกต้อง
                $skipped++;
                continue;
            }
        }
        
        try {
            $updateStmt->execute([
                ':id' => $id,
                ':time' => $normalizedTime,
                ':y' => $yearId
            ]);
            $updated++;
        } catch (PDOException $e) {
            $skipped++;
        }
    }
    
    fclose($handle);
    
    $_SESSION['flash'] = [
        'type' => 'success', 
        'msg' => "นำเข้าข้อมูลสำเร็จ: อัพเดต $updated รายการ" . ($skipped > 0 ? ", ข้าม $skipped รายการ" : "")
    ];
    header('Location: ' . BASE_URL . '/reports_athletics.php');
    exit;
}

// ถ้าไม่ได้ POST redirect กลับ
header('Location: ' . BASE_URL . '/reports_athletics.php');
exit;
