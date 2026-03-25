<?php
// public/matches_schedule.php — กำหนด “คู่ที่/วัน/เวลา” แบบรวมตามกีฬาหลัก

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo = db();
$yearId = active_year_id($pdo);
$yearName = active_year_name($pdo) ?? '';
if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ensure schema (best-effort)
ensure_match_pairs_schedule_no($pdo);

if (!function_exists('formatGender')) {
  function formatGender($gender) {
    if ($gender === 'ช') return 'ชาย';
    if ($gender === 'ญ') return 'หญิง';
    if ($gender === 'รวม') return 'ชาย-หญิง';
    if ($gender === 'ผสม') return 'ชาย-หญิง';
    return $gender;
  }
}

function be_date_to_ce(?string $dateStr): ?string {
  $dateStr = trim((string)$dateStr);
  if ($dateStr === '') return null;
  if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $m)) return null;
  $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
  $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
  $yearBE = (int)$m[3];
  $yearCE = $yearBE - 543;
  return $yearCE . '-' . $month . '-' . $day;
}

function ce_date_to_be(?string $date): string {
  if (!$date) return '';
  try {
    $dt = new DateTime($date);
    $day = $dt->format('d');
    $month = $dt->format('m');
    $yearBE = ((int)$dt->format('Y')) + 543;
    return $day . '/' . $month . '/' . $yearBE;
  } catch (Throwable $e) {
    return '';
  }
}

$selectedSportName = trim($_GET['sport_name'] ?? '');

// main sport list (only those with match_pairs)
$stMain = $pdo->prepare("SELECT DISTINCT SUBSTRING_INDEX(s.name, ' ', 1) AS main_sport_name
  FROM sports s
  INNER JOIN match_pairs mp ON mp.sport_id = s.id AND mp.year_id = :y
  JOIN sport_categories c ON c.id = s.category_id
 WHERE s.year_id = :y2 AND s.is_active = 1 AND c.name <> 'กรีฑา'
 ORDER BY main_sport_name");
$stMain->execute([':y' => $yearId, ':y2' => $yearId]);
$mainSports = $stMain->fetchAll(PDO::FETCH_ASSOC);

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $selectedSportName !== '') {
  $scheduleNos = $_POST['schedule_no'] ?? [];
  $matchDates  = $_POST['match_date'] ?? [];
  $matchTimes  = $_POST['match_time'] ?? [];
  $venues      = $_POST['venue'] ?? [];

  try {
    $pdo->beginTransaction();

    $updated = 0;
    $hasScheduleNo = db_table_has_column($pdo, 'match_pairs', 'schedule_no');

    foreach ($scheduleNos as $matchId => $noRaw) {
      $matchId = (int)$matchId;
      $noRaw = trim((string)$noRaw);
      $scheduleNo = ($noRaw === '') ? null : (int)$noRaw;

      $date = be_date_to_ce($matchDates[$matchId] ?? '');
      $time = trim((string)($matchTimes[$matchId] ?? ''));
      $time = $time !== '' ? $time : null;

      $venue = trim((string)($venues[$matchId] ?? ''));
      $venue = $venue !== '' ? $venue : null;

      if ($hasScheduleNo) {
        $upd = $pdo->prepare('UPDATE match_pairs SET schedule_no=?, match_date=?, match_time=?, venue=? WHERE id=? AND year_id=?');
        $upd->execute([$scheduleNo, $date, $time, $venue, $matchId, $yearId]);
      } else {
        // Fallback: only update date/time/venue
        $upd = $pdo->prepare('UPDATE match_pairs SET match_date=?, match_time=?, venue=? WHERE id=? AND year_id=?');
        $upd->execute([$date, $time, $venue, $matchId, $yearId]);
      }
      $updated++;
    }

    $pdo->commit();

    log_activity('UPDATE', 'match_pairs', null, sprintf('อัปเดตตารางแข่งขัน (รวมกีฬา): %s | อัปเดต %d แถว | ปีการศึกษา ID:%d', $selectedSportName, $updated, $yearId));
    $_SESSION['flash'] = 'บันทึกตารางแข่งขันแล้ว';
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_error'] = 'บันทึกไม่สำเร็จ: ' . $e->getMessage();
  }

  header('Location: ' . BASE_URL . '/matches_schedule.php?sport_name=' . urlencode($selectedSportName));
  exit;
}

// Load rows
$rows = [];
$hasScheduleNo = db_table_has_column($pdo, 'match_pairs', 'schedule_no');

