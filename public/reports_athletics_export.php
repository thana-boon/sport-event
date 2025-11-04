<?php
// Export สูจิบัตรกรีฑา (A4 แนวตั้ง) — รองรับทีม: แสดงครบตาม team_size ต่อ 1 ลู่

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = db();
$yearId   = active_year_id($pdo);
$yearName = function_exists('active_year_name') ? active_year_name($pdo) : 'ปีการศึกษา';

// คำนวณครั้งที่จาก พ.ศ.
$currentYearBE = (int)date('Y') + 543;
$gameNumber = $currentYearBE - 2552;

// โลโก้ล่าสุด
$logoPath = null;
$logoDir = __DIR__ . '/uploads/logo';
if (is_dir($logoDir)) {
  $files = glob($logoDir.'/*.{png,jpg,jpeg}', GLOB_BRACE);
  if ($files) { usort($files, fn($a,$b)=>filemtime($b)-filemtime($a)); $logoPath=$files[0]; }
}

$colorMap = ['เขียว'=>'#4CAF50','ฟ้า'=>'#2196F3','ชมพู'=>'#E91E63','ส้ม'=>'#FF9800'];

// events (เรียงตามรหัส)
$sqlEv = "
  SELECT
    ae.id, ae.event_code, ae.sport_id, ae.best_student_id, ae.best_time, ae.best_year_be, ae.notes,
    s.name AS sport_name, s.gender, s.participant_type, s.grade_levels, s.team_size,
    CONCAT(st.first_name,' ',st.last_name) AS best_student_name
  FROM athletics_events ae
  LEFT JOIN sports s    ON s.id = ae.sport_id
  LEFT JOIN students st ON st.id = ae.best_student_id
  WHERE ae.year_id = :y
  ORDER BY 
    CASE WHEN ae.event_code REGEXP '^[0-9]+$' THEN CAST(ae.event_code AS UNSIGNED) ELSE 999999 END,
    ae.event_code
";
$qEv = $pdo->prepare($sqlEv);
$qEv->execute([':y'=>$yearId]);
$evs = $qEv->fetchAll(PDO::FETCH_ASSOC);

// ดึงฮีต
$getHeats = function(PDO $pdo, int $yearId, int $sportId){
  $q=$pdo->prepare("SELECT id,heat_no,lanes_used FROM track_heats WHERE year_id=:y AND sport_id=:s ORDER BY heat_no");
  $q->execute([':y'=>$yearId, ':s'=>$sportId]);
  return $q->fetchAll(PDO::FETCH_ASSOC);
};

// lane assignments (เฉพาะข้อมูล lane/สี + ผูกทะเบียนถ้ามี)
$getLanesRaw = function(PDO $pdo, int $heatId){
  $sql = "
    SELECT la.lane_no, la.color, la.registration_id,
           r.id AS reg_id, r.student_id,
           st.student_code, st.first_name, st.last_name, st.class_level, st.class_room, st.number_in_room
    FROM track_lane_assignments la
    LEFT JOIN registrations r ON r.id = la.registration_id
    LEFT JOIN students st ON st.id = r.student_id
    WHERE la.heat_id = :hid
    ORDER BY la.lane_no
  ";
  $q=$pdo->prepare($sql); $q->execute([':hid'=>$heatId]);
  $rows=$q->fetchAll(PDO::FETCH_ASSOC);
  $out=[]; foreach($rows as $r){ $out[(int)$r['lane_no']]=$r; } return $out;
};

