<?php
// reports_calendar_export.php — Generate PDF ปฏิทินกีฬา

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo = db();
$yearId = active_year_id($pdo);
$yearName = function_exists('active_year_name') ? active_year_name($pdo) : 'ปีการศึกษา';

// โหลดข้อมูล Logo
$meta = ['logo_path' => null];
$st = $pdo->prepare("SELECT logo_path FROM competition_meta WHERE year_id=:y LIMIT 1");
$st->execute([':y' => $yearId]);
if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
  $meta = $r;
}

// รับค่าจากฟอร์ม
$selectedSports = $_POST['sports'] ?? [];
$selectedMonths = $_POST['months'] ?? [];

if (empty($selectedSports) || empty($selectedMonths)) {
  die('กรุณาเลือกกีฬาและเดือน');
}

// ฟังก์ชันสร้างปฏิทิน
function generateCalendar($year, $month, $events) {
  $firstDay = mktime(0, 0, 0, $month, 1, $year);
  $daysInMonth = date('t', $firstDay);
  $dayOfWeek = date('w', $firstDay);
  
  $calendar = [];
  $week = array_fill(0, 7, null);
  
  // เติมวันว่างก่อนวันที่ 1
  for ($i = 0; $i < $dayOfWeek; $i++) {
    $week[$i] = null;
  }
  
  // เติมวันที่
  for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $week[$dayOfWeek] = [
      'day' => $day,
      'events' => $events[$date] ?? []
    ];
    
    $dayOfWeek++;
    if ($dayOfWeek == 7) {
      $calendar[] = $week;
      $week = array_fill(0, 7, null);
      $dayOfWeek = 0;
    }
  }
  
  // เติมสัปดาห์สุดท้าย
  if ($dayOfWeek > 0) {
    $calendar[] = $week;
  }
  
  return $calendar;
}

// ดึงข้อมูลการแข่งขันทั้งหมดที่เลือก (รอบคัดเลือก)
$sqlMatches = "
  SELECT 
    mp.match_date AS event_date,
    s.name AS sport_name,
    mp.match_time AS event_time,
    'รอบคัดเลือก' AS round_type
  FROM match_pairs mp
  INNER JOIN sports s ON s.id = mp.sport_id
  WHERE mp.year_id = ?
    AND mp.match_date IS NOT NULL
    AND (
      " . implode(' OR ', array_map(function($sport) {
        return "s.name LIKE CONCAT(?, ' %') OR s.name = ?";
      }, $selectedSports)) . "
    )
  ORDER BY mp.match_date, mp.match_time
";

$params = [$yearId];
foreach ($selectedSports as $sport) {
  $params[] = $sport;
  $params[] = $sport;
}

$stMatches = $pdo->prepare($sqlMatches);
$stMatches->execute($params);
$matchesRounds = $stMatches->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรอบชิง (final_date, final_time)
$sqlFinals = "
  SELECT DISTINCT
    mp.final_date AS event_date,
    s.name AS sport_name,
    mp.final_time AS event_time,
    'รอบชิง' AS round_type
  FROM match_pairs mp
  INNER JOIN sports s ON s.id = mp.sport_id
  WHERE mp.year_id = ?
    AND mp.final_date IS NOT NULL
    AND (
      " . implode(' OR ', array_map(function($sport) {
        return "s.name LIKE CONCAT(?, ' %') OR s.name = ?";
      }, $selectedSports)) . "
    )
  ORDER BY mp.final_date, mp.final_time
";

$stFinals = $pdo->prepare($sqlFinals);
$stFinals->execute($params);
$finalsRounds = $stFinals->fetchAll(PDO::FETCH_ASSOC);

// รวมข้อมูลทั้งหมด
$allMatches = array_merge($matchesRounds, $finalsRounds);

// จัดกลุ่มตามวันที่และกีฬา (ไม่ให้ซ้ำ)
$eventsByDate = [];
foreach ($allMatches as $match) {
  $date = $match['event_date'];
  $sportName = $match['sport_name'];
  $roundType = $match['round_type'] ?? '';
  
  // สร้าง key เพื่อตรวจสอบการซ้ำ: วันที่ + ชื่อกีฬา + รอบ
  $uniqueKey = $date . '|' . $sportName . '|' . $roundType;
  
  // ถ้ายังไม่มีในวันนั้น ให้เพิ่มเข้าไป
  if (!isset($eventsByDate[$uniqueKey])) {
    if (!isset($eventsByDate[$date])) {
      $eventsByDate[$date] = [];
    }
    
    $time = !empty($match['event_time']) ? substr($match['event_time'], 0, 5) : '';
    
    // เพิ่ม (คัดเลือก) หรือ (ชิง) ต่อหลังเวลา
    $roundLabel = ($roundType === 'รอบชิง') ? '(ชิง)' : '(คัดเลือก)';
    $timeWithLabel = $time ? $time . ' ' . $roundLabel : $roundLabel;
    
    $eventsByDate[$date][] = [
      'sport' => $sportName,
      'time' => $timeWithLabel
    ];
    
    // เก็บ key เพื่อไม่ให้เพิ่มซ้ำ
    $eventsByDate[$uniqueKey] = true;
  }
}

