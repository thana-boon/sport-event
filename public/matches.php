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

// ensure schema (best-effort)
ensure_match_pairs_schedule_no($pdo);

function ce_to_be_date(?string $date): string {
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

function be_to_ce_date(?string $dateStr): ?string {
  $dateStr = trim((string)$dateStr);
  if ($dateStr === '') return null;
  if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $m)) return null;
  $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
  $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
  $yearBE = (int)$m[3];
  $yearCE = $yearBE - 543;
  return $yearCE . '-' . $month . '-' . $day;
}

// ---------------- Export CSV ทั้งหมด ----------------
if (isset($_GET['action']) && $_GET['action'] === 'export_all_csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="ตารางแข่งขันทั้งหมด_' . date('Y-m-d') . '.csv"');
  
  echo "\xEF\xBB\xBF"; // UTF-8 BOM
  echo "กีฬา,รอบ,คู่ที่,คู่แข่งขัน,วันที่แข่งขัน (dd/mm/yyyy),เวลา (24 ชม.),วันที่รอบชิง (dd/mm/yyyy),เวลารอบชิง (24 ชม.)\n";
  
  // ดึงเฉพาะกีฬาที่มี match_pairs (ไม่ใช่กรีฑา)
  $qSports = $pdo->prepare("SELECT DISTINCT s.id, s.name 
                            FROM sports s 
                            INNER JOIN match_pairs mp ON s.id = mp.sport_id 
                            WHERE mp.year_id = ? 
                            ORDER BY s.name");
  $qSports->execute([$yearId]);
  $sports = $qSports->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($sports as $sport) {
    $qMatches = $pdo->prepare("SELECT round_name, match_no, side_a_color, side_b_color, match_date, match_time, final_date, final_time 
                               FROM match_pairs 
                               WHERE year_id=? AND sport_id=? 
                               ORDER BY round_no, match_no");
    $qMatches->execute([$yearId, $sport['id']]);
    $matches = $qMatches->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงวันเวลารอบชิง (ใช้จาก match แรก)
    $finalDateValue = '';
    $finalTimeValue = '';
    if (!empty($matches)) {
      if (!empty($matches[0]['final_date'])) {
        $date = new DateTime($matches[0]['final_date']);
        $day = $date->format('d');
        $month = $date->format('m');
        $year = (int)$date->format('Y') + 543;
        $finalDateValue = $day . '/' . $month . '/' . $year;
      }
      $finalTimeValue = $matches[0]['final_time'] ?? '';
    }
    
    foreach ($matches as $m) {
      $sportName = $sport['name'];
      $roundName = $m['round_name'];
      $matchNo = $m['match_no'];
      $pair = "สี{$m['side_a_color']} vs สี{$m['side_b_color']}";
      
      $dateValue = '';
      if (!empty($m['match_date'])) {
        $date = new DateTime($m['match_date']);
        $day = $date->format('d');
        $month = $date->format('m');
        $year = (int)$date->format('Y') + 543;
        $dateValue = $day . '/' . $month . '/' . $year;
      }
      
      $timeValue = $m['match_time'] ?? '';
      
      echo '"' . str_replace('"', '""', $sportName) . '",';
      echo '"' . str_replace('"', '""', $roundName) . '",';
      echo $matchNo . ',';
      echo '"' . str_replace('"', '""', $pair) . '",';
      echo $dateValue . ',';
      echo $timeValue . ',';
      echo $finalDateValue . ',';
      echo $finalTimeValue . "\n";
    }
  }
  exit;
}

// ---------------- Export Template CSV (กำหนดคู่ที่/วัน/เวลา) ----------------
if (isset($_GET['action']) && $_GET['action'] === 'export_schedule_template_csv') {
  $mainSport = trim($_GET['sport_name'] ?? '');
  if ($mainSport === '') {
    http_response_code(400);
    echo 'กรุณาระบุ sport_name';
    exit;
  }

  $hasScheduleNo = db_table_has_column($pdo, 'match_pairs', 'schedule_no');
  $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
  $isSqlite = ($driver === 'sqlite');

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="template_กำหนดคู่ที่_วันเวลา_' . $mainSport . '_' . date('Y-m-d') . '.csv"');
  echo "\xEF\xBB\xBF";

  $headers = [
    'match_id',
    'sport_full_name',
    'participant_type',
    'round_no',
    'round_name',
    'match_no',
    'schedule_no',
    'match_date_be',
    'match_time',
    'venue',
  ];
  echo implode(',', $headers) . "\n";

  $sql = "
    SELECT
      mp.id AS match_id,
      s.name AS sport_full_name,
      s.participant_type,
      mp.round_no,
      mp.round_name,
      mp.match_no,
      " . ($hasScheduleNo ? "mp.schedule_no," : "NULL AS schedule_no,") . "
      mp.match_date,
      mp.match_time,
      mp.venue
    FROM match_pairs mp
    JOIN sports s ON s.id = mp.sport_id
    JOIN sport_categories c ON c.id = s.category_id
    WHERE mp.year_id = :y
      AND s.year_id = :y2
      AND s.is_active = 1
      AND c.name NOT LIKE '%กรีฑ%'
      AND s.name LIKE " . ($isSqlite ? "(:sport_name || '%')" : "CONCAT(:sport_name, '%')") . "
    ORDER BY s.participant_type ASC, mp.round_no ASC, mp.match_no ASC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':y' => $yearId, ':y2' => $yearId, ':sport_name' => $mainSport]);
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $row = [
      $r['match_id'],
      $r['sport_full_name'],
      $r['participant_type'],
      $r['round_no'],
      $r['round_name'],
      $r['match_no'],
      $r['schedule_no'] ?? '',
      ce_to_be_date($r['match_date'] ?? null),
      $r['match_time'] ?? '',
      $r['venue'] ?? '',
    ];
    $out = array_map(function($v){
      $v = (string)($v ?? '');
      $v = str_replace('"', '""', $v);
      return '"' . $v . '"';
    }, $row);
    echo implode(',', $out) . "\n";
  }
  exit;
}

