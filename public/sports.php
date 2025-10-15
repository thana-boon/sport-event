<?php
// public/sports.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$pdo = db();
$errors = [];
$messages = [];

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$yearId     = active_year_id($pdo);
$prevYearId = previous_year_id($pdo);
if (!$yearId) {
  include __DIR__ . '/../includes/header.php';
  include __DIR__ . '/../includes/navbar.php';
  echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้เป็น Active กรุณาไปที่ <a href="'.BASE_URL.'/years.php">กำหนดปีการศึกษา</a></div></main>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

/* ========== ตัวช่วย ========== */
$genders = ['ช','ญ','รวม'];
$ptypes  = ['เดี่ยว','ทีม'];
// ให้เลือกประเภทกีฬาที่ "มีผลในปีนี้" และเปิดใช้งาน (eff_active=1)
$catStmt = $pdo->prepare("
  SELECT sc.id, sc.name,
         COALESCE(cys.is_active, sc.is_active) AS eff_active
  FROM sport_categories sc
  LEFT JOIN category_year_settings cys
    ON cys.category_id=sc.id AND cys.year_id=:y
  ORDER BY sc.name
");
$catStmt->execute([':y'=>$yearId]);
$categories = array_values(array_filter($catStmt->fetchAll(PDO::FETCH_ASSOC), fn($r)=> (int)$r['eff_active']===1));

/* grade levels normalize: แปลงรูปแบบอิสระ → 'ป4,ป5' หรือ 'ม1,ม2,ม3' */
function normalizeGrades($txt) {
  $txt = trim((string)$txt);
  if ($txt === '') return '';
  // แทนคั่นทุกอย่างเป็นจุลภาค
  $txt = preg_replace('/[|\/\s;、]+/u', ',', $txt);
  // ตัดช่องว่างและคอมม่าเกิน
  $parts = array_filter(array_map('trim', explode(',', $txt)));
  // ปิดช่องโหว่เช่น 'ม1ม2ม3' (ไม่มีคั่น) → แทรกคอมม่า
  $fixed = [];
  foreach ($parts as $p) {
    if (preg_match_all('/(ป|ม)\d/u', $p, $m)) {
      foreach ($m[0] as $v) $fixed[] = $v;
    } else {
      $fixed[] = $p;
    }
  }
  // กรองให้เหลือเฉพาะ ป1–ป6, ม1–ม6
  $allowed = [];
  foreach ($fixed as $v) {
    if (preg_match('/^(ป[1-6]|ม[1-6])$/u', $v)) $allowed[] = $v;
  }
  $allowed = array_values(array_unique($allowed));
  return implode(',', $allowed);
}

/* ========== ACTIONS ========== */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* CREATE */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name'] ?? '');
  $catId = (int)($_POST['category_id'] ?? 0);
  $gender= trim($_POST['gender'] ?? 'รวม');
  $ptype = trim($_POST['participant_type'] ?? 'เดี่ยว');
  $size  = max(1, (int)($_POST['team_size'] ?? 1));
  $grades= normalizeGrades($_POST['grade_levels'] ?? '');
  $active= isset($_POST['is_active']) ? 1 : 0;

  if ($name==='') $errors[]='กรุณากรอกชื่อกีฬา';
  if ($catId<=0) $errors[]='กรุณาเลือกประเภทกีฬา';
  if (!in_array($gender,$genders,true)) $errors[]='เพศไม่ถูกต้อง';
  if (!in_array($ptype,$ptypes,true)) $errors[]='ประเภทผู้เข้าร่วมไม่ถูกต้อง';
  if ($grades==='') $errors[]='กรุณาระบุระดับชั้น (เช่น ป4,ป5 หรือ ม1,ม2,ม3)';

  if (!$errors) {
    try {
      $stmt = $pdo->prepare("INSERT INTO sports(year_id,category_id,name,gender,participant_type,team_size,grade_levels,is_active)
                             VALUES(?,?,?,?,?,?,?,?)");
      $stmt->execute([$yearId,$catId,$name,$gender,$ptype,$size,$grades,$active]);
      $messages[]='เพิ่มกีฬาสำเร็จ';
    } catch(Throwable $e) {
      $errors[]='เพิ่มไม่สำเร็จ (อาจชื่อ+เพศ+ประเภทซ้ำในปีนี้): '.e($e->getMessage());
    }
  }
}

/* UPDATE */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $id    = (int)($_POST['id'] ?? 0);
  $name  = trim($_POST['name'] ?? '');
  $catId = (int)($_POST['category_id'] ?? 0);
  $gender= trim($_POST['gender'] ?? 'รวม');
  $ptype = trim($_POST['participant_type'] ?? 'เดี่ยว');
  $size  = max(1, (int)($_POST['team_size'] ?? 1));
  $grades= normalizeGrades($_POST['grade_levels'] ?? '');
  $active= isset($_POST['is_active']) ? 1 : 0;

  if ($id<=0) $errors[]='ไม่พบรายการ';
  if ($name==='') $errors[]='กรุณากรอกชื่อกีฬา';
  if ($catId<=0) $errors[]='กรุณาเลือกประเภทกีฬา';
  if (!in_array($gender,$genders,true)) $errors[]='เพศไม่ถูกต้อง';
  if (!in_array($ptype,$ptypes,true)) $errors[]='ประเภทผู้เข้าร่วมไม่ถูกต้อง';
  if ($grades==='') $errors[]='กรุณาระบุระดับชั้น';

  if (!$errors) {
    try {
      $stmt = $pdo->prepare("UPDATE sports
        SET category_id=?, name=?, gender=?, participant_type=?, team_size=?, grade_levels=?, is_active=?
        WHERE id=? AND year_id=?");
      $stmt->execute([$catId,$name,$gender,$ptype,$size,$grades,$active,$id,$yearId]);
      $messages[]='แก้ไขสำเร็จ';
    } catch(Throwable $e) {
      $errors[]='แก้ไขไม่สำเร็จ: '.e($e->getMessage());
    }
  }
}

/* DELETE */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) $errors[]='ไม่พบรายการ';
  else {
    try {
      $stmt = $pdo->prepare("DELETE FROM sports WHERE id=? AND year_id=?");
      $stmt->execute([$id,$yearId]);
      $messages[]='ลบสำเร็จ';
    } catch(Throwable $e) {
      $errors[]='ลบไม่สำเร็จ (อาจมีข้อมูลเชื่อมโยงการลงทะเบียน): '.e($e->getMessage());
    }
  }
}

