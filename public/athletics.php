<?php
// public/athletics.php — จัดลู่กรีฑา (แอดมิน)
// - วิ่งเดี่ยว: สุ่ม 4 สี (เขียว/ส้ม/ชมพู/ฟ้า) สำหรับลู่ 1–4 แล้ว "วนซ้ำ" ให้ลู่ 5–8
// - วิ่งผลัด: สุ่ม 4 สี สำหรับลู่ 1–4 (คงเดิม)
// - ไม่ผูกนักวิ่งทันที: registration_id = NULL
// - ใช้เฉพาะข้อมูลจากปีการศึกษาที่ Active (active_year_id)
// - ไม่เรียก active_year_name เพื่อหลีกเลี่ยง error ตาราง/ฟังก์ชัน

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

if (!function_exists('e')) { function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } }
function flash($k,$v=null){ if($v===null){ $x=$_SESSION['__flash'][$k]??null; unset($_SESSION['__flash'][$k]); return $x; } $_SESSION['__flash'][$k]=$v; }

$pdo = db();
$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// ------------ helpers ------------
const COLORS = ['เขียว','ส้ม','ชมพู','ฟ้า']; // ชุดสีของระบบ

function safeCommit(PDO $pdo){ if ($pdo->inTransaction()) $pdo->commit(); }
function safeRollback(PDO $pdo){ if ($pdo->inTransaction()) $pdo->rollBack(); }

function bgColorHex($c){
  switch($c){
    case 'เขียว': return '#d4edda';
    case 'ฟ้า':   return '#d1ecf1';
    case 'ชมพู':  return '#f8d7da';
    case 'ส้ม':   return '#fff3cd';
    default:      return '#f8f9fa';
  }
}

