<?php
// public/reports.php
// รายงาน: ใบเช็คชื่อนักกีฬา (แยกสี) ห้องละ 1 หน้า (สูงสุด 50 แถว)

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

$pdo    = db();
$yearId = active_year_id($pdo);
$yearName = active_year_name($pdo);

// คำนวณจำนวนเหรียญทั้งหมด
$medalStmt = $pdo->prepare("
  SELECT s.id, s.name, s.team_size, c.name AS category_name
  FROM sports s
  LEFT JOIN sport_categories c ON c.id = s.category_id
  WHERE s.year_id = ? AND s.is_active = 1
  ORDER BY c.name, s.name
");
$medalStmt->execute([$yearId]);
$allSports = $medalStmt->fetchAll(PDO::FETCH_ASSOC);

$totalGold = 0;
$totalSilver = 0;
$totalBronze = 0;
$medalsByCategory = [];

foreach ($allSports as $sp) {
  $teamSize = max(1, (int)$sp['team_size']);
  $catName = $sp['category_name'] ?: 'ไม่ระบุประเภท';
  
  if (!isset($medalsByCategory[$catName])) {
    $medalsByCategory[$catName] = [
      'gold' => 0,
      'silver' => 0,
      'bronze' => 0,
      'sports' => []
    ];
  }
  
  $medalsByCategory[$catName]['gold'] += $teamSize;
  $medalsByCategory[$catName]['silver'] += $teamSize;
  $medalsByCategory[$catName]['bronze'] += $teamSize;
  $medalsByCategory[$catName]['sports'][] = [
    'name' => $sp['name'],
    'medals' => $teamSize
  ];
  
  $totalGold += $teamSize;
  $totalSilver += $teamSize;
  $totalBronze += $teamSize;
}

// ------------------------
// ACTION: export PDF
// ------------------------
$action = $_GET['action'] ?? null;

// ===== Action: รายงานนักกีฬา(ตามห้อง) =====
if ($action === 'students_by_class') {
  // เพิ่ม memory limit
  ini_set('memory_limit', '512M');
  ini_set('max_execution_time', '180');

  // ดึงนักเรียนทั้งหมดพร้อมกีฬาที่ลงทะเบียน - เรียงให้ถูกต้อง (เพิ่ม LIMIT)
  $stmt = $pdo->prepare("
    SELECT 
      s.id, s.student_code, s.first_name, s.last_name, s.color,
      s.class_level, s.class_room, s.number_in_room
    FROM students s
    WHERE s.year_id = :y
    ORDER BY 
      CASE 
        WHEN s.class_level LIKE 'ป.%' THEN 1
        WHEN s.class_level LIKE 'ม.%' THEN 2
        ELSE 3 
      END,
      CAST(REPLACE(REPLACE(s.class_level, 'ป.', ''), 'ม.', '') AS UNSIGNED),
      CAST(s.class_room AS UNSIGNED),
      CAST(s.number_in_room AS UNSIGNED),
      s.first_name
    LIMIT 5000
  ");
  $stmt->execute([':y' => $yearId]);
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ดึงกีฬาที่ลงทะเบียนของแต่ละคน
  $studentIds = array_column($students, 'id');
  $sportsByStudent = [];
  
  if (!empty($studentIds)) {
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $sportStmt = $pdo->prepare("
      SELECT r.student_id, sp.name AS sport_name
      FROM registrations r
      JOIN sports sp ON sp.id = r.sport_id
      WHERE r.year_id = ? AND r.student_id IN ($placeholders)
      ORDER BY sp.name
    ");
    $params = array_merge([$yearId], $studentIds);
    $sportStmt->execute($params);
    $registrations = $sportStmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดกลุ่มกีฬาตาม student_id
    foreach ($registrations as $reg) {
      $sid = (int)$reg['student_id'];
      if (!isset($sportsByStudent[$sid])) {
        $sportsByStudent[$sid] = [];
      }
      $sportsByStudent[$sid][] = $reg['sport_name'];
    }
  }

  // ฟังก์ชันแปลงห้องให้เรียงได้ถูกต้อง (รองรับ ป.1, ม.6)
  function parseClassRoom($classLevel, $classRoom) {
    $level = 0;
    $grade = 0;
    
    // รองรับทั้ง ป.1 และ ป1
    if (preg_match('/^ป\.?(\d+)/', $classLevel, $m)) {
      $level = 1; // ประถม
      $grade = (int)$m[1];
    } elseif (preg_match('/^ม\.?(\d+)/', $classLevel, $m)) {
      $level = 2; // มัธยม
      $grade = (int)$m[1];
    }
    
    $room = (int)$classRoom;
    
    return [$level, $grade, $room];
  }

  // จัดกลุ่มนักเรียนตามห้อง
  $byClass = [];
  foreach ($students as $st) {
    $key = trim($st['class_level']) . '/' . trim($st['class_room']);
    if (!isset($byClass[$key])) {
      $byClass[$key] = [
        'students' => [],
        'sort_key' => parseClassRoom($st['class_level'], $st['class_room'])
      ];
    }
    $byClass[$key]['students'][] = $st;
  }

  // เรียงตาม sort_key
  uasort($byClass, function($a, $b) {
    return $a['sort_key'] <=> $b['sort_key'];
  });

  $basePath = rtrim(str_replace('\\', '/', __DIR__), '/');

  // CSS - บีบให้แคบและกระชับ
  $css = <<<CSS
  @page { size: A4 portrait; margin: 5mm 8mm; }
  @font-face {
    font-family: 'THSarabunNew';
    src: url('assets/fonts/THSarabunNew.ttf') format('truetype');
  }
  @font-face {
    font-family: 'THSarabunNew';
    src: url('assets/fonts/THSarabunNew-Bold.ttf') format('truetype');
    font-weight: bold;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'THSarabunNew', sans-serif;
    font-size: 10pt;
    line-height: 1;
  }
  .page-header {
    text-align: center;
    margin-bottom: 2mm;
    padding-bottom: 1mm;
    border-bottom: 1.5px solid #0d6efd;
  }
  .page-title {
    font-size: 14pt;
    font-weight: bold;
    margin-bottom: 0.5mm;
    color: #0d6efd;
  }
  .page-meta { font-size: 10pt; color: #555; }
  .class-title {
    font-size: 12pt;
    font-weight: bold;
    margin: 1.5mm 0 1mm 0;
    color: #212529;
    padding-bottom: 0.5mm;
    border-bottom: 1px solid #dee2e6;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5mm;
  }
  th, td {
    border: 0.5px solid #333;
    padding: 0.8mm 1.2mm;
    text-align: left;
    font-size: 9pt;
    line-height: 0.9;
    vertical-align: top;
  }
  th {
    background: #f8f9fa;
    font-weight: bold;
    text-align: center;
    color: #0d6efd;
    font-size: 9pt;
    padding: 0.6mm 1.2mm;
  }
  .td-center { text-align: center; }
  .sport-list {
    font-size: 8.5pt;
    line-height: 1;
  }
  .no-sports {
    color: #999;
    font-style: italic;
    font-size: 8.5pt;
  }
  .page-break { page-break-after: always; }
  .student-count {
    font-size: 9pt;
    color: #666;
    margin-bottom: 0.8mm;
  }
  CSS;

  ob_start();
  ?>
  <!doctype html>
  <html>
  <head>
    <meta charset="utf-8">
    <style><?= $css ?></style>
  </head>
  <body>
    <?php if (empty($byClass)): ?>
      <div class="page-header">
        <div class="page-title">รายงานนักกีฬาตามห้องเรียน</div>
        <div class="page-meta"><?= htmlspecialchars($yearName, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <p style="text-align:center; color:#999;">ไม่มีข้อมูลนักเรียน</p>
    <?php else: ?>
      <?php 
      $classIndex = 0;
      $totalClasses = count($byClass);
      foreach ($byClass as $classKey => $classData): 
        $classIndex++;
        $studentList = $classData['students'];
      ?>
        <?php if ($classIndex === 1): ?>
        <div class="page-header">
          <div class="page-title">รายงานนักกีฬาตามห้องเรียน</div>
          <div class="page-meta"><?= htmlspecialchars($yearName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>

        <div class="class-title">ชั้น <?= htmlspecialchars($classKey, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="student-count">จำนวน: <?= count($studentList) ?> คน</div>
        
        <table>
          <thead>
            <tr>
              <th style="width: 8%;">เลขที่</th>
              <th style="width: 15%;">รหัส</th>
              <th style="width: 30%;">ชื่อ - นามสกุล</th>
              <th>กีฬาที่ลงทะเบียน</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            foreach ($studentList as $st): 
              $sid = (int)$st['id'];
              $sports = $sportsByStudent[$sid] ?? [];
              $fullName = trim($st['first_name'] . ' ' . $st['last_name']);
            ?>
              <tr>
                <td class="td-center"><?= htmlspecialchars($st['number_in_room'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="td-center"><?= htmlspecialchars($st['student_code'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?php if (empty($sports)): ?>
                    <span class="no-sports">-</span>
                  <?php else: ?>
                    <div class="sport-list">
                      <?= htmlspecialchars(implode(', ', $sports), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($classIndex < $totalClasses): ?>
          <div class="page-break"></div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </body>
  </html>
  <?php
  $html = ob_get_clean();

  $options = new Options();
  $options->set('isRemoteEnabled', false);
  $options->set('isHtml5ParserEnabled', true);
  $options->set('defaultFont', 'THSarabunNew');
  $options->set('isFontSubsettingEnabled', true);
  $options->set('chroot', $basePath);

  $dompdf = new Dompdf($options);
  $dompdf->setBasePath($basePath);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();

  $filename = 'รายงานนักกีฬาตามห้อง_' . $yearName . '.pdf';
  $dompdf->stream($filename, ['Attachment' => true]);
  exit;
}

// ===== Action: export PDF ใบเช็คชื่อนักกีฬา (แยกสี) =====
if ($action === 'sheet') {
  // รับสี (ไทย): เขียว/ฟ้า/ชมพู/ส้ม
  $color = $_GET['color'] ?? '';
  $validColors = ['เขียว','ฟ้า','ชมพู','ส้ม'];
  if (!in_array($color, $validColors, true)) {
    $color = 'เขียว';
  }

  // ดึงนักเรียนตามปีและสี
  $stmt = $pdo->prepare("
    SELECT s.student_code, s.first_name, s.last_name,
           s.class_level, s.class_room, s.number_in_room, s.color
    FROM students s
    WHERE s.year_id = :y AND s.color = :c
    ORDER BY s.class_level, s.class_room,
             CAST(s.number_in_room AS UNSIGNED), s.first_name
  ");
  $stmt->execute([':y'=>$yearId, ':c'=>$color]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // จัดกลุ่มตามห้อง: key = 'ชั้น/ห้อง' เช่น ป3/2 หรือ ม4/1
  $byRoom = [];
  foreach ($rows as $r) {
    $key = ($r['class_level'] ?? '') . '/' . ($r['class_room'] ?? '');
    if (!isset($byRoom[$key])) $byRoom[$key] = [];
    $byRoom[$key][] = $r;
  }

  // เตรียม HTML สำหรับ dompdf
  // BasePath ให้ dompdf หาไฟล์ฟอนต์จาก public ได้ถูกต้อง
  $basePath = rtrim(str_replace('\\', '/', __DIR__), '/'); // .../public

  // CSS: บังคับคอลัมน์ชัดเจน (ชื่อ-นามสกุลกว้างขึ้น)
  $css = <<<CSS
  @page { size: A4 portrait; margin: 6mm 6mm; }

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
    font-size: 11pt;
    line-height: 0.7;
  }

  .title {
    text-align: center; font-weight: bold; font-size: 14pt; margin-bottom: 6mm;
  }
  .meta {
    text-align: center; margin-top: -4mm; margin-bottom: 3mm;
  }

  table { width: 180mm; border-collapse: collapse; }
  th, td {
    border: 1px solid #000;
    padding: 1.2px 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  th { text-align: center; font-weight: bold; }

  .hdr-top th { border-bottom: none; }
  .hdr-btm th { border-top: none; }

  th.w-code, td.w-code { width: 16mm !important; min-width:16mm; max-width:16mm; }
  th.w-name, td.w-name { width: 100mm !important; min-width:100mm; max-width:100mm; word-break: break-word; }
  th.w-num,  td.w-num  { width: 9mm  !important; min-width:9mm;  max-width:9mm; }
  th.w-chk,  td.w-chk  { width: 5mm  !important; min-width:5mm;  max-width:5mm; }

  tbody td { height: 4.2mm; }

  .td-center { text-align: center; }

  .note-top { margin-bottom: 2mm; }

  .page-break { page-break-after: always; }
  CSS;

  // ฟังก์ชันเรนเดอร์ตารางต่อห้อง
  $renderRoom = function(string $roomKey, array $students) use ($color, $yearName) {

    // แยกชั้น/ห้องจาก key
    $parts = explode('/', $roomKey, 2);
    $classLevel = $parts[0] ?? '';
    $classRoom  = $parts[1] ?? '';
    $roomLabel  = trim($classLevel . ' / ' . $classRoom);

    // ทำ 50 แถว (เติมค่าว่างหากไม่ครบ)
    $maxRows = 50;
    $count = count($students);
    if ($count < $maxRows) {
      for ($i=$count; $i<$maxRows; $i++) {
        $students[] = [
          'student_code'  => '',
          'first_name'    => '',
          'last_name'     => '',
          'number_in_room'=> '',
        ];
      }
    }

    // สี heading ทางซ้าย (แสดงข้อความเท่านั้น)
    $colorLabel = 'สี ' . $color . ' ' . $yearName; // เพิ่มปีการศึกษา

    ob_start();
    ?>
    <div class="title">ใบเช็คชื่อนักกีฬา การแข่งขันกีฬาสีราชพฤกษ์เกม</div>
    <div class="meta">
      <div class="note-top"><?php echo htmlspecialchars($colorLabel, ENT_QUOTES, 'UTF-8'); ?></div>
      <div>ระดับชั้น <?php echo htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <table>
      <thead>
        <tr class="hdr-top">
          <th class="w-code" rowspan="2">รหัส</th>
          <th class="w-name" rowspan="2">ชื่อ - นามสกุล</th>
          <th class="w-num"  rowspan="2">เลขที่</th>
          <th class="w-chk" colspan="10"> </th>
        </tr>
        <tr class="hdr-btm">
          <?php for ($i=0;$i<10;$i++): ?>
            <th class="w-chk"></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $st): ?>
        <tr>
          <td class="w-code td-center"><?php echo htmlspecialchars($st['student_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="w-name"><?php
            $fn = trim(($st['first_name'] ?? '') . ' ' . ($st['last_name'] ?? ''));
            echo htmlspecialchars($fn, ENT_QUOTES, 'UTF-8');
          ?></td>
          <td class="w-num td-center"><?php echo htmlspecialchars($st['number_in_room'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <?php for ($i=0;$i<10;$i++): ?>
            <td class="w-chk">&nbsp;</td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php
    return ob_get_clean();
  };

  // ประกอบทุกหน้าตามห้อง
  $htmlPages = [];
  if (!empty($byRoom)) {
    foreach ($byRoom as $roomKey => $list) {
      $htmlPages[] = $renderRoom($roomKey, $list);
    }
  } else {
    // ไม่มีนักเรียนสีนี้เลย: ยิงหน้าเปล่าพร้อมข้อความ
    $htmlPages[] = '<div class="title">ใบเช็คชื่อนักกีฬา</div>
                    <div class="meta">สี '.htmlspecialchars($color,ENT_QUOTES,'UTF-8').' — ไม่มีข้อมูล</div>';
  }

  // ครอบ HTML + CSS
  $html = '<!doctype html><html><head><meta charset="utf-8">' .
          '<style>'.$css.'</style></head><body>';

  // วางเนื้อหาแต่ละหน้า
  $total = count($htmlPages);
  foreach ($htmlPages as $idx => $pageHtml) {
    $html .= $pageHtml;
    if ($idx < $total-1) $html .= '<div class="page-break"></div>';
  }
  $html .= '</body></html>';

  // สร้าง PDF
  $options = new Options();
  $options->set('isRemoteEnabled', true);
  $options->set('defaultFont', 'THSarabunNew');

  $dompdf = new Dompdf($options);
  $dompdf->setBasePath($basePath);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();

  $fname = 'signsheet_'.$color.'.pdf';
  $dompdf->stream($fname, ['Attachment' => true]); // เปลี่ยนเป็น true เพื่อดาวน์โหลด
  exit;
}

// ===== Action: export sports list =====
if ($action === 'sports_list') {
  ini_set('memory_limit', '256M');
  ini_set('max_execution_time', '60');

  // คำนวณครั้งที่จาก พ.ศ.
  $currentYearBE = (int)date('Y') + 543;
  $gameNumber = $currentYearBE - 2552;

  // ดึงกีฬาทั้งหมดที่ active ในปีนี้ (พร้อมข้อมูลประเภท)
  $stmt = $pdo->prepare("
    SELECT s.id, s.name, s.gender, s.participant_type, s.team_size, s.grade_levels,
           c.name AS category_name
    FROM sports s
    LEFT JOIN sport_categories c ON c.id = s.category_id
    WHERE s.year_id = ? AND s.is_active = 1
    ORDER BY c.name, s.name
  ");
  $stmt->execute([$yearId]);
  $sports = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // จัดกลุ่มตามประเภท
  $byCategory = [];
  foreach ($sports as $sp) {
    $cat = $sp['category_name'] ?: 'ไม่ระบุประเภท';
    if (!isset($byCategory[$cat])) $byCategory[$cat] = [];
    $byCategory[$cat][] = $sp;
  }

  $basePath = rtrim(str_replace('\\', '/', __DIR__), '/');

  $css = <<<CSS
  @page { size: A4 portrait; margin: 10mm 8mm; }
  @font-face {
    font-family: 'THSarabunNew';
    src: url('assets/fonts/THSarabunNew.ttf') format('truetype');
  }
  @font-face {
    font-family: 'THSarabunNew';
    src: url('assets/fonts/THSarabunNew-Bold.ttf') format('truetype');
    font-weight: bold;
  }
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  body {
    font-family: 'THSarabunNew', 'DejaVu Sans', sans-serif;
    font-size: 14pt;
    line-height: 1.2;
  }
  .title {
    text-align: center; font-weight: bold; font-size: 16pt; margin-bottom: 2mm;
  }
  .meta {
    text-align: center; margin-bottom: 4mm; color: #555; font-size: 12pt;
  }
  .category-section {
    margin-bottom: 3mm;
    page-break-inside: avoid;
  }
  .category-title {
    font-weight: bold; font-size: 14pt; color: #0d6efd;
    border-bottom: 1.5px solid #0d6efd; padding-bottom: 1mm; margin-bottom: 1.5mm;
  }
  table {
    width: 100%; border-collapse: collapse; margin-bottom: 2mm;
  }
  th, td {
    border: 1px solid #333; padding: 1mm 2mm; text-align: left; font-size: 12pt; line-height: 1.1;
  }
  th {
    background: #e9ecef; font-weight: bold; text-align: center; font-size: 12pt;
  }
  .td-center { text-align: center; }
  .gender-male { color: #0ea5e9; font-weight: bold; }
  .gender-female { color: #ec4899; font-weight: bold; }
  .gender-mixed { color: #8b5cf6; font-weight: bold; }
  CSS;

  ob_start();
  ?>
  <!doctype html>
  <html>
  <head>
    <meta charset="utf-8">
    <style><?= $css ?></style>
  </head>
  <body>
    <div class="title">รายการกีฬาทั้งหมด</div>
    <div class="meta">
      กีฬาราชพฤกษ์เกมส์ ครั้งที่ <?= $gameNumber ?> <?= htmlspecialchars($yearName ?? '', ENT_QUOTES, 'UTF-8') ?><br>
      จำนวนกีฬา: <?= count($sports) ?> รายการ
    </div>

    <?php if (empty($byCategory)): ?>
      <p style="text-align:center; color:#999;">ไม่มีข้อมูลกีฬา</p>
    <?php else: ?>
      <?php foreach ($byCategory as $catName => $items): ?>
        <div class="category-section">
          <div class="category-title"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></div>
          <table>
            <thead>
              <tr>
                <th style="width:5%;">ลำดับ</th>
                <th style="width:38%;">ชื่อกีฬา</th>
                <th style="width:8%;">เพศ</th>
                <th style="width:12%;">ประเภท</th>
                <th style="width:10%;">จำนวนที่รับ</th>
                <th style="width:27%;">ระดับชั้นที่เปิด</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($items as $sp): ?>
                <?php
                  $genderClass = '';
                  $genderLabel = $sp['gender'];
                  if ($sp['gender'] === 'ช') {
                    $genderClass = 'gender-male';
                    $genderLabel = 'ชาย';
                  } elseif ($sp['gender'] === 'ญ') {
                    $genderClass = 'gender-female';
                    $genderLabel = 'หญิง';
                  } else {
                    $genderClass = 'gender-mixed';
                    $genderLabel = 'ผสม';
                  }

                  $ptype = $sp['participant_type'] === 'ทีม' ? 'ทีม' : 'เดี่ยว';
                  $slots = max(1, (int)$sp['team_size']);
                  $grades = $sp['grade_levels'] ?: 'ทุกชั้น';
                ?>
                <tr>
                  <td class="td-center"><?= $no++ ?></td>
                  <td><?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="td-center <?= $genderClass ?>"><?= $genderLabel ?></td>
                  <td class="td-center"><?= $ptype ?></td>
                  <td class="td-center"><?= $slots ?> คน</td>
                  <td><?= htmlspecialchars($grades, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </body>
  </html>
  <?php
  $html = ob_get_clean();

  $options = new Options();
  $options->set('isRemoteEnabled', false);
  $options->set('isHtml5ParserEnabled', true);
  $options->set('defaultFont', 'THSarabunNew');
  $options->set('chroot', $basePath);

  $dompdf = new Dompdf($options);
  $dompdf->setBasePath($basePath);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();

  $fname = 'sports_list_' . $yearId . '.pdf';
  $dompdf->stream($fname, ['Attachment' => true]);
  exit;
}

// ------------------------
// หน้า UI: ปุ่มเลือกสีเพื่อ export
// ------------------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<!-- เพิ่มสไตล์เล็กน้อยสำหรับจัดปุ่มให้สวย -->
<style>
  .report-section { margin-bottom: 2rem; }
  .report-section-title {
    font-size: 1.1rem; font-weight: 600; color: #495057;
    margin-bottom: 1rem; padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
  }
  .color-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
  }
  .color-btn {
    display: flex; align-items: center; justify-content: center;
    padding: 1rem; border-radius: 0.5rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .color-btn:hover {
    transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }
  .color-btn.c-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
  .color-btn.c-sky { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #fff; }
  .color-btn.c-pink { background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%); color: #fff; }
  .color-btn.c-amber { background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); color: #fff; }


  .other-reports { display: flex; flex-direction: column; gap: 0.75rem; }
  .report-btn {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem; border-radius: 0.5rem; text-decoration: none;
    border: 2px solid #e9ecef; transition: all 0.2s;
  }
  .report-btn:hover {
    border-color: #0d6efd; background: #f8f9fa;
    transform: translateX(4px);
  }
  .report-btn .icon { font-size: 1.5rem; }
  .report-btn .label { font-weight: 600; color: #212529; }
  .report-btn .desc { font-size: 0.875rem; color: #6c757d; margin-top: 0.25rem; }

  .medal-card {
    border-radius: 1rem;
    border: none;
    transition: transform 0.2s;
  }
  .medal-card:hover {
    transform: translateY(-2px);
  }
  .medal-card.gold {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
  }
  .medal-card.silver {
    background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%);
    color: white;
  }
  .medal-card.bronze {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: white;
  }
  .medal-number {
    font-size: 2.5rem;
    font-weight: 700;
  }
  .medal-label {
    font-size: 1rem;
    opacity: 0.9;
  }
  .category-medal-item {
    padding: 0.75rem;
    border-radius: 0.5rem;
    background: #f8f9fa;
    margin-bottom: 0.5rem;
  }
  .category-medal-item:hover {
    background: #e9ecef;
  }
  .medal-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
  }

  @media (max-width: 576px) {
    .color-grid { grid-template-columns: repeat(2, 1fr); }
  }
</style>

<main class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="mb-1">📄 รายงานเอกสาร</h4>
      <p class="text-muted mb-0 small">ดาวน์โหลดเอกสาร PDF สำหรับการจัดการกีฬาสี</p>
    </div>
    <span class="badge bg-primary">ปี <?= htmlspecialchars($yearName ?? '', ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <!-- สรุปจำนวนเหรียญทั้งหมด -->
  <div class="report-section">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="report-section-title">🏅 สรุปจำนวนเหรียญที่ต้องเตรียม</div>
        <p class="text-muted mb-3 small">คำนวณจากจำนวนคนต่อกีฬา × จำนวนรายการกีฬา</p>
        
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card medal-card gold shadow-sm">
              <div class="card-body text-center">
                <div class="medal-number"><?= number_format($totalGold) ?></div>
                <div class="medal-label">🥇 เหรียญทอง</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card medal-card silver shadow-sm">
              <div class="card-body text-center">
                <div class="medal-number"><?= number_format($totalSilver) ?></div>
                <div class="medal-label">🥈 เหรียญเงิน</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card medal-card bronze shadow-sm">
              <div class="card-body text-center">
                <div class="medal-number"><?= number_format($totalBronze) ?></div>
                <div class="medal-label">🥉 เหรียญทองแดง</div>
              </div>
            </div>
          </div>
        </div>

        <!-- รายละเอียดแยกตามประเภทกีฬา -->
        <details class="mt-3">
          <summary class="fw-semibold mb-3" style="cursor: pointer; color: #0d6efd;">
            📊 ดูรายละเอียดแยกตามประเภทกีฬา
          </summary>
          
          <?php foreach ($medalsByCategory as $catName => $data): ?>
            <div class="category-medal-item">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></div>
                <div>
                  <span class="medal-badge" style="background: #fbbf24; color: white;">🥇 <?= $data['gold'] ?></span>
                  <span class="medal-badge" style="background: #9ca3af; color: white;">🥈 <?= $data['silver'] ?></span>
                  <span class="medal-badge" style="background: #f97316; color: white;">🥉 <?= $data['bronze'] ?></span>
                </div>
              </div>
              <div class="small text-muted">
                <?php foreach ($data['sports'] as $idx => $sport): ?>
                  <span>
                    <?= htmlspecialchars($sport['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $sport['medals'] ?> เหรียญ)<?= $idx < count($data['sports'])-1 ? ' • ' : '' ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </details>
      </div>
    </div>
  </div>

  <!-- ใบเช็คชื่อนักกีฬา (แยกตามสี) -->
  <div class="report-section">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="report-section-title">✅ ใบเช็คชื่อนักกีฬา</div>
        <p class="text-muted mb-3 small">เอกสารเช็คชื่อนักกีฬาแยกตามสี (1 ห้อง = 1 หน้า, สูงสุด 50 แถว)</p>
        <div class="color-grid">
          <a class="color-btn c-green" href="?action=sheet&color=<?= urlencode('เขียว') ?>">
            🟢 สีเขียว
          </a>
          <a class="color-btn c-sky" href="?action=sheet&color=<?= urlencode('ฟ้า') ?>">
            🔵 สีฟ้า
          </a>
          <a class="color-btn c-pink" href="?action=sheet&color=<?= urlencode('ชมพู') ?>">
            🩷 สีชมพู
          </a>
          <a class="color-btn c-amber" href="?action=sheet&color=<?= urlencode('ส้ม') ?>">
            🟠 สีส้ม
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- รายงานอื่น ๆ -->
  <div class="report-section">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="report-section-title">📋 รายงานอื่น ๆ</div>
        <div class="other-reports">
          <a class="report-btn" href="<?= BASE_URL ?>/reports_export_registration.php">
            <div>
              <div class="label">📝 ใบลงทะเบียนกีฬา</div>
              <div class="desc">รายละเอียดการลงทะเบียนนักกีฬาทุกรายการ</div>
            </div>
            <div class="icon">→</div>
          </a>

          <a class="report-btn" href="?action=sports_list">
            <div>
              <div class="label">🏆 รายการกีฬาทั้งหมด</div>
              <div class="desc">รายชื่อกีฬา ระดับชั้น จำนวนที่รับ (จัดกลุ่มตามประเภท)</div>
            </div>
            <div class="icon">→</div>
          </a>

          <a class="report-btn" href="?action=students_by_class">
            <div>
              <div class="label">👥 รายงานนักกีฬา (ตามห้อง)</div>
              <div class="desc">รายชื่อนักเรียนแยกตามห้อง พร้อมกีฬาที่ลงทะเบียน</div>
            </div>
            <div class="icon">→</div>
          </a>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
