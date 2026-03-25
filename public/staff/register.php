<?php
// public/staff/register.php — เพิ่มการแสดงวงแดงเตือนนักกีฬาที่ลงเกิน
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['staff'])) {
  header('Location: ' . BASE_URL . '/staff/login.php');
  exit;
}
$pdo = db();
$staff = $_SESSION['staff'];
$staffColor = $staff['color'] ?? null;

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function parse_grade_levels($s){
  $p=array_filter(array_map(function($x){ return str_replace('.', '', trim($x)); }, explode(',', (string)$s)));
  return $p?:[];
}
function name_is_male_prefix($firstName){
  return mb_strpos($firstName,'เด็กชาย')===0 || mb_strpos($firstName,'นาย')===0;
}
function name_is_female_prefix($firstName){
  return mb_strpos($firstName,'เด็กหญิง')===0 || mb_strpos($firstName,'นางสาว')===0;
}
function registration_open_safe(PDO $pdo): bool {
  if (function_exists('registration_open')) {
    return registration_open($pdo);
  }
  $y = $pdo->query("SELECT registration_is_open, registration_start, registration_end FROM academic_years WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if (empty($y['registration_is_open'])) {
    return false;
  }
  
  // ตรวจสอบช่วงเวลา
  $now = date('Y-m-d H:i:s');
  
  // ถ้ามีเวลาเริ่ม และยังไม่ถึงเวลาเริ่ม
  if (!empty($y['registration_start']) && $now < $y['registration_start']) {
    return false;
  }
  
  // ถ้ามีเวลาสิ้นสุด และเกินเวลาสิ้นสุดไปแล้ว
  if (!empty($y['registration_end']) && $now > $y['registration_end']) {
    return false;
  }
  
  return true;
}

$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../../includes/header.php';
  include __DIR__ . '/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">⚠️ ยังไม่ได้ตั้งปีการศึกษาให้เป็น Active</div></main>';
  include __DIR__ . '/../../includes/footer.php';
  exit;
}
$registrationOpen = registration_open_safe($pdo);

// -------- โหลดข้อมูลจำนวนกีฬาที่ลงทะเบียนของแต่ละนักเรียน + ตรวจสอบเกิน --------
$studentSportsCount = [];
$studentOverLimit = []; // เก็บ student_id ที่ลงเกินในหมวดใดหมวดหนึ่ง

