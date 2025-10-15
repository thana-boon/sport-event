<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo      = db();
$yearId   = active_year_id($pdo);
$yearName = active_year_name($pdo) ?? '';

// ===== Utilities (declare globally) =====
if (!function_exists('thai_date')) {
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
}
if (!function_exists('color_bg')) {
  function color_bg($color) {
    $map = [
      'แดง'   => '#ffcccc',
      'น้ำเงิน'=> '#cce0ff',
      'เขียว' => '#ccffcc',
      'เหลือง'=> '#fff6b3',
      'ชมพู'  => '#ffd6e7',
      'ม่วง'  => '#e6ccff',
      'ส้ม'   => '#ffe0b3',
      'ฟ้า'   => '#cceeff',
      'เทา'   => '#eeeeee',
      'ดำ'   => '#dddddd',
      'ขาว'  => '#ffffff',
    ];
    $color = trim((string)($color ?? ''));
    return $map[$color] ?? '#ffffff';
  }
}

if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// โหลดข้อมูลหัวรายงาน (ครั้งที่/วันเริ่ม/วันสิ้นสุด/ชื่อรายการ/โลโก้)
$meta = ['edition_no'=>'', 'start_date'=>null, 'end_date'=>null, 'title'=>'', 'logo_path'=>null];
$st = $pdo->prepare("SELECT edition_no,start_date,end_date,title,logo_path FROM competition_meta WHERE year_id=:y LIMIT 1");
$st->execute([':y'=>$yearId]);
if ($r = $st->fetch(PDO::FETCH_ASSOC)) $meta = $r;

// UI mode (no export)
if (!isset($_GET['export'])) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  ?>
  <main class="container py-4">
    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header bg-white"><h5 class="mb-0">ดาวน์โหลดรายงาน</h5></div>
          <div class="card-body">
            <p class="text-muted">รายงาน <strong>รายการการแข่งขัน</strong> — ปีการศึกษา: <strong><?php echo e($yearName); ?></strong></p>
            <a class="btn btn-success" href="<?php echo BASE_URL; ?>/reports_matches.php?export=1" target="_blank">รายการแข่งขัน (PDF)</a>
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/reports.php">ย้อนกลับ</a>
          </div>
        </div>
      </div>
    </div>
  </main>
  <?php
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ================= Export (PDF) Mode =================

// Sports (ordering to mirror booklet)
$sqlSports = "
  SELECT s.id, s.name, s.gender, s.participant_type, s.team_size, s.grade_levels,
         c.name AS category_name
    FROM sports s
    JOIN sport_categories c ON c.id = s.category_id
   WHERE s.year_id = :y
     AND s.is_active = 1
     AND c.name <> 'กรีฑา'
   ORDER BY s.name ASC, c.name ASC, s.participant_type ASC, s.gender ASC
";
$st = $pdo->prepare($sqlSports);
$st->execute([':y'=>$yearId]);
$sports = $st->fetchAll(PDO::FETCH_ASSOC);

