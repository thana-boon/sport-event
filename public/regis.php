<?php
// public/regis.php — แผงควบคุมผู้ดูแล (God Mode) เวอร์ชันใช้ includes/navbar.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo = db();
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function parse_grade_levels($s){
  // normalize: ลบจุด (.) ออก เพื่อให้ "ป.4" กลายเป็น "ป4"
  $p=array_filter(array_map(function($x){ return str_replace('.', '', trim($x)); }, explode(',', (string)$s)));
  return $p?:[];
}
function name_is_male_prefix($f){ return mb_strpos($f,'เด็กชาย')===0 || mb_strpos($f,'นาย')===0; }
function name_is_female_prefix($f){ return mb_strpos($f,'เด็กหญิง')===0 || mb_strpos($f,'นางสาว')===0; }

$yearId = active_year_id($pdo);
$yearBe = null; $regOpen = false; $regStart = null; $regEnd = null;
if ($yearId) {
  $st = $pdo->prepare("SELECT year_be,registration_is_open,registration_start,registration_end FROM academic_years WHERE id=?");
  $st->execute([$yearId]);
  if ($row=$st->fetch(PDO::FETCH_ASSOC)){
    $yearBe  = (int)$row['year_be'];
    $regOpen = (bool)$row['registration_is_open'];
    $regStart= $row['registration_start'];
    $regEnd  = $row['registration_end'];
  }
}

$messages=[]; $errors=[]; $warnings=[];

/* 1) สวิตช์ เปิด/ปิดลงทะเบียน */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='toggle_registration') {
  if (!$yearId) { $errors[]='ยังไม่ได้ตั้งปีการศึกษาให้ Active'; }
  else {
    $oldStatus = $regOpen;
    $oldStart = $regStart;
    $oldEnd = $regEnd;
    
    $val   = isset($_POST['registration_is_open']) ? 1 : 0;
    $start = ($_POST['registration_start'] ?? '') ?: null;
    $end   = ($_POST['registration_end'] ?? '') ?: null;
    
    $up = $pdo->prepare("UPDATE academic_years SET registration_is_open=?,registration_start=?,registration_end=? WHERE id=?");
    $up->execute([$val,$start,$end,$yearId]);
    $regOpen=(bool)$val; $regStart=$start; $regEnd=$end;
    
    // 🔥 LOG: เปลี่ยนสถานะการลงทะเบียน
    $changes = [];
    if ($oldStatus !== (bool)$val) {
      $changes[] = "สถานะ: " . ($oldStatus ? 'เปิด' : 'ปิด') . " → " . ($val ? 'เปิด' : 'ปิด');
    }
    if ($oldStart !== $start) {
      $changes[] = "เริ่ม: " . ($oldStart ?: '-') . " → " . ($start ?: '-');
    }
    if ($oldEnd !== $end) {
      $changes[] = "สิ้นสุด: " . ($oldEnd ?: '-') . " → " . ($end ?: '-');
    }
    
    log_activity('UPDATE', 'academic_years', $yearId, 
      sprintf("เปลี่ยนสถานะการลงทะเบียน | ปี %d | %s", 
        $yearBe,
        !empty($changes) ? implode(' | ', $changes) : 'ไม่มีการเปลี่ยนแปลง'));
    
    $messages[]='อัปเดตสถานะการลงทะเบียนเรียบร้อย';
  }
}