/* COPY FROM PREVIOUS YEAR */
if ($action === 'copy_prev' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$prevYearId) {
    $errors[]='ไม่พบปีการศึกษาก่อนหน้า';
  } else {
    try {
      $pdo->beginTransaction();
      // คัดลอกทั้งชุด (แม้ category_id เดิม) → ถ้า duplicate ให้ UPDATE
      $sql = "
        INSERT INTO sports(year_id, category_id, name, gender, participant_type, team_size, grade_levels, is_active)
        SELECT :cur, s.category_id, s.name, s.gender, s.participant_type, s.team_size, s.grade_levels, s.is_active
        FROM sports s WHERE s.year_id=:prev
        ON DUPLICATE KEY UPDATE
          category_id=VALUES(category_id),
          team_size=VALUES(team_size),
          grade_levels=VALUES(grade_levels),
          is_active=VALUES(is_active)";
      $st = $pdo->prepare($sql);
      $st->execute([':cur'=>$yearId, ':prev'=>$prevYearId]);
      $pdo->commit();
      $messages[]='คัดลอกจากปีที่แล้วเรียบร้อย';
    } catch(Throwable $e) {
      $pdo->rollBack();
      $errors[]='คัดลอกไม่สำเร็จ: '.e($e->getMessage());
    }
  }
}

