<?php
// public/users.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
// จำกัดให้เฉพาะ admin เข้าหน้านี้
if (($_SESSION['admin']['role'] ?? 'admin') !== 'admin') {
  http_response_code(403);
  echo 'Forbidden';
  exit;
}

$pdo = db();
$errors = [];
$messages = [];

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$roles = ['admin','staff','referee'];
$colors = ['ส้ม','เขียว','ชมพู','ฟ้า'];
// map น้ำเงิน -> ฟ้า เผื่อพิมพ์มาใน CSV
function normalizeColor($c) {
  $c = trim($c);
  $map = ['น้ำเงิน'=>'ฟ้า', 'ฟ้า'=>'ฟ้า', 'ชมพู'=>'ชมพู', 'ส้ม'=>'ส้ม', 'เขียว'=>'เขียว', 'blue'=>'ฟ้า', 'pink'=>'ชมพู', 'orange'=>'ส้ม', 'green'=>'เขียว'];
  return $map[$c] ?? $c;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* CREATE */
if ($action === 'create' && $_SERVER['REQUEST_METHOD']==='POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $display  = trim($_POST['display_name'] ?? '');
  $role     = trim($_POST['role'] ?? 'staff');
  $color    = normalizeColor($_POST['staff_color'] ?? '');
  $active   = isset($_POST['is_active']) ? 1 : 0;

  if ($username==='') $errors[]='กรุณากรอกชื่อผู้ใช้';
  if ($password==='') $errors[]='กรุณากรอกรหัสผ่าน';
  if ($display==='') $errors[]='กรุณากรอกชื่อแสดง';
  if (!in_array($role,$roles,true)) $errors[]='สิทธิ์ไม่ถูกต้อง';
  if ($role==='staff') {
    if (!in_array($color,$colors,true)) $errors[]='กรุณาเลือกสีของ Staff';
  } else {
    $color = null;
  }

  if (!$errors) {
    try {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users(username,password_hash,display_name,role,staff_color,is_active)
                             VALUES(?,?,?,?,?,?)");
      $stmt->execute([$username,$hash,$display,$role,$color,$active]);
      $insertedId = $pdo->lastInsertId();
      
      // 🔥 LOG: เพิ่มผู้ใช้สำเร็จ
      log_activity('CREATE', 'users', $insertedId, 
        sprintf("เพิ่มผู้ใช้: %s | ชื่อแสดง: %s | สิทธิ์: %s%s | สถานะ: %s", 
          $username, 
          $display, 
          $role,
          ($role === 'staff' && $color) ? " | สี: {$color}" : '',
          $active ? 'เปิด' : 'ปิด'));
      
      $messages[]='เพิ่มผู้ใช้เรียบร้อย';
    } catch (Throwable $e) {
      // 🔥 LOG: เพิ่มผู้ใช้ไม่สำเร็จ
      log_activity('ERROR', 'users', null, 
        sprintf("เพิ่มผู้ใช้ไม่สำเร็จ: %s | ชื่อผู้ใช้: %s", 
          $e->getMessage(), 
          $username));
      
      $errors[]='เพิ่มไม่สำเร็จ (อาจชื่อผู้ใช้ซ้ำ): '.e($e->getMessage());
    }
  }
}

/* UPDATE */
if ($action === 'update' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id       = (int)($_POST['id'] ?? 0);
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? ''); // เว้นว่าง = ไม่เปลี่ยน
  $display  = trim($_POST['display_name'] ?? '');
  $role     = trim($_POST['role'] ?? 'staff');
  $color    = normalizeColor($_POST['staff_color'] ?? '');
  $active   = isset($_POST['is_active']) ? 1 : 0;

  if ($id<=0) $errors[]='ไม่พบผู้ใช้';
  if ($username==='') $errors[]='กรุณากรอกชื่อผู้ใช้';
  if ($display==='') $errors[]='กรุณากรอกชื่อแสดง';
  if (!in_array($role,$roles,true)) $errors[]='สิทธิ์ไม่ถูกต้อง';
  if ($role==='staff') {
    if (!in_array($color,$colors,true)) $errors[]='กรุณาเลือกสีของ Staff';
  } else {
    $color = null;
  }
  // กันตัวเองลบสิทธิ์ตัวเอง (อย่างต่ำให้ยัง active และเป็น admin)
  $selfId = (int)($_SESSION['admin']['id'] ?? 0);
  if ($id === $selfId && ($active==0 || $role!=='admin')) {
    $errors[]='ไม่สามารถเปลี่ยนสิทธิ์/ปิดใช้งานผู้ใช้ที่กำลังล็อกอินอยู่';
  }

  if (!$errors) {
    try {
      // ดึงข้อมูลเดิมก่อนแก้ไข
      $oldStmt = $pdo->prepare("SELECT username, display_name, role, staff_color, is_active FROM users WHERE id=?");
      $oldStmt->execute([$id]);
      $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
      
      // เช็ค username ซ้ำ (ยกเว้นตัวเอง)
      $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>?");
      $chk->execute([$username,$id]);
      if ((int)$chk->fetchColumn() > 0) {
        $errors[]='ชื่อผู้ใช้ซ้ำ';
      } else {
        if ($password!=='') {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $sql = "UPDATE users SET username=?, password_hash=?, display_name=?, role=?, staff_color=?, is_active=? WHERE id=?";
          $args= [$username,$hash,$display,$role,$color,$active,$id];
        } else {
          $sql = "UPDATE users SET username=?, display_name=?, role=?, staff_color=?, is_active=? WHERE id=?";
          $args= [$username,$display,$role,$color,$active,$id];
        }
        $stmt=$pdo->prepare($sql);
        $stmt->execute($args);
        
        // 🔥 LOG: แก้ไขผู้ใช้สำเร็จ
        if ($oldData) {
          $changes = [];
          if ($oldData['username'] !== $username) $changes[] = "ชื่อผู้ใช้: {$oldData['username']} → {$username}";
          if ($oldData['display_name'] !== $display) $changes[] = "ชื่อแสดง: {$oldData['display_name']} → {$display}";
          if ($oldData['role'] !== $role) $changes[] = "สิทธิ์: {$oldData['role']} → {$role}";
          if ($oldData['staff_color'] !== $color) {
            $oldColor = $oldData['staff_color'] ?: '-';
            $newColor = $color ?: '-';
            $changes[] = "สี: {$oldColor} → {$newColor}";
          }
          if ((int)$oldData['is_active'] !== $active) {
            $changes[] = "สถานะ: " . ((int)$oldData['is_active'] ? 'เปิด' : 'ปิด') . " → " . ($active ? 'เปิด' : 'ปิด');
          }
          if ($password !== '') $changes[] = "รหัสผ่าน: เปลี่ยนแปลง";
          
          log_activity('UPDATE', 'users', $id, 
            sprintf("แก้ไขผู้ใช้: %s | %s", 
              $username,
              !empty($changes) ? implode(' | ', $changes) : 'ไม่มีการเปลี่ยนแปลง'));
        } else {
          log_activity('UPDATE', 'users', $id, 
            sprintf("แก้ไขผู้ใช้ ID:%d → %s", $id, $username));
        }
        
        $messages[]='บันทึกผู้ใช้เรียบร้อย';
        // ถ้าแก้ตัวเอง อัปเดต session ด้วย
        if ($id === $selfId) {
          $_SESSION['admin']['username'] = $username;
          $_SESSION['admin']['display_name'] = $display;
          $_SESSION['admin']['role'] = $role;
          $_SESSION['admin']['staff_color'] = $color;
        }
      }
    } catch (Throwable $e) {
      // 🔥 LOG: แก้ไขผู้ใช้ไม่สำเร็จ
      log_activity('ERROR', 'users', $id, 
        sprintf("แก้ไขผู้ใช้ไม่สำเร็จ: %s | ID:%d | ชื่อผู้ใช้: %s", 
          $e->getMessage(), 
          $id, 
          $username));
      
      $errors[]='แก้ไขไม่สำเร็จ: '.e($e->getMessage());
    }
  }
}