/* 2) โหลดหมวดกีฬา (ไว้กรอง + max_per_student) */
$catStmt=$pdo->prepare("
  SELECT sc.id, sc.name, COALESCE(cys.max_per_student, sc.max_per_student) AS max_per_student
  FROM sport_categories sc
  LEFT JOIN category_year_settings cys ON cys.category_id=sc.id AND cys.year_id=:y
  ORDER BY sc.name
");
$catStmt->execute([':y'=>$yearId]);
$categories=$catStmt->fetchAll(PDO::FETCH_ASSOC);
$catInfo=[]; foreach($categories as $c){ $catInfo[(int)$c['id']] = ['name'=>$c['name'],'max'=> is_null($c['max_per_student'])?0:(int)$c['max_per_student']]; }
$categoryFilter=(int)($_GET['category_id']??0);

/* 3) บันทึกจัดทีม (แอดมิน) */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='admin_save_lineup') {
  $sportId  = (int)($_POST['sport_id'] ?? 0);
  $teamColor= trim($_POST['team_color'] ?? '');
  if ($sportId<=0 || $teamColor===''){ $errors[]='ข้อมูลไม่ครบ (กีฬา/สี)'; }
  else {
    $st=$pdo->prepare("
      SELECT s.id,s.name,s.gender,s.participant_type,s.team_size,s.grade_levels,s.category_id,
             sc.name AS category_name, COALESCE(cys.max_per_student, sc.max_per_student) AS max_per_student
      FROM sports s
      JOIN sport_categories sc ON sc.id=s.category_id
      LEFT JOIN category_year_settings cys ON cys.category_id=sc.id AND cys.year_id=s.year_id
      WHERE s.id=? AND s.year_id=? AND s.is_active=1
    ");
    $st->execute([$sportId,$yearId]);
    $sport=$st->fetch(PDO::FETCH_ASSOC);
    if (!$sport){ $errors[]='ไม่พบกีฬานี้ในปีการศึกษาปัจจุบัน'; }
    else{
      $teamSize=(int)$sport['team_size']; $chosen=[];
      for($i=1;$i<=$teamSize;$i++){ $sid=(int)($_POST['student_id_'.$i]??0); if($sid>0)$chosen[]=$sid; }
      $chosen=array_values(array_unique($chosen));
      if (count($chosen)>$teamSize){ $errors[]='จำนวนผู้เล่นเกินที่กำหนด (สูงสุด '.$teamSize.' คน)'; }

      if (!$errors){
        $students=[];
        if ($chosen){
          $in=implode(',',array_fill(0,count($chosen),'?'));
          $params=array_merge([$yearId],$chosen);
          $q=$pdo->prepare("SELECT id,first_name,last_name,class_level,color FROM students WHERE year_id=? AND id IN ($in)");
          $q->execute($params);
          while($r=$q->fetch(PDO::FETCH_ASSOC)){$students[(int)$r['id']]=$r;}
        }
        $allowed=parse_grade_levels($sport['grade_levels']); $gender=$sport['gender'];
        foreach($chosen as $sid){
          if(empty($students[$sid])){ $errors[]='พบรหัสนักเรียนที่ไม่ถูกต้อง'; break; }
          $stu=$students[$sid];
          if($stu['color']!==$teamColor){ $errors[]='มีนักเรียนที่ไม่ใช่สีที่เลือก'; break; }
          // normalize ทั้งสองฝั่ง (ลบจุด) ก่อนเทียบ
          $stuLevel = str_replace('.', '', trim($stu['class_level']));
          if($allowed && !in_array($stuLevel,$allowed,true)){ $errors[]='ชั้นไม่ตรงตามเงื่อนไขกีฬา'; break; }
          if($gender==='ช' && !name_is_male_prefix($stu['first_name'])){ $errors[]='มีนักเรียนที่ไม่ใช่เพศชาย'; break; }
          if($gender==='ญ' && !name_is_female_prefix($stu['first_name'])){ $errors[]='มีนักเรียนที่ไม่ใช่เพศหญิง'; break; }
        }
        // แจ้งเตือน (ไม่บล็อก) ถ้าเกิน max_per_student ของหมวด
        if(!$errors && $chosen){
          $maxPer=(int)($sport['max_per_student']??0);
          if($maxPer>0){
            $over=[];
            $chk=$pdo->prepare("
              SELECT COUNT(*) FROM registrations r
              JOIN sports sx ON sx.id=r.sport_id
              WHERE r.year_id=? AND r.student_id=? AND sx.category_id=?
            ");
            foreach($chosen as $sid){
              $chk->execute([$yearId,$sid,(int)$sport['category_id']]);
              $cnt=(int)$chk->fetchColumn();
              if($cnt>=$maxPer){ $nm=$students[$sid]['first_name'].' '.$students[$sid]['last_name']; $over[]=$nm.' (ลงแล้ว '.$cnt.'/'.$maxPer.' ในประเภท '.$sport['category_name'].')'; }
            }
            if($over){ $warnings[]='เกินจำนวนสูงสุดต่อประเภทกีฬา:<br>- '.e(implode('<br>- ',$over)); }
          }
        }
        if(!$errors){
          try{
            $pdo->beginTransaction();
            
            // ดึงข้อมูลเดิมก่อนลบ (สำหรับ log)
            $oldStmt = $pdo->prepare("
              SELECT s.first_name, s.last_name, s.student_code
              FROM registrations r
              JOIN students s ON s.id = r.student_id
              WHERE r.year_id=? AND r.sport_id=? AND r.color=?
              ORDER BY s.first_name, s.last_name
            ");
            $oldStmt->execute([$yearId, $sportId, $teamColor]);
            $oldPlayers = $oldStmt->fetchAll(PDO::FETCH_ASSOC);
            $oldPlayerNames = array_map(function($p) {
              return $p['student_code'] . ' ' . $p['first_name'] . ' ' . $p['last_name'];
            }, $oldPlayers);
            
            $del=$pdo->prepare("DELETE FROM registrations WHERE year_id=? AND sport_id=? AND color=?");
            $del->execute([$yearId,$sportId,$teamColor]);
            
            $newPlayerNames = [];
            if($chosen){
              $ins=$pdo->prepare("INSERT INTO registrations (year_id,sport_id,student_id,color) VALUES (?,?,?,?)");
              foreach($chosen as $sid){ 
                $ins->execute([$yearId,$sportId,$sid,$teamColor]);
                $stu = $students[$sid];
                $newPlayerNames[] = $stu['first_name'] . ' ' . $stu['last_name'];
              }
            }
            
            $pdo->commit();
            
            // 🔥 LOG: บันทึกจัดทีมสำเร็จ
            $logDetail = sprintf("จัดทีม: %s | สี%s | จำนวน: %d/%d คน", 
              $sport['name'], 
              $teamColor,
              count($chosen),
              $teamSize);
            
            if (!empty($oldPlayerNames)) {
              $logDetail .= sprintf(" | ทีมเดิม: [%s]", implode(', ', $oldPlayerNames));
            } else {
              $logDetail .= " | ทีมเดิม: -";
            }
            
            if (!empty($newPlayerNames)) {
              $logDetail .= sprintf(" | ทีมใหม่: [%s]", implode(', ', $newPlayerNames));
            } else {
              $logDetail .= " | ทีมใหม่: ล้างทีม";
            }
            
            $logDetail .= " | ปีการศึกษา ID:{$yearId}";
            
            log_activity('UPDATE', 'registrations', $sportId, $logDetail);
            
            $messages[]='บันทึกสำเร็จ: อัปเดตรายชื่อทีมสี'.$teamColor.' ในกีฬา '.e($sport['name']);
          }catch(Throwable $e){ 
            $pdo->rollBack(); 
            
            // 🔥 LOG: บันทึกจัดทีมไม่สำเร็จ
            log_activity('ERROR', 'registrations', $sportId, 
              sprintf("จัดทีมไม่สำเร็จ: %s | กีฬา: %s | สี%s | จำนวน: %d คน", 
                $e->getMessage(), 
                $sport['name'], 
                $teamColor,
                count($chosen)));
            
            $errors[]='บันทึกไม่สำเร็จ: '.$e->getMessage(); 
          }
        }
      }
    }
  }
}

/* 6) ลบนักเรียนที่ลงทะเบียนทั้งหมด */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete_all_registrations') {
  $confirm = trim($_POST['confirm_delete'] ?? '');
  if ($confirm === 'DELETE') {
    try {
      // นับจำนวนก่อนลบ
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE year_id=?");
      $countStmt->execute([$yearId]);
      $totalCount = (int)$countStmt->fetchColumn();
      
      // นับตามกีฬาและสี
      $detailStmt = $pdo->prepare("
        SELECT s.name AS sport_name, r.color, COUNT(*) AS cnt
        FROM registrations r
        JOIN sports s ON s.id = r.sport_id
        WHERE r.year_id = ?
        GROUP BY s.name, r.color
        ORDER BY s.name, r.color
      ");
      $detailStmt->execute([$yearId]);
      $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
      $detailText = array_map(function($d) {
        return "{$d['sport_name']} สี{$d['color']} ({$d['cnt']} คน)";
      }, $details);
      
      $stmt = $pdo->prepare("DELETE FROM registrations WHERE year_id=?");
      $stmt->execute([$yearId]);
      $deleted = $stmt->rowCount();
      
      // 🔥 LOG: ลบการลงทะเบียนทั้งหมดสำเร็จ
      log_activity('DELETE', 'registrations', null, 
        sprintf("ลบการลงทะเบียนทั้งหมด: %d รายการ | ปี %d | รายละเอียด: [%s]", 
          $deleted,
          $yearBe,
          !empty($detailText) ? implode(', ', $detailText) : 'ไม่มีข้อมูล'));
      
      $messages[] = "✅ ลบการลงทะเบียนทั้งหมด {$deleted} รายการ เรียบร้อย (ปีการศึกษา {$yearBe})";
    } catch (Throwable $e) {
      // 🔥 LOG: ลบการลงทะเบียนทั้งหมดไม่สำเร็จ
      log_activity('ERROR', 'registrations', null, 
        sprintf("ลบการลงทะเบียนทั้งหมดไม่สำเร็จ: %s | ปี %d", 
          $e->getMessage(), 
          $yearBe));
      
      $errors[] = 'ลบไม่สำเร็จ: '.e($e->getMessage());
    }
  } else {
    $errors[] = 'ยืนยันไม่ถูกต้อง (ต้องพิมพ์คำว่า DELETE ตัวพิมพ์ใหญ่)';
  }
}

/* 4) โหลดโหมดจัดทีม (ผ่าน GET) */
$sportId = (int)($_GET['sport_id'] ?? 0);
$teamColor = trim($_GET['color'] ?? '');
$sportDetail=null; $prefill=[]; $eligible=[]; $studentMap=[]; $teamSize=0;
if ($sportId>0 && $teamColor!=='') {
  $st=$pdo->prepare("SELECT id,name,gender,participant_type,team_size,grade_levels,category_id FROM sports WHERE id=? AND year_id=? AND is_active=1");
  $st->execute([$sportId,$yearId]);
  $sportDetail=$st->fetch(PDO::FETCH_ASSOC);
  if($sportDetail){
    $teamSize=(int)$sportDetail['team_size']; $levels=parse_grade_levels($sportDetail['grade_levels']); $gender=$sportDetail['gender'];
    $qPref=$pdo->prepare("
      SELECT s.id, CONCAT(s.first_name,' ',s.last_name) AS fullname, s.student_code, s.class_level, s.class_room, s.number_in_room
      FROM registrations r
      JOIN students s ON s.id=r.student_id AND s.year_id=r.year_id
      WHERE r.year_id=? AND r.sport_id=? AND r.color=?
      ORDER BY
        CASE WHEN s.class_level LIKE 'ป%' THEN 1 WHEN s.class_level LIKE 'ม%' THEN 2 ELSE 3 END,
        CAST(REPLACE(SUBSTRING(s.class_level,2), '.', '') AS UNSIGNED), s.class_room, s.number_in_room, s.first_name, s.last_name
    ");
    $qPref->execute([$yearId,$sportId,$teamColor]);
    $prefill=$qPref->fetchAll(PDO::FETCH_ASSOC);

    $genderCond="1=1";
    if($gender==='ช') $genderCond="(s.first_name LIKE 'เด็กชาย%' OR s.first_name LIKE 'นาย%')";
    elseif($gender==='ญ') $genderCond="(s.first_name LIKE 'เด็กหญิง%' OR s.first_name LIKE 'นางสาว%')";

   // เปลี่ยนจาก ? เป็น named param :lv0, :lv1, ... เพื่อให้ตรงกับจำนวน bind
   $levelPlaceholders = [];
   if ($levels) {
     foreach ($levels as $idx => $lv) {
       $levelPlaceholders[] = ":lv{$idx}";
     }
   }
   
    $sql="
      SELECT s.id, CONCAT(s.first_name,' ',s.last_name) AS fullname, s.student_code, s.class_level, s.class_room, s.number_in_room
      FROM students s
      WHERE s.year_id=:yid AND s.color=:col AND $genderCond
      ".($levels ? "AND REPLACE(s.class_level, '.', '') IN (".implode(',', $levelPlaceholders).")" : "")."
      ORDER BY
        CASE WHEN s.class_level LIKE 'ป%' THEN 1 WHEN s.class_level LIKE 'ม%' THEN 2 ELSE 3 END,
        CAST(REPLACE(SUBSTRING(s.class_level,2), '.', '') AS UNSIGNED), s.class_room, s.number_in_room, s.first_name, s.last_name
    ";
   $bind=['yid'=>$yearId, 'col'=>$teamColor];
   if($levels) {
     foreach($levels as $idx => $lv) {
       $bind["lv{$idx}"] = $lv;
     }
   }
    $q=$pdo->prepare($sql); $q->execute($bind); $eligible=$q->fetchAll(PDO::FETCH_ASSOC);

    foreach(array_merge($prefill,$eligible) as $r){
      $label=$r['student_code'].' '.$r['fullname'].' ('.$r['class_level'].'/'.$r['class_room'].' เลขที่ '.$r['number_in_room'].')';
      $studentMap[$label]=(int)$r['id'];
    }
  }
}

/* 5) ตารางภาพรวมกีฬา */
$where=["s.year_id=:y","s.is_active=1"]; $listParams=[':y'=>$yearId];
if($categoryFilter>0){ $where[]="s.category_id=:cid"; $listParams[':cid']=$categoryFilter; }
$sqlSports="
  SELECT s.id,s.name,s.gender,s.participant_type,s.team_size,s.grade_levels,s.category_id,
         sc.name AS category_name,
         (SELECT COUNT(*) FROM registrations r WHERE r.year_id=s.year_id AND r.sport_id=s.id AND r.color='เขียว') AS c_green,
         (SELECT COUNT(*) FROM registrations r WHERE r.year_id=s.year_id AND r.sport_id=s.id AND r.color='ฟ้า')   AS c_blue,
         (SELECT COUNT(*) FROM registrations r WHERE r.year_id=s.year_id AND r.sport_id=s.id AND r.color='ชมพู')  AS c_pink,
         (SELECT COUNT(*) FROM registrations r WHERE r.year_id=s.year_id AND r.sport_id=s.id AND r.color='ส้ม')    AS c_orange
  FROM sports s
  JOIN sport_categories sc ON sc.id=s.category_id
  WHERE ".implode(' AND ',$where)."
  ORDER BY sc.name, s.name
";
$stList=$pdo->prepare($sqlSports); $stList->execute($listParams); $sports=$stList->fetchAll(PDO::FETCH_ASSOC);

/* VIEW */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <?php if($messages): ?><div class="alert alert-success"><?php echo implode('<br>',array_map('e',$messages)); ?></div><?php endif; ?>
  <?php if($warnings): ?><div class="alert alert-warning"><?php echo implode('<br>',$warnings); ?></div><?php endif; ?>
  <?php if($errors):   ?><div class="alert alert-danger"><?php echo implode('<br>',array_map('e',$errors)); ?></div><?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">ควบคุมการลงทะเบียน • ปีการศึกษา <?php echo e($yearBe?:'-'); ?></h5>
      <?php if(!$yearId): ?>
        <div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้ Active</div>
      <?php else: ?>
      <form method="post" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="toggle_registration">
        <div class="col-12 col-md-auto">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="sw" name="registration_is_open" <?php echo $regOpen?'checked':''; ?>>
            <label class="form-check-label" for="sw">เปิดรับลงทะเบียน</label>
          </div>
        </div>
        <div class="col-12 col-md-auto">
          <label class="form-label">เริ่ม (ถ้ามี)</label>
          <input type="datetime-local" class="form-control" name="registration_start"
                 value="<?php echo $regStart?date('Y-m-d\\TH:i',strtotime($regStart)):''; ?>">
        </div>
        <div class="col-12 col-md-auto">
          <label class="form-label">สิ้นสุด (ถ้ามี)</label>
          <input type="datetime-local" class="form-control" name="registration_end"
                 value="<?php echo $regEnd?date('Y-m-d\\TH:i',strtotime($regEnd)):''; ?>">
        </div>
        <div class="col-12 col-md-auto">
          <button class="btn btn-primary">บันทึก</button>
        </div>
        <div class="col-12"><div class="small text-muted">* ผู้ดูแลจัดทีมได้เสมอ แม้ระบบปิดรับลงทะเบียน</div></div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">เครื่องมือจัดการ</h5>
      <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAllRegsModal">
        ลบการลงทะเบียนทั้งหมด (ปีการศึกษา <?php echo e($yearBe?:'-'); ?>)
      </button>
      <div class="small text-muted mt-2">* จะลบเฉพาะข้อมูลลงทะเบียน (ตาราง registrations) ไม่ลบข้อมูลนักเรียน/กีฬา</div>
    </div>
  </div>

  <?php if($sportId>0 && $teamColor!=='' && $sportDetail): ?>
    <div class="mb-3">
      <a href="<?php echo BASE_URL; ?>/regis.php" class="btn btn-sm btn-outline-secondary">&larr; กลับภาพรวม</a>
    </div>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body">
        <h5 class="card-title mb-2">จัดทีม • <?php echo e($sportDetail['name']); ?> • สี<?php echo e($teamColor); ?></h5>
        <div class="text-muted mb-3">
          เพศ: <?php echo e($sportDetail['gender']); ?> • รูปแบบ: <?php echo e($sportDetail['participant_type']); ?> • จำนวนต่อสี: <strong><?php echo (int)$sportDetail['team_size']; ?></strong>
          <?php if(!empty($sportDetail['grade_levels'])): ?> • ชั้นที่เปิด: <?php echo e($sportDetail['grade_levels']); ?><?php endif; ?>
        </div>

        <form method="post" action="<?php echo BASE_URL; ?>/regis.php?sport_id=<?php echo (int)$sportId; ?>&color=<?php echo urlencode($teamColor); ?>" id="lineupForm">
          <input type="hidden" name="action" value="admin_save_lineup">
          <input type="hidden" name="sport_id" value="<?php echo (int)$sportId; ?>">
          <input type="hidden" name="team_color" value="<?php echo e($teamColor); ?>">

          <datalist id="students_datalist">
            <?php
              // รวม prefill+eligible แต่กรองซ้ำตาม student id (เก็บตัวแรกไว้ เพื่อให้ prefill มีลำดับก่อน)
              $studentItems = [];
              foreach (array_merge($prefill, $eligible) as $r) {
                $id = (int)($r['id'] ?? 0);
                if ($id === 0) continue;
                if (isset($studentItems[$id])) continue;
                $studentItems[$id] = $r;
              }
              foreach ($studentItems as $r) {
                $label = $r['student_code'].' '.$r['fullname'].' ('.$r['class_level'].'/'.$r['class_room'].' เลขที่ '.$r['number_in_room'].')';
            ?>
              <option value="<?php echo e($label); ?>"></option>
            <?php } ?>
          </datalist>

          <div class="row g-3">
            <?php
              // labelsPref เก็บลำดับจาก prefill เท่านั้น (ถ้ามี)
              $labelsPref = [];
              foreach ($prefill as $r) {
                $labelsPref[] = $r['student_code'].' '.$r['fullname'].' ('.$r['class_level'].'/'.$r['class_room'].' เลขที่ '.$r['number_in_room'].')';
              }
              // สร้าง map จาก $studentItems ที่กรองซ้ำแล้ว (label => id)
              $map = [];
              foreach ($studentItems as $r) {
                $lbl = $r['student_code'].' '.$r['fullname'].' ('.$r['class_level'].'/'.$r['class_room'].' เลขที่ '.$r['number_in_room'].')';
                $map[$lbl] = (int)$r['id'];
              }
           ?>
              <?php for($i=1;$i<=$teamSize;$i++):
                $val=$labelsPref[$i-1]??''; $prefId=$val && isset($map[$val])?$map[$val]:0;
              ?>
                <div class="col-md-6">
                  <label class="form-label">ผู้เล่นที่ <?php echo $i; ?></label>
                  <input type="text" class="form-control student-input" list="students_datalist" placeholder="พิมพ์ค้นหา รหัส/ชื่อ..." autocomplete="off" value="<?php echo e($val); ?>">
                  <input type="hidden" name="student_id_<?php echo $i; ?>" class="student-id-hidden" value="<?php echo (int)$prefId; ?>">
                  <div class="form-text">ปล่อยว่าง = ไม่ใช้ช่องนี้</div>
                </div>
              <?php endfor; ?>
          </div>

          <div class="mt-3 d-flex gap-2">
            <a class="btn btn-light" href="<?php echo BASE_URL; ?>/regis.php">ยกเลิก</a>
            <button class="btn btn-primary">บันทึก (แทนที่ชุดเดิมของสีนี้)</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      (function(){
        const map = <?php echo json_encode($map ?? [], JSON_UNESCAPED_UNICODE); ?>;
        const form = document.getElementById('lineupForm');
        form.addEventListener('submit', function(ev){
          const inputs = Array.from(form.querySelectorAll('.student-input'));
          const used = new Set();
          for (let i=0;i<inputs.length;i++){
            const label = inputs[i].value.trim();
            const hid = inputs[i].parentElement.querySelector('.student-id-hidden');
            if (label===''){ hid.value=''; continue; }
            const id = map[label] || 0;
            if (!id){ ev.preventDefault(); alert('กรุณาเลือกจากรายการ หรือปล่อยว่าง'); return; }
            if (used.has(id)){ ev.preventDefault(); alert('มีชื่อซ้ำในฟอร์ม'); return; }
            used.add(id); hid.value = id;
          }
        });
      })();
    </script>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <h5 class="card-title mb-0">ภาพรวมกีฬา (ปีการศึกษา <?php echo e($yearBe?:'-'); ?>)</h5>
        <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/regis.php">
          <div class="col-auto">
            <label class="form-label">ประเภทกีฬา</label>
            <select class="form-select" name="category_id">
              <option value="0">ทั้งหมด</option>
              <?php foreach($categories as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>" <?php echo $categoryFilter===(int)$c['id']?'selected':''; ?>>
                  <?php echo e($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto"><button class="btn btn-primary">กรอง</button></div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>กีฬา</th><th>ประเภท</th><th>เพศ</th><th>รูปแบบ</th><th>ชั้นที่เปิด</th>
              <th class="text-center">รับ/สี</th>
              <th class="text-center">เขียว</th>
              <th class="text-center">ฟ้า</th>
              <th class="text-center">ชมพู</th>
              <th class="text-center">ส้ม</th>
              <th>จัดทีม</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!$sports): ?>
            <tr><td colspan="11" class="text-muted">ยังไม่มีกีฬา</td></tr>
          <?php else: foreach($sports as $sp):
            $cap=(int)$sp['team_size'];
            $g=(int)$sp['c_green']; $b=(int)$sp['c_blue']; $p=(int)$sp['c_pink']; $o=(int)$sp['c_orange'];
          ?>
            <tr>
              <td class="fw-semibold"><?php echo e($sp['name']); ?></td>
              <td><?php echo e($sp['category_name']); ?></td>
              <td><?php echo e($sp['gender']); ?></td>
              <td><?php echo e($sp['participant_type']); ?></td>
              <td><?php echo e($sp['grade_levels']?:'-'); ?></td>
              <td class="text-center"><?php echo $cap; ?></td>
              <td class="text-center"><span class="badge <?php echo ($g<$cap?'bg-success':'bg-secondary'); ?>"><?php echo $g; ?></span></td>
              <td class="text-center"><span class="badge <?php echo ($b<$cap?'bg-success':'bg-secondary'); ?>"><?php echo $b; ?></span></td>
              <td class="text-center"><span class="badge <?php echo ($p<$cap?'bg-success':'bg-secondary'); ?>"><?php echo $p; ?></span></td>
              <td class="text-center"><span class="badge <?php echo ($o<$cap?'bg-success':'bg-secondary'); ?>"><?php echo $o; ?></span></td>
              <td class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-success" href="<?php echo BASE_URL; ?>/regis.php?sport_id=<?php echo (int)$sp['id']; ?>&color=<?php echo urlencode('เขียว'); ?>">สีเขียว</a>
                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/regis.php?sport_id=<?php echo (int)$sp['id']; ?>&color=<?php echo urlencode('ฟ้า'); ?>">สีฟ้า</a>
                <a class="btn btn-sm btn-outline-pink" href="<?php echo BASE_URL; ?>/regis.php?sport_id=<?php echo (int)$sp['id']; ?>&color=<?php echo urlencode('ชมพู'); ?>">สีชมพู</a>
                <a class="btn btn-sm btn-outline-warning" href="<?php echo BASE_URL; ?>/regis.php?sport_id=<?php echo (int)$sp['id']; ?>&color=<?php echo urlencode('ส้ม'); ?>">สีส้ม</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Delete All Registrations Modal -->
<div class="modal fade" id="deleteAllRegsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/regis.php">
      <input type="hidden" name="action" value="delete_all_registrations">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">⚠️ ลบการลงทะเบียนทั้งหมด</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger mb-3">
          <strong>คำเตือน:</strong> การกระทำนี้จะลบ<strong>ข้อมูลลงทะเบียนทั้งหมด</strong>ในปีการศึกษา <?php echo e($yearBe?:'-'); ?> (ทุกกีฬา ทุกสี) และ<strong>ไม่สามารถกู้คืนได้</strong>
        </div>
        <p class="mb-2">ข้อมูลที่จะถูกลบ:</p>
        <ul class="mb-3">
          <li>รายการลงทะเบียนทั้งหมด (ตาราง <code>registrations</code>)</li>
        </ul>
        <p class="mb-2"><strong>ข้อมูลที่ยังคงอยู่:</strong></p>
        <ul class="mb-3">
          <li>ข้อมูลนักเรียน (ตาราง <code>students</code>)</li>
          <li>ข้อมูลกีฬา (ตาราง <code>sports</code>)</li>
          <li>ข้อมูลผลการแข่งขัน (ตาราง <code>track_results</code>, <code>athletics_events</code>)</li>
        </ul>
        <hr>
        <p>กรุณาพิมพ์คำว่า <code class="text-danger fw-bold">DELETE</code> เพื่อยืนยัน:</p>
        <input type="text" class="form-control" name="confirm_delete" placeholder="พิมพ์ DELETE" required autocomplete="off">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-danger">ลบทั้งหมด</button>
      </div>
    </form>
  </div>
</div>

<style>
/* ปุ่มสีชมพู (Bootstrap ไม่มี) */
.btn-outline-pink{
  --bs-btn-color:#d63384; --bs-btn-border-color:#d63384;
  --bs-btn-hover-bg:#d63384; --bs-btn-hover-border-color:#c22273;
  --bs-btn-active-bg:#c22273; --bs-btn-active-border-color:#b01f66;
  --bs-btn-disabled-color:#d63384; --bs-btn-disabled-border-color:#d63384;
}
</style>