// ---------------- AJAX: โหลดข้อมูลตารางแข่งขัน ----------------
if (isset($_GET['action']) && $_GET['action'] === 'get_schedule') {
  header('Content-Type: application/json; charset=utf-8');
  $sportId = (int)($_GET['sport_id'] ?? 0);
  
  if ($sportId > 0) {
    // NOTE: ตอนนี้ตาราง match_pairs อาจมีหลายรอบ (คัดเลือก/ชิงที่ 3/ชิงชนะเลิศ)
    // Modal นี้ใช้แก้เฉพาะรอบคัดเลือก (round_no = 1)
    $qv = $pdo->prepare("SELECT id, round_name, round_no, match_no, side_a_color, side_b_color, match_date, match_time, venue, final_date, final_time
                         FROM match_pairs WHERE year_id=? AND sport_id=? AND round_no=1 ORDER BY round_no, match_no");
    $qv->execute([$yearId, $sportId]);
    $rows = $qv->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$rows) {
      echo json_encode(['success' => true, 'hasMatches' => false, 'html' => '<div class="text-muted">ยังไม่มีคู่แข่งขัน</div>'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    
    $html = '<div class="mb-3">';
    $html .= '<div class="fw-semibold mb-3">รอบคัดเลือก</div>';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-sm table-bordered align-middle">';
    $html .= '<thead class="table-light"><tr>';
    $html .= '<th style="width:60px" class="text-center">คู่ที่</th>';
    $html .= '<th>คู่แข่งขัน</th>';
    $html .= '<th style="width:200px">วันที่แข่งขัน (วัน/เดือน/ปี พ.ศ.)</th>';
    $html .= '<th style="width:120px">เวลา (24 ชม.)</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($rows as $r) {
      $aBg = bgColorHex($r['side_a_color']);
      $bBg = bgColorHex($r['side_b_color']);
      $matchId = (int)$r['id'];
      
      // แปลงวันที่เป็นรูปแบบไทย dd/mm/yyyy
      $dateValue = '';
      if (!empty($r['match_date'])) {
        $date = new DateTime($r['match_date']);
        $day = $date->format('d');
        $month = $date->format('m');
        $year = (int)$date->format('Y') + 543;
        $dateValue = $day . '/' . $month . '/' . $year;
      }
      
      $html .= '<tr>';
      $html .= '<td class="text-center fw-bold">' . e($r['match_no']) . '</td>';
      $html .= '<td>';
      $html .= '<span class="px-2 py-1 rounded-3 me-1" style="background:' . $aBg . '">สี' . e($r['side_a_color']) . '</span>';
      $html .= '<span class="text-muted">vs</span>';
      $html .= '<span class="px-2 py-1 rounded-3 ms-1" style="background:' . $bBg . '">สี' . e($r['side_b_color']) . '</span>';
      $html .= '</td>';
      $html .= '<td><input type="text" name="match_date[' . $matchId . ']" class="form-control form-control-sm" placeholder="เช่น 15/01/2568" value="' . e($dateValue) . '" pattern="\d{1,2}/\d{1,2}/\d{4}"></td>';
      $html .= '<td><input type="time" name="match_time[' . $matchId . ']" class="form-control form-control-sm" value="' . e($r['match_time'] ?? '') . '"></td>';
      $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '</div>';
    
    // ช่องกรอกวันเวลารอบชิง (ใช้ค่าจาก match แรก)
    $finalDateValue = '';
    $finalTimeValue = '';
    if (!empty($rows[0]['final_date'])) {
      $date = new DateTime($rows[0]['final_date']);
      $day = $date->format('d');
      $month = $date->format('m');
      $year = (int)$date->format('Y') + 543;
      $finalDateValue = $day . '/' . $month . '/' . $year;
    }
    if (!empty($rows[0]['final_time'])) {
      $finalTimeValue = $rows[0]['final_time'];
    }
    
    $html .= '<div class="mt-4">';
    $html .= '<div class="fw-semibold mb-2">รอบชิง (ชิงที่ 3 และชิงชนะเลิศ)</div>';
    $html .= '<div class="row g-2">';
    $html .= '<div class="col-md-6">';
    $html .= '<label class="form-label">วันที่แข่งขันรอบชิง (วัน/เดือน/ปี พ.ศ.)</label>';
    $html .= '<input type="text" name="final_date" class="form-control" placeholder="เช่น 22/01/2569" value="' . e($finalDateValue) . '" pattern="\\d{1,2}/\\d{1,2}/\\d{4}">';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<label class="form-label">เวลา (24 ชม.)</label>';
    $html .= '<input type="time" name="final_time" class="form-control" value="' . e($finalTimeValue) . '">';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'hasMatches' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล'], JSON_UNESCAPED_UNICODE);
  }
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
    $insQualify = $pdo->prepare("INSERT INTO match_pairs
      (year_id, sport_id, round_name, round_no, match_no, match_date, match_time, venue,
       side_a_label, side_a_color, side_b_label, side_b_color, winner, score_a, score_b, status, notes, created_at)
      VALUES (?, ?, 'รอบคัดเลือก', 1, ?, NULL, NULL, NULL, ?, ?, ?, ?, NULL, NULL, NULL, 'scheduled', NULL, NOW())");
      //                                                                                                     ^^^^^^^^^ เปลี่ยนจาก 'pending' เป็น 'scheduled'

    // รอบชิง: เพิ่มเป็นแถวใน match_pairs เพื่อให้กำหนด “คู่ที่/วัน/เวลา” แยกกันได้
    $insThird = $pdo->prepare("INSERT INTO match_pairs
      (year_id, sport_id, round_name, round_no, match_no, match_date, match_time, venue,
       side_a_label, side_a_color, side_b_label, side_b_color, winner, score_a, score_b, status, notes, created_at)
      VALUES (?, ?, 'รอบชิงที่ 3', 2, 1, NULL, NULL, NULL, ?, NULL, ?, NULL, NULL, NULL, NULL, 'scheduled', NULL, NOW())");

    $insFinal = $pdo->prepare("INSERT INTO match_pairs
      (year_id, sport_id, round_name, round_no, match_no, match_date, match_time, venue,
       side_a_label, side_a_color, side_b_label, side_b_color, winner, score_a, score_b, status, notes, created_at)
      VALUES (?, ?, 'รอบชิงชนะเลิศ', 3, 1, NULL, NULL, NULL, ?, NULL, ?, NULL, NULL, NULL, NULL, 'scheduled', NULL, NOW())");

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
      // สลับฝ่ายแบบสุ่มเพื่อเพิ่มความหลากหลาย
      if (random_int(0,1) === 1) { [$c1, $c2] = [$c2, $c1]; }
      $insQualify->execute([$yearId,(int)$sport['id'],$mno++,"สี$c1",$c1,"สี$c2",$c2]);
      $matchDetails[] = "สี{$c1} vs สี{$c2}";
    }

    // ใส่คู่ชิง (อ้างอิงตาม match_no ของรอบคัดเลือกภายในรายการนี้)
    $insThird->execute([$yearId, (int)$sport['id'], 'ผู้แพ้คู่ที่ 1', 'ผู้แพ้คู่ที่ 2']);
    $insFinal->execute([$yearId, (int)$sport['id'], 'ผู้ชนะคู่ที่ 1', 'ผู้ชนะคู่ที่ 2']);
    
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
  
  if ($action==='update_schedule' && !empty($_POST['sport_id'])){
    try {
      $sid = (int)$_POST['sport_id'];
      $sportName = $map[$sid]['name'] ?? "ID:{$sid}";
      
      $matchDates = $_POST['match_date'] ?? [];
      $matchTimes = $_POST['match_time'] ?? [];
      
      // รับวันเวลารอบชิง
      $finalDateStr = trim($_POST['final_date'] ?? '');
      $finalTimeStr = trim($_POST['final_time'] ?? '');
      
      $updated = 0;
      foreach($matchDates as $matchId => $dateStr){
        $matchId = (int)$matchId;
        $dateStr = trim($dateStr);
        $time = trim($matchTimes[$matchId] ?? '');
        
        // แปลงวันที่จากรูปแบบ dd/mm/yyyy (พ.ศ.) เป็น yyyy-mm-dd (ค.ศ.)
        $date = null;
        if ($dateStr !== '' && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
          $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
          $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
          $yearBE = (int)$matches[3];
          $yearCE = $yearBE - 543;
          $date = $yearCE . '-' . $month . '-' . $day;
        }
        
        $time = $time !== '' ? $time : null;
        
        $upd = $pdo->prepare("UPDATE match_pairs SET match_date=?, match_time=? WHERE id=? AND year_id=?");
        $upd->execute([$date, $time, $matchId, $yearId]);
        $updated++;
      }
      
      // แปลงวันที่รอบชิง
      $finalDate = null;
      if ($finalDateStr !== '' && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $finalDateStr, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $yearBE = (int)$matches[3];
        $yearCE = $yearBE - 543;
        $finalDate = $yearCE . '-' . $month . '-' . $day;
      }
      $finalTime = $finalTimeStr !== '' ? $finalTimeStr : null;
      
      // อัปเดตวันเวลารอบชิงให้ทุก match ของกีฬานี้
      $updFinal = $pdo->prepare("UPDATE match_pairs SET final_date=?, final_time=? WHERE sport_id=? AND year_id=?");
      $updFinal->execute([$finalDate, $finalTime, $sid, $yearId]);
      
      // 🔥 LOG: อัปเดตวัน-เวลาแข่งขันสำเร็จ
      log_activity('UPDATE', 'match_pairs', $sid, 
        sprintf("อัปเดตวัน-เวลาแข่งขัน: %s | อัปเดต %d คู่ | รอบชิง: %s %s | ปีการศึกษา ID:%d", 
          $sportName, 
          $updated,
          $finalDate ?? '-',
          $finalTime ?? '-',
          $yearId));
      
      flash('ok', "บันทึกวัน-เวลาแข่งขันสำเร็จ ({$sportName})");
    } catch(Throwable $e){
      // 🔥 LOG: อัปเดตวัน-เวลาแข่งขันไม่สำเร็จ
      log_activity('ERROR', 'match_pairs', $sid ?? null, 
        sprintf("อัปเดตวัน-เวลาแข่งขันไม่สำเร็จ: %s | กีฬา: %s", 
          $e->getMessage(), 
          $sportName ?? 'unknown'));
      
      flash('err','บันทึกไม่สำเร็จ: '.$e->getMessage());
    }
    header('Location: '.BASE_URL.'/matches.php'.$qs); exit;
  }
  
  // ---------------- Import CSV ทั้งหมด ----------------
  if ($action==='import_all_csv' && isset($_FILES['csv_file'])) {
    try {
      $file = $_FILES['csv_file'];
      
      if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
      }
      
      if (!str_ends_with(strtolower($file['name']), '.csv')) {
        throw new Exception('กรุณาอัปโหลดไฟล์ .csv เท่านั้น');
      }
      
      $content = file_get_contents($file['tmp_name']);
      $content = str_replace("\xEF\xBB\xBF", '', $content); // ลบ BOM
      $lines = explode("\n", $content);
      
      // ข้าม header
      array_shift($lines);
      
      $pdo->beginTransaction();
      $updateCount = 0;
      $errorLines = [];
      
      // สร้าง map ของกีฬา (ดึงเฉพาะที่มี match_pairs)
      $qSports = $pdo->prepare("SELECT DISTINCT s.id, s.name 
                                FROM sports s 
                                INNER JOIN match_pairs mp ON s.id = mp.sport_id 
                                WHERE mp.year_id = ?");
      $qSports->execute([$yearId]);
      $sportMap = [];
      foreach ($qSports as $s) {
        $sportMap[$s['name']] = $s['id'];
      }
      
      foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Parse CSV (รองรับ quoted fields)
        $cols = str_getcsv($line);
        if (count($cols) < 6) {
          $errorLines[] = ($lineNum + 2); // +2 เพราะข้าม header และเริ่มต้นที่ 1
          continue;
        }
        
        $sportName = trim($cols[0]);
        $roundName = trim($cols[1]);
        $matchNo = (int)trim($cols[2]);
        // $pair = trim($cols[3]); // ไม่ใช้
        $dateStr = trim($cols[4]);
        $timeStr = trim($cols[5]);
        $finalDateStr = trim($cols[6] ?? '');
        $finalTimeStr = trim($cols[7] ?? '');
        
        if (!isset($sportMap[$sportName])) {
          $errorLines[] = ($lineNum + 2) . " (ไม่พบกีฬา: $sportName)";
          continue;
        }
        
        $sportId = $sportMap[$sportName];
        
        // แปลงวันที่จาก dd/mm/yyyy (BE) เป็น yyyy-mm-dd (CE)
        $mysqlDate = null;
        if (!empty($dateStr) && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $m)) {
          $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
          $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
          $year = (int)$m[3] - 543; // แปลง BE เป็น CE
          $mysqlDate = "$year-$month-$day";
        }
        
        // แปลงวันที่รอบชิง
        $mysqlFinalDate = null;
        if (!empty($finalDateStr) && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $finalDateStr, $m)) {
          $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
          $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
          $year = (int)$m[3] - 543;
          $mysqlFinalDate = "$year-$month-$day";
        }
        
        // อัปเดตในฐานข้อมูล
        $qUpdate = $pdo->prepare("UPDATE match_pairs 
                                  SET match_date = ?, match_time = ?, final_date = ?, final_time = ? 
                                  WHERE year_id = ? AND sport_id = ? AND round_name = ? AND match_no = ?");
        $result = $qUpdate->execute([$mysqlDate, $timeStr ?: null, $mysqlFinalDate, $finalTimeStr ?: null, $yearId, $sportId, $roundName, $matchNo]);
        
        if ($result && $qUpdate->rowCount() > 0) {
          $updateCount++;
        }
      }
      
      $pdo->commit();
      
      $msg = "Import สำเร็จ: อัปเดต {$updateCount} คู่";
      if (!empty($errorLines)) {
        $msg .= " | ข้ามบรรทัดที่มีข้อผิดพลาด: " . implode(', ', array_slice($errorLines, 0, 10));
        if (count($errorLines) > 10) {
          $msg .= " และอื่นๆ";
        }
      }
      
      log_activity('UPDATE', 'match_pairs', null, $msg);
      flash('ok', $msg);
      
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      log_activity('ERROR', 'match_pairs', null, "Import CSV ไม่สำเร็จ: {$e->getMessage()}");
      flash('err', 'Import ไม่สำเร็จ: ' . $e->getMessage());
    }
    
    header('Location: ' . BASE_URL . '/matches.php' . $qs);
    exit;
  }

  // ---------------- Import Template CSV (กำหนดคู่ที่/วัน/เวลา) ----------------
  if ($action==='import_schedule_template_csv' && isset($_FILES['csv_file'])) {
    $mainSport = trim($_POST['sport_name'] ?? '');
    try {
      $file = $_FILES['csv_file'];
      if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
      }
      if (!preg_match('/\.csv$/i', (string)$file['name'])) {
        throw new Exception('กรุณาอัปโหลดไฟล์ .csv เท่านั้น');
      }

      ensure_match_pairs_schedule_no($pdo);
      $hasScheduleNo = db_table_has_column($pdo, 'match_pairs', 'schedule_no');

      $content = file_get_contents($file['tmp_name']);
      $content = str_replace("\xEF\xBB\xBF", '', $content);
      $lines = preg_split('/\r\n|\n|\r/', $content);
      if (!$lines || count($lines) < 2) {
        throw new Exception('ไฟล์ CSV ว่างหรือรูปแบบไม่ถูกต้อง');
      }

      $header = str_getcsv(array_shift($lines));
      $idx = array_flip($header);
      if (!isset($idx['match_id'])) {
        throw new Exception('ไม่พบคอลัมน์ match_id');
      }

      $pdo->beginTransaction();
      $updated = 0;
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $cols = str_getcsv($line);
        $matchId = (int)($cols[$idx['match_id']] ?? 0);
        if ($matchId <= 0) continue;

        $scheduleNoRaw = $idx['schedule_no'] ?? null;
        $scheduleNo = null;
        if ($scheduleNoRaw !== null) {
          $sn = trim((string)($cols[$scheduleNoRaw] ?? ''));
          $scheduleNo = ($sn === '') ? null : (int)$sn;
        }

        $dateBE = trim((string)($cols[$idx['match_date_be']] ?? ''));
        $dateCE = be_to_ce_date($dateBE);

        $time = trim((string)($cols[$idx['match_time']] ?? ''));
        $time = $time !== '' ? $time : null;

        $venue = trim((string)($cols[$idx['venue']] ?? ''));
        $venue = $venue !== '' ? $venue : null;

        if ($hasScheduleNo) {
          $upd = $pdo->prepare('UPDATE match_pairs SET schedule_no=?, match_date=?, match_time=?, venue=? WHERE id=? AND year_id=?');
          $upd->execute([$scheduleNo, $dateCE, $time, $venue, $matchId, $yearId]);
        } else {
          $upd = $pdo->prepare('UPDATE match_pairs SET match_date=?, match_time=?, venue=? WHERE id=? AND year_id=?');
          $upd->execute([$dateCE, $time, $venue, $matchId, $yearId]);
        }
        $updated++;
      }

      $pdo->commit();
      log_activity('UPDATE', 'match_pairs', null, sprintf('Import template กำหนดคู่ที่/วันเวลา: %s | อัปเดต %d แถว | ปีการศึกษา ID:%d', $mainSport ?: '-', $updated, $yearId));
      flash('ok', 'Import template สำเร็จ (' . $updated . ' แถว)');
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      log_activity('ERROR', 'match_pairs', null, 'Import template ไม่สำเร็จ: ' . $e->getMessage());
      flash('err', 'Import template ไม่สำเร็จ: ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/matches.php' . $qs);
    exit;
  }
}

// ---------------- page ----------------
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
$ok=flash('ok'); $err=flash('err');
?>

<!-- เพิ่ม SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  .swal2-popup {
    font-family: 'Kanit', sans-serif;
  }
</style>

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

  <!-- แถบเครื่องมือ -->
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-lg-5">
          <label class="form-label small text-muted mb-1">กีฬาหลัก</label>
          <div class="input-group input-group-sm">
            <select id="mainSportSelect" class="form-select">
              <option value="">ทุกกีฬา</option>
              <?php
                // ดึงชื่อกีฬาหลัก (คำแรก) แบบ DISTINCT สำหรับรายการแข่งขัน
                $matchSportsStmt = $pdo->prepare("SELECT DISTINCT 
                                                    SUBSTRING_INDEX(s.name, ' ', 1) AS main_sport_name
                                                  FROM sports s
                                                  JOIN sport_categories c ON c.id = s.category_id
                                                  INNER JOIN match_pairs mp ON s.id = mp.sport_id
                                                  WHERE s.year_id = :y AND mp.year_id = :y2
                                                  ORDER BY main_sport_name");
                $matchSportsStmt->execute([':y'=>$yearId, ':y2'=>$yearId]);
                $matchSportsList = $matchSportsStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($matchSportsList as $msp):
              ?>
                <option value="<?php echo e($msp['main_sport_name']); ?>"><?php echo e($msp['main_sport_name']); ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline-danger" onclick="exportMatchList()">📄 PDF</button>
          </div>
          <div class="form-text">เลือกเพื่อใช้งาน “กำหนดตาราง” และ Template CSV</div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
            <button type="button" class="btn btn-primary btn-sm" onclick="openMainSchedule()">🗓️ กำหนดคู่ที่/วัน/เวลา</button>

            <div class="dropdown">
              <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                CSV / Template
              </button>
              <ul class="dropdown-menu">
                <li><h6 class="dropdown-header">Template (กีฬาหลัก)</h6></li>
                <li><a class="dropdown-item" href="#" onclick="downloadScheduleTemplate(); return false;">🧾 Export Template กำหนดคู่ที่/วันเวลา</a></li>
                <li><a class="dropdown-item" href="#" onclick="triggerImportScheduleTemplate(); return false;">📤 Import Template กำหนดคู่ที่/วันเวลา</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">ทั้งระบบ</h6></li>
                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/matches.php?action=export_all_csv">📥 Export ตารางแข่งขัน (ทั้งหมด) CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="triggerImportAllCsv(); return false;">📤 Import ตารางแข่งขัน (ทั้งหมด) CSV</a></li>
              </ul>
            </div>

            <div class="dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                เครื่องมือ
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="confirmGenAll(); return false;">🎲 สุ่มทั้งหมด (ตามตัวกรอง)</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="confirmClearAll(); return false;">🗑️ ล้างทั้งหมด (ตามตัวกรอง)</a></li>
              </ul>
            </div>

            <!-- ฟอร์มซ่อนไว้สำหรับ submit จาก JS -->
            <form method="post" id="genAllForm" class="d-none">
              <input type="hidden" name="action" value="gen_all">
            </form>
            <form method="post" id="clearAllForm" class="d-none">
              <input type="hidden" name="action" value="clear_all">
            </form>

            <!-- ฟอร์มนำเข้า CSV (ซ่อน) -->
            <form method="post" enctype="multipart/form-data" id="importForm" style="display:none;">
              <input type="hidden" name="action" value="import_all_csv">
              <input type="file" id="importFileInput" name="csv_file" accept=".csv" onchange="confirmImport(event)">
            </form>

            <form method="post" enctype="multipart/form-data" id="importScheduleForm" style="display:none;">
              <input type="hidden" name="action" value="import_schedule_template_csv">
              <input type="hidden" name="sport_name" id="importScheduleSportName" value="">
              <input type="file" id="importScheduleFileInput" name="csv_file" accept=".csv" onchange="confirmImportScheduleTemplate(event)">
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  function getMainSportSelection() {
    var sel = document.getElementById('mainSportSelect');
    return sel ? sel.value : '';
  }

  function triggerImportAllCsv() {
    var input = document.getElementById('importFileInput');
    if (input) input.click();
  }

  function triggerImportScheduleTemplate() {
    var input = document.getElementById('importScheduleFileInput');
    if (input) input.click();
  }

  function exportMatchList() {
    var sportName = getMainSportSelection();
    var url = '<?php echo BASE_URL; ?>/export_match_list.php';
    if (sportName) {
      url += '?sport_name=' + encodeURIComponent(sportName);
    }
    window.open(url, '_blank');
  }

  function openMainSchedule() {
    var sportName = getMainSportSelection();
    if (!sportName) {
      Swal.fire({
        icon: 'info',
        title: 'กรุณาเลือกกีฬาหลัก',
        text: 'เลือกกีฬาหลักก่อน เพื่อไปหน้ากำหนดคู่ที่/วัน/เวลา',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#0d6efd'
      });
      return;
    }
    var url = '<?php echo BASE_URL; ?>/matches_schedule.php?sport_name=' + encodeURIComponent(sportName);
    window.location.href = url;
  }

  function downloadScheduleTemplate() {
    var sportName = getMainSportSelection();
    if (!sportName) {
      Swal.fire({
        icon: 'info',
        title: 'กรุณาเลือกกีฬาหลัก',
        text: 'เลือกกีฬาหลักก่อน เพื่อ Export template CSV',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#0d6efd'
      });
      return;
    }
    var url = '<?php echo BASE_URL; ?>/matches.php?action=export_schedule_template_csv&sport_name=' + encodeURIComponent(sportName);
    window.location.href = url;
  }

  async function confirmImportScheduleTemplate(event) {
    var sportName = getMainSportSelection();
    const file = event.target.files[0];
    if (!file) return;

    if (!sportName) {
      await Swal.fire({
        icon: 'info',
        title: 'กรุณาเลือกกีฬาหลัก',
        text: 'เลือกกีฬาหลักก่อน เพื่อ Import template CSV',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#0d6efd'
      });
      event.target.value = '';
      return;
    }

    const result = await Swal.fire({
      icon: 'question',
      title: 'ยืนยันการ Import Template?',
      html: `กีฬา: <strong>${sportName}</strong><br>ไฟล์: <strong>${file.name}</strong><br><span class="text-muted">ระบบจะอัปเดตคู่ที่/วัน/เวลา/สนาม ตาม match_id</span>`,
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน Import',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });

    if (result.isConfirmed) {
      document.getElementById('importScheduleSportName').value = sportName;
      document.getElementById('importScheduleForm').submit();
    } else {
      event.target.value = '';
    }
  }
  </script>

  <?php if($ok): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ!',
        text: '<?php echo addslashes($ok); ?>',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#0d6efd',
        timer: 3000,
        timerProgressBar: true
      });
    </script>
  <?php endif; ?>
  
  <?php if($err): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด!',
        text: '<?php echo addslashes($err); ?>',
        confirmButtonText: 'ตรวจสอบ',
        confirmButtonColor: '#dc3545'
      });
    </script>
  <?php endif; ?>

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
                  <button class="btn btn-sm btn-outline-secondary" onclick="openScheduleModal(<?php echo $sid; ?>, '<?php echo addslashes($s['name']); ?>')" <?php echo $cnt? '':'disabled'; ?>>ดู</button>
                  <form method="post" class="d-inline" id="genForm<?php echo $sid; ?>">
                    <input type="hidden" name="action" value="gen_one">
                    <input type="hidden" name="sport_id" value="<?php echo $sid; ?>">
                    <button type="button" class="btn btn-sm btn-primary" onclick="confirmGenOne(<?php echo $sid; ?>, '<?php echo addslashes($s['name']); ?>')">สุ่ม</button>
                  </form>
                  <form method="post" class="d-inline" id="clearForm<?php echo $sid; ?>">
                    <input type="hidden" name="action" value="clear_one">
                    <input type="hidden" name="sport_id" value="<?php echo $sid; ?>">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmClearOne(<?php echo $sid; ?>, '<?php echo addslashes($s['name']); ?>')">ล้าง</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal สำหรับแก้ไขวัน-เวลาแข่งขัน -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scheduleModalTitle">กำลังโหลด...</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo BASE_URL; ?>/matches.php" id="scheduleForm">
        <input type="hidden" name="action" value="update_schedule">
        <input type="hidden" name="sport_id" id="scheduleSportId">
        <div class="modal-body" id="scheduleModalBody">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">กำลังโหลด...</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
          <button type="submit" class="btn btn-primary btn-sm" id="scheduleSaveBtn" style="display:none;">💾 บันทึกวัน-เวลาแข่งขัน</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // ฟังก์ชันเปิด Modal และโหลดข้อมูล
  async function openScheduleModal(sportId, sportName) {
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    document.getElementById('scheduleModalTitle').textContent = 'คู่การแข่งขัน — ' + sportName + ' (รอบคัดเลือก)';
    document.getElementById('scheduleSportId').value = sportId;
    document.getElementById('scheduleModalBody').innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">กำลังโหลด...</span>
        </div>
      </div>`;
    document.getElementById('scheduleSaveBtn').style.display = 'none';
    
    modal.show();
    
    try {
      const response = await fetch('<?php echo BASE_URL; ?>/matches.php?action=get_schedule&sport_id=' + sportId);
      const data = await response.json();
      
      if (data.success) {
        document.getElementById('scheduleModalBody').innerHTML = data.html;
        if (data.hasMatches) {
          document.getElementById('scheduleSaveBtn').style.display = 'inline-block';
        }
      } else {
        document.getElementById('scheduleModalBody').innerHTML = '<div class="alert alert-danger">' + (data.message || 'เกิดข้อผิดพลาด') + '</div>';
      }
    } catch (error) {
      document.getElementById('scheduleModalBody').innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้</div>';
    }
  }
  
  async function confirmGenAll() {
    const result = await Swal.fire({
      icon: 'question',
      title: 'ยืนยันการสุ่มทั้งหมด?',
      html: 'สุ่มรอบคัดเลือกทั้งหมดของรายการที่กรองอยู่<br><span class="text-danger">ของเดิมจะถูกล้าง</span>',
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    
    if (result.isConfirmed) {
      // แสดง animation กำลังสุ่ม
      showRandomizingAnimation();
      
      // Submit form หลังจาก animation เสร็จและรอ 2 วินาที
      setTimeout(() => {
        document.getElementById('genAllForm').submit();
      }, 5000); // 3 วิสำหรับ animation + 2 วิแสดงผล
    }
  }
  
  function showRandomizingAnimation() {
    const colors = ['เขียว', 'ฟ้า', 'ชมพู', 'ส้ม'];
    const colorStyles = {
      'เขียว': '#28a745',
      'ฟ้า': '#17a2b8',
      'ชมพู': '#e83e8c',
      'ส้ม': '#ffc107'
    };
    
    let shuffleInterval;
    let currentColors = [...colors];
    let shuffleCount = 0;
    const maxShuffles = 20;
    
    Swal.fire({
      title: '<div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🎲 กำลังสุ่มคู่แข่งขัน... 🎲</div>',
      html: `
        <div style="padding: 20px;">
          <div id="shuffleDisplay" style="display: flex; justify-content: center; gap: 15px; margin: 30px 0; flex-wrap: wrap;">
            ${colors.map(c => `<div class="color-badge" style="background: ${colorStyles[c]}; color: white; padding: 15px 25px; border-radius: 12px; font-weight: bold; font-size: 1.2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;">สี${c}</div>`).join('')}
          </div>
          <div style="margin: 20px 0;">
            <div class="progress" style="height: 8px; border-radius: 10px; background: #e9ecef;">
              <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: linear-gradient(90deg, #198754, #20c997);"></div>
            </div>
          </div>
          <div id="shuffleCounter" style="color: #6c757d; font-size: 0.9rem; margin-top: 10px;">กำลังประมวลผล...</div>
        </div>
      `,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        const progressBar = document.getElementById('progressBar');
        const shuffleCounter = document.getElementById('shuffleCounter');
        const badges = document.querySelectorAll('.color-badge');
        
        // Animation หมุนสุ่มสี
        shuffleInterval = setInterval(() => {
          shuffleCount++;
          const progress = Math.min((shuffleCount / maxShuffles) * 100, 95);
          progressBar.style.width = progress + '%';
          
          // Shuffle array
          for (let i = currentColors.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [currentColors[i], currentColors[j]] = [currentColors[j], currentColors[i]];
          }
          
          // Update badges with animation
          badges.forEach((badge, idx) => {
            badge.style.transform = 'scale(0.9) rotateY(180deg)';
            badge.style.opacity = '0.6';
            
            setTimeout(() => {
              badge.textContent = 'สี' + currentColors[idx];
              badge.style.background = colorStyles[currentColors[idx]];
              badge.style.transform = 'scale(1) rotateY(0deg)';
              badge.style.opacity = '1';
            }, 150);
          });
          
          shuffleCounter.innerHTML = `กำลังสุ่ม... <strong style="color: #198754;">${shuffleCount}/${maxShuffles}</strong>`;
          
          if (shuffleCount >= maxShuffles) {
            clearInterval(shuffleInterval);
            progressBar.style.width = '100%';
            shuffleCounter.innerHTML = '<strong style="color: #198754;">✓ เสร็จสิ้น! กำลังบันทึกข้อมูล...</strong>';
            
            // เพิ่ม sparkle effect เมื่อเสร็จ
            badges.forEach((badge, idx) => {
              setTimeout(() => {
                badge.style.boxShadow = '0 0 20px rgba(25, 135, 84, 0.6), 0 4px 6px rgba(0,0,0,0.1)';
                badge.style.transform = 'scale(1.05)';
              }, idx * 100);
            });
          }
        }, 150);
      }
    });
  }

  async function confirmClearAll() {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'ยืนยันการล้างทั้งหมด?',
      html: 'ล้างคู่ทั้งหมดของรายการที่กรองอยู่<br><span class="text-danger fw-bold">ไม่สามารถกู้คืนได้!</span>',
      showCancelButton: true,
      confirmButtonText: 'ยืนยันการลบ',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    
    if (result.isConfirmed) {
      document.getElementById('clearAllForm').submit();
    }
  }

  async function confirmGenOne(sportId, sportName) {
    const result = await Swal.fire({
      icon: 'question',
      title: 'ยืนยันการสุ่ม?',
      html: `สุ่มคู่การแข่งขันสำหรับ<br><strong>${sportName}</strong><br><span class="text-warning">ของเดิม (ถ้ามี) จะถูกล้าง</span>`,
      showCancelButton: true,
      confirmButtonText: 'สุ่มเลย',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    
    if (result.isConfirmed) {
      document.getElementById('genForm' + sportId).submit();
    }
  }

  async function confirmClearOne(sportId, sportName) {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'ยืนยันการล้าง?',
      html: `ล้างคู่การแข่งขันของ<br><strong>${sportName}</strong><br><span class="text-danger fw-bold">ไม่สามารถกู้คืนได้!</span>`,
      showCancelButton: true,
      confirmButtonText: 'ยืนยันการลบ',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    
    if (result.isConfirmed) {
      document.getElementById('clearForm' + sportId).submit();
    }
  }
  
  // ฟังก์ชัน Import CSV ทั้งหมด
  async function confirmImport(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const result = await Swal.fire({
      icon: 'question',
      title: 'ยืนยันการ Import?',
      html: `ไฟล์: <strong>${file.name}</strong><br>ขนาด: ${(file.size / 1024).toFixed(2)} KB<br><br>ระบบจะอัปเดตวัน-เวลาแข่งขันทั้งหมดตาม CSV`,
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน Import',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d'
    });
    
    if (result.isConfirmed) {
      Swal.fire({
        title: 'กำลัง Import...',
        html: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      document.getElementById('importForm').submit();
    } else {
      event.target.value = ''; // Clear file input
    }
  }

  // ฟังก์ชัน Export CSV Template (เก็าไว้สำหรับใช้ใน modal ถ้าต้องการ)
  function exportTemplate() {
    const sportId = document.getElementById('scheduleSportId').value;
    const sportName = document.getElementById('scheduleModalTitle').textContent;
    
    // ดึงข้อมูลจากตาราง
    const rows = document.querySelectorAll('#scheduleModalBody tbody tr');
    if (rows.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'ไม่มีข้อมูล',
        text: 'ไม่พบคู่แข่งขันให้ Export'
      });
      return;
    }
    
    // สร้าง CSV
    let csv = '\uFEFF'; // BOM สำหรับ UTF-8
    csv += 'คู่ที่,คู่แข่งขัน,วันที่แข่งขัน (dd/mm/yyyy),เวลา (24 ชม.)\n';
    
    rows.forEach(row => {
      const matchNo = row.cells[0].textContent.trim();
      const matchPair = row.cells[1].textContent.trim().replace(/\s+/g, ' ');
      const dateInput = row.cells[2].querySelector('input').value;
      const timeInput = row.cells[3].querySelector('input').value;
      
      csv += `${matchNo},"${matchPair}",${dateInput},${timeInput}\n`;
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const filename = `ตารางแข่งขัน_${sportName.replace(/[^\w\u0E00-\u0E7F]/g, '_')}_${new Date().toISOString().slice(0,10)}.csv`;
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Swal.fire({
      icon: 'success',
      title: 'Export สำเร็จ!',
      text: 'ดาวน์โหลดไฟล์ CSV เรียบร้อยแล้ว',
      timer: 2000,
      showConfirmButton: false
    });
  }

  // ฟังก์ชัน Import CSV
  async function importCSV(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // ตรวจสอบนามสกุลไฟล์
    if (!file.name.endsWith('.csv')) {
      Swal.fire({
        icon: 'error',
        title: 'ไฟล์ไม่ถูกต้อง',
        text: 'กรุณาเลือกไฟล์ .csv เท่านั้น'
      });
      event.target.value = '';
      return;
    }
    
    try {
      const text = await file.text();
      const lines = text.split('\n');
      
      // ข้าม header (บรรทัดแรก)
      const dataLines = lines.slice(1).filter(line => line.trim());
      
      if (dataLines.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'ไฟล์ว่างเปล่า',
          text: 'ไม่พบข้อมูลในไฟล์ CSV'
        });
        event.target.value = '';
        return;
      }
      
      // แสดง preview ก่อน import
      const result = await Swal.fire({
        icon: 'question',
        title: 'ยืนยันการ Import?',
        html: `พบข้อมูล <strong>${dataLines.length}</strong> แถว<br>ต้องการนำเข้าข้อมูลหรือไม่?`,
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน Import',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
      });
      
      if (!result.isConfirmed) {
        event.target.value = '';
        return;
      }
      
      // อ่านและแปลงข้อมูล
      const rows = document.querySelectorAll('#scheduleModalBody tbody tr');
      let importCount = 0;
      let errorCount = 0;
      
      dataLines.forEach((line, index) => {
        // แยกคอลัมน์ (รองรับ quoted fields)
        const columns = line.match(/(".*?"|[^,]+)(?=\s*,|\s*$)/g);
        if (!columns || columns.length < 4) {
          errorCount++;
          return;
        }
        
        const matchNo = columns[0].replace(/"/g, '').trim();
        const dateValue = columns[2].replace(/"/g, '').trim();
        const timeValue = columns[3].replace(/"/g, '').trim();
        
        // หา row ที่ตรงกับ matchNo
        rows.forEach(row => {
          const rowMatchNo = row.cells[0].textContent.trim();
          if (rowMatchNo === matchNo) {
            const dateInput = row.cells[2].querySelector('input');
            const timeInput = row.cells[3].querySelector('input');
            
            if (dateValue) dateInput.value = dateValue;
            if (timeValue) timeInput.value = timeValue;
            importCount++;
          }
        });
      });
      
      event.target.value = '';
      
      if (importCount > 0) {
        Swal.fire({
          icon: 'success',
          title: 'Import สำเร็จ!',
          html: `นำเข้าข้อมูล <strong>${importCount}</strong> แถว${errorCount > 0 ? '<br><span class="text-warning">ข้าม ' + errorCount + ' แถว (ข้อมูลไม่ถูกต้อง)</span>' : ''}`,
          confirmButtonText: 'ตกลง'
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Import ไม่สำเร็จ',
          text: 'ไม่พบข้อมูลที่ตรงกัน',
          confirmButtonText: 'ตกลง'
        });
      }
      
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: 'ไม่สามารถอ่านไฟล์ CSV ได้: ' + error.message
      });
      event.target.value = '';
    }
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>