/* DELETE */
if ($action === 'delete' && $_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)($_POST['id'] ?? 0);
  $selfId = (int)($_SESSION['admin']['id'] ?? 0);
  if ($id<=0) $errors[]='ไม่พบผู้ใช้';
  elseif ($id===$selfId) $errors[]='ไม่สามารถลบผู้ใช้ที่กำลังล็อกอินอยู่';
  else {
    try {
      // ดึงข้อมูลก่อนลบ
      $oldStmt = $pdo->prepare("SELECT username, display_name, role, staff_color, is_active FROM users WHERE id=?");
      $oldStmt->execute([$id]);
      $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
      
      $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
      
      // 🔥 LOG: ลบผู้ใช้สำเร็จ
      if ($oldData) {
        log_activity('DELETE', 'users', $id, 
          sprintf("ลบผู้ใช้: %s | ชื่อแสดง: %s | สิทธิ์: %s%s | สถานะ: %s", 
            $oldData['username'], 
            $oldData['display_name'], 
            $oldData['role'],
            ($oldData['staff_color'] ? " | สี: {$oldData['staff_color']}" : ''),
            (int)$oldData['is_active'] ? 'เปิด' : 'ปิด'));
      } else {
        log_activity('DELETE', 'users', $id, sprintf("ลบผู้ใช้ ID:%d", $id));
      }
      
      $messages[]='ลบผู้ใช้เรียบร้อย';
    } catch (Throwable $e) {
      // 🔥 LOG: ลบผู้ใช้ไม่สำเร็จ
      log_activity('ERROR', 'users', $id, 
        sprintf("ลบผู้ใช้ไม่สำเร็จ: %s | ID:%d", $e->getMessage(), $id));
      
      $errors[]='ลบไม่สำเร็จ: '.e($e->getMessage());
    }
  }
}