// pool: รายชื่อผู้ลงทะเบียนตาม "สี" ของกีฬานี้ (เพื่อเติมให้ครบ team_size)
$getRegPools = function(PDO $pdo, int $yearId, int $sportId){
  $sql="
    SELECT r.id AS reg_id, r.color,
           st.student_code, st.first_name, st.last_name, st.class_level, st.class_room, st.number_in_room
    FROM registrations r
    JOIN students st ON st.id = r.student_id
    WHERE r.year_id=:y AND r.sport_id=:s
    ORDER BY r.registered_at, r.id
  ";
  $q=$pdo->prepare($sql); $q->execute([':y'=>$yearId, ':s'=>$sportId]);
  $pools=[];
  foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
    $c = $row['color'] ?? '';
    if ($c==='') continue;
    if (!isset($pools[$c])) $pools[$c]=[];
    $pools[$c][]=$row;
  }
  return $pools;
};

$bestText = function(array $r): string {
  $time = formatTime($r['best_time'] ?? '');
  $year = trim((string)($r['best_year_be'] ?? ''));
  
  if (!empty($r['best_student_id']) && !empty($r['best_student_name'])) {
    $name = htmlspecialchars($r['best_student_name'], ENT_QUOTES, 'UTF-8');
    if ($time !== '—' && $year !== '') {
      return "{$name} ({$time}, ปีการศึกษา {$year})";
    } elseif ($time !== '—') {
      return "{$name} ({$time})";
    } else {
      return $name;
    }
  }
  if (!empty($r['notes'])) {
    $notes = htmlspecialchars($r['notes'], ENT_QUOTES, 'UTF-8');
    if ($time !== '—' && $year !== '') {
      return "{$notes} ({$time}, ปีการศึกษา {$year})";
    } elseif ($time !== '—') {
      return "{$notes} ({$time})";
    } else {
      return $notes;
    }
  }
  return '—';
};

function formatTime($seconds) {
  if ($seconds === null || $seconds === '') return '—';
  $sec = (float)$seconds;
  
  if ($sec < 60) {
    // น้อยกว่า 60 วินาที → แสดงเป็นวินาที
    return number_format($sec, 2, '.', '') . ' วินาที';
  } else {
    // 60 วินาทีขึ้นไป → แปลงเป็นนาที:วินาที
    $minutes = floor($sec / 60);
    $remainSec = $sec - ($minutes * 60);
    return $minutes . ':' . number_format($remainSec, 2, '.', '') . ' นาที';
  }
}

