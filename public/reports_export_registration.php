<?php
// /public/reports_export_registration.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
                 DB_USER, DB_PASS, [
                   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                 ]);
}

/** ปีการศึกษาปัจจุบัน (ถ้ามี) */
$yearClause = '';
try {
  $yr = $pdo->query("SELECT id, year_be FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch();
  if ($yr) $yearClause = " AND s.year_id = ".(int)$yr['id']." ";
} catch (Throwable $e) {}

/** กีฬา + หมวด */
$sql = "SELECT s.id, s.name, s.gender, s.participant_type, s.team_size, s.grade_levels,
               c.name AS category_name
        FROM sports s
        JOIN sport_categories c ON c.id = s.category_id
        WHERE s.is_active = 1 {$yearClause}
        ORDER BY c.name ASC, s.name ASC";
$sports = $pdo->query($sql)->fetchAll();

/** แยกระดับชั้น (ป.=ประถม, ม.=มัธยม) */
$levelCode = function($txt){
  $t = trim((string)$txt);
  if ($t !== '' && mb_substr($t,0,1)==='ป') return 'P';
  if ($t !== '' && mb_substr($t,0,1)==='ม') return 'S';
  return '';
};

$grouped = ['P'=>[], 'S'=>[]];
foreach ($sports as $sp) {
  $lv = $levelCode($sp['grade_levels']);
  $grouped[$lv==='S' ? 'S' : 'P'][] = $sp;
}

/** สร้าง HTML (คอลัมน์เดียว) ใช้ฟอนต์ Sarabun */
ob_start();
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ใบลงทะเบียนกีฬา</title>
<!-- โหลดฟอนต์ Sarabun (กรณี fallback HTML) -->
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *{ box-sizing:border-box; }
  html,body{ height:100%; }
  body{ font-family:"Sarabun", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color:#0f172a; margin:0; }
  .wrap{ padding:24px; }
  h1{ margin:0 0 6px 0; font-weight:700; color:#0ea5e9; }
  h2{ margin:0 0 18px 0; font-weight:500; color:#334155; }
  .section-title{ margin:28px 0 10px; padding-top:12px; border-top:2px solid #e2e8f0; font-size:18px; color:#0f172a; }
  .sport-card{ page-break-inside:avoid; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin:10px 0; }
  .sport-head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px; }
  .sport-name{ font-weight:600; }
  .sport-meta{ color:#64748b; font-size:13px; white-space:nowrap; }
  .grid{ width:100%; border-collapse:collapse; }
  .grid th, .grid td{ border:1px solid #cbd5e1; padding:6px 8px; font-size:14px; vertical-align:top; }
  .grid th{ background:#f1f5f9; text-align:center; font-weight:600; }
  .slot{ height:26px; border-bottom:1px dashed #e2e8f0; }
  .slot:last-child{ border-bottom:none; }
  .muted{ color:#64748b; }

  /* new: page break helpers for print/pdf */
  .page-break { page-break-before: always; break-before: page; }
</style>
</head>
<body>
<div class="wrap">
  <h1>ใบลงทะเบียนนักกีฬา</h1>

  <?php
  $sections = ['P'=>'ระดับประถม', 'S'=>'ระดับมัธยม'];
  $firstSection = true;
  foreach ($sections as $key => $title):
    if (empty($grouped[$key])) continue;

    // ถ้าไม่ใช่ section แรก ให้ขึ้นหน้าใหม่
    if (!$firstSection) {
      echo '<div class="page-break"></div>';
    }
    $firstSection = false;
  ?>
    <div class="section-title"><?= htmlspecialchars($title) ?></div>

    <?php
    // group by category
    $byCat = [];
    foreach ($grouped[$key] as $sp) {
      $byCat[$sp['category_name']][] = $sp;
    }
    ksort($byCat, SORT_NATURAL);

    $firstCat = true;
    foreach ($byCat as $catName => $items):
      // ถ้าไม่ใช่ category แรกใน section นี้ ให้ขึ้นหน้าใหม่
      if (!$firstCat) {
        echo '<div class="page-break"></div>';
      }
      $firstCat = false;
    ?>
      <div class="muted" style="margin:8px 0 6px;"><?= htmlspecialchars($catName) ?></div>

      <?php foreach ($items as $sp):
        $slots = max(1, (int)$sp['team_size']);
      ?>
        <div class="sport-card">
          <div class="sport-head">
            <div class="sport-name"><?= htmlspecialchars($sp['name']) ?></div>
            <div class="sport-meta">
              ประเภท: <?= $sp['participant_type']==='ทีม'?'ทีม':'เดี่ยว' ?> |
              จำนวนช่อง: <?= $slots ?> |
              ชั้นที่เปิด: <?= htmlspecialchars($sp['grade_levels'] ?: '-') ?>
            </div>
          </div>
          <table class="grid">
            <thead><tr><th>ลงชื่อ</th></tr></thead>
            <tbody><tr><td>
              <?php for($i=1; $i<=$slots; $i++): ?>
                <div class="slot"><?= $i ?>.&nbsp;</div>
              <?php endfor; ?>
            </td></tr></tbody>
          </table>
        </div>
      <?php endforeach; // items ?>
    <?php endforeach; // byCat ?>
  <?php endforeach; // sections ?>
</div>
</body>
</html>
<?php
$html = ob_get_clean();

/**
 * ดาวน์โหลดเป็น PDF ถ้ามี Dompdf (พร้อมฟอนต์ Sarabun)
 * หมายเหตุ: Dompdf โหลดฟอนต์จาก remote ได้เมื่อ isRemoteEnabled=true
 * และเรากำหนด @font-face ให้ใช้ Sarabun จาก Google Fonts
 */
$pdfDone = false;
try {
  $autoloader = __DIR__ . '/../vendor/autoload.php';
  if (file_exists($autoloader)) {
    require_once $autoloader;

    $cssFont = "@font-face{
      font-family:'Sarabun';
      font-style: normal;
      font-weight: 400;
      src: url('https://fonts.gstatic.com/s/sarabun/v13/DtVjJx26TKEr37c9Qp9DkA.ttf') format('truetype');
    }
    @font-face{
      font-family:'Sarabun';
      font-style: normal;
      font-weight: 600;
      src: url('https://fonts.gstatic.com/s/sarabun/v13/DtVjJx26TKEr37c9SpFDkA.ttf') format('truetype');
    }
    body{ font-family:'Sarabun', sans-serif; }";

    if (class_exists('\\Dompdf\\Dompdf')) {
      $opts = new Dompdf\Options();
      $opts->set('isRemoteEnabled', true);      // อนุญาตโหลดฟอนต์จาก Google
      $opts->set('defaultFont', 'Sarabun');
      $dompdf = new Dompdf\Dompdf($opts);

      // ฉีด CSS ฟอนต์เพิ่มให้แน่ใจว่าใช้ Sarabun
      $htmlForPdf = preg_replace(
        '#</head>#i',
        "<style>$cssFont</style></head>",
        $html,
        1
      );

      $dompdf->loadHtml($htmlForPdf, 'UTF-8');
      $dompdf->setPaper('A4', 'portrait');
      $dompdf->render();

      // ดาวน์โหลดทันที
      $dompdf->stream("registration-sheets.pdf", ["Attachment" => true]);
      $pdfDone = true;
      exit;
    }
  }
} catch (Throwable $e) {
  // ถ้าทำ PDF ไม่สำเร็จ จะ fallback เป็นดาวน์โหลด HTML ด้านล่าง
}

/** Fallback: ดาวน์โหลดเป็น HTML (ยังคงใช้ Sarabun จาก Google) */
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="registration-sheets.html"');
echo $html;
