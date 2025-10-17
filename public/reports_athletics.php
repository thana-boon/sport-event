<?php
// reports_athletics.php — จัดการรหัส “รายการกรีฑา” + สถิติ + Import จากปีก่อน + Export PDF

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$pdo = db();
$yearId = active_year_id($pdo);
$yearName = function_exists('active_year_name') ? active_year_name($pdo) : 'ปีการศึกษา';

if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------------------- utils ----------------------
function prev_year_id(PDO $pdo, int $currentId): ?int {
  // เอาปีที่ id < ปัจจุบัน และใกล้ที่สุด
  $q = $pdo->prepare("SELECT MAX(id) FROM academic_years WHERE id < :c");
  $q->execute([':c'=>$currentId]);
  $id = $q->fetchColumn();
  return $id ? (int)$id : null;
}

function sport_key(array $r): string {
  // ใช้จับคู่ชนิดกีฬา: ชื่อ+เพศ+ประเภท+ระดับชั้น (หมวดกรีฑาอยู่แล้วจาก SQL)
  return trim(($r['name']??'')).'|'.trim(($r['gender']??'')).'|'.trim(($r['participant_type']??'')).'|'.trim(($r['grade_levels']??''));
}

function resolve_student_id(PDO $pdo, int $yearId, string $typedName): ?int {
  $typedName = trim($typedName);
  if ($typedName === '') return null;
  $sql = "SELECT id FROM students WHERE year_id=:y AND CONCAT(first_name,' ',last_name)=:n LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([':y'=>$yearId, ':n'=>$typedName]);
  $id = $st->fetchColumn();
  return $id ? (int)$id : null;
}

// ---------------------- sports เฉพาะกรีฑา ----------------------
$sqlSports = "
  SELECT s.id, s.name, s.gender, s.participant_type, s.grade_levels
  FROM sports s
  JOIN sport_categories c ON c.id = s.category_id
  WHERE s.year_id = :y AND s.is_active = 1 AND c.name = 'กรีฑา'
  ORDER BY s.gender, s.participant_type, s.name
";
$stmSports = $pdo->prepare($sqlSports);
$stmSports->execute([':y'=>$yearId]);
$sports = $stmSports->fetchAll(PDO::FETCH_ASSOC);