// Matches by sport
function loadMatchesOfSport(PDO $pdo, int $yearId, int $sportId){
  $sql = "SELECT id, round_name, round_no, match_no, match_date, match_time, venue,
                 side_a_label, side_a_color, side_b_label, side_b_color,
                 winner, score_a, score_b, status
            FROM match_pairs
           WHERE year_id = :y AND sport_id = :s
           ORDER BY round_no ASC, match_no ASC";
  $st = $pdo->prepare($sql);
  $st->execute([':y'=>$yearId, ':s'=>$sportId]);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ===== HTML + CSS (match booklet style) =====
$html = '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8">';
$html .= '<style>
  @page { margin: 12mm 8mm 12mm 8mm; }
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
  body { font-family: "THSarabunNew", DejaVu Sans, sans-serif; font-size: 14pt; color:#222; }
  h1,h2,h3 { margin:0; }
  .header { display:flex; align-items:center; gap:12px; margin-bottom:2mm; }
  .header img.logo { height:38px; }
  .title { font-size: 18pt; font-weight: 700; }
  .subtitle { font-size: 12pt; color:#555; }
  hr { border:0; border-top:1px solid #bbb; margin:3mm 0 2mm 0; }
  .section { page-break-inside: avoid; margin-top: 3mm; }
  .sport-name { font-size: 16pt; font-weight:700; margin-bottom: 2px; }
  .muted { color:#666; font-size:11pt; }
  table { width:100%; border-collapse: collapse; margin-top: 2px; font-size: 13pt; }
  th, td { border: 1px solid #999; padding: 1.2mm 2.5px; vertical-align: middle; height: 2.8mm; }
  thead th { background:#f2f2f2; font-weight:700; text-align:center; }
  .nowrap { white-space:nowrap; }
  .cell-color { text-align:center; color:#000; }
  .round-label { font-weight:700; margin-top:1.5mm; font-size:14pt; }
  .small { font-size: 11pt; }
</style></head><body>';

// ฟังก์ชันหัวกระดาษ
function match_header($logoHtml, $meta, $yearName, $range) {
  $headLeft = '<div class="title">ตารางรายการแข่งขัน</div>';
  $headLeft .= '<div class="subtitle">';
  if (!empty($meta['title'])) { $headLeft .= e($meta['title']).' • '; }
  $headLeft .= 'ปีการศึกษา ' . e($yearName);
  if ($range) { $headLeft .= ' • ' . e($range); }
  if (!empty($meta['edition_no'])) { $headLeft .= ' • ครั้งที่ ' . e($meta['edition_no']); }
  $headLeft .= '</div>';
  return '<div class="header">'. $logoHtml .'<div>'.$headLeft.'</div></div><hr/>';
}

// เตรียมโลโก้
$logoHtml = '';
if (!empty($meta['logo_path'])) {
  $logo = $meta['logo_path'];
  $rel = ltrim($logo, '/');
  $logoHtml = '<img class="logo" src="'. e($rel) .'" />';
}
$range = '';
if (!empty($meta['start_date']) && !empty($meta['end_date'])) {
  $range = thai_date($meta['start_date']) . ' - ' . thai_date($meta['end_date']);
} elseif (!empty($meta['start_date'])) {
  $range = thai_date($meta['start_date']);
}

// ===== เรียงตามชื่อกีฬาหลัก (คำแรก) =====
$prevMainSport = null;
foreach($sports as $sp){
  // ดึงชื่อกีฬาหลัก (คำแรก)
  $mainSport = explode(' ', trim($sp['name']))[0];

  // ถ้าเป็นชื่อกีฬาหลักใหม่ ให้ขึ้นหน้าใหม่และใส่หัวกระดาษ
  if ($prevMainSport !== null && $mainSport !== $prevMainSport) {
    $html .= '<div class="page-break"></div>';
    $html .= match_header($logoHtml, $meta, $yearName, $range);
  }
  if ($prevMainSport === null) {
    $html .= match_header($logoHtml, $meta, $yearName, $range);
  }
  $prevMainSport = $mainSport;

  $html .= '<div class="section">';
  $html .= '<div class="sport-name">'. e($sp['name']) .'</div>';
  if (!empty($sp['grade_levels'])){
    $html .= '<div class="muted">ระดับชั้น: '. e($sp['grade_levels']) .'</div>';
  }

  $matches = loadMatchesOfSport($pdo, $yearId, (int)$sp['id']);
  if (!$matches) {
    $html .= '<div class="muted">ยังไม่มีการกำหนดคู่แข่งขัน</div>';
    $html .= '</div>';
    continue;
  }

  // Group by round_no
  $byRound = [];
  foreach($matches as $m){
    $key = (int)$m['round_no'];
    if (!isset($byRound[$key])) $byRound[$key] = ['name'=>$m['round_name'], 'items'=>[]];
    $byRound[$key]['items'][] = $m;
  }

  foreach ($byRound as $rno => $grp) {
    $rname = trim($grp['name']) !== '' ? $grp['name'] : ('รอบที่ '.$rno);
    $html .= '<div class="round-label">'. e($rname) .'</div>';
    $html .= '<table><thead><tr>
                <th style="width:10mm">คู่ที่</th>
                <th style="width:22mm">วัน/เวลา</th>
                <th>ทีม A</th>
                <th style="width:13mm">สี</th>
                <th class="small" style="width:13mm">ผล A</th>
                <th>ทีม B</th>
                <th style="width:13mm">สี</th>
                <th class="small" style="width:13mm">ผล B</th>
                <th style="width:18mm">สนาม</th>
              </tr></thead><tbody>';

    foreach ($grp['items'] as $m) {
      $dt = '';
      if (!empty($m['match_date'])) $dt .= thai_date($m['match_date']);
      if (!empty($m['match_time'])) $dt .= ($dt? ' ' : '') . substr($m['match_time'],0,5);
      $bgA = color_bg($m['side_a_color'] ?? '');
      $bgB = color_bg($m['side_b_color'] ?? '');

      $html .= '<tr>
        <td class="nowrap" style="text-align:center">'. e($m['match_no']) .'</td>
        <td class="nowrap" style="text-align:center">'. e($dt ?: '-') .'</td>
        <td>'. e($m['side_a_label']) .'</td>
        <td class="cell-color" style="background:'. $bgA .'">'. e($m['side_a_color'] ?? '-') .'</td>
        <td class="nowrap" style="text-align:center">'. e($m['score_a'] ?? '') .'</td>
        <td>'. e($m['side_b_label']) .'</td>
        <td class="cell-color" style="background:'. $bgB .'">'. e($m['side_b_color'] ?? '-') .'</td>
        <td class="nowrap" style="text-align:center">'. e($m['score_b'] ?? '') .'</td>
        <td class="nowrap" style="text-align:center">'. e($m['venue'] ?? '-') .'</td>
      </tr>';
    }
    $html .= '</tbody></table>';
  }

  // ช่องว่างสำหรับรอบถัดไป (ชิงที่ 3 / ชิงชนะเลิศ)
  $firstRoundNos = array_map(function($m){ return (int)$m['match_no']; },
                      array_values(array_filter($matches, fn($mm)=> (int)$mm['round_no'] === 1)));
  sort($firstRoundNos);
  $m1 = $firstRoundNos[0] ?? 1;
  $m2 = $firstRoundNos[1] ?? 2;

  $html .= '<div class="round-label" style="margin-top:2mm">รอบชิงที่ 3 (ช่องสำหรับกรอกภายหลัง)</div>';
  $html .= '<table><thead><tr>
              <th style="width:18mm">รายการ</th>
              <th>ทีม/สถานะ</th>
              <th style="width:13mm">สี</th>
              <th class="small" style="width:13mm">ผล</th>
              <th>ทีม/สถานะ</th>
              <th style="width:13mm">สี</th>
              <th class="small" style="width:13mm">ผล</th>
            </tr></thead><tbody>';
  $bg = color_bg('');
  $html .= '<tr>
    <td class="nowrap" style="text-align:center">ชิงที่ 3</td>
    <td>ผู้แพ้คู่ที่ '. e($m1) .'</td><td style="background:'.$bg.'"></td><td></td>
    <td>ผู้แพ้คู่ที่ '. e($m2) .'</td><td style="background:'.$bg.'"></td><td></td>
  </tr>';
  $html .= '</tbody></table>';

  $html .= '<div class="round-label" style="margin-top:1mm">รอบชิงชนะเลิศ (ช่องสำหรับกรอกภายหลัง)</div>';
  $html .= '<table><thead><tr>
              <th style="width:18mm">รายการ</th>
              <th>ทีม/สถานะ</th>
              <th style="width:13mm">สี</th>
              <th class="small" style="width:13mm">ผล</th>
              <th>ทีม/สถานะ</th>
              <th style="width:13mm">สี</th>
              <th class="small" style="width:13mm">ผล</th>
            </tr></thead><tbody>';
  $html .= '<tr>
    <td class="nowrap" style="text-align:center">ชิงชนะเลิศ</td>
    <td>ผู้ชนะคู่ที่ '. e($m1) .'</td><td style="background:'.$bg.'"></td><td></td>
    <td>ผู้ชนะคู่ที่ '. e($m2) .'</td><td style="background:'.$bg.'"></td><td></td>
  </tr>';
  $html .= '</tbody></table>';

  $html .= '</div>'; // .section
}

$html .= '</body></html>';

// ===== Dompdf =====
require_once __DIR__ . '/../vendor/autoload.php';
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'THSarabunNew'); // match booklet
$options->setChroot(__DIR__); // public/ (so assets/fonts/* is accessible)
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'รายการแข่งขัน_'.$yearName.'.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