/* COPY/CSV (template/export/import) */
if (($_GET['action'] ?? '') === 'template') {
  // 🔥 LOG: ดาวน์โหลด template
  log_activity('DOWNLOAD', 'users', null, 'ดาวน์โหลด CSV Template สำหรับนำเข้าผู้ใช้');
  
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="users_template.csv"');
  echo "\xEF\xBB\xBF";
  $out=fopen('php://output','w');
  // หัวคอลัมน์: username,password,display_name,role,staff_color,is_active
  fputcsv($out, ['username','password','display_name','role','staff_color','is_active']);
  fputcsv($out, ['staff_green','123456','ครูสีเขียว','staff','เขียว',1]);
  fputcsv($out, ['ref01','123456','กรรมการ 1','referee','',1]);
  fclose($out); exit;
}

if (($_GET['action'] ?? '') === 'export') {
  // นับจำนวนก่อน export
  $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
  $totalExport = (int)$countStmt->fetchColumn();
  
  // 🔥 LOG: ส่งออก CSV
  log_activity('EXPORT', 'users', null, 
    sprintf("ส่งออกผู้ใช้เป็น CSV: %d คน", $totalExport));
  
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="users.csv"');
  echo "\xEF\xBB\xBF";
  $out=fopen('php://output','w');
  fputcsv($out, ['username','display_name','role','staff_color','is_active','created_at']);
  $q=$pdo->query("SELECT username,display_name,role,staff_color,is_active,created_at FROM users ORDER BY role, username");
  while($r=$q->fetch(PDO::FETCH_ASSOC)){
    fputcsv($out, [$r['username'],$r['display_name'],$r['role'],$r['staff_color'],$r['is_active'],$r['created_at']]);
  }
  fclose($out); exit;
}