function ensure_finals_for_sport(PDO $pdo, int $yearId, int $sportId, bool $hasScheduleNo): void {
  // ต้องมีรอบคัดเลือกก่อน
  $stCnt = $pdo->prepare('SELECT COUNT(*) FROM match_pairs WHERE year_id=? AND sport_id=? AND round_no=1');
  $stCnt->execute([$yearId, $sportId]);
  if ((int)$stCnt->fetchColumn() <= 0) return;

  // ดึงคู่ที่ 1-2 (ใช้ schedule_no ถ้ามี เพื่อสร้าง label ให้สื่อถึง “คู่ที่” ที่ user ตั้ง)
  $stQ = $pdo->prepare('SELECT match_no' . ($hasScheduleNo ? ', schedule_no' : '') . ' FROM match_pairs WHERE year_id=? AND sport_id=? AND round_no=1 ORDER BY match_no ASC LIMIT 2');
  $stQ->execute([$yearId, $sportId]);
  $q = $stQ->fetchAll(PDO::FETCH_ASSOC);
  $q1 = $q[0]['schedule_no'] ?? $q[0]['match_no'] ?? 1;
  $q2 = $q[1]['schedule_no'] ?? $q[1]['match_no'] ?? 2;

  // ถ้าเคยตั้ง final_date/final_time แบบเดิมไว้ ให้ดึงมาเป็นค่าเริ่มต้น
  $stFinalLegacy = $pdo->prepare('SELECT final_date, final_time FROM match_pairs WHERE year_id=? AND sport_id=? AND round_no=1 AND (final_date IS NOT NULL OR final_time IS NOT NULL) ORDER BY match_no ASC LIMIT 1');
  $stFinalLegacy->execute([$yearId, $sportId]);
  $legacy = $stFinalLegacy->fetch(PDO::FETCH_ASSOC) ?: [];
  $legacyDate = $legacy['final_date'] ?? null;
  $legacyTime = $legacy['final_time'] ?? null;

  // check if round 2 exists
  $stHas2 = $pdo->prepare('SELECT 1 FROM match_pairs WHERE year_id=? AND sport_id=? AND round_no=2 LIMIT 1');
  $stHas2->execute([$yearId, $sportId]);
  $has2 = (bool)$stHas2->fetchColumn();

  // check if round 3 exists
  $stHas3 = $pdo->prepare('SELECT 1 FROM match_pairs WHERE year_id=? AND sport_id=? AND round_no=3 LIMIT 1');
  $stHas3->execute([$yearId, $sportId]);
  $has3 = (bool)$stHas3->fetchColumn();

  if ($has2 && $has3) return;

  // insert missing rounds
  $ins = $pdo->prepare("INSERT INTO match_pairs
    (year_id, sport_id, round_name, round_no, match_no, match_date, match_time, venue,
     side_a_label, side_a_color, side_b_label, side_b_color,
     winner, score_a, score_b, status, notes, created_at)
    VALUES (?, ?, ?, ?, 1, ?, ?, NULL, ?, NULL, ?, NULL, NULL, NULL, NULL, 'scheduled', NULL, CURRENT_TIMESTAMP)");

  if (!$has2) {
    $ins->execute([$yearId, $sportId, 'รอบชิงที่ 3', 2, $legacyDate, $legacyTime, 'ผู้แพ้คู่ที่ '.$q1, 'ผู้แพ้คู่ที่ '.$q2]);
  }
  if (!$has3) {
    $ins->execute([$yearId, $sportId, 'รอบชิงชนะเลิศ', 3, $legacyDate, $legacyTime, 'ผู้ชนะคู่ที่ '.$q1, 'ผู้ชนะคู่ที่ '.$q2]);
  }
}

if ($selectedSportName !== '') {
  // ถ้าข้อมูลเดิมมีแค่รอบคัดเลือก ให้สร้างรอบชิงให้ครบ (เพื่อให้กำหนดคู่ที่/วัน/เวลาได้)
  try {
    $stSportIds = $pdo->prepare("SELECT DISTINCT s.id
      FROM sports s
      INNER JOIN match_pairs mp ON mp.sport_id = s.id AND mp.year_id = :y
      JOIN sport_categories c ON c.id = s.category_id
     WHERE s.year_id = :y2 AND s.is_active = 1 AND c.name <> 'กรีฑา'
       AND s.name LIKE CONCAT(:sport_name, '%')");
    $stSportIds->execute([':y' => $yearId, ':y2' => $yearId, ':sport_name' => $selectedSportName]);
    $sportIds = array_map(fn($r) => (int)$r['id'], $stSportIds->fetchAll(PDO::FETCH_ASSOC));

    if ($sportIds) {
      $pdo->beginTransaction();
      foreach ($sportIds as $sid) {
        ensure_finals_for_sport($pdo, $yearId, $sid, $hasScheduleNo);
      }
      $pdo->commit();
    }
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // ignore; page can still render qualifying rounds
  }

  $sql = "
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
      s.name AS sport_full_name,
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

  $st = $pdo->prepare($sql);
  $st->execute([':y' => $yearId, ':y2' => $yearId, ':sport_name' => $selectedSportName]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <?php if (!empty($_SESSION['flash'])): ?><div class="alert alert-success"><?php echo e($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>

  <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
    <div>
      <h4 class="mb-1">🗓️ กำหนดตารางการแข่งขัน (รวมกีฬาหลัก)</h4>
      <div class="text-muted small">ปีการศึกษา: <strong><?php echo e($yearName); ?></strong></div>
    </div>

    <form method="get" class="d-flex gap-2 align-items-end">
      <div>
        <label class="form-label mb-1">เลือกกีฬาหลัก</label>
        <select name="sport_name" class="form-select">
          <option value="">-- เลือกกีฬา --</option>
          <?php foreach ($mainSports as $ms): $mn = $ms['main_sport_name']; ?>
            <option value="<?php echo e($mn); ?>" <?php echo $selectedSportName===$mn?'selected':''; ?>><?php echo e($mn); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary">แสดง</button>
      <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/matches.php">กลับหน้า สุ่มจับคู่</a>
    </form>
  </div>

  <?php if ($selectedSportName === ''): ?>
    <div class="alert alert-info">เลือกกีฬาหลักก่อน เพื่อกำหนด “คู่ที่/วัน/เวลา”</div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">กีฬา: <?php echo e($selectedSportName); ?></div>
          <div class="text-muted small">กรอก “คู่ที่” (ลำดับรวม), วันที่/เวลา และสถานที่ (ถ้ามี)</div>
        </div>
        <div class="text-muted small">
          <?php if (!$hasScheduleNo): ?>
            <span class="badge bg-warning text-dark">ยังไม่มีฟิลด์ schedule_no (ระบบจะบันทึกได้เฉพาะวัน/เวลา)</span>
          <?php else: ?>
            <span class="badge bg-success">รองรับลำดับคู่ (schedule_no)</span>
          <?php endif; ?>
        </div>
      </div>

      <form method="post">
        <input type="hidden" name="action" value="save">
        <div class="card-body table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:90px" class="text-center">คู่ที่</th>
                <th style="width:130px" class="text-center">วัน (พ.ศ.)</th>
                <th style="width:110px" class="text-center">เวลา</th>
                <th>ระดับชั้น/รายการ</th>
                <th style="width:220px">คู่แข่งขัน</th>
                <th style="width:140px">สถานที่</th>
                <th style="width:90px" class="text-center">รอบ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีคู่แข่งขันของกีฬานี้ (ไปสุ่มที่หน้า สุ่มจับคู่ ก่อน)</td></tr>
              <?php endif; ?>
              <?php foreach ($rows as $r):
                $id = (int)$r['id'];
                $level = trim((string)($r['grade_levels'] ?? ''));
                $gender = trim((string)($r['gender'] ?? ''));
                $levelText = $level;
                if ($gender !== '') {
                  $levelText = $levelText !== '' ? ($levelText . ' • ' . formatGender($gender)) : formatGender($gender);
                }
                $levelText = $levelText !== '' ? $levelText : '-';

                $dateBE = ce_date_to_be($r['match_date'] ?? null);
                $time = $r['match_time'] ?? '';
                $scheduleNo = $r['schedule_no'] ?? '';
                $pairText = 'สี' . e($r['side_a_color'] ?? '-') . ' vs สี' . e($r['side_b_color'] ?? '-');
              ?>
              <tr>
                <td class="text-center">
                  <input type="number" class="form-control form-control-sm text-center" name="schedule_no[<?php echo $id; ?>]" value="<?php echo e($scheduleNo); ?>" min="1" step="1" placeholder="-">
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm text-center" name="match_date[<?php echo $id; ?>]" value="<?php echo e($dateBE); ?>" placeholder="dd/mm/2569" pattern="\d{1,2}/\d{1,2}/\d{4}">
                </td>
                <td>
                  <input type="time" class="form-control form-control-sm text-center" name="match_time[<?php echo $id; ?>]" value="<?php echo e($time); ?>">
                </td>
                <td>
                  <div class="fw-semibold"><?php echo e($levelText); ?></div>
                  <div class="text-muted small"><?php echo e($r['sport_full_name'] ?? ''); ?></div>
                </td>
                <td><?php echo $pairText; ?></td>
                <td>
                  <input type="text" class="form-control form-control-sm" name="venue[<?php echo $id; ?>]" value="<?php echo e($r['venue'] ?? ''); ?>" placeholder="เช่น สนาม 1">
                </td>
                <td class="text-center"><span class="badge bg-secondary"><?php echo e($r['round_name'] ?? ('รอบ '.$r['round_no'])); ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
          <div class="text-muted small">* ถ้ายังไม่กำหนดวัน/เวลา ปล่อยว่างได้</div>
          <button class="btn btn-success">💾 บันทึก</button>
        </div>
      </form>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
