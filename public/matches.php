<?php
// public/matches.php — จับคู่การแข่งขัน (ยกเว้นกรีฑา)
// เวอร์ชัน "ฟิลเตอร์กระชับ" + "จับคู่เฉพาะรอบคัดเลือก"
// แก้: ปุ่ม POST (สุ่มทั้งหมด/ล้างทั้งหมด) แยกออกจากฟอร์ม GET (เลิกซ้อนฟอร์ม)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

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

// ---------------- utility: transactions & color helpers ----------------
function safeCommit(PDO $pdo){ if ($pdo->inTransaction()) { $pdo->commit(); } }
function safeRollback(PDO $pdo){ if ($pdo->inTransaction()) { $pdo->rollBack(); } }

const COLORS = ['เขียว','ฟ้า','ชมพู','ส้ม'];

function bgColorHex($c){
  switch($c){
    case 'เขียว': return '#d4edda';
    case 'ฟ้า':   return '#d1ecf1';
    case 'ชมพู':  return '#f8d7da';
    case 'ส้ม':   return '#fff3cd';
    default:      return '#f8f9fa';
  }
}

// ---------------- core helpers ----------------
function clear_pairs(PDO $pdo, int $yearId, int $sportId): void {
  $pdo->prepare("DELETE FROM match_pairs WHERE year_id=? AND sport_id=?")->execute([$yearId,$sportId]);
}
function schedule_qualify_round(array $colors): array {
  // รอบคัดเลือก: 2 คู่ A-B, C-D
  [$A,$B,$C,$D] = $colors;
  return [ [$A,$B], [$C,$D] ];
}
function generate_for_sport(PDO $pdo, int $yearId, array $sport): array {
  if (!$sport) return ['ok'=>false,'msg'=>'ไม่พบกีฬา'];
  try {
    if (!$pdo->inTransaction()) { $pdo->beginTransaction(); }
    
    // นับจำนวนคู่เดิมก่อนลบ (สำหรับ log)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM match_pairs WHERE year_id=? AND sport_id=?");
    $countStmt->execute([$yearId, (int)$sport['id']]);
    $oldCount = (int)$countStmt->fetchColumn();
    
    clear_pairs($pdo,$yearId,(int)$sport['id']);
    $ins = $pdo->prepare("INSERT INTO match_pairs
      (year_id, sport_id, round_name, round_no, match_no, match_date, match_time, venue,
       side_a_label, side_a_color, side_b_label, side_b_color, winner, score_a, score_b, status, notes, created_at)
      VALUES (?, ?, 'รอบคัดเลือก', 1, ?, NULL, NULL, NULL, ?, ?, ?, ?, NULL, NULL, NULL, 'pending', NULL, NOW())");

    // สุ่มสีก่อนสร้างคู่ เพื่อให้ไม่ออกเหมือนเดิมตลอด
    $colors = COLORS;
    // use secure Fisher–Yates shuffle
    for ($i = count($colors) - 1; $i > 0; $i--) {
      $j = random_int(0, $i);
      [$colors[$i], $colors[$j]] = [$colors[$j], $colors[$i]];
    }

    $pairs = schedule_qualify_round($colors);
    $mno = 1;
    $matchDetails = [];
    foreach ($pairs as $pair) {
      [$c1, $c2] = $pair;
      // สลับฝั่งแบบสุ่มเพื่อเพิ่มความหลากหลาย
      if (random_int(0,1) === 1) { [$c1, $c2] = [$c2, $c1]; }
      $ins->execute([$yearId,(int)$sport['id'],$mno++,"สี$c1",$c1,"สี$c2",$c2]);
      $matchDetails[] = "สี{$c1} vs สี{$c2}";
    }
    
    safeCommit($pdo);
    
    // 🔥 LOG: สุ่มคู่แข่งขันสำเร็จ
    log_activity('CREATE', 'match_pairs', (int)$sport['id'], 
      sprintf("สุ่มคู่แข่งขัน: %s | รอบคัดเลือก %d คู่ | คู่เดิม: %d | คู่ใหม่: [%s] | ปีการศึกษา ID:%d", 
        $sport['name'], 
        count($pairs),
        $oldCount,
        implode(', ', $matchDetails),
        $yearId));
    
    return ['ok'=>true,'msg'=>"สุ่มรอบคัดเลือก: {$sport['name']} สำเร็จ"];
  } catch (Throwable $e) {
    safeRollback($pdo);
    
    // 🔥 LOG: สุ่มคู่แข่งขันไม่สำเร็จ
    log_activity('ERROR', 'match_pairs', (int)$sport['id'], 
      sprintf("สุ่มคู่แข่งขันไม่สำเร็จ: %s | กีฬา: %s | ปีการศึกษา ID:%d", 
        $e->getMessage(), 
        $sport['name'] ?? 'unknown',
        $yearId));
    
    return ['ok'=>false,'msg'=>$e->getMessage()];
  }
}
function match_count(PDO $pdo, int $yearId, int $sportId): int {
  $q=$pdo->prepare("SELECT COUNT(*) FROM match_pairs WHERE year_id=? AND sport_id=?");
  $q->execute([$yearId,$sportId]); return (int)$q->fetchColumn();
}

// ---------------- filters ----------------
$catStmt = $pdo->prepare("SELECT DISTINCT sc.id, sc.name FROM sport_categories sc
  JOIN sports s ON s.category_id=sc.id
  WHERE s.year_id=? AND s.is_active=1 AND sc.name NOT LIKE '%กรีฑ%' ORDER BY sc.name");
$catStmt->execute([$yearId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$catId = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$gender = $_GET['gender'] ?? '';
$type = $_GET['type'] ?? '';
$grade = trim($_GET['grade'] ?? '');

$params = [$yearId];
$where = "s.year_id=? AND s.is_active=1 AND sc.name NOT LIKE '%กรีฑ%'";

if ($catId > 0) { $where .= " AND sc.id=?"; $params[] = $catId; }
if ($gender !== '' && $gender!=='ทั้งหมด') { $where .= " AND s.gender=?"; $params[] = $gender; }
if ($type !== '' && $type!=='ทั้งหมด') { $where .= " AND s.participant_type=?"; $params[] = $type; }
if ($grade !== '') { $where .= " AND s.grade_levels LIKE ?"; $params[] = "%$grade%"; }

$sql = "SELECT s.id, s.name, s.gender, s.participant_type, s.grade_levels, sc.name AS cat_name
        FROM sports s
        JOIN sport_categories sc ON sc.id=s.category_id
        WHERE $where
        ORDER BY sc.name, s.name";
$st=$pdo->prepare($sql); $st->execute($params);
$sports=$st->fetchAll(PDO::FETCH_ASSOC);
$map=[]; foreach($sports as $sp){ $map[(int)$sp['id']]=$sp; }

// ---------------- actions ----------------
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'';
  // เก็บ query string ของฟิลเตอร์ไว้หลัง redirect
  $qs = [];
  if ($catId>0) $qs['cat_id']=$catId;
  if ($gender!=='') $qs['gender']=$gender;
  if ($type!=='') $qs['type']=$type;
  if ($grade!=='') $qs['grade']=$grade;
  $qs = $qs ? ('?'.http_build_query($qs)) : '';

  if ($action==='gen_one' && !empty($_POST['sport_id'])){
    $sid=(int)$_POST['sport_id']; $res=generate_for_sport($pdo,$yearId,$map[$sid]??[]);
    flash($res['ok']?'ok':'err',$res['msg']); header('Location: '.BASE_URL.'/matches.php'.$qs); exit;
  }
  
  if ($action==='gen_all'){
    $ok=0; $fail=[]; $sportNames = [];
    foreach($sports as $row){ 
      $r=generate_for_sport($pdo,$yearId,$row); 
      if($r['ok']) { 
        $ok++; 
        $sportNames[] = $row['name'];
      } else { 
        $fail[]=$row['name']; 
      }
    }
    
    // 🔥 LOG: สุ่มทั้งหมดสำเร็จ
    $logDetail = sprintf("สุ่มคู่แข่งขันทั้งหมด: สำเร็จ %d รายการ | กีฬา: [%s]", 
      $ok, 
      implode(', ', $sportNames));
    if ($fail) {
      $logDetail .= sprintf(" | ล้มเหลว %d รายการ: [%s]", count($fail), implode(', ', $fail));
    }
    $logDetail .= " | ปีการศึกษา ID:{$yearId}";
    log_activity('CREATE', 'match_pairs', null, $logDetail);
    
    $msg="สุ่มรอบคัดเลือกทั้งหมดสำเร็จ $ok รายการ"; if($fail) $msg.=" (ผิดพลาด: ".implode(', ',$fail).")";
    flash('ok',$msg); header('Location: '.BASE_URL.'/matches.php'.$qs); exit;
  }
  
  if ($action==='clear_one' && !empty($_POST['sport_id'])){
    try {
      if (!$pdo->inTransaction()) { $pdo->beginTransaction(); }
      $sid=(int)$_POST['sport_id'];
      
      // นับจำนวนก่อนลบ
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM match_pairs WHERE year_id=? AND sport_id=?");
      $countStmt->execute([$yearId, $sid]);
      $deletedCount = (int)$countStmt->fetchColumn();
      
      // ดึงชื่อกีฬา
      $sportName = $map[$sid]['name'] ?? "ID:{$sid}";
      
      clear_pairs($pdo,$yearId,$sid);
      safeCommit($pdo);
      
      // 🔥 LOG: ล้างคู่แข่งขันสำเร็จ
      log_activity('DELETE', 'match_pairs', $sid, 
        sprintf("ล้างคู่แข่งขัน: %s | ลบ %d คู่ | ปีการศึกษา ID:%d", 
          $sportName, 
          $deletedCount,
          $yearId));
      
      flash('ok','ล้างคู่ของรายการนี้แล้ว');
    } catch(Throwable $e){
      safeRollback($pdo);
      
      // 🔥 LOG: ล้างคู่แข่งขันไม่สำเร็จ
      log_activity('ERROR', 'match_pairs', $sid ?? null, 
        sprintf("ล้างคู่แข่งขันไม่สำเร็จ: %s | กีฬา: %s", 
          $e->getMessage(), 
          $sportName ?? 'unknown'));
      
      flash('err','ล้างไม่สำเร็จ: '.$e->getMessage());
    }
    header('Location: '.BASE_URL.'/matches.php'.$qs); exit;
  }
  
  if ($action==='clear_all'){
    try {
      if (!$pdo->inTransaction()) { $pdo->beginTransaction(); }
      
      $totalDeleted = 0;
      $sportNames = [];
      foreach($sports as $row){ 
        // นับจำนวนก่อนลบ
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM match_pairs WHERE year_id=? AND sport_id=?");
        $countStmt->execute([$yearId, (int)$row['id']]);
        $count = (int)$countStmt->fetchColumn();
        
        if ($count > 0) {
          $sportNames[] = "{$row['name']} ({$count} คู่)";
          $totalDeleted += $count;
        }
        
        clear_pairs($pdo,$yearId,(int)$row['id']); 
      }
      
      safeCommit($pdo);
      
      // 🔥 LOG: ล้างทั้งหมดสำเร็จ
      log_activity('DELETE', 'match_pairs', null, 
        sprintf("ล้างคู่แข่งขันทั้งหมด: ลบทั้งหมด %d คู่ | กีฬา: [%s] | ปีการศึกษา ID:%d", 
          $totalDeleted,
          implode(', ', $sportNames),
          $yearId));
      
      flash('ok','ล้างคู่ทั้งหมดแล้ว');
    } catch(Throwable $e){
      safeRollback($pdo);
      
      // 🔥 LOG: ล้างทั้งหมดไม่สำเร็จ
      log_activity('ERROR', 'match_pairs', null, 
        sprintf("ล้างคู่แข่งขันทั้งหมดไม่สำเร็จ: %s | ปีการศึกษา ID:%d", 
          $e->getMessage(), 
          $yearId));
      
      flash('err','ล้างทั้งหมดไม่สำเร็จ: '.$e->getMessage());
    }
    header('Location: '.BASE_URL.'/matches.php'.$qs); exit;
  }
}

// ---------------- page ----------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
$ok=flash('ok'); $err=flash('err');
?>
<main class="container py-4">

  <!-- ฟิลเตอร์แบบกระชับ (GET) -->
  <form class="row g-2 align-items-end mb-2" method="get">
    <div class="col-12 col-sm-auto">
      <select name="cat_id" class="form-select form-select-sm">
        <option value="0">หมวด: ทั้งหมด</option>
        <?php foreach($categories as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo $catId===(int)$c['id']?'selected':''; ?>>
            <?php echo e($c['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-sm-auto">
      <select name="gender" class="form-select form-select-sm">
        <?php foreach(['ทั้งหมด','ช','ญ','รวม'] as $g): ?>
          <option value="<?php echo e($g); ?>" <?php echo $gender===$g?'selected':''; ?>>เพศ: <?php echo e($g); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-sm-auto">
      <?php $types=['ทั้งหมด','เดี่ยว','ทีม','รวม']; ?>
      <select name="type" class="form-select form-select-sm">
        <?php foreach($types as $t): ?>
          <option value="<?php echo e($t); ?>" <?php echo $type===$t?'selected':''; ?>>รูปแบบ: <?php echo e($t); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-8 col-sm-auto">
      <input type="text" name="grade" class="form-control form-control-sm" placeholder="ชั้น: เช่น ม4" value="<?php echo e($grade); ?>">
    </div>
    <div class="col-4 col-sm-auto d-flex gap-2">
      <button class="btn btn-sm btn-primary">กรอง</button>
      <a href="<?php echo BASE_URL; ?>/matches.php" class="btn btn-sm btn-outline-secondary">ล้าง</a>
    </div>
  </form>

  <!-- ปุ่ม POST แยกฟอร์ม (ไม่ซ้อนกับฟอร์ม GET) -->
  <div class="d-flex gap-2 justify-content-end mb-3">
    <form method="post" onsubmit="return confirm('สุ่มรอบคัดเลือกทั้งหมดของรายการที่กรองอยู่? ของเดิมจะถูกล้าง');" class="m-0">
      <input type="hidden" name="action" value="gen_all">
      <button class="btn btn-success btn-sm">สุ่มทั้งหมด</button>
    </form>
    <form method="post" onsubmit="return confirm('ล้างคู่ทั้งหมดของรายการที่กรองอยู่?');" class="m-0">
      <input type="hidden" name="action" value="clear_all">
      <button class="btn btn-outline-danger btn-sm">ล้างทั้งหมด</button>
    </form>
  </div>

  <?php if($ok): ?><div class="alert alert-success py-2"><?php echo e($ok); ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger py-2"><?php echo e($err); ?></div><?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>กีฬา</th><th>หมวด</th><th>เพศ</th><th>รูปแบบ</th><th>ชั้นที่เปิด</th><th class="text-center">มีคู่แล้ว</th><th class="text-end">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$sports): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">ไม่พบรายการตามตัวกรอง</td></tr>
          <?php endif; ?>
          <?php foreach($sports as $s): $sid=(int)$s['id']; $cnt=match_count($pdo,$yearId,$sid); ?>
            <tr>
              <td class="fw-semibold"><?php echo e($s['name']); ?></td>
              <td><span class="badge bg-secondary"><?php echo e($s['cat_name']); ?></span></td>
              <td><?php echo e($s['gender']); ?></td>
              <td><?php echo e($s['participant_type']); ?></td>
              <td><span class="text-muted"><?php echo e($s['grade_levels']?:'-'); ?></span></td>
              <td class="text-center"><span class="badge <?php echo $cnt>0?'bg-success':'bg-secondary'; ?>"><?php echo $cnt; ?></span></td>
              <td class="text-end">
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $sid; ?>" <?php echo $cnt? '':'disabled'; ?>>ดู</button>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="gen_one">
                    <input type="hidden" name="sport_id" value="<?php echo $sid; ?>">
                    <button class="btn btn-sm btn-primary">สุ่ม</button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('ล้างคู่ของรายการนี้?');">
                    <input type="hidden" name="action" value="clear_one">
                    <input type="hidden" name="sport_id" value="<?php echo $sid; ?>">
                    <button class="btn btn-sm btn-outline-danger">ล้าง</button>
                  </form>
                </div>
              </td>
            </tr>

            <!-- Modal -->
            <div class="modal fade" id="viewModal<?php echo $sid; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">คู่การแข่งขัน — <?php echo e($s['name']); ?> (รอบคัดเลือก)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <?php
                      $qv=$pdo->prepare("SELECT round_name, round_no, match_no, side_a_color, side_b_color
                                        FROM match_pairs WHERE year_id=? AND sport_id=? ORDER BY round_no, match_no");
                      $qv->execute([$yearId,$sid]); $rows=$qv->fetchAll(PDO::FETCH_ASSOC);
                      if(!$rows){ echo '<div class="text-muted">ยังไม่มีคู่</div>'; }
                      else {
                        echo '<div class="mb-3"><div class="fw-semibold mb-2">รอบคัดเลือก</div>';
                        foreach($rows as $r){
                          $aBg=bgColorHex($r['side_a_color']); $bBg=bgColorHex($r['side_b_color']);
                          echo '<div class="d-flex align-items-center gap-2 mb-2">';
                          echo '<span class="px-2 py-1 rounded-3" style="background:'.$aBg.'">สี'.e($r['side_a_color']).'</span>';
                          echo '<span class="text-muted">vs</span>';
                          echo '<span class="px-2 py-1 rounded-3" style="background:'.$bBg.'">สี'.e($r['side_b_color']).'</span>';
                          echo '</div>';
                        }
                        echo '</div>';
                      }
                    ?>
                  </div>
                  <div class="modal-footer"><button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button></div>
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