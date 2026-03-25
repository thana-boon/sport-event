<?php
// export_combined_booklet.php - Export รวมเล่ม: ปก + รายการแข่งขัน + สูจิบัตรนักกีฬา
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
$yearName = active_year_name($pdo) ?? '';

// ensure schema (best-effort)
ensure_match_pairs_schedule_no($pdo);

if (!$yearId) {
  die('ยังไม่ได้ตั้งปีการศึกษาให้ Active');
}

$selectedSportName = trim($_GET['sport_name'] ?? '');
if ($selectedSportName === '') {
  die('กรุณาเลือกกีฬา');
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function color_bg($color) {
  $map = [
    'เขียว' => '#43a047',
    'ฟ้า'   => '#1976d2',
    'ชมพู'  => '#e91e63',
    'ส้ม'   => '#fb8c00',
    'เหลือง'=> '#fbc02d',
    'ม่วง'  => '#8e24aa',
    'แดง'   => '#d32f2f',
  ];
  return $map[trim($color)] ?? '#888';
}

function formatGender($gender) {
  if ($gender === 'ช') return 'ชาย';
  if ($gender === 'ญ') return 'หญิง';
  if ($gender === 'รวม') return 'ชาย-หญิง';
  if ($gender === 'ผสม') return 'ชาย-หญิง';
  return $gender;
}

function db_table_has_column_local(PDO $pdo, string $table, string $column): bool {
  // local shim (in case helpers.php older cache) - delegates if exists
  if (function_exists('db_table_has_column')) {
    return db_table_has_column($pdo, $table, $column);
  }
  try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $st->execute([$table, $column]);
    return (int)$st->fetchColumn() > 0;
  } catch (Throwable $e) {
    return false;
  }
}

// ดึงข้อมูล meta
$meta = ['edition_no'=>'', 'start_date'=>null, 'end_date'=>null, 'title'=>'', 'logo_path'=>null];
$st = $pdo->prepare("SELECT edition_no,start_date,end_date,title,logo_path FROM competition_meta WHERE year_id=:y LIMIT 1");
$st->execute([':y'=>$yearId]);
if ($r = $st->fetch(PDO::FETCH_ASSOC)) $meta = $r;

// ตรวจสอบว่ามีหน้าปกหรือไม่ (รองรับทั้ง PDF และรูปภาพ)
$coverStmt = $pdo->prepare("SELECT cover_pdf FROM sport_covers WHERE sport_name = ?");
$coverStmt->execute([$selectedSportName]);
$coverPdf = $coverStmt->fetchColumn();
$coverPath = null;
$coverType = null; // 'pdf' หรือ 'image'