ob_start();
?>
<!doctype html>
<html lang="th"><head>
<meta charset="utf-8">
<title>สูจิบัตรกรีฑา - <?=$yearName?></title>
<style>
  @font-face { font-family:'THSarabunNew'; src:url('assets/fonts/THSarabunNew.ttf') format('truetype'); }
  @font-face { font-family:'THSarabunNew'; src:url('assets/fonts/THSarabunNew-Bold.ttf') format('truetype'); font-weight:bold; }

  /* ขนาดตัวอักษรหลัก */
  body{ font-family:'THSarabunNew', DejaVu Sans, sans-serif; font-size:14pt; line-height:1; color:#222; }

  .header{ text-align:center; margin-bottom:6px; }
  .logo{ height:54px; margin:0 auto 6px; display:inline-block; }
  h1{ font-size:20pt; margin:0; text-align:center; }
  h2{ font-size:12.5pt; margin:2px 0 6px 0; text-align:center; color:#555; }
  .event-card{ page-break-inside:avoid; margin-bottom:10px; }
  .title-row{ display:flex; align-items:baseline; gap:8px; margin-bottom:4px; justify-content:flex-start; flex-wrap:wrap; }
  .event-title{ font-size:14pt; font-weight:bold; display:inline-block; vertical-align:middle; }
  .muted{ color:#666; font-size:12pt; display:inline-block; vertical-align:middle; margin-left:8px; }
  .best-stat{ color:#000; font-weight:700; font-size:14pt; display:inline-block; vertical-align:middle; margin-left:8px; }

  /* ตาราง: ปรับให้ช่องสูงใกล้เคียงตัวอักษรที่สุด */
  table{ width:100%; border-collapse:collapse; font-size:14pt; }
  thead th{ background:#f6f6f6; font-weight:700; text-align:center; padding:0.08em 0.18em; }
  th, td{
    border:1px solid #000;
    padding:0.08em 0.18em;    /* very small padding -> ชิดตัวอักษร */
    vertical-align:middle;
    line-height:1;            /* ทำให้ความสูงสัมพันธ์กับ font-size */
    height:1.05em;           /* ให้แถวสูงเท่ากับขนาดตัวอักษร (เล็กน้อยเผื่อเส้น) */
    font-size:14pt;
  }

  /* แถบสี: เล็กลงให้ไม่กินพื้นที่แนวตั้งมาก */
  .color-badge{
    display:inline-block;
    padding:0.06em 0.45em;
    border-radius:5px;
    color:#ffffff;
    font-weight:700;
    font-size:12pt;   /* เล็กกว่าตัวอักษรหลักเล็กน้อย */
    line-height:1;
    min-width:30px;
    text-align:center;
    box-shadow: inset 0 -1px 0 rgba(0,0,0,0.06);
  }
  .color-col{ text-align:center; }

  .lane-col{ width:34px; text-align:center; }
  .class-col{ width:76px; text-align:center; }
  .no-col{ width:50px; text-align:center; }
  .pos-col{ width:54px; text-align:center; }
  .time-col{ width:70px; text-align:center; }
  .name-col{ width:auto; }

  /* บรรทัดย่อย: ถ้ามีหลายคนในลู่ ให้ใช้บรรทัดชิดกัน */
  .name-col.small { font-size:14pt; line-height:1; }

  .page-break{ page-break-after:always; }
  .small{ font-size:12pt; }
</style>
</head><body>
  <div class="header">
    <?php if($GLOBALS['logoPath'] && file_exists($GLOBALS['logoPath'])): ?>
      <img class="logo" src="<?= 'uploads/logo/' . basename($GLOBALS['logoPath']) ?>" alt="logo">
    <?php else: ?><div class="logo" style="font-weight:bold;">LOGO</div><?php endif; ?>
    <h1>สูจิบัตรกรีฑา</h1>
    <h2>กีฬาราชพฤกษ์เกมส์ ครั้งที่ <?=$gameNumber?> <?=$yearName?></h2>
  </div>
  
  <?php $cnt=0;
  foreach($evs as $ev):
    $cnt++;
    $teamSize = max(1, (int)($ev['team_size'] ?? 1));
    $heats = $getHeats($pdo,$yearId,(int)$ev['sport_id']);
    $pools = $getRegPools($pdo,$yearId,(int)$ev['sport_id']);
    $title = ($ev['event_code']? htmlspecialchars($ev['event_code']) . ' — ' : '') . htmlspecialchars($ev['sport_name'] ?? '-');
    $subtitle = htmlspecialchars(($ev['gender'] ?? '') . ' • ' . ($ev['participant_type'] ?? '') . ' • ' . ($ev['grade_levels'] ?? ''));
    $best = $bestText($ev);
  ?>
  <div class="event-card">
    <div class="title-row">
      <div class="event-title"><?=$title?></div>
      <?php if(!empty($best)): ?>
        <div class="best-stat">สถิติดีที่สุด: <?=$best?></div>
      <?php endif; ?>
    </div>
  
    <?php if(!$heats): ?>
      <div class="muted">ยังไม่ได้กำหนดฮีต/ลู่ สำหรับกีฬานี้</div>
    <?php else: foreach($heats as $h):
      $lanesMap = $getLanesRaw($pdo,(int)$h['id']);
      $lanesUsed=(int)$h['lanes_used'];
    ?>
      <table>
        <thead>
          <tr>
            <th class="lane-col">ลู่</th>
            <th class="color-col">สี</th>
            <th class="name-col">ชื่อ - นามสกุล</th>
            <th class="class-col">ชั้น</th>
            <th class="no-col">เลขที่</th>
            <th class="pos-col">อันดับ</th>
            <th class="time-col">เวลา</th>
          </tr>
        </thead>
        <tbody>
        <?php
        for($lane=1; $lane<=$lanesUsed; $lane++):
          $row = $lanesMap[$lane] ?? null;
          $color = $row['color'] ?? '';
          $bg = $colorMap[$color] ?? '#999';

          // เก็บรายชื่อของลู่นี้หลายคนถ้าเป็นทีม
          $members = [];

          // 1) ถ้าลู่นี้มี registration_id แล้ว → ใส่คนนั้นก่อน
          if ($row && !empty($row['first_name'])) {
            $members[] = [
              'first_name'=>$row['first_name'],
              'last_name'=>$row['last_name'],
              'class_level'=>$row['class_level'],
              'class_room'=>$row['class_room'],
              'number_in_room'=>$row['number_in_room'],
            ];
          }

          // 2) ถ้าต้องการมากกว่า 1 คน (ทีม) → เติมจาก pool สีเดียวกัน ให้ครบ team_size
          $need = $teamSize - count($members);
          if ($need > 0 && $color !== '' && !empty($pools[$color])) {
            while($need > 0 && !empty($pools[$color])) {
              $pick = array_shift($pools[$color]);
              // กันซ้ำบุคคลเดียวกับข้อ 1)
              if ($row && !empty($row['first_name']) &&
                  $pick['first_name']===$row['first_name'] &&
                  $pick['last_name']===$row['last_name']) {
                continue;
              }
              $members[] = [
                'first_name'=>$pick['first_name'],
                'last_name'=>$pick['last_name'],
                'class_level'=>$pick['class_level'],
                'class_room'=>$pick['class_room'],
                'number_in_room'=>$pick['number_in_room'],
              ];
              $need--;
            }
          }

          // ทำข้อความหลายบรรทัด — แยก "ชื่อ", "ชั้น", "เลขที่" คนละคอลัมน์
          $nameCell = $classCell = $noCell = '&nbsp;';

          if (count($members) > 0) {
            $nameLines  = [];
            $classLines = [];
            $noLines    = [];

            foreach ($members as $m) {
              // ชื่อ
              $nameLines[] = htmlspecialchars(
                trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')),
                ENT_QUOTES, 'UTF-8'
              );

              // ชั้น (เช่น ป3/2)
              $cls = trim(
                (string)($m['class_level'] ?? '') .
                (($m['class_room'] ?? '') !== '' ? '/' . $m['class_room'] : '')
              );
              $classLines[] = $cls !== '' ? htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') : '&nbsp;';

              // เลขที่
              $no  = trim((string)($m['number_in_room'] ?? ''));
              $noLines[] = $no !== '' ? htmlspecialchars($no, ENT_QUOTES, 'UTF-8') : '&nbsp;';
            }

            $nameCell  = implode('<br>', $nameLines);
            $classCell = implode('<br>', $classLines);
            $noCell    = implode('<br>', $noLines);
          }

        ?>
          <tr>
            <td class="lane-col"><?=$lane?></td>
            <td class="color-col">
              <?= $color ? '<div class="color-badge" style="background:'.htmlspecialchars($bg,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($color,ENT_QUOTES,'UTF-8').'</div>' : '' ?>
            </td>
            <td class="name-col small"><?=$nameCell?></td>
            <td class="class-col"><?=$classCell?></td>
            <td class="no-col"><?=$noCell?></td>
            <td class="pos-col">&nbsp;</td>
            <td class="time-col">&nbsp;</td>
          </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    <?php endforeach; endif; ?>
  </div>

  <?php if($cnt%3===0): ?><div class="page-break"></div><?php endif; ?>
  <?php endforeach; ?>
</body></html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->setChroot(__DIR__ . '/');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4','portrait');
$dompdf->loadHtml($html,'UTF-8');
$dompdf->render();

$filename = 'สูจิบัตรกรีฑา_'.date('Ymd_His').'.pdf';
$dompdf->stream($filename, ['Attachment'=> isset($_GET['download']) ? true : false]);