// ---------------------- Import from previous year ----------------------
if (isset($_GET['import']) && $_GET['import'] === 'prev') {
  $prevId = prev_year_id($pdo, $yearId);
  if (!$prevId) {
    $_SESSION['flash'] = ['type'=>'warning','msg'=>'ไม่พบปีการศึกษาก่อนหน้า'];
    header('Location: ' . BASE_URL . '/reports_athletics.php'); exit;
  }

  // กีฬาในปีปัจจุบัน -> map ด้วย key
  $cur = $pdo->prepare("
    SELECT s.id, s.name, s.gender, s.participant_type, s.grade_levels
    FROM sports s JOIN sport_categories c ON c.id=s.category_id
    WHERE s.year_id=:y AND c.name='กรีฑา'
  ");
  $cur->execute([':y'=>$yearId]);
  $curMap = [];
  foreach($cur->fetchAll(PDO::FETCH_ASSOC) as $r){ $curMap[sport_key($r)] = (int)$r['id']; }

  // ดึงรายการปีที่แล้ว + ติดชื่อคนทำสถิติไว้ใน notes
  $prev = $pdo->prepare("
    SELECT ae.event_code, ae.sport_id, ae.best_student_id, ae.best_time, ae.best_year_be, ae.notes,
           s.name, s.gender, s.participant_type, s.grade_levels,
           CONCAT(st.first_name,' ',st.last_name) AS best_student_name
    FROM athletics_events ae
    JOIN sports s ON s.id = ae.sport_id
    LEFT JOIN students st ON st.id = ae.best_student_id
    JOIN sport_categories c ON c.id=s.category_id
    WHERE ae.year_id=:p AND c.name='กรีฑา'
    ORDER BY ae.id
  ");
  $prev->execute([':p'=>$prevId]);
  $rows = $prev->fetchAll(PDO::FETCH_ASSOC);

  $ins = $pdo->prepare("
    INSERT INTO athletics_events (year_id, event_code, sport_id, best_student_id, best_time, best_year_be, notes)
    VALUES (:y,:code,:sport_id,NULL,:best_time,:best_year,:notes)
  ");
  $copied=0; $skipped=0;
  foreach($rows as $r){
    $key = sport_key($r);
    if (!isset($curMap[$key])) { $skipped++; continue; }
    $sportIdNew = $curMap[$key];

    $notes = $r['notes'] ?? '';
    if (!empty($r['best_student_name'])) {
      $n = trim($r['best_student_name']);
      if ($n!=='') $notes = ($notes? $notes.'; ' : '').$n.' (จากปีก่อน)';
    }
    $ins->execute([
      ':y'=>$yearId,
      ':code'=>$r['event_code'],
      ':sport_id'=>$sportIdNew,
      ':best_time'=>$r['best_time'],
      ':best_year'=>$r['best_year_be'],
      ':notes'=>$notes
    ]);
    $copied++;
  }
  $_SESSION['flash'] = ['type'=>'success','msg'=>"คัดลอกจากปีก่อนเรียบร้อย: เพิ่ม $copied รายการ, ข้าม $skipped"];
  header('Location: ' . BASE_URL . '/reports_athletics.php'); exit;
}

// ---------------------- Save / Delete / Edit ----------------------
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
  $eventId   = (int)($_POST['event_id'] ?? 0);
  $eventCode = trim($_POST['event_code'] ?? '');
  $sportId   = (int)($_POST['sport_id'] ?? 0);
  $bestText  = trim($_POST['best_student_text'] ?? '');
  $bestTime  = trim($_POST['best_time'] ?? '');
  $bestYear  = trim($_POST['best_year_be'] ?? '');
  $notes     = trim($_POST['notes'] ?? '');

  // ตรวจว่า sport เป็นกรีฑา
  $chk = $pdo->prepare("
    SELECT 1 FROM sports s JOIN sport_categories c ON c.id=s.category_id
    WHERE s.id=:sid AND s.year_id=:y AND c.name='กรีฑา'
  ");
  $chk->execute([':sid'=>$sportId, ':y'=>$yearId]);
  if (!$chk->fetchColumn()) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'เลือกกีฬาไม่ถูกต้อง (ต้องเป็นหมวดกรีฑา)'];
    header('Location: ' . BASE_URL . '/reports_athletics.php'); exit;
  }

  $bestStudentId = null;
  if ($bestText !== '') {
    $bestStudentId = resolve_student_id($pdo, $yearId, $bestText);
    if (!$bestStudentId && $notes==='') $notes = $bestText; // ถ้าไม่พบ เก็บข้อความไว้ที่ notes
  }

  if ($eventId > 0) {
    $sql = "UPDATE athletics_events
            SET event_code=:code, sport_id=:sport_id, best_student_id=:best_id,
                best_time=:best_time, best_year_be=:best_year, notes=:notes
            WHERE id=:id AND year_id=:y";
    $st = $pdo->prepare($sql);
    $st->execute([
      ':code'=>$eventCode, ':sport_id'=>$sportId, ':best_id'=>$bestStudentId,
      ':best_time'=>$bestTime, ':best_year'=>($bestYear===''?null:$bestYear),
      ':notes'=>$notes, ':id'=>$eventId, ':y'=>$yearId
    ]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'อัปเดตแล้ว'];
  } else {
    $sql = "INSERT INTO athletics_events
            (year_id, event_code, sport_id, best_student_id, best_time, best_year_be, notes)
            VALUES (:y,:code,:sport_id,:best_id,:best_time,:best_year,:notes)";
    $st = $pdo->prepare($sql);
    $st->execute([
      ':y'=>$yearId, ':code'=>$eventCode, ':sport_id'=>$sportId, ':best_id'=>$bestStudentId,
      ':best_time'=>$bestTime, ':best_year'=>($bestYear===''?null:$bestYear), ':notes'=>$notes
    ]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'เพิ่มรายการแล้ว'];
  }
  header('Location: ' . BASE_URL . '/reports_athletics.php'); exit;
}

if (isset($_GET['action'], $_GET['id']) && $_GET['action']==='delete') {
  $id=(int)$_GET['id'];
  $del=$pdo->prepare("DELETE FROM athletics_events WHERE id=:id AND year_id=:y");
  $del->execute([':id'=>$id, ':y'=>$yearId]);
  $_SESSION['flash']=['type'=>'success','msg'=>'ลบรายการแล้ว'];
  header('Location: ' . BASE_URL . '/reports_athletics.php'); exit;
}

if (isset($_GET['edit'])) {
  $eid=(int)$_GET['edit'];
  $q=$pdo->prepare("SELECT * FROM athletics_events WHERE id=:id AND year_id=:y");
  $q->execute([':id'=>$eid, ':y'=>$yearId]);
  $editing=$q->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ---------------------- ดึงรายการที่สร้างไว้ ----------------------
$sqlEvents = "
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
$stmEv=$pdo->prepare($sqlEvents);
$stmEv->execute([':y'=>$yearId]);
$events=$stmEv->fetchAll(PDO::FETCH_ASSOC);

// ---------------------- view helpers ----------------------
function renderBestName($r){
  if (!empty($r['best_student_id']) && !empty($r['best_student_name'])) return e($r['best_student_name']);
  if (!empty($r['notes'])) return e($r['notes']);
  return '—';
}
function renderBestTime($r){
  $t = trim((string)($r['best_time'] ?? ''));
  $y = trim((string)($r['best_year_be'] ?? ''));
  if ($t==='' && $y==='') return '—';
  if ($t!=='' && $y!=='') return e($t) . ' (' . e($y) . ')';
  return e($t.$y);
}

// ---------------------- render ----------------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <?php if(!empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?=e($_SESSION['flash']['type'])?>"><?=e($_SESSION['flash']['msg'])?></div>
    <?php $_SESSION['flash']=null; endif; ?>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">กำหนดรหัส “รายการกรีฑา” • <?=e($yearName)?> ปีการศึกษา</h5>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="?import=prev" onclick="return confirm('คัดลอกจากปีก่อนเข้าปีนี้?');">คัดลอกจากปีก่อน</a>
      <a class="btn btn-success" href="<?=BASE_URL?>/reports_athletics_export.php?download=1">Export ทั้งหมด</a>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-header">เพิ่ม/แก้ไข รายการ</div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="event_id" value="<?= $editing? (int)$editing['id'] : 0 ?>">
        <div class="col-md-3">
          <label class="form-label">รหัส (เช่น 101)</label>
          <input type="text" name="event_code" class="form-control" value="<?= e($editing['event_code'] ?? '') ?>" placeholder="101">
        </div>
        <div class="col-md-5">
          <label class="form-label">กีฬา/เงื่อนไข (เฉพาะกรีฑา)</label>
          <select name="sport_id" class="form-select" required>
            <option value="">— เลือกกีฬา —</option>
            <?php foreach($sports as $sp): ?>
              <?php
                $lab = $sp['name'].' • '.$sp['gender'].' • '.$sp['participant_type'].' • '.$sp['grade_levels'];
                $sel = ($editing && (int)$editing['sport_id'] === (int)$sp['id']) ? 'selected' : '';
              ?>
              <option value="<?=$sp['id']?>" <?=$sel?>><?=e($lab)?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">สถิติดีที่สุด (พิมพ์ชื่อได้)</label>
          <input type="text" name="best_student_text" class="form-control" value="<?= e(isset($editing['best_student_id']) && $editing['best_student_id'] ? '' : ($editing['notes'] ?? '')) ?>" placeholder="เช่น นาย กรีฑา">
          <div class="form-text">ถ้าพบในฐานข้อมูลจะจับคู่เป็น best_student_id; หากไม่พบจะเก็บข้อความไว้</div>
        </div>

        <div class="col-md-2">
          <label class="form-label">เวลา (เช่น 11.25)</label>
          <input type="text" name="best_time" class="form-control" value="<?= e($editing['best_time'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">ปีที่ทำสถิติ (พ.ศ.)</label>
          <input type="text" name="best_year_be" class="form-control" value="<?= e($editing['best_year_be'] ?? '') ?>">
        </div>
        <div class="col-md-8">
          <label class="form-label">หมายเหตุ</label>
          <input type="text" name="notes" class="form-control" value="<?= e($editing['notes'] ?? '') ?>">
        </div>

        <div class="col-12">
          <button class="btn btn-primary" name="save_event" value="1">บันทึก</button>
          <?php if($editing): ?><a class="btn btn-light" href="<?=BASE_URL?>/reports_athletics.php">ยกเลิก</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">รายการที่กำหนดไว้</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:120px">จัดการ</th>
              <th style="width:90px">รหัส</th>
              <th>รายการ</th>
              <th style="width:230px" class="text-center">สถิติดีที่สุด</th>
              <th style="width:120px" class="text-center">เวลา</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$events): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีรายการ</td></tr>
            <?php else: foreach($events as $r): ?>
              <tr>
                <td>
                  <a class="btn btn-sm btn-warning" href="?edit=<?=$r['id']?>">แก้ไข</a>
                  <a class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบรายการนี้?');" href="?action=delete&id=<?=$r['id']?>">ลบ</a>
                </td>
                <td><span class="badge bg-secondary"><?= $r['event_code']? e($r['event_code']) : '-' ?></span></td>
                <td>
                  <div class="fw-semibold"><?= e($r['sport_name'] ?? '-') ?></div>
                  <div class="text-muted small">
                    <?= e(($r['gender'] ?? '').' • '.($r['participant_type'] ?? '').' • '.($r['grade_levels'] ?? '')) ?>
                  </div>
                </td>
                <td class="text-center">
                  <?php
                    if (!empty($r['best_student_id']) && !empty($r['best_student_name'])) echo e($r['best_student_name']);
                    elseif (!empty($r['notes'])) echo e($r['notes']);
                    else echo '—';
                  ?>
                </td>
                <td class="text-center">
                  <?php
                    $t = trim((string)($r['best_time'] ?? ''));
                    $y = trim((string)($r['best_year_be'] ?? ''));
                    if ($t==='' && $y==='') echo '—';
                    else if ($t!=='' && $y!=='') echo e($t).' ('.e($y).')';
                    else echo e($t.$y);
                  ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php';
