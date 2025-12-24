<?php
// reports_athletics_schedule.php — ตารางการแข่งขันกรีฑา (เรียงตามรหัส + คำนวณเวลาอัตโนมัติ)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo = db();
$yearId = active_year_id($pdo);
$yearName = function_exists('active_year_name') ? active_year_name($pdo) : 'ปีการศึกษา';

// คำนวณครั้งที่จาก พ.ศ.
$currentYearBE = (int)date('Y') + 543; // พ.ศ.ปัจจุบัน
$gameNumber = $currentYearBE - 2552; // ครั้งที่

// ฟังก์ชันแปลงเพศ
function formatGender($gender) {
  if ($gender === 'ช') return 'ชาย';
  if ($gender === 'ญ') return 'หญิง';
  return $gender; // ผสม หรืออื่นๆ คงเดิม
}

// ✅ เพิ่ม team_size และ participant_type ใน SELECT
$sql = "
  SELECT
    ae.event_code,
    s.name AS sport_name,
    s.gender,
    s.participant_type,
    s.team_size,
    s.grade_levels
  FROM athletics_events ae
  LEFT JOIN sports s ON s.id = ae.sport_id
  WHERE ae.year_id = :y
  ORDER BY 
    CASE WHEN ae.event_code REGEXP '^[0-9]+$' THEN CAST(ae.event_code AS UNSIGNED) ELSE 999999 END,
    ae.event_code
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':y' => $yearId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ปรับเวลาให้มีช่วงพักเที่ยง (12:20-12:50)
 * @param string $time เวลาในรูปแบบ HH:MM
 * @return string เวลาที่ปรับแล้ว
 */
function adjustLunchBreak($time) {
    // แปลงเวลาเป็นนาที (จากเที่ยงคืน)
    list($h, $m) = explode(':', $time);
    $totalMinutes = ((int)$h * 60) + (int)$m;
    
    // ช่วงเช้าสิ้นสุด: 12:20 = 740 นาที
    $morningEnd = 12 * 60 + 20; // 740
    // ช่วงบ่ายเริ่ม: 12:50 = 770 นาที
    $afternoonStart = 12 * 60 + 50; // 770
    
    // ✅ ถ้าเวลาอยู่ระหว่าง 12:20 - 12:50 → ปรับเป็น 12:50
    if ($totalMinutes > $morningEnd && $totalMinutes < $afternoonStart) {
        $totalMinutes = $afternoonStart;
        $h = floor($totalMinutes / 60);
        $m = $totalMinutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }
    
    return $time;
}

// ========================================
// ✅ สร้างตารางแข่งขัน (เวลาเริ่มต้น 08:25)
// ========================================

$schedule = [];
$startTime = '08:25';

// คำนวณเวลา
$currentTime = $startTime;

foreach ($events as $event) {
    // ✅ ใช้ participant_type แทน team_size (ป้องกัน undefined)
    $participantType = $event['participant_type'] ?? 'เดี่ยว';
    $duration = ($participantType === 'ทีม') ? 5 : 5; // ทีม 15 นาที, เดี่ยว 5 นาที
    
    // ✅ ตรวจสอบและปรับเวลาก่อนเพิ่มรายการ
    $currentTime = adjustLunchBreak($currentTime);
    
    $schedule[] = array_merge($event, [
        'time' => $currentTime,
        'duration' => $duration
    ]);
    
    // ✅ คำนวณเวลาถัดไป
    list($h, $m) = explode(':', $currentTime);
    $totalMinutes = ((int)$h * 60) + (int)$m + $duration;
    $h = floor($totalMinutes / 60);
    $m = $totalMinutes % 60;
    $nextTime = sprintf('%02d:%02d', $h, $m);
    
    // ✅ ปรับเวลาอีกครั้งหลังจากคำนวณแล้ว (กรณีข้ามช่วงพัก)
    $currentTime = adjustLunchBreak($nextTime);
}

