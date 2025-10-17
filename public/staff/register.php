<?php
// public/staff/register.php — เวอร์ชัน “ปิดแก้ไขเมื่อ admin ปิดรับลงทะเบียน” (+เตือนเกินเพดานต่อประเภท)
// ---------------------------------------------------------------
// คุณสมบัติ:
//  - ถ้า admin ปิดสวิตช์ลงทะเบียน (ที่ academic_years.registration_is_open = 0
//    หรือผ่าน helper registration_open($pdo) == false)
//    * ปุ่มบันทึกจะ disabled
//    * ปุ่ม "ลงทะเบียน/แก้ไข" ในหน้ารายการจะ disabled
//    * ถ้ามีการ POST จะไม่บันทึก และแจ้งเตือน
// ---------------------------------------------------------------

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
function parse_grade_levels($s){ $p=array_filter(array_map('trim', explode(',', (string)$s))); return $p?:[]; }
function name_is_male_prefix($firstName){
  return mb_strpos($firstName,'เด็กชาย')===0 || mb_strpos($firstName,'นาย')===0;
}
function name_is_female_prefix($firstName){
  return mb_strpos($firstName,'เด็กหญิง')===0 || mb_strpos($firstName,'นางสาว')===0;
}
// ใช้ helper ถ้ามี; ถ้าไม่มีให้เช็คคอลัมน์ registration_is_open โดยตรง
function registration_open_safe(PDO $pdo): bool {
  if (function_exists('registration_open')) {
    return registration_open($pdo);
  }
  $y = $pdo->query("SELECT registration_is_open FROM academic_years WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  return !empty($y['registration_is_open']);
}

$yearId = active_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../../includes/header.php';
  include __DIR__ . '/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้เป็น Active</div></main>';
  include __DIR__ . '/../../includes/footer.php';
  exit;
}
$registrationOpen = registration_open_safe($pdo);

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
    $errors[] = 'ขณะนี้ปิดรับลงทะเบียน แก้ไขไม่ได้';
  } else {
    $sportId = (int)($_POST['sport_id'] ?? 0);
    if ($sportId <= 0) { $errors[]='ไม่พบกีฬา'; }

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
    if (!$sport) { $errors[]='ไม่พบกีฬานี้ในปีการศึกษาปัจจุบัน'; }

    // รวบรวม student_id_* (อนุญาตให้เว้นว่างได้)
    $teamSize = (int)($sport['team_size'] ?? 0);
    $chosen = [];
    for ($i=1;$i<=$teamSize;$i++){
      $sid = (int)($_POST['student_id_'.$i] ?? 0);
      if ($sid>0) $chosen[] = $sid;
    }
    $chosen = array_values(array_unique($chosen));
    if (count($chosen) > $teamSize) {
      $errors[] = 'จำนวนผู้เล่นเกินที่กำหนด (สูงสุด '.$teamSize.' คน)';
    }

    if (!$errors) {
      // โหลดข้อมูลนักเรียนที่เลือก
      $students = [];
      if ($chosen) {
        $in = implode(',', array_fill(0,count($chosen),'?'));
        $params = array_merge([$yearId], $chosen);
        $q = $pdo->prepare("SELECT id, first_name, last_name, class_level, color
                            FROM students WHERE year_id=? AND id IN ($in)");
        $q->execute($params);
        while($r=$q->fetch(PDO::FETCH_ASSOC)){ $students[(int)$r['id']]=$r; }
      }

      $allowedLevels = parse_grade_levels($sport['grade_levels']);
      $gender = $sport['gender']; // 'ช','ญ','รวม'

      foreach ($chosen as $sid) {
        if (empty($students[$sid])) { $errors[]='พบรหัสนักเรียนที่ไม่ถูกต้อง'; break; }
        $stu = $students[$sid];
        if ($stu['color'] !== $staffColor) { $errors[]='มีนักเรียนที่ไม่ใช่สีของคุณ'; break; }
        if ($allowedLevels && !in_array($stu['class_level'], $allowedLevels, true)) {
          $errors[]='มีนักเรียนชั้นที่ไม่ตรงตามที่กีฬากำหนด'; break;
        }
        if ($gender==='ช' && !name_is_male_prefix($stu['first_name'])) { $errors[]='มีนักเรียนที่ไม่ใช่เพศชาย'; break; }
        if ($gender==='ญ' && !name_is_female_prefix($stu['first_name'])) { $errors[]='มีนักเรียนที่ไม่ใช่เพศหญิง'; break; }
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
            $warnings[] = 'มีผู้เล่นที่ลงเกินจำนวนสูงสุดที่อนุญาตต่อประเภทกีฬา: <br>- '.e(implode('<br>- ', $overNames));
          }
        }
      }
      // ================================================================

      if (!$errors) {
        try{
          $pdo->beginTransaction();
          // ลบชุดเดิมของสีนี้ในกีฬานี้ก่อน
          $del = $pdo->prepare("DELETE FROM registrations WHERE year_id=? AND sport_id=? AND color=?");
          $del->execute([$yearId,$sportId,$staffColor]);

          // แทรกชุดใหม่ (เท่าจำนวนที่เลือก)
          if ($chosen) {
            $ins = $pdo->prepare("INSERT INTO registrations (year_id,sport_id,student_id,color) VALUES (?,?,?,?)");
            foreach ($chosen as $sid) {
              $ins->execute([$yearId,$sportId,$sid,$staffColor]);
            }
          }
          $pdo->commit();
          $messages[] = 'บันทึกสำเร็จ: อัปเดตรายชื่อทีมสี'.$staffColor.' ในกีฬา '.e($sport['name']);
        }catch(Throwable $e){
          $pdo->rollBack();
          $errors[] = 'บันทึกไม่สำเร็จ: '.$e->getMessage();
        }
      }
    }
  }
}

