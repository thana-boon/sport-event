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

// ดึงข้อมูลรายการกรีฑา
$sql = "
  SELECT
    ae.event_code,
    s.name AS sport_name,
    s.gender,
    s.participant_type,
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

// คำนวณเวลาการแข่งขัน
function calculateSchedule(array $events): array {
  $schedule = [];
  $startMorning = strtotime('08:25');
  $endMorning = strtotime('12:30');
  $startAfternoon = strtotime('12:50');
  $intervalMinutes = 5;
  
  $currentTime = $startMorning;
  $isAfternoon = false;
  
  foreach ($events as $event) {
    // ถ้าเกินเวลาเช้า → ข้ามไปบ่าย
    if (!$isAfternoon && $currentTime > $endMorning) {
      $currentTime = $startAfternoon;
      $isAfternoon = true;
    }
    
    $schedule[] = array_merge($event, [
      'time' => date('H:i', $currentTime),
      'session' => $isAfternoon ? 'บ่าย' : 'เช้า'
    ]);
    
    $currentTime += ($intervalMinutes * 60);
  }
  
  return $schedule;
}

$schedule = calculateSchedule($events);

// ถ้าต้องการดาวน์โหลด PDF
if (isset($_GET['download'])) {
  $options = new Options();
  $options->set('isHtml5ParserEnabled', true);
  $options->set('isRemoteEnabled', true);
  $options->set('defaultFont', 'sarabun');
  
  $dompdf = new Dompdf($options);
  
  // โหลด logo จากตำแหน่งที่ถูกต้อง
  $logoPath = __DIR__ . '/uploads/logo/logo_year_' . $yearId . '_*.png';
  $logoFiles = glob($logoPath);
  $logoData = '';
  
  if (!empty($logoFiles) && file_exists($logoFiles[0])) {
    $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFiles[0]));
  } else {
    // ลองหาตำแหน่งอื่น
    $altPaths = [
      __DIR__ . '/../public/uploads/logo/logo_year_' . $yearId . '_*.png',
      __DIR__ . '/uploads/logo/*.png',
      __DIR__ . '/../assets/logo.png'
    ];
    foreach ($altPaths as $pattern) {
      $files = glob($pattern);
      if (!empty($files) && file_exists($files[0])) {
        $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($files[0]));
        break;
      }
    }
  }
  
  // สร้าง HTML
  ob_start();
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <style>
      @font-face {
        font-family: 'sarabun';
        font-style: normal;
        font-weight: normal;
        src: url('<?= __DIR__ ?>/../fonts/THSarabunNew.ttf') format('truetype');
      }
      @font-face {
        font-family: 'sarabun';
        font-style: normal;
        font-weight: bold;
        src: url('<?= __DIR__ ?>/../fonts/THSarabunNew-Bold.ttf') format('truetype');
      }
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        font-family: 'sarabun', sans-serif;
        font-size: 14pt;
        margin: 0;
        padding: 15px;
        line-height: 1.2;
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
        font-size: 14pt;
        font-weight: bold;
        margin: 2px 0;
        line-height: 1.1;
      }
      .subtitle {
        font-size: 12pt;
        margin: 2px 0;
        line-height: 1.1;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
      }
      th, td {
        border: 1px solid #000;
        padding: 3px 5px;
        text-align: center;
        font-size: 12pt;
        line-height: 1.1;
      }
      th {
        background-color: #ddd;
        font-weight: bold;
        font-size: 12pt;
      }
      .morning {
        background-color: #fff3cd;
      }
      .afternoon {
        background-color: #d1ecf1;
      }
      .event-code {
        font-weight: bold;
        font-size: 13pt;
      }
      .session-row {
        font-weight: bold;
        font-size: 12pt;
        padding: 4px;
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
        <?php 
        $currentSession = '';
        foreach ($schedule as $item): 
          // แสดง session divider
          if ($currentSession !== $item['session']) {
            $currentSession = $item['session'];
            $bgClass = $currentSession === 'เช้า' ? 'morning' : 'afternoon';
            echo '<tr class="' . $bgClass . '">';
            echo '<td colspan="5" class="session-row">';
            echo 'ช่วง' . htmlspecialchars($currentSession, ENT_QUOTES, 'UTF-8');
            echo '</td></tr>';
          }
        ?>
        <tr>
          <td class="event-code"><?= htmlspecialchars($item['event_code'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($item['time'], ENT_QUOTES, 'UTF-8') ?> น.</td>
          <td style="text-align: left; padding-left: 8px;"><?= htmlspecialchars($item['sport_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
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
  
  $dompdf->loadHtml($html);
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
  .session-badge {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-weight: 600;
    display: inline-block;
    margin: 1rem 0;
  }
  .morning-badge {
    background: #fff3cd;
    color: #856404;
  }
  .afternoon-badge {
    background: #d1ecf1;
    color: #0c5460;
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
            <?php 
            $currentSession = '';
            foreach ($schedule as $item): 
              if ($currentSession !== $item['session']) {
                $currentSession = $item['session'];
                $badgeClass = $currentSession === 'เช้า' ? 'morning-badge' : 'afternoon-badge';
            ?>
            <tr>
              <td colspan="5" class="text-center">
                <span class="session-badge <?= $badgeClass ?>">
                  ⏰ ช่วง<?= htmlspecialchars($currentSession, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
            </tr>
            <?php } ?>
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