// ------------ load athletics sports ------------
// หมวดกรีฑาเท่านั้น
$st = $pdo->prepare("
  SELECT s.id, s.name, s.gender, s.participant_type, s.grade_levels, sc.name AS cat_name
  FROM sports s
  JOIN sport_categories sc ON sc.id=s.category_id
  WHERE s.year_id = ? AND s.is_active = 1 AND sc.name LIKE '%กรีฑ%'
  ORDER BY s.participant_type DESC, s.gender, s.name
");
$st->execute([$yearId]);
$sports = $st->fetchAll(PDO::FETCH_ASSOC);
$spMap = [];
foreach($sports as $row){ $spMap[(int)$row['id']] = $row; }

// ------------ core db ops ------------
function clear_heats(PDO $pdo, int $yearId, int $sportId){
  // ลบ assignments ของ heat ทั้งหมดของกีฬานี้ก่อน
  $q = $pdo->prepare("SELECT id FROM track_heats WHERE year_id=? AND sport_id=?");
  $q->execute([$yearId, $sportId]);
  $heatIds = $q->fetchAll(PDO::FETCH_COLUMN);
  if ($heatIds){
    $in = implode(',', array_fill(0, count($heatIds), '?'));
    $pdo->prepare("DELETE FROM track_lane_assignments WHERE heat_id IN ($in)")->execute($heatIds);
  }
  $pdo->prepare("DELETE FROM track_heats WHERE year_id=? AND sport_id=?")->execute([$yearId, $sportId]);
}

function generate_one_heat(PDO $pdo, int $yearId, array $sport): array {
  if (!$sport) return ['ok'=>false,'msg'=>'ไม่พบกีฬาที่ต้องการ'];

  // ทีม = ผลัด, เดี่ยว = วิ่งเดี่ยว
  $isRelay = ($sport['participant_type'] === 'ทีม');
  $lanesUsed = $isRelay ? 4 : 8;

  try {
    if (!$pdo->inTransaction()) $pdo->beginTransaction();

    // นับจำนวน heat เดิมก่อนลบ (สำหรับ log)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM track_heats WHERE year_id=? AND sport_id=?");
    $countStmt->execute([$yearId, (int)$sport['id']]);
    $oldHeatCount = (int)$countStmt->fetchColumn();

    // ล้างของเดิม
    clear_heats($pdo, $yearId, (int)$sport['id']);

    // สร้าง heat ใหม่ (แบบ 1 ฮีตต่อกีฬา)
    $insHeat = $pdo->prepare("INSERT INTO track_heats (year_id, sport_id, heat_no, lanes_used, created_at) VALUES (?, ?, 1, ?, NOW())");
    $insHeat->execute([$yearId, (int)$sport['id'], $lanesUsed]);
    $heatId = (int)$pdo->lastInsertId();

    // กำหนดสีลงลู่
    $laneDetails = [];
    if ($isRelay) {
      // ผลัด: สุ่ม 4 สี สำหรับลู่ 1–4 (คงเดิม)
      $laneColors = COLORS;
      shuffle($laneColors);
      $assign = [];
      for ($i=1; $i<=4; $i++) {
        $assign[$i] = $laneColors[$i-1];
        $laneDetails[] = "ลู่{$i}:สี{$laneColors[$i-1]}";
      }
    } else {
      // เดี่ยว: สุ่ม 4 สีให้ลู่ 1–4 แล้ว "วนซ้ำ" ให้ลู่ 5–8
      $base = COLORS;
      shuffle($base);
      $assign = [];
      for ($i=1; $i<=8; $i++) {
        $assign[$i] = $base[($i-1) % 4];
        $laneDetails[] = "ลู่{$i}:สี{$base[($i-1) % 4]}";
      }
    }

    // บันทึกลง track_lane_assignments
    $insLane = $pdo->prepare("INSERT INTO track_lane_assignments (heat_id, lane_no, color, registration_id, created_at) VALUES (?, ?, ?, NULL, NOW())");
    foreach ($assign as $lane => $color) {
      $insLane->execute([$heatId, $lane, $color]);
    }

    safeCommit($pdo);
    
    // 🔥 LOG: สุ่มลู่กรีฑาสำเร็จ
    $lbl = $isRelay ? 'วิ่งผลัด' : 'วิ่งเดี่ยว';
    log_activity('CREATE', 'track_heats', $heatId, 
      sprintf("สุ่มลู่กรีฑา: %s (%s) | ใช้ %d ลู่ | Heat เดิม: %d | รายละเอียด: [%s] | ปีการศึกษา ID:%d", 
        $sport['name'], 
        $lbl,
        $lanesUsed,
        $oldHeatCount,
        implode(', ', $laneDetails),
        $yearId));
    
    return ['ok'=>true,'msg'=>"สุ่มลู่สำเร็จ: {$sport['name']} ({$lbl})"];
  } catch (Throwable $e) {
    safeRollback($pdo);
    
    // 🔥 LOG: สุ่มลู่กรีฑาไม่สำเร็จ
    log_activity('ERROR', 'track_heats', (int)$sport['id'], 
      sprintf("สุ่มลู่กรีฑาไม่สำเร็จ: %s | กีฬา: %s | ปีการศึกษา ID:%d", 
        $e->getMessage(), 
        $sport['name'],
        $yearId));
    
    return ['ok'=>false,'msg'=>$e->getMessage()];
  }
}

// ------------ actions ------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'gen_one' && !empty($_POST['sport_id'])) {
    $sid = (int)$_POST['sport_id'];
    $res = generate_one_heat($pdo, $yearId, $spMap[$sid] ?? []);
    flash($res['ok'] ? 'ok' : 'err', $res['msg']);
    header('Location: ' . BASE_URL . '/athletics.php'); exit;
  }
  
  if ($action === 'clear_one' && !empty($_POST['sport_id'])) {
    try {
      if (!$pdo->inTransaction()) $pdo->beginTransaction();
      $sid = (int)$_POST['sport_id'];
      
      // นับจำนวนก่อนลบ
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM track_heats WHERE year_id=? AND sport_id=?");
      $countStmt->execute([$yearId, $sid]);
      $deletedCount = (int)$countStmt->fetchColumn();
      
      // ดึงชื่อกีฬา
      $sportName = $spMap[$sid]['name'] ?? "ID:{$sid}";
      $sportType = $spMap[$sid]['participant_type'] ?? 'unknown';
      $lbl = ($sportType === 'ทีม') ? 'วิ่งผลัด' : 'วิ่งเดี่ยว';
      
      clear_heats($pdo, $yearId, $sid);
      safeCommit($pdo);
      
      // 🔥 LOG: ล้างฮีตสำเร็จ
      log_activity('DELETE', 'track_heats', $sid, 
        sprintf("ล้างลู่กรีฑา: %s (%s) | ลบ %d ฮีต | ปีการศึกษา ID:%d", 
          $sportName, 
          $lbl,
          $deletedCount,
          $yearId));
      
      flash('ok', 'ล้างฮีตของรายการนี้แล้ว');
    } catch (Throwable $e) {
      safeRollback($pdo);
      
      // 🔥 LOG: ล้างฮีตไม่สำเร็จ
      log_activity('ERROR', 'track_heats', $sid ?? null, 
        sprintf("ล้างลู่กรีฑาไม่สำเร็จ: %s | กีฬา: %s", 
          $e->getMessage(), 
          $sportName ?? 'unknown'));
      
      flash('err', 'ล้างไม่สำเร็จ: ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/athletics.php'); exit;
  }
  
  if ($action === 'gen_all') {
    $ok=0; $fail=[]; $sportNames = [];
    foreach ($sports as $sp) {
      $r = generate_one_heat($pdo, $yearId, $sp);
      if ($r['ok']) {
        $ok++;
        $sportNames[] = $sp['name'];
      } else {
        $fail[] = $sp['name'];
      }
    }
    
    // 🔥 LOG: สุ่มลู่ทั้งหมดสำเร็จ
    $logDetail = sprintf("สุ่มลู่กรีฑาทั้งหมด: สำเร็จ %d รายการ | กีฬา: [%s]", 
      $ok, 
      implode(', ', $sportNames));
    if ($fail) {
      $logDetail .= sprintf(" | ล้มเหลว %d รายการ: [%s]", count($fail), implode(', ', $fail));
    }
    $logDetail .= " | ปีการศึกษา ID:{$yearId}";
    log_activity('CREATE', 'track_heats', null, $logDetail);
    
    $msg = "สุ่มลู่ทั้งหมดสำเร็จ {$ok} รายการ"; 
    if ($fail) $msg .= " (ผิดพลาด: " . implode(', ', $fail) . ")";
    flash('ok', $msg); 
    header('Location: ' . BASE_URL . '/athletics.php'); exit;
  }
  
  if ($action === 'clear_all') {
    try {
      if (!$pdo->inTransaction()) $pdo->beginTransaction();
      
      $totalDeleted = 0;
      $sportDetails = [];
      
      foreach ($sports as $sp) {
        // นับจำนวนก่อนลบ
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM track_heats WHERE year_id=? AND sport_id=?");
        $countStmt->execute([$yearId, (int)$sp['id']]);
        $count = (int)$countStmt->fetchColumn();
        
        if ($count > 0) {
          $lbl = ($sp['participant_type'] === 'ทีม') ? 'ผลัด' : 'เดี่ยว';
          $sportDetails[] = "{$sp['name']} ({$lbl}, {$count} ฮีต)";
          $totalDeleted += $count;
        }
        
        clear_heats($pdo, $yearId, (int)$sp['id']);
      }
      
      safeCommit($pdo);
      
      // 🔥 LOG: ล้างฮีตทั้งหมดสำเร็จ
      log_activity('DELETE', 'track_heats', null, 
        sprintf("ล้างลู่กรีฑาทั้งหมด: ลบทั้งหมด %d ฮีต | กีฬา: [%s] | ปีการศึกษา ID:%d", 
          $totalDeleted,
          implode(', ', $sportDetails),
          $yearId));
      
      flash('ok', 'ล้างฮีตทั้งหมดแล้ว');
    } catch (Throwable $e) {
      safeRollback($pdo);
      
      // 🔥 LOG: ล้างฮีตทั้งหมดไม่สำเร็จ
      log_activity('ERROR', 'track_heats', null, 
        sprintf("ล้างลู่กรีฑาทั้งหมดไม่สำเร็จ: %s | ปีการศึกษา ID:%d", 
          $e->getMessage(), 
          $yearId));
      
      flash('err', 'ล้างทั้งหมดไม่สำเร็จ: ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/athletics.php'); exit;
  }
}

// ------------ page ------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
$ok = flash('ok'); $err = flash('err');
?>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1">จัดลู่กรีฑา</h5>
      <div class="text-muted small">วิ่งเดี่ยวใช้ 8 ลู่ (สุ่ม 4 สีแล้ววนซ้ำ), วิ่งผลัดใช้ 4 ลู่ (สุ่ม 4 สี)</div>
    </div>
    <div class="d-flex gap-2">
      <form method="post" onsubmit="return confirm('สุ่มลู่ทั้งหมด? ของเดิมจะถูกล้าง');">
        <input type="hidden" name="action" value="gen_all">
        <button class="btn btn-success">สุ่มทั้งหมด</button>
      </form>
      <form method="post" onsubmit="return confirm('ล้างฮีตทั้งหมด?');">
        <input type="hidden" name="action" value="clear_all">
        <button class="btn btn-outline-danger">ล้างทั้งหมด</button>
      </form>
    </div>
  </div>

  <?php if($ok): ?><div class="alert alert-success"><?php echo e($ok); ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>กีฬา</th><th>เพศ</th><th>รูปแบบ</th><th>ชั้นที่เปิด</th><th class="text-end">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$sports): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีกรีฑาที่เปิดใช้งาน</td></tr>
          <?php endif; ?>
          <?php foreach($sports as $s): ?>
            <tr>
              <td class="fw-semibold"><?php echo e($s['name']); ?></td>
              <td><?php echo e($s['gender']); ?></td>
              <td><?php echo e($s['participant_type']); ?></td>
              <td><span class="text-muted"><?php echo e($s['grade_levels']?:'-'); ?></span></td>
              <td class="text-end">
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#v<?php echo (int)$s['id']; ?>">ดู</button>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="gen_one">
                    <input type="hidden" name="sport_id" value="<?php echo (int)$s['id']; ?>">
                    <button class="btn btn-sm btn-primary">สุ่ม</button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('ล้างฮีตของรายการนี้?');">
                    <input type="hidden" name="action" value="clear_one">
                    <input type="hidden" name="sport_id" value="<?php echo (int)$s['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger">ล้าง</button>
                  </form>
                </div>
              </td>
            </tr>

            <!-- Modal ดูลู่ -->
            <div class="modal fade" id="v<?php echo (int)$s['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">ลู่ — <?php echo e($s['name']); ?> (<?php echo e($s['participant_type']==='ทีม'?'ผลัด':'เดี่ยว'); ?>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <?php
                      // โหลด heat ล่าสุดของกีฬานี้
                      $qh=$pdo->prepare("SELECT id, lanes_used FROM track_heats WHERE year_id=? AND sport_id=? ORDER BY id DESC LIMIT 1");
                      $qh->execute([$yearId,(int)$s['id']]);
                      $heat=$qh->fetch(PDO::FETCH_ASSOC);
                      if (!$heat){
                        echo '<div class="text-muted">ยังไม่ได้สุ่มลู่</div>';
                      } else {
                        $qa=$pdo->prepare("SELECT lane_no, color FROM track_lane_assignments WHERE heat_id=? ORDER BY lane_no");
                        $qa->execute([(int)$heat['id']]);
                        $lanes=$qa->fetchAll(PDO::FETCH_ASSOC);
                        if (!$lanes){ echo '<div class="text-muted">ยังไม่มีการจัดลู่</div>'; }
                        else {
                          echo '<div class="row g-2">';
                          foreach($lanes as $ln){
                            $bg = bgColorHex($ln['color']);
                            echo '<div class="col-6 col-md-3">';
                            echo '<div class="p-2 rounded-3 border" style="background:'.$bg.'">';
                            echo '<div class="small text-muted">ลู่ '.(int)$ln['lane_no'].'</div>';
                            echo '<div class="fw-semibold">สี'.e($ln['color']).'</div>';
                            echo '</div></div>';
                          }
                          echo '</div>';
                        }
                      }
                    ?>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                  </div>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