// -------- เปิดโหมด “กีฬาเดียว” ถ้ามี sport_id --------
$sportId = (int)($_GET['sport_id'] ?? 0);
$sportDetail = null; $eligibleStudents = []; $teamSize = 0; $studentMap = [];
$prefill = []; // รายชื่อที่ลงทะเบียนไว้แล้วของสีนี้
if ($sportId>0) {
  $st = $pdo->prepare("SELECT id,name,gender,participant_type,team_size,grade_levels FROM sports WHERE id=? AND year_id=? AND is_active=1");
  $st->execute([$sportId,$yearId]);
  $sportDetail = $st->fetch(PDO::FETCH_ASSOC);
  if ($sportDetail) {
    $teamSize = (int)$sportDetail['team_size'];
    $levels = parse_grade_levels($sportDetail['grade_levels']);
    $gender = $sportDetail['gender'];

    // รายชื่อที่ลงทะเบียนไว้แล้วของ “สีนี้”
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
        CAST(SUBSTRING(s.class_level, 2) AS UNSIGNED),
        s.class_room, s.number_in_room, s.first_name, s.last_name
    ");
    $qPrefill->execute([$yearId,$sportId,$staffColor]);
    $prefill = $qPrefill->fetchAll(PDO::FETCH_ASSOC);

    // เงื่อนไขเพศ
    $genderCond = "1=1";
    if ($gender==='ช') {
      $genderCond = "(s.first_name LIKE 'เด็กชาย%' OR s.first_name LIKE 'นาย%')";
    } elseif ($gender==='ญ') {
      $genderCond = "(s.first_name LIKE 'เด็กหญิง%' OR s.first_name LIKE 'นางสาว%')";
    }

    // ดึง “นักเรียนที่เลือกได้”
    $sql = "
      SELECT s.id,
             CONCAT(s.first_name,' ',s.last_name) AS fullname,
             s.student_code,
             s.class_level, s.class_room, s.number_in_room
      FROM students s
      WHERE s.year_id=? AND s.color=? 
        AND $genderCond
        ".($levels ? "AND s.class_level IN (" . implode(',', array_fill(0,count($levels),'?')). ")" : "")."
      ORDER BY
        CASE WHEN s.class_level LIKE 'ป%' THEN 1
             WHEN s.class_level LIKE 'ม%' THEN 2
             ELSE 3 END,
        CAST(SUBSTRING(s.class_level, 2) AS UNSIGNED),
        s.class_room, s.number_in_room, s.first_name, s.last_name
    ";
    $bind = [$yearId,$staffColor];
    if ($levels) { foreach($levels as $lv){ $bind[]=$lv; } }

    $q = $pdo->prepare($sql);
    $q->execute($bind);
    $eligibleStudents = $q->fetchAll(PDO::FETCH_ASSOC);

    // หา student_id ที่ลงทะเบียนแล้วในกีฬานี้ แต่เป็นสีอื่น (ให้ exclude)
    $blockedStmt = $pdo->prepare("SELECT student_id FROM registrations WHERE year_id=? AND sport_id=? AND color<>?");
    $blockedStmt->execute([$yearId, $sportId, $staffColor]);
    $blockedIds = $blockedStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $blockedMap = [];
    foreach ($blockedIds as $bid) { $blockedMap[(int)$bid] = true; }

    // รวม datalist = คนที่เลือกได้ทั้งหมด + คนที่เคยลงไว้แล้ว (แต่ตัดคนที่ลงด้วยสีอื่นออก)
    // ให้คงรายการ prefill (สีของคุณ) ไว้เสมอ
    $prefillIds = [];
    foreach ($prefill as $row) { $prefillIds[] = (int)$row['id']; }

    foreach (array_merge($prefill, $eligibleStudents) as $row) {
      $sid = (int)$row['id'];
      // ถ้าคนนี้ลงด้วยสีอื่นแล้ว และไม่ใช่คนที่อยู่ใน prefill (สีของคุณ) -> ข้าม
      if (isset($blockedMap[$sid]) && !in_array($sid, $prefillIds, true)) {
        continue;
      }
      $label = $row['student_code'].' '.$row['fullname'].' ('.$row['class_level'].'/'.$row['class_room'].' เลขที่ '.$row['number_in_room'].')';
      $studentMap[$label] = $sid;
    }
  }
}