/* CSV TEMPLATE */
if (($_GET['action'] ?? '') === 'template') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="sports_template.csv"');
  echo "\xEF\xBB\xBF";
  $out=fopen('php://output','w');
  // หัวคอลัมน์ตามภาพ: กีฬา, เพศ, ประเภทกีฬา, ประเภทผู้เข้แข่งขัน, จำนวน, ระดับชั้น
  fputcsv($out, ['กีฬา','เพศ','ประเภทกีฬา','ประเภทผู้เข้แข่งขัน','จำนวน','ระดับชั้น']);
  fputcsv($out, ['ฟุตบอล','ช','กีฬากลาง','ทีม',7,'ม1,ม2,ม3']);
  fclose($out); exit;
}

/* EXPORT CSV */
if (($_GET['action'] ?? '') === 'export') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="sports_'.$yearId.'.csv"');
  echo "\xEF\xBB\xBF";
  $out=fopen('php://output','w');
  fputcsv($out, ['กีฬา','เพศ','ประเภทกีฬา','ประเภทผู้เข้แข่งขัน','จำนวน','ระดับชั้น']);
  $q = $pdo->prepare("SELECT s.name, s.gender, sc.name AS cat_name, s.participant_type, s.team_size, s.grade_levels
                      FROM sports s JOIN sport_categories sc ON sc.id=s.category_id
                      WHERE s.year_id=? ORDER BY sc.name, s.name");
  $q->execute([$yearId]);
  while($r=$q->fetch(PDO::FETCH_ASSOC)){
    fputcsv($out, [$r['name'],$r['gender'],$r['cat_name'],$r['participant_type'],$r['team_size'],$r['grade_levels']]);
  }
  fclose($out); exit;
}

/* IMPORT CSV */
if ($action==='import_csv' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (!isset($_FILES['csv']) || $_FILES['csv']['error']!==UPLOAD_ERR_OK) {
    $errors[]='อัปโหลดไฟล์ไม่สำเร็จ';
  } else {
    $path=$_FILES['csv']['tmp_name'];
    $h=fopen($path,'r');
    if(!$h){ $errors[]='เปิดไฟล์ไม่ได้'; }
    else{
      $first=fgets($h);
      if(substr($first,0,3)==="\xEF\xBB\xBF") $first=substr($first,3);
      $header=str_getcsv($first);
      $expected=['กีฬา','เพศ','ประเภทกีฬา','ประเภทผู้เข้แข่งขัน','จำนวน','ระดับชั้น'];
      $norm=fn($a)=>array_map('trim',$a);
      if($norm($header)!==$expected){
        $errors[]='หัวคอลัมน์ไม่ตรงเทมเพลต';
      }else{
        $ins=0;$upd=0;$skip=0;

        // สร้าง map ชื่อประเภท → id (เฉพาะที่ active ในปีนี้)
        $catMap=[];
        foreach($categories as $c){ $catMap[$c['name']] = (int)$c['id']; }

        $pdo->beginTransaction();
        try{
          while(($row=fgetcsv($h))!==false){
            if(count($row)<6){ $skip++; continue; }
            [$name,$gender,$catName,$ptype,$size,$grades]=$row;
            $name=trim($name); $gender=trim($gender); $catName=trim($catName); $ptype=trim($ptype);
            $size=(int)$size; if($size<=0) $size=1; $grades=normalizeGrades($grades);

            if($name==='' || !isset($catMap[$catName]) || !in_array($gender,$genders,true) || !in_array($ptype,$ptypes,true) || $grades===''){
              $skip++; continue;
            }
            $catId=$catMap[$catName];

            // มีอยู่ไหมในปีนี้ (ตาม unique key)
            $chk=$pdo->prepare("SELECT id FROM sports WHERE year_id=? AND name=? AND gender=? AND participant_type=? LIMIT 1");
            $chk->execute([$yearId,$name,$gender,$ptype]);
            $exists=$chk->fetchColumn();

            if($exists){
              // update
            }else{
              // insert
            }
          }
          $pdo->commit();
          $messages[]="✅ นำเข้าเสร็จสิ้น: เพิ่มใหม่ {$ins} แถว, อัปเดต {$upd} แถว, ข้าม {$skip} แถว";
        }catch(Throwable $e){
          $pdo->rollBack();
          $errors[]='นำเข้าไม่สำเร็จ: '.e($e->getMessage());
        }
        fclose($h);
      }
    }
  }
}

