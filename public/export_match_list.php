<?php
// Export รายการแข่งขัน (แบบง่าย) - แสดงเฉพาะตารางว่าสีไหนเจอสีไหน

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = db();
$yearId = active_year_id($pdo);
$yearName = function_exists('active_year_name') ? active_year_name($pdo) : 'ปีการศึกษา';

if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

if (!$yearId) {
  die('ยังไม่ได้ตั้งปีการศึกษาให้ Active');
}

// ตรวจสอบว่ามีการเลือกกีฬาเฉพาะหรือไม่
$selectedSportName = isset($_GET['sport_name']) ? trim($_GET['sport_name']) : '';

// ดึงข้อมูลกีฬาที่มีคู่แข่งขัน (กรองตามชื่อกีฬาหลักถ้ามีการเลือก)
if ($selectedSportName !== '') {
  // Export ทุกรายการของกีฬาที่เลือก (เช่น "ว่ายน้ำ" จะได้ทุกรายการที่ชื่อขึ้นต้นด้วย "ว่ายน้ำ")
  $sqlSports = "
    SELECT DISTINCT s.id, s.name, s.gender, s.participant_type, s.grade_levels
    FROM sports s
    INNER JOIN match_pairs mp ON s.id = mp.sport_id
    WHERE mp.year_id = ? AND s.name LIKE CONCAT(?, '%')
    ORDER BY s.name, s.gender, s.grade_levels
  ";
  $stSports = $pdo->prepare($sqlSports);
  $stSports->execute([$yearId, $selectedSportName]);
} else {
  // Export ทั้งหมด
  $sqlSports = "
    SELECT DISTINCT s.id, s.name, s.gender, s.participant_type, s.grade_levels
    FROM sports s
    INNER JOIN match_pairs mp ON s.id = mp.sport_id
    WHERE mp.year_id = ?
    ORDER BY s.name, s.gender, s.grade_levels
  ";
  $stSports = $pdo->prepare($sqlSports);
  $stSports->execute([$yearId]);
}
$sports = $stSports->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันสีพื้นหลัง
function colorBg($color) {
  $map = [
    'เขียว' => '#d4edda',
    'ฟ้า'   => '#d1ecf1',
    'ชมพู'  => '#f8d7da',
    'ส้ม'   => '#fff3cd'
  ];
  return $map[$color] ?? '#ffffff';
}

ob_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายการแข่งขัน</title>
<style>
  @page { margin: 12mm; }
  @font-face {
    font-family: 'THSarabunNew';
    src: url('assets/fonts/THSarabunNew.ttf') format('truetype');
  }
  @font-face {
    font-family: 'THSarabunNew';
    src: url('assets/fonts/THSarabunNew-Bold.ttf') format('truetype');
    font-weight: bold;
  }
  
  body {
    font-family: 'THSarabunNew', 'DejaVu Sans', sans-serif;
    font-size: 14pt;
    line-height: 1.2;
    color: #333;
  }
  
  h2 {
    font-size: 16pt;
    font-weight: bold;
    margin: 10px 0 3px 0;
    padding: 0;
    color: #333;
    page-break-after: avoid;
  }
  
  .sport-info {
    font-size: 13pt;
    color: #666;
    margin-bottom: 5px;
    font-weight: normal;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    page-break-inside: avoid;
  }
  
  th {
    background: #e9ecef;
    border: 1px solid #adb5bd;
    padding: 5px 8px;
    text-align: center;
    font-weight: bold;
    font-size: 14pt;
    color: #495057;
  }
  
  td {
    border: 1px solid #dee2e6;
    padding: 6px 8px;
    text-align: center;
    font-size: 14pt;
  }
  
  .match-no {
    font-weight: bold;
    width: 70px;
    background: #f8f9fa;
    font-size: 14pt;
    color: #495057;
  }
  
  .match-cell {
    padding: 8px 10px;
  }
  
  .color-box {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 4px;
    margin: 0 4px;
    font-size: 14pt;
    border: 1px solid rgba(0,0,0,0.15);
  }
  
  .vs-text {
    font-weight: normal;
    color: #6c757d;
    margin: 0 6px;
    font-size: 13pt;
  }
  
  .no-matches {
    color: #999;
    font-style: italic;
    padding: 15px;
    text-align: center;
    font-size: 15pt;
  }
</style>
</head>
<body>

<?php if (empty($sports)): ?>
  <p class="no-matches">ยังไม่มีรายการแข่งขัน</p>
<?php else: ?>
  
  <?php foreach ($sports as $sport): ?>
    <?php
      // ดึงคู่แข่งขัน
      $qMatches = $pdo->prepare("
        SELECT match_no, side_a_color, side_b_color, match_date, match_time
        FROM match_pairs
        WHERE year_id = ? AND sport_id = ?
        ORDER BY round_no, match_no
      ");
      $qMatches->execute([$yearId, $sport['id']]);
      $matches = $qMatches->fetchAll(PDO::FETCH_ASSOC);
      
      if (empty($matches)) continue;
    ?>
    
    <h2><?= htmlspecialchars($sport['name']) ?></h2>
    <div class="sport-info">
      <?= htmlspecialchars($sport['gender']) ?> • 
      <?= htmlspecialchars($sport['participant_type']) ?> • 
      <?= htmlspecialchars($sport['grade_levels']) ?>
    </div>
    
    <table>
      <thead>
        <tr>
          <th class="match-no">คู่ที่</th>
          <th style="width: 150px;">วันที่-เวลา</th>
          <th>คู่แข่งขัน</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matches as $m): ?>
          <tr>
            <td class="match-no"><?= htmlspecialchars($m['match_no']) ?></td>
            <td style="text-align: center; font-size: 13pt;">
              <?php
                $dateTime = '';
                if (!empty($m['match_date'])) {
                  // แปลงวันที่เป็นภาษาไทย
                  $date = new DateTime($m['match_date']);
                  $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                  $day = $date->format('j');
                  $month = $thaiMonths[(int)$date->format('n')];
                  $year = $date->format('Y') + 543;
                  $dateTime = "{$day} {$month} {$year}";
                  
                  // เพิ่มเวลาถ้ามี
                  if (!empty($m['match_time'])) {
                    $time = substr($m['match_time'], 0, 5); // HH:MM
                    $dateTime .= " เวลา {$time} น.";
                  }
                } else {
                  $dateTime = '—';
                }
                echo htmlspecialchars($dateTime);
              ?>
            </td>
            <td class="match-cell">
              <span class="color-box" style="background: <?= colorBg($m['side_a_color']) ?>">
                สี<?= htmlspecialchars($m['side_a_color']) ?>
              </span>
              <span class="vs-text">vs</span>
              <span class="color-box" style="background: <?= colorBg($m['side_b_color']) ?>">
                สี<?= htmlspecialchars($m['side_b_color']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    
  <?php endforeach; ?>
  
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isFontSubsettingEnabled', true);
$options->setChroot(__DIR__);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ปรับชื่อไฟล์ตามกีฬาที่เลือก
if ($selectedSportName !== '') {
  $filename = 'รายการแข่งขัน_' . $selectedSportName . '_' . date('Y-m-d') . '.pdf';
} else {
  $filename = 'รายการแข่งขัน_ทั้งหมด_' . date('Y-m-d') . '.pdf';
}
$dompdf->stream($filename, ['Attachment' => true]);