// -------- ตารางกีฬา (โหมดรายการ) --------
$where = ["s.year_id=:y", "s.is_active=1"];
$params = [':y'=>$yearId];
if ($categoryFilter>0) { $where[]="s.category_id=:cid"; $params[':cid']=$categoryFilter; }

$sqlSports = "
  SELECT s.id, s.name, s.gender, s.participant_type, s.team_size, s.grade_levels,
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

// VIEW
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/navbar.php';
?>
<main class="container py-4">
  <?php if (!$registrationOpen): ?>
    <div class="alert alert-warning">ระบบกำลังปิดรับลงทะเบียน ขณะนี้ดูข้อมูลได้อย่างเดียว</div>
  <?php endif; ?>

  <?php if ($messages): ?>
    <div class="alert alert-success"><?php echo implode('<br>', array_map('e',$messages)); ?></div>
  <?php endif; ?>
  <?php if ($warnings): ?>
    <div class="alert alert-warning"><?php echo implode('<br>', $warnings); ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><?php echo implode('<br>', array_map('e',$errors)); ?></div>
  <?php endif; ?>

  <?php if ($sportId>0 && $sportDetail): ?>
    <!-- โหมดเลือก/แก้ไขผู้เล่นของกีฬาหนึ่ง -->
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <a href="<?php echo BASE_URL; ?>/staff/register.php" class="btn btn-sm btn-outline-secondary">&larr; กลับรายการกีฬา</a>
      <div class="small text-muted">
        ลงแล้ว (สี<?php echo e($staffColor); ?>): <?php echo count($prefill); ?>/<?php echo (int)$sportDetail['team_size']; ?> คน
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <h5 class="card-title mb-2">ลงทะเบียนกีฬา: <?php echo e($sportDetail['name']); ?></h5>
        <div class="text-muted mb-3">
          เพศ: <?php echo e($sportDetail['gender']); ?>
          • รูปแบบ: <?php echo e($sportDetail['participant_type']); ?>
          • จำนวนต่อสี: <strong><?php echo (int)$sportDetail['team_size']; ?></strong>
          <?php if (!empty($sportDetail['grade_levels'])): ?>
            • ชั้นที่เปิด: <?php echo e($sportDetail['grade_levels']); ?>
          <?php endif; ?>
        </div>

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
            ?>
              <div class="col-md-6">
                <label class="form-label">ผู้เล่นที่ <?php echo $i; ?></label>
                <input type="text" class="form-control student-input" list="students_datalist" placeholder="พิมพ์ค้นหา รหัส/ชื่อ..." autocomplete="off" value="<?php echo e($val); ?>" <?php echo !$registrationOpen?'disabled':''; ?>>
                <input type="hidden" name="student_id_<?php echo $i; ?>" class="student-id-hidden" value="<?php echo (int)$prefillId; ?>">
                <div class="form-text">ปล่อยว่าง = ไม่ใช้ช่องนี้</div>
              </div>
            <?php endfor; ?>
          </div>

          <div class="mt-3 d-flex gap-2">
            <a class="btn btn-light" href="<?php echo BASE_URL; ?>/staff/register.php">ย้อนกลับ</a>
            <button class="btn btn-primary" <?php echo !$registrationOpen?'disabled':''; ?>>บันทึกการลงทะเบียน (แทนที่ชุดเดิม)</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      // ป้องกัน submit เมื่อปิดลงทะเบียน
      const regOpen = <?php echo $registrationOpen ? 'true':'false'; ?>;
      if (regOpen) {
        const mapLabelToId = <?php echo json_encode($studentMap, JSON_UNESCAPED_UNICODE); ?>;
        const form = document.getElementById('lineupForm');
        form.addEventListener('submit', function(ev){
          const inputs = Array.from(form.querySelectorAll('.student-input'));
          const used = new Set();
          for (let i=0;i<inputs.length;i++){
            const label = inputs[i].value.trim();
            const hid = inputs[i].parentElement.querySelector('.student-id-hidden');
            if (label === '') { hid.value = ''; continue; }
            const id = mapLabelToId[label] || 0;
            if (!id) { ev.preventDefault(); alert('กรุณาเลือกจากรายการ หรือปล่อยว่าง'); return; }
            if (used.has(id)) { ev.preventDefault(); alert('มีชื่อผู้เล่นซ้ำกันในฟอร์ม'); return; }
            used.add(id);
            hid.value = id;
          }
        });
      }
    </script>

  <?php else: ?>
    <!-- โหมดตารางรายการกีฬา -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
          <h5 class="card-title mb-0">ลงทะเบียนกีฬา (สี<?php echo e($staffColor); ?>)</h5>
          <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/staff/register.php">
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
            <div class="col-auto">
              <button class="btn btn-primary">กรอง</button>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>กีฬา</th>
                <th>ประเภท</th>
                <th>เพศ</th>
                <th>รูปแบบ</th>
                <th>ชั้นที่เปิด</th>
                <th class="text-center">รับ</th>
                <th class="text-center">ลงแล้ว</th>
                <th class="text-center">ว่าง</th>
                <th>จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$sports): ?>
                <tr><td colspan="9" class="text-muted">ยังไม่มีกีฬาที่เปิดในปีนี้</td></tr>
              <?php else: foreach($sports as $sp):
                $reg = (int)$sp['reg_count'];
                $cap = (int)$sp['team_size'];
                $left = max(0, $cap - $reg);
              ?>
                <tr>
                  <td class="fw-semibold"><?php echo e($sp['name']); ?></td>
                  <td><?php echo e($sp['category_name']); ?></td>
                  <td><?php echo e($sp['gender']); ?></td>
                  <td><?php echo e($sp['participant_type']); ?></td>
                  <td><?php echo e($sp['grade_levels'] ?: '-'); ?></td>
                  <td class="text-center"><?php echo $cap; ?></td>
                  <td class="text-center"><?php echo $reg; ?></td>
                  <td class="text-center">
                    <span class="badge <?php echo $left>0?'bg-success':'bg-secondary'; ?>"><?php echo $left; ?></span>
                  </td>
                  <td class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-primary <?php echo !$registrationOpen?'disabled':''; ?>"
                       <?php if ($registrationOpen): ?>
                         href="<?php echo BASE_URL; ?>/staff/register.php?sport_id=<?php echo (int)$sp['id']; ?>"
                       <?php else: ?>
                         href="javascript:void(0)" title="ปิดรับลงทะเบียน"
                       <?php endif; ?>>
                       <?php echo $reg>0 ? 'แก้ไข' : 'ลงทะเบียน'; ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
