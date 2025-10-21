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
$yearName = active_year_name($pdo); // มีใน helpers.php เวอร์ชั่นล่าสุด; ถ้าไม่มี ให้เปลี่ยนเป็น title จาก academic_years

// ------------------------
// ACTION: export PDF
// ------------------------
$action = $_GET['action'] ?? null;
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

// ------------------------
// หน้า UI: ปุ่มเลือกสีเพื่อ export
// ------------------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<!-- เพิ่มสไตล์เล็กน้อยสำหรับจัดปุ่มให้สวย -->
<style>
  .report-actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
  .report-actions .colors { display:flex; gap:.5rem; flex-wrap:wrap; }
  .report-actions .colors .btn { min-width:110px; display:inline-flex; align-items:center; justify-content:center; gap:.5rem; font-weight:600; }
  .report-actions .btn-register { margin-left:auto; }
  /* สีแบบกำหนดเองให้คงความสด */
  .c-green { background:#10b981; border-color:#10b981; color:#fff; }
  .c-sky   { background:#0ea5e9; border-color:#0ea5e9; color:#fff; }
  .c-pink  { background:#f472b6; border-color:#f472b6; color:#fff; }
  .c-amber { background:#fb923c; border-color:#fb923c; color:#fff; }
  @media (max-width:576px){
    .report-actions .btn-register { width:100%; order:3; }
    .report-actions .colors { width:100%; order:1; justify-content:space-between; }
  }
</style>

<main class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">รายงาน &raquo; ใบเช็คชื่อนักกีฬา (แยกตามสี)</h5>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <p class="text-muted mb-3">ดาวน์โหลด PDF ขนาด A4 (หนึ่งหน้า = 1 ห้อง)</p>

      <!-- เรียงปุ่มเป็นกลุ่มเดียวกัน ดูเป็นระเบียบ -->
      <div class="report-actions mb-3">
        <div class="colors" role="group" aria-label="สี">
          <a class="btn c-green" href="?action=sheet&color=<?php echo urlencode('เขียว'); ?>">สีเขียว</a>
          <a class="btn c-sky"   href="?action=sheet&color=<?php echo urlencode('ฟ้า'); ?>">สีฟ้า</a>
          <a class="btn c-pink"  href="?action=sheet&color=<?php echo urlencode('ชมพู'); ?>">สีชมพู</a>
          <a class="btn c-amber" href="?action=sheet&color=<?php echo urlencode('ส้ม'); ?>">สีส้ม</a>
        </div>

        <a class="btn btn-outline-primary btn-register" href="<?= BASE_URL ?>/reports_export_registration.php">
          ใบลงทะเบียนกีฬา (PDF)
        </a>
      </div>

      <hr>

      <div class="small text-muted">
        ปีการศึกษา: <strong><?php echo htmlspecialchars($yearName ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php';