/* ========== FILTER & LIST ========== */
$catFilter = (int)($_GET['category_id'] ?? 0);
$genderF   = trim($_GET['gender'] ?? '');
$ptypeF    = trim($_GET['participant_type'] ?? '');
$qtext     = trim($_GET['q'] ?? '');
$page      = max(1,(int)($_GET['page'] ?? 1));
$perPage   = 20;

$where = ["s.year_id=:y"];
$params = [':y'=>$yearId];
if ($catFilter>0){ $where[]="s.category_id=:c"; $params[':c']=$catFilter; }
if ($genderF!=='' && in_array($genderF,$genders,true)){ $where[]="s.gender=:g"; $params[':g']=$genderF; }
if ($ptypeF!=='' && in_array($ptypeF,$ptypes,true)){ $where[]="s.participant_type=:t"; $params[':t']=$ptypeF; }
if ($qtext!==''){ $where[]="(s.name LIKE :q OR s.grade_levels LIKE :q)"; $params[':q']='%'.$qtext.'%'; }
$whereSql = implode(' AND ', $where);

// count
$st=$pdo->prepare("SELECT COUNT(*) FROM sports s WHERE $whereSql");
$st->execute($params);
$total=(int)$st->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));
$offset=($page-1)*$perPage;

// list
$sql="SELECT s.*, sc.name AS cat_name
      FROM sports s JOIN sport_categories sc ON sc.id=s.category_id
      WHERE $whereSql
      ORDER BY sc.name, s.name
      LIMIT :lim OFFSET :off";
$st=$pdo->prepare($sql);
foreach($params as $k=>$v){ $st->bindValue($k,$v); }
$st->bindValue(':lim',$perPage,PDO::PARAM_INT);
$st->bindValue(':off',$offset,PDO::PARAM_INT);
$st->execute();
$rows=$st->fetchAll(PDO::FETCH_ASSOC);