$stmt = $pdo->prepare("
  SELECT 
    s.id AS student_id,
    sc.id AS category_id,
    sc.name AS category_name,
    COUNT(DISTINCT r.sport_id) AS sport_count,
    COALESCE(cys.max_per_student, sc.max_per_student) AS max_per_student
  FROM students s
  JOIN registrations r ON r.student_id = s.id AND r.year_id = s.year_id
  JOIN sports sp ON sp.id = r.sport_id
  JOIN sport_categories sc ON sc.id = sp.category_id
  LEFT JOIN category_year_settings cys ON cys.category_id = sc.id AND cys.year_id = s.year_id
  WHERE s.year_id = :y AND s.color = :col
  GROUP BY s.id, sc.id
  ORDER BY s.id, sc.id
");
$stmt->execute([':y' => $yearId, ':col' => $staffColor]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $sid = (int)$row['student_id'];
  $catId = (int)$row['category_id'];
  $count = (int)$row['sport_count'];
  $maxPer = (int)($row['max_per_student'] ?? 0);
  
  if (!isset($studentSportsCount[$sid])) {
    $studentSportsCount[$sid] = [];
  }
  
  $studentSportsCount[$sid][$catId] = [
    'count' => $count,
    'max' => $maxPer,
    'over' => ($maxPer > 0 && $count > $maxPer)
  ];
  
  // ถ้าลงเกินในหมวดไหน ให้เก็บ student_id ไว้
  if ($maxPer > 0 && $count > $maxPer) {
    $studentOverLimit[$sid] = true;
  }
}

// ตรวจสอบว่าเป็นโหมด "ดูอย่างเดียว" หรือไม่
$viewMode = isset($_GET['view']) && $_GET['view'] === '1';

// -------- โหลดประเภทกีฬา (ไว้ทำฟิลเตอร์) --------
$catStmt = $pdo->prepare("
  SELECT sc.id, sc.name
  FROM sport_categories sc
  LEFT JOIN category_year_settings cys
    ON cys.category_id=sc.id AND cys.year_id=:y
  WHERE COALESCE(cys.is_active, sc.is_active) = 1
  ORDER BY sc.name
");
$catStmt->execute([':y'=>$yearId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
$categoryFilter = (int)($_GET['category_id'] ?? 0);

$messages=[]; $warnings=[]; $errors=[];

// -------- ACTION: บันทึก (แทนที่ทั้งชุดของสีนี้ในกีฬานี้) --------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='save_lineup') {
  if (!$registrationOpen) {
    $errors[] = '⛔ ขณะนี้ปิดรับลงทะเบียน แก้ไขไม่ได้';
    
    // LOG: พยายามแก้ไขขณะปิดรับลงทะเบียน
    log_activity('ATTEMPT_EDIT_CLOSED', 'registrations', null, 
      'พยายามแก้ไขรายชื่อขณะระบบปิดรับลงทะเบียน | สี: ' . $staffColor);
    
  } else {
    $sportId = (int)($_POST['sport_id'] ?? 0);
    if ($sportId <= 0) { $errors[]='❌ ไม่พบกีฬา'; }

    // ดึงข้อมูลกีฬา + หมวด + เพดานการลงต่อหมวด
    $st = $pdo->prepare("
      SELECT s.id,s.name,s.gender,s.team_size,s.grade_levels,s.category_id,
             sc.name AS category_name, COALESCE(cys.max_per_student, sc.max_per_student) AS max_per_student
      FROM sports s
      JOIN sport_categories sc ON sc.id=s.category_id
      LEFT JOIN category_year_settings cys
        ON cys.category_id=sc.id AND cys.year_id=s.year_id
      WHERE s.id=? AND s.year_id=? AND s.is_active=1
    ");
    $st->execute([$sportId,$yearId]);
    $sport = $st->fetch(PDO::FETCH_ASSOC);
    if (!$sport) { $errors[]='❌ ไม่พบกีฬานี้ในปีการศึกษาปัจจุบัน'; }

    // รวบรวม student_id_* (อนุญาตให้เว้นว่างได้)
    $teamSize = (int)($sport['team_size'] ?? 0);
    $chosen = [];
    for ($i=1;$i<=$teamSize;$i++){
      $sid = (int)($_POST['student_id_'.$i] ?? 0);
      if ($sid>0) $chosen[] = $sid;
    }
    $chosen = array_values(array_unique($chosen));
    if (count($chosen) > $teamSize) {
      $errors[] = '⚠️ จำนวนผู้เล่นเกินที่กำหนด (สูงสุด '.$teamSize.' คน)';
    }

    if (!$errors) {
      // โหลดข้อมูลนักเรียนที่เลือก
      $students = [];
      if ($chosen) {
        $in = implode(',', array_fill(0,count($chosen),'?'));
        $params = array_merge([$yearId], $chosen);
        $q = $pdo->prepare("SELECT id, first_name, last_name, class_level, color, student_code
                            FROM students WHERE year_id=? AND id IN ($in)");
        $q->execute($params);
        while($r=$q->fetch(PDO::FETCH_ASSOC)){ $students[(int)$r['id']]=$r; }
      }

      $allowedLevels = parse_grade_levels($sport['grade_levels']);
      $gender = $sport['gender']; // 'ช','ญ','รวม'

      foreach ($chosen as $sid) {
        if (empty($students[$sid])) { $errors[]='❌ พบรหัสนักเรียนที่ไม่ถูกต้อง'; break; }
        $stu = $students[$sid];
        if ($stu['color'] !== $staffColor) { $errors[]='❌ มีนักเรียนที่ไม่ใช่สีของคุณ'; break; }
        // normalize ทั้งสองฝั่ง (ลบจุด) ก่อนเทียบ
        $stuLevel = str_replace('.', '', trim($stu['class_level']));
        if ($allowedLevels && !in_array($stuLevel, $allowedLevels, true)) {
          $errors[]='❌ มีนักเรียนชั้นที่ไม่ตรงตามที่กีฬากำหนด'; break;
        }
        if ($gender==='ช' && !name_is_male_prefix($stu['first_name'])) { $errors[]='❌ มีนักเรียนที่ไม่ใช่เพศชาย'; break; }
        if ($gender==='ญ' && !name_is_female_prefix($stu['first_name'])) { $errors[]='❌ มีนักเรียนที่ไม่ใช่เพศหญิง'; break; }
      }

      // ==== ตรวจเงื่อนไข "หนึ่งคนลงได้กี่กีฬาในหมวดนี้" (แจ้งเตือนเท่านั้น) ====
      if (!$errors && $chosen) {
        $maxPer = (int)($sport['max_per_student'] ?? 0); // 0 = ไม่จำกัด
        if ($maxPer > 0) {
          $overNames = [];
          $chk = $pdo->prepare("
            SELECT COUNT(*) FROM registrations r
            JOIN sports sx ON sx.id = r.sport_id
            WHERE r.year_id=? AND r.student_id=? AND sx.category_id=?
          ");
          foreach ($chosen as $sid) {
            $chk->execute([$yearId, $sid, (int)$sport['category_id']]);
            $countInCat = (int)$chk->fetchColumn();
            if ($countInCat >= $maxPer) {
              $nm = $students[$sid]['first_name'].' '.$students[$sid]['last_name'];
              $overNames[] = $nm.' (ลงแล้ว '.$countInCat.'/'.$maxPer.' ในประเภท '.$sport['category_name'].')';
            }
          }
          if ($overNames) {
            $warnings[] = '⚠️ มีผู้เล่นที่ลงเกินจำนวนสูงสุดที่อนุญาตต่อประเภทกีฬา: <br>- '.implode('<br>- ', array_map('e', $overNames));
          }
        }
      }
      // ================================================================

      if (!$errors) {
        try{
          $pdo->beginTransaction();
          
          // ดึงข้อมูลเดิมก่อนลบ (เพื่อ log)
          $oldRegStmt = $pdo->prepare("
            SELECT s.first_name, s.last_name, s.student_code
            FROM registrations r
            JOIN students s ON s.id = r.student_id
            WHERE r.year_id=? AND r.sport_id=? AND r.color=?
          ");
          $oldRegStmt->execute([$yearId, $sportId, $staffColor]);
          $oldPlayers = $oldRegStmt->fetchAll(PDO::FETCH_ASSOC);
          $oldPlayerNames = array_map(function($p) {
            return $p['student_code'] . ' ' . $p['first_name'] . ' ' . $p['last_name'];
          }, $oldPlayers);
          
          // ลบชุดเดิมของสีนี้ในกีฬานี้ก่อน
          $del = $pdo->prepare("DELETE FROM registrations WHERE year_id=? AND sport_id=? AND color=?");
          $del->execute([$yearId,$sportId,$staffColor]);

          // เก็บข้อมูลผู้เล่นใหม่
          $newPlayerNames = [];
          
          // แทรกชุดใหม่ (เท่าจำนวนที่เลือก)
          if ($chosen) {
            $ins = $pdo->prepare("INSERT INTO registrations (year_id,sport_id,student_id,color) VALUES (?,?,?,?)");
            foreach ($chosen as $sid) {
              $ins->execute([$yearId,$sportId,$sid,$staffColor]);
              
              // เก็บชื่อผู้เล่นใหม่
              if (!empty($students[$sid])) {
                $stu = $students[$sid];
                $newPlayerNames[] = $stu['student_code'] . ' ' . $stu['first_name'] . ' ' . $stu['last_name'];
              }
            }
          }
          
          $pdo->commit();
          
          // LOG: บันทึกสำเร็จ
          $actionType = count($oldPlayers) > 0 ? 'UPDATE' : 'CREATE';
          $logDetails = sprintf(
            "กีฬา: %s | สี: %s | ผู้เล่นเดิม: %d คน [%s] → ผู้เล่นใหม่: %d คน [%s]",
            $sport['name'],
            $staffColor,
            count($oldPlayers),
            count($oldPlayerNames) > 0 ? implode(', ', $oldPlayerNames) : '-',
            count($chosen),
            count($newPlayerNames) > 0 ? implode(', ', $newPlayerNames) : '-'
          );
          
          log_activity($actionType, 'registrations', $sportId, $logDetails);
          
          $messages[] = '✅ บันทึกสำเร็จ: อัปเดตรายชื่อทีมสี'.e($staffColor).' ในกีฬา '.e($sport['name']);
          
        }catch(Throwable $e){
          $pdo->rollBack();
          
          // LOG: บันทึกไม่สำเร็จ
          log_activity('ERROR', 'registrations', $sportId, 
            'บันทึกไม่สำเร็จ: ' . $e->getMessage() . ' | กีฬา: ' . ($sport['name'] ?? 'unknown') . ' | สี: ' . $staffColor);
          
          $errors[] = '❌ บันทึกไม่สำเร็จ: '.$e->getMessage();
        }
      }
    }
  }
}

// -------- เปิดโหมด "กีฬาเดียว" ถ้ามี sport_id --------
$sportId = (int)($_GET['sport_id'] ?? 0);
$sportDetail = null; $eligibleStudents = []; $teamSize = 0; $minSize = 0; $studentMap = [];
$prefill = []; // รายชื่อที่ลงทะเบียนไว้แล้วของสีนี้
if ($sportId>0) {
  $st = $pdo->prepare("SELECT id,name,gender,participant_type,team_size,min_participants,grade_levels,category_id FROM sports WHERE id=? AND year_id=? AND is_active=1");
  $st->execute([$sportId,$yearId]);
  $sportDetail = $st->fetch(PDO::FETCH_ASSOC);
  if ($sportDetail) {
    $teamSize = (int)$sportDetail['team_size'];
    $minSize = (int)($sportDetail['min_participants'] ?? $teamSize);
    $levels = parse_grade_levels($sportDetail['grade_levels']);
    $gender = $sportDetail['gender'];

    // รายชื่อที่ลงทะเบียนไว้แล้วของ "สีนี้"
    $qPrefill = $pdo->prepare("
      SELECT s.id,
             CONCAT(s.first_name,' ',s.last_name) AS fullname,
             s.student_code, s.class_level, s.class_room, s.number_in_room
      FROM registrations r
      JOIN students s ON s.id=r.student_id AND s.year_id=r.year_id
      WHERE r.year_id=? AND r.sport_id=? AND r.color=?
      ORDER BY
        CASE WHEN s.class_level LIKE 'ป%' THEN 1
             WHEN s.class_level LIKE 'ม%' THEN 2
             ELSE 3 END,
        CAST(REPLACE(SUBSTRING(s.class_level, 2), '.', '') AS UNSIGNED),
        s.class_room, s.number_in_room, s.first_name, s.last_name
    ");
    $qPrefill->execute([$yearId,$sportId,$staffColor]);
    $prefill = $qPrefill->fetchAll(PDO::FETCH_ASSOC);

    // ถ้าไม่ใช่ view mode ให้โหลดข้อมูลสำหรับแก้ไข
    if (!$viewMode && $registrationOpen) {
      // เงื่อนไขเพศ
      $genderCond = "1=1";
      if ($gender==='ช') {
        $genderCond = "(s.first_name LIKE 'เด็กชาย%' OR s.first_name LIKE 'นาย%')";
      } elseif ($gender==='ญ') {
        $genderCond = "(s.first_name LIKE 'เด็กหญิง%' OR s.first_name LIKE 'นางสาว%')";
      }

      $levelPlaceholders = [];
      if ($levels) {
        foreach ($levels as $idx => $lv) {
          $levelPlaceholders[] = ":lv{$idx}";
        }
      }

      $sql = "
        SELECT s.id,
               CONCAT(s.first_name,' ',s.last_name) AS fullname,
               s.student_code,
               s.class_level, s.class_room, s.number_in_room
        FROM students s
        WHERE s.year_id=:yid AND s.color=:col
          AND $genderCond
          ".($levels ? "AND REPLACE(s.class_level, '.', '') IN (" . implode(',', $levelPlaceholders). ")" : "")."
        ORDER BY
          CASE WHEN s.class_level LIKE 'ป%' THEN 1
               WHEN s.class_level LIKE 'ม%' THEN 2
               ELSE 3 END,
          CAST(REPLACE(SUBSTRING(s.class_level, 2), '.', '') AS UNSIGNED),
          s.class_room, 
          CAST(s.number_in_room AS UNSIGNED), 
          s.first_name, s.last_name
      ";
      $bind = ['yid'=>$yearId, 'col'=>$staffColor];
      if ($levels) {
        foreach ($levels as $idx => $lv) {
          $bind["lv{$idx}"] = $lv;
        }
      }

      $q = $pdo->prepare($sql);
      $q->execute($bind);
      $eligibleStudents = $q->fetchAll(PDO::FETCH_ASSOC);

      // หา student_id ที่ลงทะเบียนแล้วในกีฬานี้ แต่เป็นสีอื่น (ให้ exclude)
      $blockedStmt = $pdo->prepare("SELECT student_id FROM registrations WHERE year_id=? AND sport_id=? AND color<>?");
      $blockedStmt->execute([$yearId, $sportId, $staffColor]);
      $blockedIds = $blockedStmt->fetchAll(PDO::FETCH_COLUMN, 0);
      $blockedMap = [];
      foreach ($blockedIds as $bid) { $blockedMap[(int)$bid] = true; }

      $prefillIds = [];
      foreach ($prefill as $row) { $prefillIds[] = (int)$row['id']; }

      foreach (array_merge($prefill, $eligibleStudents) as $row) {
        $sid = (int)$row['id'];
        if (isset($blockedMap[$sid]) && !in_array($sid, $prefillIds, true)) {
          continue;
        }
        $label = $row['student_code'].' '.$row['fullname'].' ('.$row['class_level'].'/'.$row['class_room'].' เลขที่ '.$row['number_in_room'].')';
        $studentMap[$label] = $sid;
      }
    }
  }
}

// -------- ตารางกีฬา (โหมดรายการ) + ตรวจสอบมีนักกีฬาเกินหรือไม่ --------
$where = ["s.year_id=:y", "s.is_active=1"];
$params = [':y'=>$yearId];
if ($categoryFilter>0) { $where[]="s.category_id=:cid"; $params[':cid']=$categoryFilter; }

$sqlSports = "
  SELECT s.id, s.name, s.gender, s.participant_type, s.team_size, s.min_participants, s.grade_levels,
         sc.name AS category_name,
         (SELECT COUNT(*) FROM registrations r WHERE r.year_id=s.year_id AND r.sport_id=s.id AND r.color=:color) AS reg_count
  FROM sports s
  JOIN sport_categories sc ON sc.id=s.category_id
  WHERE ".implode(' AND ',$where)."
  ORDER BY sc.name, s.name
";
$stList = $pdo->prepare($sqlSports);
$stList->execute(array_merge($params, [':color'=>$staffColor]));
$sports = $stList->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่าแต่ละกีฬามีนักกีฬาที่ลงเกินหรือไม่
$sportHasOverLimit = [];
foreach ($sports as $sp) {
  $sid = (int)$sp['id'];
  $qOver = $pdo->prepare("
    SELECT DISTINCT r.student_id
    FROM registrations r
    WHERE r.year_id = :y AND r.sport_id = :sid AND r.color = :col
  ");
  $qOver->execute([':y' => $yearId, ':sid' => $sid, ':col' => $staffColor]);
  $studentIds = $qOver->fetchAll(PDO::FETCH_COLUMN);
  
  $hasOver = false;
  foreach ($studentIds as $stid) {
    if (isset($studentOverLimit[(int)$stid])) {
      $hasOver = true;
      break;
    }
  }
  $sportHasOverLimit[$sid] = $hasOver;
}

// Color themes
$colorThemes = [
  'เขียว' => ['bg' => '#d4edda', 'hex' => '#28a745', 'light' => '#e8f5e9'],
  'ฟ้า'   => ['bg' => '#d1ecf1', 'hex' => '#17a2b8', 'light' => '#e1f5fe'],
  'ชมพู'  => ['bg' => '#f8d7da', 'hex' => '#e83e8c', 'light' => '#fce4ec'],
  'ส้ม'   => ['bg' => '#fff3cd', 'hex' => '#fd7e14', 'light' => '#fff8e1'],
];
$currentTheme = $colorThemes[$staffColor] ?? ['bg' => '#f8f9fa', 'hex' => '#6c757d', 'light' => '#f8f9fa'];

$pageTitle = 'ลงทะเบียนกีฬา - สี' . $staffColor;
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/navbar.php';
?>

<style>
  body {
    background: linear-gradient(135deg, <?php echo $currentTheme['light']; ?> 0%, #ffffff 100%);
  }
  .page-header {
    background: linear-gradient(135deg, <?php echo $currentTheme['hex']; ?>, <?php echo $currentTheme['hex']; ?>dd);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 24px <?php echo $currentTheme['hex']; ?>33;
  }
  .sport-card {
    border-radius: 1rem;
    border: 2px solid <?php echo $currentTheme['hex']; ?>33;
    transition: all 0.2s;
  }
  .sport-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    border-color: <?php echo $currentTheme['hex']; ?>;
  }
  .sport-card.has-over-limit {
    border-color: #dc3545 !important;
    border-width: 3px !important;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
  }
  .sport-card.has-over-limit:hover {
    box-shadow: 0 8px 16px rgba(220, 53, 69, 0.3), 0 0 0 3px rgba(220, 53, 69, 0.2);
  }
  .sport-card.below-min {
    border-left: 4px solid #ffc107 !important;
  }
  .over-limit-badge {
    background: #dc3545;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    animation: pulse 2s infinite;
  }
  .below-min-badge {
    background: #ffc107;
    color: #856404;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    font-weight: 600;
  }
  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
  }
  .form-card {
    background: white;
    border-radius: 1rem;
    border: 2px solid <?php echo $currentTheme['hex']; ?>33;
  }
  .player-input {
    border-radius: 0.75rem;
    border: 2px solid #e5e7eb;
    transition: all 0.2s;
  }
  .player-input:focus {
    border-color: <?php echo $currentTheme['hex']; ?>;
    box-shadow: 0 0 0 0.2rem <?php echo $currentTheme['hex']; ?>33;
  }
  .player-input.over-limit-input {
    border-color: #dc3545 !important;
    border-width: 3px !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
  }
  .player-input.over-limit-input:focus {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.3rem rgba(220, 53, 69, 0.35) !important;
  }
  .status-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 1rem;
    font-weight: 500;
  }
  .filter-card {
    background: white;
    border-radius: 1rem;
    border: 2px solid <?php echo $currentTheme['hex']; ?>33;
  }
  .view-only-card {
    background: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-left: 4px solid <?php echo $currentTheme['hex']; ?>;
  }
  .view-only-card.over-limit-card {
    border-left-color: #dc3545;
    background: #fff5f5;
  }
  .view-mode-badge {
    background: <?php echo $currentTheme['hex']; ?>33;
    color: <?php echo $currentTheme['hex']; ?>;
    padding: 0.5rem 1rem;
    border-radius: 1rem;
    font-weight: 500;
    display: inline-block;
  }
  .swal2-popup {
    font-family: 'Kanit', sans-serif;
  }
</style>

<!-- เพิ่ม SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<main class="container py-4">
  <!-- Page Header -->
  <div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h3 class="mb-1">
          <?php echo $viewMode ? '👁️ ดูรายชื่อนักกีฬา' : '✍️ ลงทะเบียนกีฬา'; ?>
        </h3>
        <p class="mb-0 opacity-75">
          <?php echo $viewMode ? 'ดูรายชื่อนักกีฬาที่ลงทะเบียนแล้ว' : 'จัดการรายชื่อนักกีฬา'; ?> 
          สี<?php echo e($staffColor); ?>
        </p>
      </div>
      <div class="text-end">
        <div style="font-size: 2.5rem; opacity: 0.7;">
          <?php echo $viewMode ? '👁️' : '🏆'; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  <?php if (!$registrationOpen && !$viewMode): ?>
    <div class="alert alert-warning border-0 shadow-sm">
      <strong>⛔ ระบบกำลังปิดรับลงทะเบียน</strong> ขณะนี้ดูข้อมูลได้อย่างเดียว
    </div>
  <?php endif; ?>

  <?php if ($viewMode): ?>
    <div class="alert alert-info border-0 shadow-sm">
      <strong>👁️ โหมดดูอย่างเดียว</strong> คุณกำลังดูรายชื่อที่ลงทะเบียนไว้แล้ว
    </div>
  <?php endif; ?>

  <?php if ($messages): ?>
    <div class="alert alert-success border-0 shadow-sm">
      <?php echo implode('<br>', $messages); ?>
    </div>
  <?php endif; ?>
  <?php if ($warnings): ?>
    <div class="alert alert-warning border-0 shadow-sm">
      <?php echo implode('<br>', $warnings); ?>
    </div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger border-0 shadow-sm">
      <?php echo implode('<br>', $errors); ?>
    </div>
  <?php endif; ?>

  <?php if ($sportId>0 && $sportDetail): ?>
    <!-- โหมดเลือก/แก้ไข/ดูผู้เล่น -->
    <div class="mb-3">
      <a href="<?php echo BASE_URL; ?>/staff/register.php" class="btn btn-outline-secondary">
        ← กลับรายการกีฬา
      </a>
      <span class="ms-3 text-muted">
        ลงแล้ว (สี<?php echo e($staffColor); ?>): 
        <strong style="color: <?php echo $currentTheme['hex']; ?>;">
          <?php echo count($prefill); ?>/<?php echo (int)$sportDetail['team_size']; ?>
        </strong> คน
        <?php if (count($prefill) > 0 && count($prefill) < $minSize): ?>
          <span class="below-min-badge ms-2">⚠️ ยังไม่ถึงขั้นต่ำ (ต้อง <?php echo $minSize; ?> คน)</span>
        <?php endif; ?>
      </span>
      <?php if ($viewMode): ?>
        <span class="ms-2 view-mode-badge">👁️ โหมดดู</span>
      <?php endif; ?>
    </div>

    <div class="card form-card shadow-sm">
      <div class="card-body p-4">
        <h5 class="mb-1">🏅 <?php echo e($sportDetail['name']); ?></h5>
        <div class="d-flex flex-wrap gap-3 mb-4 text-muted">
          <span>👫 เพศ: <strong><?php echo e($sportDetail['gender']); ?></strong></span>
          <span>🎯 รูปแบบ: <strong><?php echo e($sportDetail['participant_type']); ?></strong></span>
          <span>👥 จำนวนต่อสี: <strong><?php echo (int)$sportDetail['team_size']; ?></strong> (ขั้นต่ำ: <?php echo $minSize; ?>)</span>
          <?php if (!empty($sportDetail['grade_levels'])): ?>
            <span>🎓 ชั้นที่เปิด: <strong><?php echo e($sportDetail['grade_levels']); ?></strong></span>
          <?php endif; ?>
        </div>

        <?php if ($viewMode): ?>
          <!-- โหมดดูอย่างเดียว -->
          <?php if (empty($prefill)): ?>
            <div class="text-center py-5">
              <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
              <p class="text-muted">ยังไม่มีการลงทะเบียนในกีฬานี้</p>
            </div>
          <?php else: ?>
            <div class="mb-3">
              <h6 class="fw-bold mb-3">👥 รายชื่อนักกีฬาที่ลงทะเบียนแล้ว:</h6>
              <?php foreach ($prefill as $idx => $player): 
                $playerId = (int)$player['id'];
                $isOverLimit = isset($studentOverLimit[$playerId]);
                $cardClass = $isOverLimit ? 'view-only-card over-limit-card' : 'view-only-card';
              ?>
                <div class="<?php echo $cardClass; ?>">
                  <div class="d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px; border: 2px solid <?php echo $isOverLimit ? '#dc3545' : $currentTheme['hex']; ?>;">
                      <strong style="color: <?php echo $isOverLimit ? '#dc3545' : $currentTheme['hex']; ?>;"><?php echo $idx + 1; ?></strong>
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-bold"><?php echo e($player['fullname']); ?></div>
                      <div class="small text-muted">
                        รหัส: <?php echo e($player['student_code']); ?> | 
                        ชั้น: <?php echo e($player['class_level']); ?>/<?php echo e($player['class_room']); ?> 
                        เลขที่: <?php echo e($player['number_in_room']); ?>
                      </div>
                      <?php if ($isOverLimit): ?>
                        <div class="mt-2">
                          <span class="over-limit-badge">⚠️ ลงเกินจำนวนที่กำหนด</span>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div>
                      <span class="badge" style="background: <?php echo $isOverLimit ? '#dc3545' : $currentTheme['hex']; ?>;">
                        <?php echo $isOverLimit ? '⚠️ เกิน' : '✓ ลงทะเบียนแล้ว'; ?>
                      </span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="mt-4">
            <a class="btn btn-light" href="<?php echo BASE_URL; ?>/staff/register.php">← กลับรายการกีฬา</a>
          </div>

        <?php else: ?>
          <!-- โหมดแก้ไข -->
          <form method="post" action="<?php echo BASE_URL; ?>/staff/register.php?sport_id=<?php echo (int)$sportId; ?>" id="lineupForm">
            <input type="hidden" name="action" value="save_lineup">
            <input type="hidden" name="sport_id" value="<?php echo (int)$sportId; ?>">

            <datalist id="students_datalist">
              <?php foreach($studentMap as $lbl => $id): ?>
                <option value="<?php echo e($lbl); ?>"></option>
              <?php endforeach; ?>
            </datalist>

            <div class="row g-3">
              <?php
                $prefillLabels = [];
                foreach ($prefill as $row) {
                  $prefillLabels[] = $row['student_code'].' '.$row['fullname'].' ('.$row['class_level'].'/'.$row['class_room'].' เลขที่ '.$row['number_in_room'].')';
                }
                for ($i=1;$i<=$teamSize;$i++):
                  $val = $prefillLabels[$i-1] ?? '';
                  $prefillId = $val && isset($studentMap[$val]) ? $studentMap[$val] : 0;
                  $isOverLimit = $prefillId > 0 && isset($studentOverLimit[$prefillId]);
                  $inputClass = $isOverLimit ? 'form-control player-input student-input over-limit-input' : 'form-control player-input student-input';
              ?>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    👤 ผู้เล่นที่ <?php echo $i; ?>
                    <?php if ($isOverLimit): ?>
                      <span class="badge bg-danger ms-2">⚠️ ลงเกิน</span>
                    <?php endif; ?>
                  </label>
                  <input type="text" 
                         class="<?php echo $inputClass; ?>" 
                         list="students_datalist" 
                         placeholder="🔍 พิมพ์ค้นหา รหัส/ชื่อ..." 
                         autocomplete="off" 
                         value="<?php echo e($val); ?>" 
                         data-student-id="<?php echo (int)$prefillId; ?>"
                         <?php echo !$registrationOpen?'disabled':''; ?>>
                  <input type="hidden" name="student_id_<?php echo $i; ?>" class="student-id-hidden" value="<?php echo (int)$prefillId; ?>">
                  <?php if ($isOverLimit): ?>
                    <div class="form-text text-danger">⚠️ นักกีฬาคนนี้ลงทะเบียนเกินจำนวนที่กำหนดในบางหมวด</div>
                  <?php else: ?>
                    <div class="form-text">💡 ปล่อยว่าง = ไม่ใช้ช่องนี้</div>
                  <?php endif; ?>
                </div>
              <?php endfor; ?>
            </div>

            <div class="mt-4 d-flex gap-2">
              <a class="btn btn-light" href="<?php echo BASE_URL; ?>/staff/register.php">← ย้อนกลับ</a>
              <button class="btn text-white" 
                      style="background: <?php echo $currentTheme['hex']; ?>;"
                      <?php echo !$registrationOpen?'disabled':''; ?>>
                ✅ บันทึกการลงทะเบียน
              </button>
            </div>
          </form>

          <script>
            const regOpen = <?php echo $registrationOpen ? 'true':'false'; ?>;
            const studentOverLimit = <?php echo json_encode($studentOverLimit); ?>;
            const studentMap = <?php echo json_encode($studentMap, JSON_UNESCAPED_UNICODE); ?>;
            
            if (regOpen) {
              const form = document.getElementById('lineupForm');
              const inputs = form.querySelectorAll('.student-input');
              
              // ตรวจสอบเมื่อเลือกนักกีฬา
              inputs.forEach(input => {
                input.addEventListener('input', function() {
                  const label = this.value.trim();
                  const studentId = studentMap[label] || 0;
                  const hiddenInput = this.parentElement.querySelector('.student-id-hidden');
                  
                  if (studentId && studentOverLimit[studentId]) {
                    this.classList.add('over-limit-input');
                    const labelElement = this.parentElement.querySelector('label');
                    if (!labelElement.querySelector('.badge')) {
                      const badge = document.createElement('span');
                      badge.className = 'badge bg-danger ms-2';
                      badge.textContent = '⚠️ ลงเกิน';
                      labelElement.appendChild(badge);
                    }
                    const helpText = this.parentElement.querySelector('.form-text');
                    if (helpText && !helpText.classList.contains('text-danger')) {
                      helpText.className = 'form-text text-danger';
                      helpText.textContent = '⚠️ นักกีฬาคนนี้ลงทะเบียนเกินจำนวนที่กำหนดในบางหมวด';
                    }
                  } else {
                    this.classList.remove('over-limit-input');
                    const labelElement = this.parentElement.querySelector('label');
                    const badge = labelElement.querySelector('.badge');
                    if (badge) badge.remove();
                    const helpText = this.parentElement.querySelector('.form-text');
                    if (helpText) {
                      helpText.className = 'form-text';
                      helpText.textContent = '💡 ปล่อยว่าง = ไม่ใช้ช่องนี้';
                    }
                  }
                  
                  this.setAttribute('data-student-id', studentId);
                  hiddenInput.value = studentId || '';
                });
              });
              
              form.addEventListener('submit', function(ev){
                const used = new Set();
                let hasError = false;
                let errorMessage = '';
                
                inputs.forEach(input => {
                  const label = input.value.trim();
                  const hid = input.parentElement.querySelector('.student-id-hidden');
                  if (label === '') { hid.value = ''; return; }
                  const id = studentMap[label] || 0;
                  if (!id && !hasError) { 
                    hasError = true;
                    errorMessage = 'กรุณาเลือกจากรายการ หรือปล่อยว่าง';
                    return;
                  }
                  if (used.has(id) && !hasError) { 
                    hasError = true;
                    errorMessage = 'มีชื่อผู้เล่นซ้ำกันในฟอร์ม';
                    return;
                  }
                  used.add(id);
                  hid.value = id;
                });
                
                if (hasError) {
                  ev.preventDefault();
                  Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถบันทึกได้',
                    text: errorMessage,
                    confirmButtonText: 'ตกลง'
                  });
                }
              });
            }
          </script>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <!-- โหมดตารางรายการกีฬา -->
    <div class="card filter-card shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
          <div>
            <h5 class="mb-1">📋 รายการกีฬา</h5>
            <p class="text-muted small mb-0">
              <?php echo $registrationOpen ? 'เลือกกีฬาเพื่อลงทะเบียนนักกีฬา' : 'เลือกกีฬาเพื่อดูรายชื่อนักกีฬา'; ?>
            </p>
          </div>
          <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/staff/register.php" id="filterForm">
            <div class="col-auto">
              <label class="form-label small text-muted mb-1">🔍 ค้นหากีฬา</label>
              <input type="text" 
                     id="sportSearch" 
                     class="form-control" 
                     placeholder="พิมพ์ชื่อกีฬา..."
                     style="border-color: <?php echo $currentTheme['hex']; ?>66; min-width: 200px;"
                     autocomplete="off">
            </div>
            <div class="col-auto">
              <label class="form-label small text-muted mb-1">🏅 ประเภทกีฬา</label>
              <select class="form-select" name="category_id" style="border-color: <?php echo $currentTheme['hex']; ?>66;" onchange="this.form.submit()">
                <option value="0">ทั้งหมด</option>
                <?php foreach($categories as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>" <?php echo $categoryFilter===(int)$c['id']?'selected':''; ?>>
                    <?php echo e($c['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="sportsContainer" class="table-responsive">
      <?php if (!$sports): ?>
        <div class="text-center py-5">
          <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
          <p class="text-muted">ยังไม่มีกีฬาที่เปิดในปีนี้</p>
        </div>
      <?php else: 
        foreach($sports as $sp):
          $sid = (int)$sp['id'];
          $reg = (int)$sp['reg_count'];
          $cap = (int)$sp['team_size'];
          $minCap = (int)($sp['min_participants'] ?? $cap);
          $left = max(0, $cap - $reg);
          $progress = $cap > 0 ? ($reg / $cap) * 100 : 0;
          $hasOverLimit = $sportHasOverLimit[$sid] ?? false;
          $belowMin = ($reg > 0 && $reg < $minCap); // ลงแล้ว แต่ยังไม่ถึงขั้นต่ำ
          $cardClass = $hasOverLimit ? 'card sport-card has-over-limit shadow-sm mb-3' : 'card sport-card shadow-sm mb-3';
          if ($belowMin) $cardClass .= ' below-min';
      ?>
        <div class="<?php echo $cardClass; ?>">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h6 class="mb-1 fw-bold"><?php echo e($sp['name']); ?></h6>
                  <?php if ($hasOverLimit): ?>
                    <span class="over-limit-badge">⚠️ มีนักกีฬาลงเกิน</span>
                  <?php endif; ?>
                  <?php if ($belowMin): ?>
                    <span class="below-min-badge">⚠️ ยังไม่ถึงขั้นต่ำ (<?php echo $reg; ?>/<?php echo $minCap; ?>)</span>
                  <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 text-muted small">
                  <span>📂 <?php echo e($sp['category_name']); ?></span>
                  <span>👫 <?php echo e($sp['gender']); ?></span>
                  <span>🎯 <?php echo e($sp['participant_type']); ?></span>
                  <?php if ($sp['grade_levels']): ?>
                    <span>🎓 <?php echo e($sp['grade_levels']); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-md-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="flex-grow-1">
                    <div class="progress" style="height: 1.5rem; border-radius: 1rem;">
                      <div class="progress-bar" 
                           style="width: <?php echo $progress; ?>%; background: <?php echo $hasOverLimit ? '#dc3545' : $currentTheme['hex']; ?>;"
                           role="progressbar">
                        <?php echo $reg; ?>/<?php echo $cap; ?>
                      </div>
                    </div>
                  </div>
                  <span class="status-badge <?php echo $left>0?'bg-success':'bg-secondary'; ?> text-white">
                    <?php echo $left>0 ? "เหลือ {$left}" : "เต็ม"; ?>
                  </span>
                </div>
              </div>
              <div class="col-md-2 text-end">
                <?php if ($registrationOpen): ?>
                  <!-- ปุ่มแก้ไข (เปิดลงทะเบียน) -->
                  <a class="btn btn-sm text-white"
                     style="background: <?php echo $hasOverLimit ? '#dc3545' : $currentTheme['hex']; ?>;"
                     href="<?php echo BASE_URL; ?>/staff/register.php?sport_id=<?php echo (int)$sp['id']; ?>">
                     <?php echo $reg>0 ? '✏️ แก้ไข' : '✍️ ลงทะเบียน'; ?>
                  </a>
                <?php else: ?>
                  <!-- ปุ่มดู (ปิดลงทะเบียน) -->
                  <a class="btn btn-sm <?php echo $hasOverLimit ? 'btn-danger' : 'btn-outline-secondary'; ?>"
                     href="<?php echo BASE_URL; ?>/staff/register.php?sport_id=<?php echo (int)$sp['id']; ?>&view=1">
                     👁️ ดูรายชื่อ
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <script>
      // Filter กีฬาแบบ real-time
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('sportSearch');
        const sportsContainer = document.getElementById('sportsContainer');
        const sportCards = sportsContainer.querySelectorAll('.sport-card');
        
        if (searchInput && sportCards.length > 0) {
          searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            sportCards.forEach(function(card) {
              const sportName = card.querySelector('h6').textContent.toLowerCase();
              const categoryName = card.querySelector('.text-muted.small span:first-child').textContent.toLowerCase();
              
              // ค้นหาทั้งชื่อกีฬาและประเภท
              if (sportName.includes(searchText) || categoryName.includes(searchText)) {
                card.style.display = '';
                visibleCount++;
              } else {
                card.style.display = 'none';
              }
            });
            
            // แสดงข้อความถ้าไม่พบ
            let noResultMsg = sportsContainer.querySelector('.no-results');
            if (visibleCount === 0 && searchText !== '') {
              if (!noResultMsg) {
                noResultMsg = document.createElement('div');
                noResultMsg.className = 'no-results text-center py-5';
                noResultMsg.innerHTML = '<div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div><p class="text-muted">ไม่พบกีฬา "' + searchText + '"</p>';
                sportsContainer.appendChild(noResultMsg);
              }
            } else if (noResultMsg) {
              noResultMsg.remove();
            }
          });
          
          // Focus ที่ช่องค้นหาเมื่อกด /
          document.addEventListener('keydown', function(e) {
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
              e.preventDefault();
              searchInput.focus();
            }
          });
        }
      });
    </script>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