// Font paths
$basePath = rtrim(str_replace('\\', '/', __DIR__), '/');
$fontPath = $basePath . '/assets/fonts/THSarabunNew.ttf';
$fontBoldPath = $basePath . '/assets/fonts/THSarabunNew-Bold.ttf';

$fontBase64 = '';
$fontBoldBase64 = '';

if (file_exists($fontPath)) {
  $fontBase64 = base64_encode(file_get_contents($fontPath));
}

if (file_exists($fontBoldPath)) {
  $fontBoldBase64 = base64_encode(file_get_contents($fontBoldPath));
}

// โหลด Logo (ปิดการใช้งาน)
// $logoBase64 = '';
// if (!empty($meta['logo_path'])) {
//   $logoFile = __DIR__ . '/uploads/logo/' . basename($meta['logo_path']);
//   if (file_exists($logoFile)) {
//     $logoBase64 = base64_encode(file_get_contents($logoFile));
//   }
// }

ob_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ปฏิทินกีฬา</title>
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
  
  @page {
    margin: 6mm;
    size: A4 landscape;
  }
  
  body {
    font-family: 'THSarabunNew', 'DejaVu Sans', sans-serif;
    font-size: 14pt;
    margin: 0;
    padding: 0;
  }
  
  .page-break {
    page-break-after: always;
  }
  
  .calendar-header {
    text-align: center;
    margin-bottom: 2px;
  }
  
  .calendar-title {
    font-size: 15pt;
    font-weight: bold;
    margin-bottom: 1px;
  }
  
  .calendar-subtitle {
    font-size: 13pt;
    color: #666;
    margin-bottom: 2px;
  }
  
  .calendar-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    table-layout: fixed;
  }
  
  .calendar-table th {
    background: #4a5568;
    color: white;
    padding: 3px;
    text-align: center;
    font-size: 13pt;
    font-weight: bold;
    border: 1px solid #2d3748;
  }
  
  .calendar-table th.sunday {
    background: #e53e3e;
  }
  
  .calendar-table td {
    border: 1px solid #cbd5e0;
    min-height: 50px;
    height: auto;
    vertical-align: top;
    padding: 2px;
    position: relative;
    font-size: 10pt;
    word-wrap: break-word;
  }
  
  .calendar-table td.empty {
    background: #f7fafc;
  }
  
  .day-number {
    font-size: 13pt;
    font-weight: bold;
    color: #2d3748;
    margin-bottom: 0px;
    display: block;
  }
  
  .sunday .day-number {
    color: #e53e3e;
  }
  
  .event-item {
    font-size: 9pt;
    background: #e3f2fd;
    padding: 1px 2px;
    margin-bottom: 1px;
    border-radius: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.1;
    display: block;
    max-width: 100%;
    box-sizing: border-box;
  }
  
  .event-time {
    font-size: 8pt;
    color: #1565c0;
    font-weight: bold;
  }
  
  .legend {
    display: none;
  }
  
  .legend-title {
    font-weight: bold;
    margin-bottom: 5px;
  }
  
  .legend-item {
    display: inline-block;
    margin-right: 15px;
    padding: 3px 8px;
    background: #edf2f7;
    border-radius: 3px;
    border-left: 3px solid #4299e1;
  }
</style>
</head>
<body>

<?php
$thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

$monthCount = 0;
foreach ($selectedMonths as $monthKey):
  list($year, $month) = explode('-', $monthKey);
  $year = (int)$year;
  $month = (int)$month;
  
  $calendar = generateCalendar($year, $month, $eventsByDate);
  $monthName = $thaiMonths[$month];
  $yearBE = $year + 543;
  
  if ($monthCount > 0) echo '<div class="page-break"></div>';
  $monthCount++;
?>

<div class="calendar-header">
  <div style="text-align: center; font-size: 13pt; margin-bottom: 1px;">กีฬาราชพฤกษ์เกมส์</div>
  <div class="calendar-title">ปฏิทินกีฬา - <?= $monthName ?> <?= $yearBE ?></div>
  <div class="calendar-subtitle"><?= htmlspecialchars($yearName) ?></div>
</div>

<table class="calendar-table">
  <thead>
    <tr>
      <?php foreach ($thaiDays as $i => $day): ?>
        <th<?= $i == 0 ? ' class="sunday"' : '' ?>><?= $day ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($calendar as $week): ?>
      <tr>
        <?php for ($i = 0; $i < 7; $i++): ?>
          <?php if ($week[$i] === null): ?>
            <td class="empty"></td>
          <?php else: ?>
            <td<?= $i == 0 ? ' class="sunday"' : '' ?>>
              <div class="day-number"><?= $week[$i]['day'] ?></div>
              <?php foreach ($week[$i]['events'] as $event): ?>
                <div class="event-item">
                  <?= htmlspecialchars($event['sport']) ?>
                  <?php if ($event['time']): ?>
                    <span class="event-time"><?= htmlspecialchars($event['time']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </td>
          <?php endif; ?>
        <?php endfor; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="legend">
  <div class="legend-title">🏅 กีฬาที่แสดง:</div>
  <?php foreach ($selectedSports as $sport): ?>
    <span class="legend-item"><?= htmlspecialchars($sport) ?></span>
  <?php endforeach; ?>
</div>

<?php endforeach; ?>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'THSarabunNew');
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'ปฏิทินกีฬา_' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
