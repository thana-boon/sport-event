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

$pdo     = db();
$yearId  = active_year_id($pdo);
$yearName = active_year_name($pdo) ?? '';

if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$meta = ['edition_no'=>'', 'start_date'=>null, 'end_date'=>null, 'title'=>'', 'logo_path'=>null];
$st = $pdo->prepare("SELECT edition_no,start_date,end_date,title,logo_path FROM competition_meta WHERE year_id=:y LIMIT 1");
$st->execute([':y'=>$yearId]);
if ($r = $st->fetch(PDO::FETCH_ASSOC)) $meta = $r;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_meta'])) {
  $edition = trim($_POST['edition_no'] ?? '');
  $start   = trim($_POST['start_date'] ?? '');
  $end     = trim($_POST['end_date'] ?? '');
  $title   = trim($_POST['title'] ?? '');
  $start = $start !== '' ? $start : null;
  $end   = $end   !== '' ? $end   : null;

  $chk = $pdo->prepare("SELECT 1 FROM competition_meta WHERE year_id=:y LIMIT 1");
  $chk->execute([':y'=>$yearId]);
  if ($chk->fetchColumn()) {
    $up = $pdo->prepare("UPDATE competition_meta
                         SET edition_no=:ed, start_date=:sd, end_date=:edate, title=:tt
                         WHERE year_id=:y");
    $up->execute([':ed'=>$edition, ':sd'=>$start, ':edate'=>$end, ':tt'=>$title, ':y'=>$yearId]);
  } else {
    $ins = $pdo->prepare("INSERT INTO competition_meta(year_id, edition_no, start_date, end_date, title)
                          VALUES(:y,:ed,:sd,:edate,:tt)");
    $ins->execute([':y'=>$yearId, ':ed'=>$edition, ':sd'=>$start, ':edate'=>$end, ':tt'=>$title]);
  }
  $_SESSION['flash'] = 'บันทึกข้อมูลการแข่งขันแล้ว';
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

if (!isset($_GET['export'])) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  ?>
  <main class="container py-4">
    <?php if (!empty($_SESSION['flash'])): ?><div class="alert alert-success"><?php echo e($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-white"><h5 class="mb-0">กำหนดข้อมูลการแข่งขัน</h5></div>
          <div class="card-body">
            <form method="post" class="row g-3">
              <div class="col-12">
                <label class="form-label">ชื่อรายการ (ถ้ามี)</label>
                <input type="text" name="title" class="form-control" value="<?php echo e($meta['title'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">ครั้งที่</label>
                <input type="text" name="edition_no" class="form-control" value="<?php echo e($meta['edition_no'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">วันเริ่ม</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo e($meta['start_date'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">วันสิ้นสุด</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo e($meta['end_date'] ?? ''); ?>">
              </div>
              <div class="col-12 d-flex gap-2">
                <button type="submit" name="save_meta" class="btn btn-primary">บันทึก</button>
                <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/reports.php">ย้อนกลับ</a>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header bg-white"><h5 class="mb-0">ดาวน์โหลดรายงาน</h5></div>
          <div class="card-body">
            <p class="text-muted">สูจิบัตรรายชื่อนักกีฬา — ปีการศึกษา: <strong><?php echo e($yearName); ?></strong></p>
            <div class="d-flex gap-2">
              <a class="btn btn-success" href="<?php echo BASE_URL; ?>/reports_booklet.php?export=1" target="_blank">สูจิบัตรรายชื่อ</a>
              <a class="btn btn-info" href="<?php echo BASE_URL; ?>/reports_matches.php?export=1" target="_blank">รายการแข่งขัน</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white"><h5 class="mb-0">อัปโหลดโลโก้หัวรายงาน</h5></div>
          <div class="card-body">
            <?php if (!empty($meta['logo_path'])): ?>
              <div class="mb-3">
                <div class="text-muted small mb-1">โลโก้ปัจจุบัน:</div>
                <img src="<?php echo BASE_URL . '/' . e($meta['logo_path']); ?>" alt="Logo" style="max-width: 200px; height:auto;">
              </div>
            <?php endif; ?>
            <form method="post" action="<?php echo BASE_URL; ?>/upload_logo.php" enctype="multipart/form-data">
              <input type="file" name="logo" class="form-control mb-2" accept=".png,.jpg,.jpeg,.webp,.svg" required>
              <button type="submit" class="btn btn-outline-primary">อัปโหลดโลโก้</button>
              <div class="form-text">รองรับ: PNG, JPG, WEBP, SVG</div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>
  <?php
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ===== Export mode =====
// ดึงข้อมูลกีฬา เรียงตามชื่อกีฬา
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

function loadPlayersOfSport(PDO $pdo,int $yearId,int $sportId){
  $sql = "SELECT r.color, st.student_code, st.first_name, st.last_name,
                 st.class_level, st.class_room, st.number_in_room
            FROM registrations r
            JOIN students st ON st.id=r.student_id
           WHERE r.year_id=:y AND r.sport_id=:s
           ORDER BY st.class_level, st.class_room, st.number_in_room, st.student_code";
  $q = $pdo->prepare($sql);
  $q->execute([':y'=>$yearId, ':s'=>$sportId]);
  return $q->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันแปลงชื่อสีเป็นรหัสสี
function color_bg($color) {
  $map = [
    'เขียว' => '#43a047',
    'ฟ้า'   => '#1976d2',
    'ชมพู'  => '#e91e63',
    'ส้ม'   => '#fb8c00',
    'เหลือง'=> '#fbc02d',
    'ม่วง'  => '#8e24aa',
    'แดง'   => '#d32f2f',
    // เพิ่มสีอื่นๆ ตามต้องการ
  ];
  return $map[trim($color)] ?? '#888';
}

$edition  = trim($meta['edition_no'] ?? '');
$rangeStr = '';
if (!empty($meta['start_date']) && !empty($meta['end_date'])) {
  $rangeStr = sprintf('วันที่ %s ถึง %s',
    thai_date($meta['start_date']),
    thai_date($meta['end_date'])
  );
}
$logoHtml = '';
if (!empty($meta['logo_path'])) {
  $logoHtml = '<img src="uploads/logo/' . htmlspecialchars(basename($meta['logo_path']), ENT_QUOTES, 'UTF-8') . '" style="height:22mm; width:auto;">';
}

// เพิ่ม CSS สำหรับ page break
$css = '
  @page { size: A4 portrait; margin: 14mm 12mm 16mm 12mm; }
  .page-break { page-break-before: always; }
  @font-face { font-family:"THSarabunNew";
               src: url("assets/fonts/THSarabunNew.ttf") format("truetype"); }
  @font-face { font-family:"THSarabunNew";
               src: url("assets/fonts/THSarabunNew-Bold.ttf") format("truetype");
               font-weight:bold; }
  body{ font-family:"THSarabunNew", DejaVu Sans, sans-serif; font-size:14pt; line-height:1.1; }
  h1{ font-size:18pt; margin:0; text-align:center; font-weight:bold; }
  .meta{ text-align:center; margin-top:2mm; color:#444; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ border:1px solid #000; padding:0.2mm 1.5px; vertical-align:middle; height: 3.6mm; }
  th{ text-align:center; font-weight:bold; background:#f7f7f7; }
  .sport-head{ margin-top:10mm; font-size:14pt; font-weight:bold; }
  .small{ font-size:10pt; color:#666; }
  .nowrap{ white-space:nowrap; }
  .header-table td { border:none; }
  .cell-color { color: #fff; font-weight: bold; text-align: center; }
';

// ฟังก์ชันสร้างหัวกระดาษ
function report_header($logoHtml, $yearName, $meta, $edition, $rangeStr) {
  $h = '<table class="header-table" width="100%"><tr>';
  $h .= '<td style="width:25mm; text-align:left; vertical-align:middle;">'. $logoHtml .'</td>';
  $h .= '<td style="text-align:center; vertical-align:middle;">'
      .  '<h1>สูจิบัตรรายชื่อนักกีฬา</h1>'
      .  '<div class="meta">'. e($yearName);
  if (($meta['title'] ?? '') !== '') { $h .= ' • '. e($meta['title']); }
  if ($edition!=='') { $h .= ' • ครั้งที่ '. e($edition); }
  if ($rangeStr!=='') { $h .= ' • '. e($rangeStr); }
  $h .= '</div></td>';
  $h .= '<td style="width:25mm;"></td>';
  $h .= '</tr></table>';
  return $h;
}

$html = '<!doctype html><html><head><meta charset="utf-8"><style>'.$css.'</style></head><body>';
$html .= report_header($logoHtml, $yearName, $meta, $edition, $rangeStr);

if (!$sports){
  $html .= '<p class="small">ยังไม่มีกีฬาที่เปิดใช้งาน</p>';
}else{
  $prevMainSport = null;
  foreach($sports as $sp){
    // ดึงชื่อกีฬาหลัก (คำแรก)
    $mainSport = explode(' ', trim($sp['name']))[0];

    // ถ้าเป็นชื่อกีฬาหลักใหม่ ให้ขึ้นหน้าใหม่และใส่หัวกระดาษ
    if ($prevMainSport !== null && $mainSport !== $prevMainSport) {
      $html .= '<div class="page-break"></div>';
      $html .= report_header($logoHtml, $yearName, $meta, $edition, $rangeStr);
    }
    $prevMainSport = $mainSport;

    $players = loadPlayersOfSport($pdo,$yearId,(int)$sp['id']);
    $html .= '<div class="sport-head">'. e($sp['name']) .' — '. e($sp['participant_type'])
          .' • หมวด: '. e($sp['category_name']) .' • เพศ: '. e($sp['gender']) .'</div>';
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
    if (!$players){
      $html .= '<tr><td colspan="7" style="text-align:center">ยังไม่มีผู้ลงทะเบียน</td></tr>';
    }else{
      $i=1;
      foreach($players as $p){
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
    }
    $html .= '</tbody></table>';
  }
}
$html .= '</body></html>';

require_once __DIR__ . '/../vendor/autoload.php';
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->setChroot(__DIR__);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'สูจิบัตรรายชื่อนักกีฬา_'.$yearName.'.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;

function thai_date($date) {
  $months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
  ];
  $ts = strtotime($date);
  $d = (int)date('j', $ts);
  $m = (int)date('n', $ts);
  $y = (int)date('Y', $ts) + 543;
  return "{$d} {$months[$m]} {$y}";
}