// ถ้าต้องการดาวน์โหลด PDF
if (isset($_GET['download'])) {
  // ✅ กำหนด path ที่ถูกต้อง
  $basePath = rtrim(str_replace('\\', '/', __DIR__), '/');
  $fontPath = $basePath . '/assets/fonts/THSarabunNew.ttf';
  $fontBoldPath = $basePath . '/assets/fonts/THSarabunNew-Bold.ttf';
  
  // ✅ แปลง font เป็น base64
  $fontBase64 = '';
  $fontBoldBase64 = '';
  
  if (file_exists($fontPath)) {
    $fontBase64 = base64_encode(file_get_contents($fontPath));
  }
  
  if (file_exists($fontBoldPath)) {
    $fontBoldBase64 = base64_encode(file_get_contents($fontBoldPath));
  }
  
  $options = new Options();
  $options->set('isHtml5ParserEnabled', true);
  $options->set('isRemoteEnabled', true);
  $options->set('defaultFont', 'THSarabunNew');
  $options->set('isFontSubsettingEnabled', true);
  
  $dompdf = new Dompdf($options);
  
  // โหลด logo
  $logoPath = __DIR__ . '/uploads/logo/logo_year_' . $yearId . '_*.png';
  $logoFiles = glob($logoPath);
  $logoData = '';
  
  if (!empty($logoFiles) && file_exists($logoFiles[0])) {
    $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFiles[0]));
  }
  
  // สร้าง HTML
  ob_start();
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <style>
      <?php if ($fontBase64): ?>
      @font-face {
        font-family: 'THSarabunNew';
        font-style: normal;
        font-weight: normal;
        src: url(data:font/truetype;charset=utf-8;base64,<?= $fontBase64 ?>) format('truetype');
      }
      <?php endif; ?>
      
      <?php if ($fontBoldBase64): ?>
      @font-face {
        font-family: 'THSarabunNew';
        font-style: normal;
        font-weight: bold;
        src: url(data:font/truetype;charset=utf-8;base64,<?= $fontBoldBase64 ?>) format('truetype');
      }
      <?php endif; ?>
      
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        font-family: 'THSarabunNew', 'DejaVu Sans', sans-serif;
        font-size: 16pt;
        margin: 0;
        padding: 15px;
        line-height: 1.3;
      }
      .header {
        text-align: center;
        margin-bottom: 12px;
      }
      .logo {
        max-width: 80px;
        height: auto;
        display: block;
        margin: 0 auto 5px auto;
      }
      .title {
        font-size: 18pt;
        font-weight: bold;
        margin: 3px 0;
        line-height: 1.2;
      }
      .subtitle {
        font-size: 16pt;
        margin: 3px 0;
        line-height: 1.2;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
      }
      th, td {
        border: 1px solid #000;
        padding: 4px 6px;
        text-align: center;
        font-size: 15pt;
        line-height: 1.2;
      }
      th {
        background-color: #ddd;
        font-weight: bold;
        font-size: 16pt;
      }
      .event-code {
        font-weight: bold;
        font-size: 16pt;
      }
      .text-left {
        text-align: left;
        padding-left: 8px;
      }
    </style>
  </head>
  <body>
    <div class="header">
      <?php if ($logoData): ?>
        <img src="<?= $logoData ?>" class="logo" alt="Logo">
      <?php endif; ?>
      <div class="title">ตารางการแข่งขันกรีฑา โรงเรียนสุคนธีรวิทย์</div>
      <div class="subtitle">กีฬาราชพฤกษ์เกมส์ ครั้งที่ <?= $gameNumber ?> <?= htmlspecialchars($yearName, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    
    <table>
      <thead>
        <tr>
          <th style="width: 10%;">รหัส</th>
          <th style="width: 13%;">เวลา</th>
          <th style="width: 40%;">รายการแข่งขัน</th>
          <th style="width: 20%;">ระดับชั้น</th>
          <th style="width: 17%;">เพศ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($schedule as $item): ?>
        <tr>
          <td class="event-code"><?= htmlspecialchars($item['event_code'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($item['time'], ENT_QUOTES, 'UTF-8') ?> น.</td>
          <td class="text-left"><?= htmlspecialchars($item['sport_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($item['grade_levels'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars(formatGender($item['gender'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($schedule)): ?>
        <tr>
          <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
            ยังไม่มีรายการแข่งขัน
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </body>
  </html>
  <?php
  $html = ob_get_clean();
  
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  
  $filename = 'ตารางการแข่งขันกรีฑา_' . $yearName . '.pdf';
  $dompdf->stream($filename, ['Attachment' => true]);
  exit;
}

// แสดงหน้า Preview
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
  .schedule-preview {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
</style>

<main class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1">📋 ตารางการแข่งขันกรีฑา</h4>
      <p class="text-muted mb-0 small">กีฬาราชพฤกษ์เกมส์ ครั้งที่ <?= $gameNumber ?> <?= htmlspecialchars($yearName, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/reports_athletics.php" class="btn btn-outline-secondary">
        ← กลับ
      </a>
      <a href="?download=1" class="btn btn-success">
        📥 ดาวน์โหลด PDF
      </a>
    </div>
  </div>
  
  <div class="card schedule-preview">
    <div class="card-body">
      <div class="text-center mb-4">
        <h5 class="fw-bold">ตารางการแข่งขันกรีฑา โรงเรียนสุคนธีรวิทย์</h5>
        <p class="text-muted">กีฬาราชพฤกษ์เกมส์ ครั้งที่ <?= $gameNumber ?> <?= htmlspecialchars($yearName, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 10%;" class="text-center">รหัส</th>
              <th style="width: 12%;" class="text-center">เวลา</th>
              <th style="width: 40%;">รายการแข่งขัน</th>
              <th style="width: 20%;" class="text-center">ระดับชั้น</th>
              <th style="width: 18%;" class="text-center">เพศ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($schedule as $item): ?>
            <tr>
              <td class="text-center">
                <strong><?= htmlspecialchars($item['event_code'] ?: '-', ENT_QUOTES, 'UTF-8') ?></strong>
              </td>
              <td class="text-center"><?= htmlspecialchars($item['time'], ENT_QUOTES, 'UTF-8') ?> น.</td>
              <td><?= htmlspecialchars($item['sport_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= htmlspecialchars($item['grade_levels'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= htmlspecialchars(formatGender($item['gender'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($schedule)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-5">
                ยังไม่มีรายการแข่งขัน
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>