/* ========== VIEW ========== */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <div class="row g-3">
    <!-- LEFT: CREATE / IMPORT / EXPORT -->
    <div class="col-lg-4">
      <div class="card rounded-4 shadow-sm border-0 mb-3">
        <div class="card-body">
          <h5 class="card-title mb-3">เพิ่มกีฬา (ปีปัจจุบัน)</h5>

          <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e',$errors)); ?></div><?php endif; ?>
          <?php if ($messages): ?><div class="alert alert-success"><?= implode('<br>', array_map('e',$messages)); ?></div><?php endif; ?>

          <form method="post" action="<?php echo BASE_URL; ?>/sports.php" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-12">
              <label class="form-label">ชื่อกีฬา</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="col-12">
              <label class="form-label">ประเภทกีฬา</label>
              <select class="form-select" name="category_id" required>
                <option value="">-- เลือกประเภท --</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int)$c['id']; ?>"><?= e($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">เพศ</label>
              <select class="form-select" name="gender" required>
                <?php foreach ($genders as $g): ?>
                  <option><?= $g; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">ประเภทผู้เข้าร่วม</label>
              <select class="form-select" name="participant_type" required>
                <?php foreach ($ptypes as $t): ?>
                  <option><?= $t; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">จำนวนสมาชิก/ทีม</label>
              <input type="number" class="form-control" name="team_size" min="1" value="1" required>
              <div class="form-text">เดี่ยว = 1</div>
            </div>
            <div class="col-6">
              <label class="form-label">ระดับชั้นที่อนุญาต</label>
              <input type="text" class="form-control" name="grade_levels" placeholder="ป4,ป5 หรือ ม1,ม2,ม3" required>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                <label class="form-check-label" for="is_active">เปิดใช้งาน</label>
              </div>
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-primary">บันทึก</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card rounded-4 shadow-sm border-0">
        <div class="card-body">
          <h5 class="card-title mb-3">นำเข้า / ส่งออก</h5>

          <div class="d-grid gap-2 mb-2">
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/sports.php?action=template">ดาวน์โหลดเทมเพลต CSV</a>
          </div>

          <form method="post" enctype="multipart/form-data" action="<?php echo BASE_URL; ?>/sports.php" class="mb-3">
            <input type="hidden" name="action" value="import_csv">
            <div class="mb-2">
              <label class="form-label">อัปโหลด CSV (UTF-8)</label>
              <input type="file" class="form-control" name="csv" accept=".csv" required>
              <div class="form-text">หัวคอลัมน์: กีฬา, เพศ, ประเภทกีฬา, ประเภทผู้เข้แข่งขัน, จำนวน, ระดับชั้น</div>
            </div>
            <div class="d-grid">
              <button class="btn btn-success">นำเข้า</button>
            </div>
          </form>

          <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/sports.php?action=export">ส่งออก CSV</a>
          </div>
        </div>
      </div>

      <?php if ($prevYearId): ?>
      <form class="mt-3" method="post" action="<?php echo BASE_URL; ?>/sports.php" onsubmit="return confirm('คัดลอกกีฬาจากปีที่แล้วมาปีนี้?');">
        <input type="hidden" name="action" value="copy_prev">
        <button class="btn btn-outline-secondary w-100">คัดลอกจากปีที่แล้ว</button>
      </form>
      <?php endif; ?>

      <a class="d-inline-block mt-3 text-decoration-none" href="<?php echo BASE_URL; ?>/index.php">&larr; กลับแดชบอร์ด</a>
    </div>

    <!-- RIGHT: LIST -->
    <div class="col-lg-8">
      <div class="card rounded-4 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
            <h5 class="card-title mb-0">รายการกีฬา (ปีนี้)</h5>
            <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/sports.php">
              <div class="col-auto">
                <label class="form-label">ประเภท</label>
                <select class="form-select" name="category_id">
                  <option value="0">ทั้งหมด</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id']; ?>" <?= $catFilter===(int)$c['id']?'selected':''; ?>><?= e($c['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label">เพศ</label>
                <select class="form-select" name="gender">
                  <option value="">ทั้งหมด</option>
                  <?php foreach ($genders as $g): ?>
                    <option value="<?= $g; ?>" <?= $genderF===$g?'selected':''; ?>><?= $g; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label">รูปแบบ</label>
                <select class="form-select" name="participant_type">
                  <option value="">ทั้งหมด</option>
                  <?php foreach ($ptypes as $t): ?>
                    <option value="<?= $t; ?>" <?= $ptypeF===$t?'selected':''; ?>><?= $t; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label">ค้นหา</label>
                <input type="text" class="form-control" name="q" value="<?= e($qtext); ?>" placeholder="ชื่อกีฬา / ระดับชั้น">
              </div>
              <div class="col-auto">
                <button class="btn btn-primary">ค้นหา</button>
              </div>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>กีฬา</th>
                  <th>ประเภทกีฬา</th>
                  <th>เพศ</th>
                  <th>รูปแบบ</th>
                  <th class="text-center">จำนวน</th>
                  <th>ระดับชั้น</th>
                  <th class="text-center" style="width:120px;">สถานะ</th>
                  <th style="width:220px;">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$rows): ?>
                  <tr><td colspan="8" class="text-muted">ยังไม่มีข้อมูล</td></tr>
                <?php else: foreach($rows as $s): ?>
                  <tr>
                    <td class="fw-semibold"><?= e($s['name']); ?></td>
                    <td><?= e($s['cat_name']); ?></td>
                    <td><?= e($s['gender']); ?></td>
                    <td><?= e($s['participant_type']); ?></td>
                    <td class="text-center"><?= (int)$s['team_size']; ?></td>
                    <td><?= e($s['grade_levels']); ?></td>
                    <td class="text-center">
                      <?= ((int)$s['is_active']===1) ? '<span class="badge bg-success">เปิด</span>' : '<span class="badge bg-secondary">ปิด</span>'; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= (int)$s['id']; ?>"
                                data-name="<?= e($s['name']); ?>"
                                data-cat="<?= (int)$s['category_id']; ?>"
                                data-gender="<?= e($s['gender']); ?>"
                                data-ptype="<?= e($s['participant_type']); ?>"
                                data-size="<?= (int)$s['team_size']; ?>"
                                data-grades="<?= e($s['grade_levels']); ?>"
                                data-active="<?= (int)$s['is_active']; ?>">
                          แก้ไข
                        </button>
                        <form method="post" action="<?php echo BASE_URL; ?>/sports.php" onsubmit="return confirm('ลบรายการนี้?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$s['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger">ลบ</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($pages>1): ?>
          <nav>
            <ul class="pagination justify-content-end">
              <?php
                $base=$_GET; unset($base['page']);
                $build=function($p)use($base){$base['page']=$p; return '?'.http_build_query($base);};
              ?>
              <li class="page-item <?= $page<=1?'disabled':''; ?>"><a class="page-link" href="<?= $build(max(1,$page-1)); ?>">&laquo;</a></li>
              <?php for($p=max(1,$page-2);$p<=min($pages,$page+2);$p++): ?>
                <li class="page-item <?= $p===$page?'active':''; ?>"><a class="page-link" href="<?= $build($p); ?>"><?= $p; ?></a></li>
              <?php endfor; ?>
              <li class="page-item <?= $page>=$pages?'disabled':''; ?>"><a class="page-link" href="<?= $build(min($pages,$page+1)); ?>">&raquo;</a></li>
            </ul>
          </nav>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/sports.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขกีฬา (ปีนี้)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-12">
          <label class="form-label">ชื่อกีฬา</label>
          <input type="text" class="form-control" id="edit-name" name="name" required>
        </div>
        <div class="col-12">
          <label class="form-label">ประเภทกีฬา</label>
          <select class="form-select" id="edit-cat" name="category_id" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id']; ?>"><?= e($c['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">เพศ</label>
          <select class="form-select" id="edit-gender" name="gender" required>
            <?php foreach ($genders as $g): ?><option><?= $g; ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">ประเภทผู้เข้าร่วม</label>
          <select class="form-select" id="edit-ptype" name="participant_type" required>
            <?php foreach ($ptypes as $t): ?><option><?= $t; ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">จำนวนสมาชิก/ทีม</label>
          <input type="number" class="form-control" id="edit-size" name="team_size" min="1" required>
        </div>
        <div class="col-6">
          <label class="form-label">ระดับชั้นที่อนุญาต</label>
          <input type="text" class="form-control" id="edit-grades" name="grade_levels" required>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="edit-active" name="is_active" value="1">
            <label class="form-check-label" for="edit-active">เปิดใช้งาน</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('edit-id').value     = b.getAttribute('data-id');
    document.getElementById('edit-name').value   = b.getAttribute('data-name');
    document.getElementById('edit-cat').value    = b.getAttribute('data-cat');
    document.getElementById('edit-gender').value = b.getAttribute('data-gender');
    document.getElementById('edit-ptype').value  = b.getAttribute('data-ptype');
    document.getElementById('edit-size').value   = b.getAttribute('data-size');
    document.getElementById('edit-grades').value = b.getAttribute('data-grades');
    document.getElementById('edit-active').checked = (b.getAttribute('data-active') === '1');
  });
}
</script>