if ($coverPdf) {
  $fullPath = __DIR__ . '/uploads/covers/' . $coverPdf;
  if (file_exists($fullPath)) {
    $ext = strtolower(pathinfo($coverPdf, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
      // PDF ไม่สามารถรวมได้ด้วย dompdf - ข้ามไป
      $coverPath = null;
      $coverType = null;
    } else {
      // รูปภาพ (jpg, png, etc.)
      $coverPath = $fullPath;
      $coverType = 'image';
    }
  }
}

// ดึงข้อมูลกีฬา
$sqlSports = "
  SELECT s.id, s.name, s.gender, s.participant_type, s.team_size, s.grade_levels,
         c.name AS category_name,
         CASE 
           WHEN s.grade_levels LIKE 'ป%' OR s.grade_levels LIKE '%,ป%' THEN 1
           WHEN s.grade_levels LIKE 'ม%' OR s.grade_levels LIKE '%,ม%' THEN 2
           ELSE 3
         END AS level_order,
         CASE s.participant_type
           WHEN 'เดี่ยว' THEN 1
           WHEN 'ทีม' THEN 2
           ELSE 3
         END AS type_order
    FROM sports s
    JOIN sport_categories c ON c.id = s.category_id
   WHERE s.year_id = :y
     AND s.is_active = 1
     AND s.name LIKE CONCAT(:sport_name, '%')
   ORDER BY level_order ASC, type_order ASC, s.gender ASC, s.name ASC, c.name ASC
";
$st = $pdo->prepare($sqlSports);
$st->execute([':y'=>$yearId, ':sport_name'=>$selectedSportName]);
$sports = $st->fetchAll(PDO::FETCH_ASSOC);

if (empty($sports)) {
  die('ไม่พบกีฬาที่เลือก');
}

// ดึงข้อมูลนักกีฬา
$allPlayers = [];
if (!empty($sports)) {
  $sportIds = array_column($sports, 'id');
  $placeholders = implode(',', array_fill(0, count($sportIds), '?'));
  
  $sqlPlayers = "SELECT r.sport_id, r.color, st.student_code, st.first_name, st.last_name,
                        st.class_level, st.class_room, st.number_in_room
                   FROM registrations r
                   JOIN students st ON st.id = r.student_id
                  WHERE r.year_id = ? AND r.sport_id IN ($placeholders)
                  ORDER BY r.sport_id, r.color, st.class_level, st.class_room, 
                           CAST(st.number_in_room AS UNSIGNED), st.student_code";
  
  $stPlayers = $pdo->prepare($sqlPlayers);
  $stPlayers->execute(array_merge([$yearId], $sportIds));
  
  while ($row = $stPlayers->fetch(PDO::FETCH_ASSOC)) {
    $sid = $row['sport_id'];
    unset($row['sport_id']);
    if (!isset($allPlayers[$sid])) {
      $allPlayers[$sid] = [];
    }
    $allPlayers[$sid][] = $row;
  }
}

// ดึงข้อมูลการแข่งขันแบบรวมตามกีฬาหลัก (เพื่อทำตารางเดียว ลดจำนวนหน้า)
$hasScheduleNo = db_table_has_column_local($pdo, 'match_pairs', 'schedule_no');
$allMatchesFlat = [];
$sqlMatches = "
  SELECT
    mp.id,
    mp.round_name,
    mp.round_no,
    mp.match_no,
    " . ($hasScheduleNo ? "mp.schedule_no," : "NULL AS schedule_no,") . "
    mp.match_date,
    mp.match_time,
    mp.venue,
    mp.side_a_label,
    mp.side_a_color,
    mp.side_b_label,
    mp.side_b_color,
    mp.winner,
    mp.score_a,
    mp.score_b,
    mp.status,
    mp.final_date,
    mp.final_time,
    s.name AS sport_full_name,
    s.participant_type,
    s.gender,
    s.grade_levels
  FROM match_pairs mp
  JOIN sports s ON s.id = mp.sport_id
  JOIN sport_categories c ON c.id = s.category_id
  WHERE mp.year_id = :y
    AND s.year_id = :y2
    AND s.is_active = 1
    AND c.name <> 'กรีฑา'
    AND s.name LIKE CONCAT(:sport_name, '%')
  ORDER BY
    " . ($hasScheduleNo ? "(mp.schedule_no IS NULL) ASC, mp.schedule_no ASC," : "") . "
    (mp.match_date IS NULL) ASC, mp.match_date ASC,
    (mp.match_time IS NULL) ASC, mp.match_time ASC,
    mp.round_no ASC, mp.match_no ASC,
    s.name ASC
";
$qMatches = $pdo->prepare($sqlMatches);
$qMatches->execute([':y' => $yearId, ':y2' => $yearId, ':sport_name' => $selectedSportName]);
$allMatchesFlat = $qMatches->fetchAll(PDO::FETCH_ASSOC);

// ===== สร้าง HTML + CSS (ใช้โครงสร้างเดียวกับ reports แยก) =====
$html = '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8">';
$html .= '<style>
  @page { margin: 12mm 8mm 12mm 25mm; }
  .page-break { page-break-before: always; }
  @font-face {
    font-family: "THSarabunNew";
    font-style: normal;
    font-weight: normal;
    src: url("assets/fonts/THSarabunNew.ttf") format("truetype");
  }
  @font-face {
    font-family: "THSarabunNew";
    font-style: normal;
    font-weight: bold;
    src: url("assets/fonts/THSarabunNew-Bold.ttf") format("truetype");
  }
  
  body {
    font-family: "THSarabunNew", DejaVu Sans, sans-serif;
    font-size: 14pt;
    color: #222;
    line-height: 1.1;
  }
  
  /* หน้าปก */
  .cover-page {
    page-break-after: always;
    text-align: center;
    padding: 0;
    margin: -12mm -8mm -12mm -25mm;
    width: 210mm;
    height: 297mm;
  }
  
  .cover-page img {
    width: 210mm;
    height: 297mm;
    object-fit: cover;
    display: block;
  }
  
  /* รายการแข่งขัน - เหมือน reports_matches.php */
  h1,h2,h3 { margin:0; }
  .header { display:flex; align-items:center; gap:12px; margin-bottom:2mm; }
  .header img.logo { height:38px; }
  .datetime-cell { line-height: 1.1; font-size: 11pt; padding: 0.5mm 1px !important; }
  .date-text { font-size: 9pt; }
  .title { font-size: 18pt; font-weight: 700; }
  .subtitle { font-size: 12pt; color:#555; }
  hr { border:0; border-top:1px solid #bbb; margin:3mm 0 2mm 0; }
  .section { page-break-inside: avoid; margin-top: 3mm; }
  .sport-name { font-size: 16pt; font-weight:700; margin-bottom: 2px; }
  .muted { color:#666; font-size:11pt; }
  table { width:100%; border-collapse: collapse; margin-top: 2px; font-size: 13pt; page-break-inside:avoid; }
  th, td {
    border: 1px solid #000;
    padding: 0.2mm 1.5px;
    vertical-align: middle;
    height: 1.2em;
  }
  thead th { background:#f2f2f2; font-weight:700; text-align:center; }
  .nowrap { white-space:nowrap; }
  .round-label { font-weight:700; margin-top:1.5mm; font-size:14pt; }
  .small { font-size: 11pt; }

  /* Match schedule: if a round table would break, move it to next page */
  .match-section { page-break-inside: auto; }
  .match-block { page-break-inside: avoid; }
  .round-label { page-break-after: avoid; }
  table.match-table { page-break-inside: avoid; }
  table.match-table thead { display: table-header-group; }
  table.match-table tr { page-break-inside: avoid; }
  
  /* สีในตารางรายการแข่งขัน - สีดำ */
  .match-cell-color { text-align:center; color:#000; }
  
  .logo-center {
    text-align: center;
    margin-bottom: 2mm;
  }
  .logo-center img.logo {
    height: 54px;
    width: auto;
    max-width: 100px;
    display: inline-block;
  }
  .headtext-center {
    text-align: center;
  }
  
  /* สูจิบัตร - เหมือน reports_booklet.php */
  .sport-section { }
  .sport-head { margin-top:3mm; font-size:14pt; font-weight:bold; }
  .color-table-wrapper { page-break-inside:avoid; }
  .header-table td { border:none; }
  .meta { text-align:center; margin-top:2mm; color:#444; }
  
  /* สีในตารางสูจิบัตร - สีดำตัวหนา */
  .cell-color { color:#000; font-weight:bold; text-align:center; }
</style></head><body>';

// ฟังก์ชัน thai_date
function thai_date($date) {
  if (!$date) return '';
  $months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
  ];
  $ts = strtotime($date);
  if (!$ts) return '';
  $d = (int)date('j', $ts);
  $m = (int)date('n', $ts);
  $y = (int)date('Y', $ts) + 543;
  return "{$d} {$months[$m]} {$y}";
}

// ฟังก์ชันหัวกระดาษสำหรับรายการแข่งขัน (เหมือน reports_matches.php)
function match_header($logoHtml, $meta, $yearName, $range) {
  $headText = '<div class="title" style="margin-bottom:2px;">ตารางรายการแข่งขัน</div>';
  $headText .= '<div class="subtitle">';
  if (!empty($meta['title'])) { $headText .= e($meta['title']); }
  if (empty($meta['title']) || strpos($meta['title'], 'ปีการศึกษา') === false) {
    if (!empty($headText)) { $headText .= ' • '; }
    $headText .= '' . e($yearName);
  }
  if ($range) { $headText .= ' • ' . e($range); }
  if (!empty($meta['edition_no'])) { $headText .= ' • ครั้งที่ ' . e($meta['edition_no']); }
  $headText .= '</div>';
  return '
    <div class="logo-center">'.$logoHtml.'</div>
    <div class="headtext-center">'.$headText.'</div>
    <hr style="margin-top:2mm;margin-bottom:2mm;"/>';
}

// ฟังก์ชันหัวกระดาษสำหรับสูจิบัตร (เหมือน reports_booklet.php)
function booklet_header($logoHtml, $yearName, $meta, $range) {
  $h = '<table class="header-table" width="100%"><tr>';
  $h .= '<td style="width:25mm; text-align:left; vertical-align:middle; border:none;">'. $logoHtml .'</td>';
  $h .= '<td style="text-align:center; vertical-align:middle; border:none;">';
  $h .= '<div class="title" style="font-size:18pt; font-weight:bold;">สูจิบัตรรายชื่อนักกีฬา</div>';
  $h .= '<div class="meta">'. e($yearName);
  if (($meta['title'] ?? '') !== '') { $h .= ' • '. e($meta['title']); }
  $h .= '</div>';
  
  if (!empty($meta['edition_no']) || $range) {
    $h .= '<div class="meta" style="margin-top:0.5mm;">';
    $parts = [];
    if (!empty($meta['edition_no'])) { $parts[] = 'ครั้งที่ '. e($meta['edition_no']); }
    if ($range) { $parts[] = e($range); }
    $h .= implode(' • ', $parts);
    $h .= '</div>';
  }
  
  $h .= '</td>';
  $h .= '<td style="width:25mm; border:none;"></td>';
  $h .= '</tr></table>';
  return $h;
}

// เตรียมข้อมูล
$logoHtml = '';
if (!empty($meta['logo_path'])) {
  $logo = $meta['logo_path'];
  $rel = ltrim($logo, '/');
  $logoHtml = '<img class="logo" src="'. e($rel) .'" style="height:22mm; width:auto;" />';
}

$range = '';
if (!empty($meta['start_date']) && !empty($meta['end_date'])) {
  $range = thai_date($meta['start_date']) . ' - ' . thai_date($meta['end_date']);
} elseif (!empty($meta['start_date'])) {
  $range = thai_date($meta['start_date']);
}

$rangeBooklet = '';
if (!empty($meta['start_date']) && !empty($meta['end_date'])) {
  $rangeBooklet = sprintf('วันที่ %s ถึง %s',
    thai_date($meta['start_date']),
    thai_date($meta['end_date'])
  );
} elseif (!empty($meta['start_date'])) {
  $rangeBooklet = 'วันที่ ' . thai_date($meta['start_date']);
}

// ส่วนที่ 1: หน้าปก (ถ้ามี - เฉพาะรูปภาพ)
if ($coverPath && $coverType === 'image') {
  $html .= '<div class="cover-page">';
  $html .= '<img src="' . $coverPath . '" alt="Cover" />';
  $html .= '</div>';
}

// ส่วนที่ 2: รายการแข่งขัน (ใช้โครงสร้างเดียวกับ reports_matches.php)
if (!empty($allMatchesFlat)) {
  $html .= match_header($logoHtml, $meta, $yearName, $range);

  // เตรียม map คู่รอบคัดเลือก (ต่อรายการย่อย) เพื่อใช้แสดงผู้ชนะ/ผู้แพ้ในรอบชิง
  $qualifyMap = []; // key: sport_full_name => [match1, match2]
  foreach ($allMatchesFlat as $m) {
    if ((int)$m['round_no'] !== 1) continue;
    $key = (string)($m['sport_full_name'] ?? '');
    if (!isset($qualifyMap[$key])) $qualifyMap[$key] = [];
    $qualifyMap[$key][] = $m;
  }
  foreach ($qualifyMap as $k => $arr) {
    usort($arr, fn($a,$b) => ((int)$a['match_no'] <=> (int)$b['match_no']));
    $qualifyMap[$k] = array_values($arr);
  }

  // Group by round_no
  $byType = ['เดี่ยว' => [], 'ทีม' => [], 'อื่นๆ' => []];
  foreach ($allMatchesFlat as $m) {
    $t = trim((string)($m['participant_type'] ?? ''));
    if ($t === 'เดี่ยว') {
      $byType['เดี่ยว'][] = $m;
    } elseif ($t === 'ทีม') {
      $byType['ทีม'][] = $m;
    } else {
      $byType['อื่นๆ'][] = $m;
    }
  }

  $html .= '<div class="section match-section">';

  // ประเภทเดี่ยวอยู่ด้วยกัน แล้วค่อยประเภททีม
  $typeOrder = ['เดี่ยว', 'ทีม', 'อื่นๆ'];
  foreach ($typeOrder as $typeLabel) {
    if (empty($byType[$typeLabel])) continue;
    $showType = $typeLabel === 'อื่นๆ' ? 'อื่นๆ' : $typeLabel;
    $html .= '<div class="sport-name" style="margin-top:1mm;">'. e($selectedSportName) . ' — ประเภท: ' . e($showType) . '</div>';

    // group by round within this type
    $byRound = [];
    foreach ($byType[$typeLabel] as $m) {
      $rno = (int)$m['round_no'];
      if (!isset($byRound[$rno])) $byRound[$rno] = ['name' => $m['round_name'] ?? ('รอบ '.$rno), 'items' => []];
      $byRound[$rno]['items'][] = $m;
    }
    ksort($byRound);

    foreach ($byRound as $rno => $grp) {
      $rname = trim((string)($grp['name'] ?? '')) !== '' ? (string)$grp['name'] : ('รอบ '.$rno);
      $html .= '<div class="match-block">';
      $html .= '<div class="round-label">'. e($rname) .'</div>';

      // sort inside group by schedule_no/date/time
      $items = $grp['items'];
      usort($items, function($a, $b) use ($hasScheduleNo) {
        $an = $a['schedule_no'] ?? null;
        $bn = $b['schedule_no'] ?? null;
        $anNull = ($an === null || $an === '');
        $bnNull = ($bn === null || $bn === '');
        if ($anNull !== $bnNull) return $anNull ? 1 : -1;
        if (!$anNull && !$bnNull) {
          $cmp = ((int)$an <=> (int)$bn);
          if ($cmp !== 0) return $cmp;
        }
        $ad = $a['match_date'] ?? null; $bd = $b['match_date'] ?? null;
        $adNull = empty($ad); $bdNull = empty($bd);
        if ($adNull !== $bdNull) return $adNull ? 1 : -1;
        if (!$adNull && !$bdNull) {
          $cmp = strcmp($ad, $bd);
          if ($cmp !== 0) return $cmp;
        }
        $at = $a['match_time'] ?? null; $bt = $b['match_time'] ?? null;
        $atNull = empty($at); $btNull = empty($bt);
        if ($atNull !== $btNull) return $atNull ? 1 : -1;
        if (!$atNull && !$btNull) {
          $cmp = strcmp($at, $bt);
          if ($cmp !== 0) return $cmp;
        }
        return ((int)($a['match_no'] ?? 0) <=> (int)($b['match_no'] ?? 0));
      });

      $html .= '<table class="match-table"><thead><tr>
        <th style="width:22mm">วันที่</th>
        <th style="width:10mm">คู่ที่</th>
        <th style="width:14mm">เวลา</th>
        <th style="width:24mm">ระดับชั้น</th>
        <th>ทีม</th>
        <th style="width:13mm">สี</th>
        <th class="small" style="width:12mm">ผล</th>
        <th>ทีม</th>
        <th style="width:13mm">สี</th>
        <th class="small" style="width:12mm">ผล</th>
      </tr></thead><tbody>';

      foreach ($items as $m) {
      $scheduleNo = $m['schedule_no'] ?? null;
      $pairNo = $scheduleNo !== null && $scheduleNo !== '' ? (int)$scheduleNo : (int)($m['match_no'] ?? 0);
      $dateText = !empty($m['match_date']) ? thai_date($m['match_date']) : '-';
      $timeText = !empty($m['match_time']) ? (substr($m['match_time'], 0, 5) . ' น.') : '-';

      $level = trim((string)($m['grade_levels'] ?? ''));
      $gender = trim((string)($m['gender'] ?? ''));
      $levelText = $level;
      if ($gender !== '') {
        $levelText = $levelText !== '' ? ($levelText . ' ' . formatGender($gender)) : formatGender($gender);
      }
      if ($levelText === '') $levelText = '-';

      $teamA = (string)($m['side_a_label'] ?? '');
      $teamB = (string)($m['side_b_label'] ?? '');
      $colorA = (string)($m['side_a_color'] ?? '');
      $colorB = (string)($m['side_b_color'] ?? '');
      $bgA = color_bg($colorA);
      $bgB = color_bg($colorB);

      // รอบชิง: แสดงผู้ชนะ/ผู้แพ้ อ้างอิงจาก “คู่รอบคัดเลือก” ของรายการย่อยเดียวกัน
      if ((int)$m['round_no'] === 2 || (int)$m['round_no'] === 3) {
        $key = (string)($m['sport_full_name'] ?? '');
        $q = $qualifyMap[$key] ?? [];
        $q1 = $q[0]['schedule_no'] ?? $q[0]['match_no'] ?? null;
        $q2 = $q[1]['schedule_no'] ?? $q[1]['match_no'] ?? null;
        $q1 = $q1 !== null && $q1 !== '' ? (int)$q1 : 1;
        $q2 = $q2 !== null && $q2 !== '' ? (int)$q2 : 2;

        if ((int)$m['round_no'] === 2) {
          $teamA = 'ผู้แพ้คู่ที่ ' . $q1;
          $teamB = 'ผู้แพ้คู่ที่ ' . $q2;
        } else {
          $teamA = 'ผู้ชนะคู่ที่ ' . $q1;
          $teamB = 'ผู้ชนะคู่ที่ ' . $q2;
        }
        $colorA = '';
        $colorB = '';
        // ใช้เทาอ่อนเพื่อให้เขียน/อ่านชัด
        $bgA = '#eeeeee';
        $bgB = '#eeeeee';
      }

      $html .= '<tr>';
      $html .= '<td class="datetime-cell" style="text-align:center"><span class="date-text">'. e($dateText) .'</span></td>';
      $html .= '<td class="nowrap" style="text-align:center">'. e($pairNo) .'</td>';
      $html .= '<td class="datetime-cell" style="text-align:center">'. e($timeText) .'</td>';
      $html .= '<td style="text-align:center">'. e($levelText) .'</td>';
      $html .= '<td style="text-align:center">'. e($teamA) .'</td>';
      $html .= '<td class="match-cell-color" style="background:'. e($bgA) .'; text-align:center">'. e($colorA !== '' ? $colorA : '-') .'</td>';
      $html .= '<td class="nowrap" style="text-align:center">'. e($m['score_a'] ?? '') .'</td>';
      $html .= '<td style="text-align:center">'. e($teamB) .'</td>';
      $html .= '<td class="match-cell-color" style="background:'. e($bgB) .'; text-align:center">'. e($colorB !== '' ? $colorB : '-') .'</td>';
      $html .= '<td class="nowrap" style="text-align:center">'. e($m['score_b'] ?? '') .'</td>';
      $html .= '</tr>';
      }

      $html .= '</tbody></table>';
      $html .= '</div>';
    }
  }

  $html .= '</div>'; // close section
}

// ส่วนที่ 3: สูจิบัตรรายชื่อนักกีฬา (ใช้โครงสร้างเดียวกับ reports_booklet.php)
$html .= '<div class="page-break"></div>';
$html .= booklet_header($logoHtml, $yearName, $meta, $rangeBooklet);

foreach ($sports as $sp) {
  $players = $allPlayers[$sp['id']] ?? [];
  $genderDisplay = formatGender($sp['gender']);
  
  $html .= '<div class="sport-section">';
  
  if (empty($players)) {
    $html .= '<div class="color-table-wrapper">';
    $html .= '<div class="sport-head">'. e($sp['name']) .' — '. e($sp['participant_type'])
          .' • หมวด: '. e($sp['category_name']) .' • เพศ: '. e($genderDisplay) .'</div>';
    if (!empty($sp['grade_levels'])){
      $html .= '<div class="small">ชั้นที่เปิด: '. e($sp['grade_levels']) .'</div>';
    }
    $html .= '<table><thead><tr>
                <th style="width:14mm">ลำดับ</th>
                <th style="width:18mm">สี</th>
                <th style="width:28mm">รหัส</th>
                <th>ชื่อ - นามสกุล</th>
                <th style="width:16mm">ชั้น</th>
                <th style="width:12mm">ห้อง</th>
                <th style="width:14mm">เลขที่</th>
              </tr></thead><tbody>';
    $html .= '<tr><td colspan="7" style="text-align:center">ยังไม่มีผู้ลงทะเบียน</td></tr>';
    $html .= '</tbody></table>';
    $html .= '</div>';
  } else {
    // แยกนักกีฬาตามสี
    $playersByColor = [];
    foreach($players as $p){
      $color = $p['color'];
      if (!isset($playersByColor[$color])) {
        $playersByColor[$color] = [];
      }
      $playersByColor[$color][] = $p;
    }
    
    $colorOrder = ['เขียว', 'ฟ้า', 'ชมพู', 'ส้ม'];
    $isFirstColor = true;
    
    foreach($colorOrder as $color){
      if (!isset($playersByColor[$color])) continue;
      
      $colorPlayers = $playersByColor[$color];
      
      $html .= '<div class="color-table-wrapper">';
      
      if ($isFirstColor) {
        $html .= '<div class="sport-head">'. e($sp['name']) .' — '. e($sp['participant_type'])
              .' • หมวด: '. e($sp['category_name']) .' • เพศ: '. e($genderDisplay) .'</div>';
        if (!empty($sp['grade_levels'])){
          $html .= '<div class="small">ชั้นที่เปิด: '. e($sp['grade_levels']) .'</div>';
        }
        $isFirstColor = false;
      }
      
      $html .= '<table style="margin-top:3mm;"><thead><tr>
                  <th style="width:14mm">ลำดับ</th>
                  <th style="width:18mm">สี</th>
                  <th style="width:28mm">รหัส</th>
                  <th>ชื่อ - นามสกุล</th>
                  <th style="width:16mm">ชั้น</th>
                  <th style="width:12mm">ห้อง</th>
                  <th style="width:14mm">เลขที่</th>
                </tr></thead><tbody>';
      
      $i=1;
      foreach($colorPlayers as $p){
        $fullname = trim($p['first_name'].' '.$p['last_name']);
        $bg = color_bg($p['color']);
        $html .= '<tr>
                    <td class="nowrap" style="text-align:center">'.($i++).'</td>
                    <td class="nowrap cell-color" style="background:'.$bg.'">'.e($p['color']).'</td>
                    <td class="nowrap" style="text-align:center">'.e($p['student_code']).'</td>
                    <td>'.e($fullname).'</td>
                    <td class="nowrap" style="text-align:center">'.e($p['class_level']).'</td>
                    <td class="nowrap" style="text-align:center">'.e($p['class_room']).'</td>
                    <td class="nowrap" style="text-align:center">'.e($p['number_in_room']).'</td>
                  </tr>';
      }
      $html .= '</tbody></table>';
      $html .= '</div>';
    }
  }
  $html .= '</div>';
}

$html .= '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->setChroot(__DIR__);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'รวมเล่ม_' . $selectedSportName . '_' . $yearName . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