if ($action==='import_csv' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (!isset($_FILES['csv']) || $_FILES['csv']['error']!==UPLOAD_ERR_OK) {
    $errors[]='อัปโหลดไฟล์ไม่สำเร็จ';
  } else {
    $h=fopen($_FILES['csv']['tmp_name'],'r');
    if(!$h){ $errors[]='เปิดไฟล์ไม่สำเร็จ'; }
    else{
      $first=fgets($h);
      if(substr($first,0,3)==="\xEF\xBB\xBF") $first=substr($first,3);
      $header=str_getcsv($first);
      $expected=['username','password','display_name','role','staff_color','is_active'];
      $norm=fn($a)=>array_map('trim',$a);
      if($norm($header)!==$expected){ $errors[]='หัวคอลัมน์ไม่ตรงเทมเพลต'; }
      else{
        $ins=0;$upd=0;$skip=0;
        $pdo->beginTransaction();
        try{
          while(($row=fgetcsv($h))!==false){
            if(count($row)<6){ $skip++; continue; }
            [$u,$p,$d,$r,$c,$act] = array_map('trim',$row);
            $r = strtolower($r);
            if(!in_array($r,$roles,true)) { $skip++; continue; }
            $c = $r==='staff' ? normalizeColor($c) : null;
            if($r==='staff' && !in_array($c,$colors,true)) { $skip++; continue; }
            $act = (int)$act ? 1:0;
            if($u==='' || $d==='') { $skip++; continue; }

            $chk=$pdo->prepare("SELECT id FROM users WHERE username=?");
            $chk->execute([$u]);
            $id=$chk->fetchColumn();

            if($id){
              // อัปเดต: ถ้าช่อง password เว้นว่าง = ไม่เปลี่ยนรหัสผ่าน
              if($p!==''){
                $hash=password_hash($p,PASSWORD_DEFAULT);
                $sql="UPDATE users SET password_hash=?, display_name=?, role=?, staff_color=?, is_active=? WHERE id=?";
                $pdo->prepare($sql)->execute([$hash,$d,$r,$c,$act,$id]);
              } else {
                $sql="UPDATE users SET display_name=?, role=?, staff_color=?, is_active=? WHERE id=?";
                $pdo->prepare($sql)->execute([$d,$r,$c,$act,$id]);
              }
              $upd++;
            } else {
              $hash=password_hash($p!==''?$p:bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
              $sql="INSERT INTO users(username,password_hash,display_name,role,staff_color,is_active)
                    VALUES(?,?,?,?,?,?)";
              $pdo->prepare($sql)->execute([$u,$hash,$d,$r,$c,$act]);
              $ins++;
            }
          }
          $pdo->commit();
          
          // 🔥 LOG: นำเข้า CSV สำเร็จ
          log_activity('IMPORT', 'users', null, 
            sprintf("นำเข้าผู้ใช้จาก CSV: เพิ่มใหม่ %d คน | อัปเดต %d คน | ข้าม %d แถว", 
              $ins, $upd, $skip));
          
          $messages[]="✅ นำเข้าเสร็จสิ้น: เพิ่มใหม่ {$ins} แถว, อัปเดต {$upd} แถว, ข้าม {$skip} แถว";
        } catch(Throwable $e){
          $pdo->rollBack();
          
          // 🔥 LOG: นำเข้า CSV ไม่สำเร็จ
          log_activity('ERROR', 'users', null, 
            sprintf("นำเข้า CSV ไม่สำเร็จ: %s | เพิ่มแล้ว: %d | อัปเดตแล้ว: %d", 
              $e->getMessage(), $ins, $upd));
          
          $errors[]='นำเข้าไม่สำเร็จ: '.e($e->getMessage());
        }
        fclose($h);
      }
    }
  }
}

/* FILTER + LIST */
$q = trim($_GET['q'] ?? '');
$roleF = trim($_GET['role'] ?? '');
$colorF= trim($_GET['staff_color'] ?? '');

$where = []; $params=[];
if ($q!==''){ $where[]="(username LIKE :q OR display_name LIKE :q)"; $params[':q']='%'.$q.'%'; }
if ($roleF!=='' && in_array($roleF,$roles,true)){ $where[]="role=:r"; $params[':r']=$roleF; }
if ($colorF!=='' && in_array($colorF,$colors,true)){ $where[]="staff_color=:c"; $params[':c']=$colorF; }
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY role, username");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* VIEW */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
          <h5 class="card-title mb-3">เพิ่มผู้ใช้</h5>
          <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e',$errors)); ?></div><?php endif; ?>
          <?php if ($messages): ?><div class="alert alert-success"><?= implode('<br>', array_map('e',$messages)); ?></div><?php endif; ?>

          <form method="post" action="<?php echo BASE_URL; ?>/users.php" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-12">
              <label class="form-label">ชื่อผู้ใช้</label>
              <input type="text" class="form-control" name="username" required>
            </div>
            <div class="col-12">
              <label class="form-label">รหัสผ่าน</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="col-12">
              <label class="form-label">ชื่อแสดง</label>
              <input type="text" class="form-control" name="display_name" required>
            </div>
            <div class="col-6">
              <label class="form-label">สิทธิ์</label>
              <select class="form-select" name="role" id="role-create" required>
                <?php foreach($roles as $r): ?><option value="<?= $r; ?>"><?= $r; ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">สี (สำหรับ Staff)</label>
              <select class="form-select" name="staff_color" id="color-create">
                <option value="">-</option>
                <?php foreach($colors as $c): ?><option><?= $c; ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="active-create" checked>
                <label class="form-check-label" for="active-create">เปิดใช้งาน</label>
              </div>
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-primary">บันทึก</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <h5 class="card-title mb-3">นำเข้า/ส่งออก</h5>
          <div class="d-grid gap-2 mb-2">
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/users.php?action=template">ดาวน์โหลดเทมเพลต CSV</a>
          </div>
          <form method="post" enctype="multipart/form-data" action="<?php echo BASE_URL; ?>/users.php" class="mb-3">
            <input type="hidden" name="action" value="import_csv">
            <div class="mb-2">
              <label class="form-label">อัปโหลด CSV (UTF-8)</label>
              <input type="file" class="form-control" name="csv" accept=".csv" required>
            </div>
            <div class="d-grid">
              <button class="btn btn-success">นำเข้า</button>
            </div>
          </form>
          <div class="d-grid">
            <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/users.php?action=export">ส่งออก CSV</a>
          </div>
        </div>
      </div>

      <a class="d-inline-block mt-3 text-decoration-none" href="<?php echo BASE_URL; ?>/index.php">&larr; กลับแดชบอร์ด</a>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
            <h5 class="card-title mb-0">รายการผู้ใช้</h5>
            <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/users.php">
              <div class="col-auto">
                <label class="form-label">สิทธิ์</label>
                <select class="form-select" name="role">
                  <option value="">ทั้งหมด</option>
                  <?php foreach($roles as $r): ?><option value="<?= $r; ?>" <?= ($roleF??'')===$r?'selected':''; ?>><?= $r; ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label">สี (Staff)</label>
                <select class="form-select" name="staff_color">
                  <option value="">ทั้งหมด</option>
                  <?php foreach($colors as $c): ?><option value="<?= $c; ?>" <?= ($colorF??'')===$c?'selected':''; ?>><?= $c; ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label">ค้นหา</label>
                <input type="text" class="form-control" name="q" value="<?= e($q); ?>" placeholder="ชื่อผู้ใช้/ชื่อแสดง">
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
                  <th>ชื่อผู้ใช้</th>
                  <th>ชื่อแสดง</th>
                  <th>สิทธิ์</th>
                  <th>สี (Staff)</th>
                  <th class="text-center">สถานะ</th>
                  <th style="width:220px;">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$rows): ?>
                  <tr><td colspan="6" class="text-muted">ยังไม่มีข้อมูล</td></tr>
                <?php else: foreach($rows as $u): ?>
                  <tr>
                    <td class="fw-semibold"><?= e($u['username']); ?></td>
                    <td><?= e($u['display_name']); ?></td>
                    <td><?= e($u['role']); ?></td>
                    <td><?= e($u['staff_color'] ?? '-'); ?></td>
                    <td class="text-center"><?= ((int)$u['is_active']===1)?'<span class="badge bg-success">เปิด</span>':'<span class="badge bg-secondary">ปิด</span>'; ?></td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= (int)$u['id']; ?>"
                                data-username="<?= e($u['username']); ?>"
                                data-display="<?= e($u['display_name']); ?>"
                                data-role="<?= e($u['role']); ?>"
                                data-color="<?= e($u['staff_color']); ?>"
                                data-active="<?= (int)$u['is_active']; ?>">
                          แก้ไข
                        </button>
                        <form method="post" action="<?php echo BASE_URL; ?>/users.php" onsubmit="return confirm('ลบผู้ใช้นี้?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$u['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger">ลบ</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/users.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขผู้ใช้</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-12">
          <label class="form-label">ชื่อผู้ใช้</label>
          <input type="text" class="form-control" id="edit-username" name="username" required>
        </div>
        <div class="col-12">
          <label class="form-label">รหัสผ่านใหม่ (เว้นว่าง = ไม่เปลี่ยน)</label>
          <input type="password" class="form-control" id="edit-password" name="password">
        </div>
        <div class="col-12">
          <label class="form-label">ชื่อแสดง</label>
          <input type="text" class="form-control" id="edit-display" name="display_name" required>
        </div>
        <div class="col-6">
          <label class="form-label">สิทธิ์</label>
          <select class="form-select" id="edit-role" name="role" required>
            <?php foreach($roles as $r): ?><option value="<?= $r; ?>"><?= $r; ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">สี (Staff)</label>
          <select class="form-select" id="edit-color" name="staff_color">
            <option value="">-</option>
            <?php foreach($colors as $c): ?><option value="<?= $c; ?>"><?= $c; ?></option><?php endforeach; ?>
          </select>
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
const roleCreate = document.getElementById('role-create');
const colorCreate= document.getElementById('color-create');
if (roleCreate) {
  const toggle = () => { colorCreate.disabled = (roleCreate.value !== 'staff'); if(colorCreate.disabled) colorCreate.value=''; }
  roleCreate.addEventListener('change', toggle); toggle();
}
const editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('edit-id').value        = b.getAttribute('data-id');
    document.getElementById('edit-username').value  = b.getAttribute('data-username');
    document.getElementById('edit-display').value   = b.getAttribute('data-display');
    document.getElementById('edit-role').value      = b.getAttribute('data-role');
    document.getElementById('edit-color').value     = b.getAttribute('data-color') || '';
    document.getElementById('edit-active').checked  = (b.getAttribute('data-active')==='1');
  });
  // disable color when role != staff
  document.getElementById('edit-role').addEventListener('change', (ev)=>{
    const col = document.getElementById('edit-color');
    col.disabled = (ev.target.value !== 'staff');
    if (col.disabled) col.value='';
  });
}
</script>
