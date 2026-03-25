<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$pdo = db();
$errors = [];
$messages = [];

// ===== GET active year =====
$yearId = active_year_id($pdo);
if (!$yearId) {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/navbar.php';
    echo '<main class="container py-5"><div class="alert alert-warning">ยังไม่ได้ตั้งปีการศึกษาให้เป็น Active กรุณาไปที่ <a href="'.BASE_URL.'/years.php">กำหนดปีการศึกษา</a> แล้วตั้งค่า Active ก่อน</div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ===== Handle CSV template download =====
if (($_GET['action'] ?? '') === 'template') {
    // 🔥 LOG: ดาวน์โหลด template
    log_activity('DOWNLOAD', 'students', null, 'ดาวน์โหลด CSV Template สำหรับนำเข้านักเรียน');
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_template.csv"');

    // ใส่ UTF-8 BOM เพื่อให้ Excel อ่านไทยถูก
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    // หัวคอลัมน์
    fputcsv($out, ['ปีการศึกษา','รหัสนักเรียน','ชื่อจริง','นามสกุล','ชั้น','ห้อง','เลขที่','สี']);
    // ตัวอย่าง 1 แถว
    fputcsv($out, [2568,'02212','เด็กชายสุพร','อา','ป.3',2,1,'เขียว']);
    fclose($out);
    exit;
}

// ===== Helpers =====
$allowedColors = ['ส้ม','เขียว','ชมพู','ฟ้า'];
function normalizeColor($c) {
    $c = trim($c);
    // เผื่อผู้ใช้พิมพ์แบบอังกฤษหรือช่องว่างแปลก ๆ
    $map = [
        'ส้ม'=>'ส้ม','เขียว'=>'เขียว','ชมพู'=>'ชมพู','ฟ้า'=>'ฟ้า',
        'orange'=>'ส้ม','green'=>'เขียว','pink'=>'ชมพู','blue'=>'ฟ้า'
    ];
    return $map[$c] ?? $c;
}

// ===== Actions: create/update/delete/import/export =====
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_code = trim($_POST['student_code'] ?? '');
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $class_level  = trim($_POST['class_level'] ?? '');
    $class_room   = (int)($_POST['class_room'] ?? 0);
    $number_in    = (int)($_POST['number_in_room'] ?? 0);
    $color        = normalizeColor($_POST['color'] ?? '');

    if ($student_code === '' || $first_name === '' || $last_name === '' || $class_level === '' || $class_room <= 0 || $number_in <= 0) {
        $errors[] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
    if (!in_array($color, $allowedColors, true)) {
        $errors[] = 'สีไม่ถูกต้อง (ต้องเป็น ส้ม/เขียว/ชมพู/ฟ้า)';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students(year_id, student_code, first_name, last_name, class_level, class_room, number_in_room, color)
                                   VALUES(?,?,?,?,?,?,?,?)");
            $stmt->execute([$yearId, $student_code, $first_name, $last_name, $class_level, $class_room, $number_in, $color]);
            $insertedId = $pdo->lastInsertId();
            
            // 🔥 LOG: เพิ่มนักเรียนสำเร็จ
            log_activity('CREATE', 'students', $insertedId, 
                sprintf("เพิ่มนักเรียน: %s %s %s | รหัส: %s | ชั้น: %s/%d เลขที่: %d | สี: %s", 
                    $first_name, $last_name, '', $student_code, $class_level, $class_room, $number_in, $color));
            
            // เก็บข้อความไว้ใน session
            $_SESSION['success_message'] = 'เพิ่มนักเรียนเรียบร้อย';
            
            // Redirect กลับพร้อม filter จาก session
            $filterParams = [];
            if (!empty($_SESSION['students_filter'])) {
                $savedFilter = $_SESSION['students_filter'];
                if (!empty($savedFilter['color'])) $filterParams['color'] = $savedFilter['color'];
                if (!empty($savedFilter['class_level'])) $filterParams['class_level'] = $savedFilter['class_level'];
                if (!empty($savedFilter['class_room'])) $filterParams['class_room'] = $savedFilter['class_room'];
                if (!empty($savedFilter['q'])) $filterParams['q'] = $savedFilter['q'];
            }
            
            $redirectUrl = BASE_URL . '/students.php' . ($filterParams ? '?' . http_build_query($filterParams) : '');
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $e) {
            // 🔥 LOG: เพิ่มนักเรียนไม่สำเร็จ
            log_activity('ERROR', 'students', null, 
                sprintf("เพิ่มนักเรียนไม่สำเร็จ: %s | รหัส: %s | ชื่อ: %s %s", 
                    $e->getMessage(), $student_code, $first_name, $last_name));
            
            $errors[] = 'เพิ่มไม่สำเร็จ (อาจรหัสนักเรียนซ้ำในปีนี้): '.e($e->getMessage());
        }
    }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = (int)($_POST['id'] ?? 0);
    $student_code = trim($_POST['student_code'] ?? '');
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $class_level  = trim($_POST['class_level'] ?? '');
    $class_room   = (int)($_POST['class_room'] ?? 0);
    $number_in    = (int)($_POST['number_in_room'] ?? 0);
    $color        = normalizeColor($_POST['color'] ?? '');

    if ($id <= 0) $errors[] = 'ไม่พบรายการ';
    if ($student_code === '' || $first_name === '' || $last_name === '' || $class_level === '' || $class_room <= 0 || $number_in <= 0) {
        $errors[] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
    if (!in_array($color, $allowedColors, true)) {
        $errors[] = 'สีไม่ถูกต้อง (ต้องเป็น ส้ม/เขียว/ชมพู/ฟ้า)';
    }

    if (!$errors) {
        try {
            // ดึงข้อมูลเดิมก่อนแก้ไข
            $oldStmt = $pdo->prepare("SELECT student_code, first_name, last_name, class_level, class_room, number_in_room, color FROM students WHERE id=? AND year_id=?");
            $oldStmt->execute([$id, $yearId]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("UPDATE students
                                   SET student_code=?, first_name=?, last_name=?, class_level=?, class_room=?, number_in_room=?, color=?
                                   WHERE id=? AND year_id=?");
            $stmt->execute([$student_code, $first_name, $last_name, $class_level, $class_room, $number_in, $color, $id, $yearId]);
            
            // 🔥 LOG: แก้ไขนักเรียนสำเร็จ
            if ($oldData) {
                $changes = [];
                if ($oldData['student_code'] !== $student_code) $changes[] = "รหัส: {$oldData['student_code']} → {$student_code}";
                if ($oldData['first_name'] !== $first_name) $changes[] = "ชื่อ: {$oldData['first_name']} → {$first_name}";
                if ($oldData['last_name'] !== $last_name) $changes[] = "นามสกุล: {$oldData['last_name']} → {$last_name}";
                if ($oldData['class_level'] !== $class_level) $changes[] = "ชั้น: {$oldData['class_level']} → {$class_level}";
                if ($oldData['class_room'] != $class_room) $changes[] = "ห้อง: {$oldData['class_room']} → {$class_room}";
                if ($oldData['number_in_room'] != $number_in) $changes[] = "เลขที่: {$oldData['number_in_room']} → {$number_in}";
                if ($oldData['color'] !== $color) $changes[] = "สี: {$oldData['color']} → {$color}";
                
                log_activity('UPDATE', 'students', $id, 
                    sprintf("แก้ไขนักเรียน ID:%d | %s %s | %s", 
                        $id, $first_name, $last_name, 
                        !empty($changes) ? implode(' | ', $changes) : 'ไม่มีการเปลี่ยนแปลง'));
            } else {
                log_activity('UPDATE', 'students', $id, 
                    sprintf("แก้ไขนักเรียน ID:%d → %s %s | รหัส: %s", $id, $first_name, $last_name, $student_code));
            }
            
            // เก็บข้อความไว้ใน session
            $_SESSION['success_message'] = 'แก้ไขเรียบร้อย';
            
            // Redirect กลับพร้อม filter จาก session
            $filterParams = [];
            if (!empty($_SESSION['students_filter'])) {
                $savedFilter = $_SESSION['students_filter'];
                if (!empty($savedFilter['color'])) $filterParams['color'] = $savedFilter['color'];
                if (!empty($savedFilter['class_level'])) $filterParams['class_level'] = $savedFilter['class_level'];
                if (!empty($savedFilter['class_room'])) $filterParams['class_room'] = $savedFilter['class_room'];
                if (!empty($savedFilter['q'])) $filterParams['q'] = $savedFilter['q'];
            }
            
            $redirectUrl = BASE_URL . '/students.php' . ($filterParams ? '?' . http_build_query($filterParams) : '');
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $e) {
            // 🔥 LOG: แก้ไขนักเรียนไม่สำเร็จ
            log_activity('ERROR', 'students', $id, 
                sprintf("แก้ไขนักเรียนไม่สำเร็จ: %s | ID:%d | รหัส: %s", 
                    $e->getMessage(), $id, $student_code));
            
            $errors[] = 'แก้ไขไม่สำเร็จ: '.e($e->getMessage());
        }
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $errors[] = 'ไม่พบรายการ';
    } else {
        try {
            // ดึงข้อมูลก่อนลบ
            $oldStmt = $pdo->prepare("SELECT student_code, first_name, last_name, class_level, class_room, number_in_room, color FROM students WHERE id=? AND year_id=?");
            $oldStmt->execute([$id, $yearId]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("DELETE FROM students WHERE id=? AND year_id=?");
            $stmt->execute([$id, $yearId]);
            
            // 🔥 LOG: ลบนักเรียนสำเร็จ
            if ($oldData) {
                log_activity('DELETE', 'students', $id, 
                    sprintf("ลบนักเรียน: %s %s | รหัส: %s | ชั้น: %s/%d เลขที่: %d | สี: %s", 
                        $oldData['first_name'], $oldData['last_name'], $oldData['student_code'], 
                        $oldData['class_level'], $oldData['class_room'], $oldData['number_in_room'], $oldData['color']));
            } else {
                log_activity('DELETE', 'students', $id, sprintf("ลบนักเรียน ID:%d", $id));
            }
            
            // เก็บข้อความไว้ใน session
            $_SESSION['success_message'] = 'ลบเรียบร้อย';
            
            // Redirect กลับพร้อม filter จาก session
            $filterParams = [];
            if (!empty($_SESSION['students_filter'])) {
                $savedFilter = $_SESSION['students_filter'];
                if (!empty($savedFilter['color'])) $filterParams['color'] = $savedFilter['color'];
                if (!empty($savedFilter['class_level'])) $filterParams['class_level'] = $savedFilter['class_level'];
                if (!empty($savedFilter['class_room'])) $filterParams['class_room'] = $savedFilter['class_room'];
                if (!empty($savedFilter['q'])) $filterParams['q'] = $savedFilter['q'];
            }
            
            $redirectUrl = BASE_URL . '/students.php' . ($filterParams ? '?' . http_build_query($filterParams) : '');
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $e) {
            // 🔥 LOG: ลบนักเรียนไม่สำเร็จ
            log_activity('ERROR', 'students', $id, 
                sprintf("ลบนักเรียนไม่สำเร็จ: %s | ID:%d", $e->getMessage(), $id));
            
            $errors[] = 'ลบไม่สำเร็จ: '.e($e->getMessage());
        }
    }
}

// ===== Action: delete all students =====
if ($action === 'delete_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = trim($_POST['confirm_delete'] ?? '');
    if ($confirm === 'DELETE') {
        try {
            // นับจำนวนก่อนลบ
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE year_id=?");
            $countStmt->execute([$yearId]);
            $totalBefore = $countStmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM students WHERE year_id=?");
            $stmt->execute([$yearId]);
            $deleted = $stmt->rowCount();
            
            // 🔥 LOG: ลบนักเรียนทั้งหมดสำเร็จ
            log_activity('DELETE', 'students', null, 
                sprintf("⚠️ ลบนักเรียนทั้งหมด: %d คน | ปีการศึกษา ID:%d", $deleted, $yearId));
            
            $messages[] = "✅ ลบนักเรียนทั้งหมด {$deleted} คน เรียบร้อย (ปีการศึกษา {$yearId})";
        } catch (Throwable $e) {
            // 🔥 LOG: ลบนักเรียนทั้งหมดไม่สำเร็จ
            log_activity('ERROR', 'students', null, 
                sprintf("ลบนักเรียนทั้งหมดไม่สำเร็จ: %s | ปีการศึกษา ID:%d", 
                    $e->getMessage(), $yearId));
            
            $errors[] = 'ลบไม่สำเร็จ: '.e($e->getMessage());
        }
    } else {
        $errors[] = 'ยืนยันไม่ถูกต้อง (ต้องพิมพ์คำว่า DELETE ตัวพิมพ์ใหญ่)';
    }
}

if ($action === 'import_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'อัปโหลดไฟล์ไม่สำเร็จ';
    } else {
        $path = $_FILES['csv']['tmp_name'];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $errors[] = 'เปิดไฟล์ไม่สำเร็จ';
        } else {
            // รองรับ BOM UTF-8
            $first = fgets($handle);
            if (substr($first, 0, 3) === "\xEF\xBB\xBF") $first = substr($first, 3);
            $header = str_getcsv($first);
            $expected = ['ปีการศึกษา','รหัสนักเรียน','ชื่อจริง','นามสกุล','ชั้น','ห้อง','เลขที่','สี'];
            $normalize = fn($arr) => array_map('trim', $arr);

            if ($normalize($header) !== $expected) {
                $errors[] = 'หัวคอลัมน์ไม่ตรงเทมเพลต (กรุณาดาวน์โหลดเทมเพลต CSV ใหม่)';
            } else {
                $inserted = 0;
                $updated  = 0;
                $skipped  = 0;

                $pdo->beginTransaction();
                try {
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) < 8) { $skipped++; continue; }
                        [$year_be, $code, $fname, $lname, $level, $room, $number, $color] = $row;

                        $color  = normalizeColor($color);
                        $code   = trim($code);
                        $fname  = trim($fname);
                        $lname  = trim($lname);
                        $level  = trim($level);
                        $room   = (int)$room;
                        $number = (int)$number;

                        if ($code==='' || $fname==='' || $lname==='' || $level==='' || $room<=0 || $number<=0) {
                            $skipped++; continue;
                        }
                        if (!in_array($color, $allowedColors, true)) {
                            $skipped++; continue;
                        }

                        // ตรวจว่ามีอยู่ไหม
                        $check = $pdo->prepare("SELECT id FROM students WHERE year_id=? AND student_code=?");
                        $check->execute([$yearId, $code]);
                        $exists = $check->fetchColumn();

                        if ($exists) {
                            $stmt = $pdo->prepare("UPDATE students 
                                SET first_name=?, last_name=?, class_level=?, class_room=?, number_in_room=?, color=? 
                                WHERE id=?");
                            $stmt->execute([$fname, $lname, $level, $room, $number, $color, $exists]);
                            $updated++;
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO students(year_id, student_code, first_name, last_name, class_level, class_room, number_in_room, color)
                                                   VALUES(?,?,?,?,?,?,?,?)");
                            $stmt->execute([$yearId, $code, $fname, $lname, $level, $room, $number, $color]);
                            $inserted++;
                        }
                    }
                    $pdo->commit();
                    
                    // 🔥 LOG: นำเข้า CSV สำเร็จ
                    log_activity('IMPORT', 'students', null, 
                        sprintf("นำเข้านักเรียนจาก CSV: เพิ่มใหม่ %d คน | อัปเดต %d คน | ข้าม %d แถว | ปีการศึกษา ID:%d", 
                            $inserted, $updated, $skipped, $yearId));
                    
                    $messages[] = "✅ นำเข้าเสร็จสิ้น: เพิ่มใหม่ {$inserted} แถว, อัปเดต {$updated} แถว, ข้าม {$skipped} แถว";
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    
                    // 🔥 LOG: นำเข้า CSV ไม่สำเร็จ
                    log_activity('ERROR', 'students', null, 
                        sprintf("นำเข้า CSV ไม่สำเร็จ: %s | เพิ่มแล้ว: %d | อัปเดตแล้ว: %d", 
                            $e->getMessage(), $inserted, $updated));
                    
                    $errors[] = 'นำเข้าไม่สำเร็จ: ' . e($e->getMessage());
                }
                fclose($handle);
            }
        }
    }
}

if ($action === 'export_csv') {
    // นับจำนวนก่อน export
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE year_id=?");
    $countStmt->execute([$yearId]);
    $totalExport = $countStmt->fetchColumn();
    
    // 🔥 LOG: ส่งออก CSV
    log_activity('EXPORT', 'students', null, 
        sprintf("ส่งออกนักเรียนเป็น CSV: %d คน | ปีการศึกษา ID:%d", $totalExport, $yearId));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_'.$yearId.'.csv"');

    // ใส่ UTF-8 BOM เช่นกัน
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ปีการศึกษา','รหัสนักเรียน','ชื่อจริง','นามสกุล','ชั้น','ห้อง','เลขที่','สี']);

    $stmt = $pdo->prepare("SELECT student_code, first_name, last_name, class_level, class_room, number_in_room, color
                           FROM students WHERE year_id=? ORDER BY class_level, class_room, number_in_room");
    $stmt->execute([$yearId]);
    $year_be = (int)date('Y') + 543;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$year_be, $r['student_code'], $r['first_name'], $r['last_name'],
                       $r['class_level'], $r['class_room'], $r['number_in_room'], $r['color']]);
    }
    fclose($out);
    exit;
}


// ===== Filtering & pagination =====
$color     = $_GET['color'] ?? '';
$classLvl  = trim($_GET['class_level'] ?? '');
$classRm   = (int)($_GET['class_room'] ?? 0);
$q         = trim($_GET['q'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;

// ถ้ามี filter ใน URL ให้เก็บลง session
if ($color !== '' || $classLvl !== '' || $classRm > 0 || $q !== '') {
    $_SESSION['students_filter'] = [
        'color' => $color,
        'class_level' => $classLvl,
        'class_room' => $classRm,
        'q' => $q
    ];
}

$where = ["year_id=:year_id"];
$params = [':year_id'=>$yearId];

if ($color !== '' && in_array($color, $allowedColors, true)) { $where[] = "color=:color"; $params[':color']=$color; }
if ($classLvl !== '') { $where[] = "class_level=:cl"; $params[':cl']=$classLvl; }
if ($classRm > 0) { $where[] = "class_room=:cr"; $params[':cr']=$classRm; }
if ($q !== '') {
    $where[] = "(student_code LIKE :kw OR first_name LIKE :kw OR last_name LIKE :kw)";
    $params[':kw'] = '%'.$q.'%';
}

$whereSql = implode(' AND ', $where);

// count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page-1)*$perPage;

// data
$stmt = $pdo->prepare("SELECT * FROM students WHERE $whereSql ORDER BY class_level, class_room, number_in_room, id LIMIT :limit OFFSET :offset");
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== UI =====
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

// สร้างตัวเลือกชั้นเรียน (ป.1 ถึง ม.6)
$classOptions = [];
for ($i=1; $i<=6; $i++) { $classOptions[] = "ป.{$i}"; }
for ($i=1; $i<=6; $i++) { $classOptions[] = "ม.{$i}"; }
?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<main class="container py-4">
  <div class="row g-3">
    <!-- left: form add + import/export -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
          <h5 class="card-title mb-3">เพิ่มนักเรียน (ปีปัจจุบัน)</h5>

          <?php if ($errors): ?>
            <div class="alert alert-danger"><?= implode('<br>', array_map('e',$errors)); ?></div>
          <?php endif; ?>
          <?php if ($messages): ?>
            <div class="alert alert-success"><?= implode('<br>', array_map('e',$messages)); ?></div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= e($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
          <?php endif; ?>

          <form method="post" class="row g-2" action="<?php echo BASE_URL; ?>/students.php">
            <input type="hidden" name="action" value="create">
            <div class="col-6">
              <label class="form-label">รหัสนักเรียน</label>
              <input type="text" class="form-control" name="student_code" required>
            </div>
            <div class="col-6">
              <label class="form-label">สี</label>
              <select class="form-select" name="color" required>
                <option value="">- เลือกสี -</option>
                <option>ส้ม</option><option>เขียว</option><option>ชมพู</option><option>ฟ้า</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">ชื่อจริง</label>
              <input type="text" class="form-control" name="first_name" required>
            </div>
            <div class="col-6">
              <label class="form-label">นามสกุล</label>
              <input type="text" class="form-control" name="last_name" required>
            </div>
            <div class="col-4">
              <label class="form-label">ชั้น</label>
              <select class="form-select" name="class_level" required>
                <option value="">- เลือก -</option>
                <?php foreach($classOptions as $cls): ?>
                  <option value="<?= $cls ?>"><?= $cls ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label">ห้อง</label>
              <input type="number" class="form-control" name="class_room" min="1" required>
            </div>
            <div class="col-4">
              <label class="form-label">เลขที่</label>
              <input type="number" class="form-control" name="number_in_room" min="1" required>
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
          <div class="d-grid gap-2">
            <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/students.php?action=template">ดาวน์โหลดเทมเพลต CSV</a>
          </div>
          <hr>
          <form method="post" enctype="multipart/form-data" action="<?php echo BASE_URL; ?>/students.php">
            <input type="hidden" name="action" value="import_csv">
            <div class="mb-2">
              <label class="form-label">อัปโหลด CSV (UTF-8)</label>
              <input type="file" class="form-control" name="csv" accept=".csv" required>
              <div class="form-text">* ใน Excel ให้ "บันทึกเป็น" CSV UTF-8 แล้วอัปโหลด</div>
            </div>
            <div class="d-grid">
              <button class="btn btn-success">นำเข้า</button>
            </div>
          </form>
          <hr>
          <form method="get" action="<?php echo BASE_URL; ?>/students.php">
            <input type="hidden" name="action" value="export_csv">
            <div class="d-grid">
              <button class="btn btn-outline-primary">ส่งออก CSV (ปีปัจจุบัน)</button>
            </div>
          </form>
          <hr>
          <div class="d-grid">
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">ลบนักเรียนทั้งหมด</button>
          </div>
        </div>
      </div>
    </div>

    <!-- right: filter + table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
          <form class="row g-2 align-items-end" method="get" action="<?php echo BASE_URL; ?>/students.php">
            <div class="col-sm-3">
              <label class="form-label">สี</label>
              <select class="form-select" name="color">
                <option value="">ทั้งหมด</option>
                <?php foreach ($allowedColors as $c): ?>
                  <option value="<?php echo $c; ?>" <?php echo ($c===$color)?'selected':''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-3">
              <label class="form-label">ชั้น</label>
              <select class="form-select" name="class_level">
                <option value="">ทั้งหมด</option>
                <?php foreach($classOptions as $cls): ?>
                  <option value="<?= $cls ?>" <?= ($cls===$classLvl)?'selected':'' ?>><?= $cls ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-2">
              <label class="form-label">ห้อง</label>
              <input type="number" class="form-control" name="class_room" value="<?php echo $classRm ?: ''; ?>" min="1">
            </div>
            <div class="col-sm-3">
              <label class="form-label">ค้นหา</label>
              <input type="text" class="form-control" name="q" value="<?php echo e($q); ?>" placeholder="ชื่อหรือรหัส">
            </div>
            <div class="col-sm-1 d-grid">
              <button class="btn btn-primary">ค้นหา</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title mb-0">จำนวนนักเรียน: <?php echo number_format($total); ?></h5>
            <small class="text-muted">แสดงครั้งละ <?php echo $perPage; ?> คน</small>
          </div>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>รหัส</th><th>ชื่อ</th><th>นามสกุล</th>
                  <th>ชั้น/ห้อง</th><th>เลขที่</th><th>สี</th><th style="width:160px;">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$rows): ?>
                  <tr><td colspan="7" class="text-muted">ไม่มีข้อมูล</td></tr>
                <?php else: foreach($rows as $s): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo e($s['student_code']); ?></td>
                    <td><?php echo e($s['first_name']); ?></td>
                    <td><?php echo e($s['last_name']); ?></td>
                    <td><?php echo e($s['class_level']); ?>/<?php echo (int)$s['class_room']; ?></td>
                    <td><?php echo (int)$s['number_in_room']; ?></td>
                    <td><span class="badge bg-secondary"><?php echo e($s['color']); ?></span></td>
                    <td>
                      <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?php echo (int)$s['id']; ?>"
                                data-code="<?php echo e($s['student_code']); ?>"
                                data-fn="<?php echo e($s['first_name']); ?>"
                                data-ln="<?php echo e($s['last_name']); ?>"
                                data-lv="<?php echo e($s['class_level']); ?>"
                                data-rm="<?php echo (int)$s['class_room']; ?>"
                                data-no="<?php echo (int)$s['number_in_room']; ?>"
                                data-color="<?php echo e($s['color']); ?>">
                          แก้ไข
                        </button>
                        <form method="post" action="<?php echo BASE_URL; ?>/students.php" class="delete-student-form">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                          <button type="button" class="btn btn-sm btn-outline-danger delete-student-btn">ลบ</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

          <!-- pagination -->
          <?php if ($pages > 1): ?>
            <nav>
              <ul class="pagination justify-content-end">
                <?php
                  $qsBase = $_GET; unset($qsBase['page']);
                  $build = function($p) use ($qsBase){ $qsBase['page']=$p; return '?'.http_build_query($qsBase); };
                ?>
                <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                  <a class="page-link" href="<?php echo $build(max(1,$page-1)); ?>">&laquo;</a>
                </li>
                <?php for($p=max(1,$page-2); $p<=min($pages,$page+2); $p++): ?>
                  <li class="page-item <?php echo $p===$page?'active':''; ?>">
                    <a class="page-link" href="<?php echo $build($p); ?>"><?php echo $p; ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page>=$pages?'disabled':''; ?>">
                  <a class="page-link" href="<?php echo $build(min($pages,$page+1)); ?>">&raquo;</a>
                </li>
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
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/students.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขข้อมูลนักเรียน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-6">
          <label class="form-label">รหัสนักเรียน</label>
          <input type="text" class="form-control" id="edit-code" name="student_code" required>
        </div>
        <div class="col-6">
          <label class="form-label">สี</label>
          <select class="form-select" id="edit-color" name="color" required>
            <option>ส้ม</option><option>เขียว</option><option>ชมพู</option><option>ฟ้า</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">ชื่อจริง</label>
          <input type="text" class="form-control" id="edit-fn" name="first_name" required>
        </div>
        <div class="col-6">
          <label class="form-label">นามสกุล</label>
          <input type="text" class="form-control" id="edit-ln" name="last_name" required>
        </div>
        <div class="col-4">
          <label class="form-label">ชั้น</label>
          <select class="form-select" id="edit-lv" name="class_level" required>
            <?php foreach($classOptions as $cls): ?>
              <option value="<?= $cls ?>"><?= $cls ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-4">
          <label class="form-label">ห้อง</label>
          <input type="number" class="form-control" id="edit-rm" name="class_room" min="1" required>
        </div>
        <div class="col-4">
          <label class="form-label">เลขที่</label>
          <input type="number" class="form-control" id="edit-no" name="number_in_room" min="1" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete All Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?php echo BASE_URL; ?>/students.php">
      <input type="hidden" name="action" value="delete_all">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">⚠️ ลบนักเรียนทั้งหมด</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger mb-3">
          <strong>คำเตือน:</strong> การกระทำนี้จะลบนักเรียน<strong>ทั้งหมด</strong>ในปีการศึกษานี้ และ<strong>ไม่สามารถกู้คืนได้</strong>
        </div>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('edit-id').value  = b.getAttribute('data-id');
    document.getElementById('edit-code').value= b.getAttribute('data-code');
    document.getElementById('edit-fn').value  = b.getAttribute('data-fn');
    document.getElementById('edit-ln').value  = b.getAttribute('data-ln');
    document.getElementById('edit-lv').value  = b.getAttribute('data-lv');
    document.getElementById('edit-rm').value  = b.getAttribute('data-rm');
    document.getElementById('edit-no').value  = b.getAttribute('data-no');
    document.getElementById('edit-color').value = b.getAttribute('data-color');
  });
}

// บันทึกค่า filter ลง localStorage
function saveFilters() {
  const colorSelect = document.querySelector('select[name="color"]');
  const levelSelect = document.querySelector('select[name="class_level"]');
  const roomInput = document.querySelector('input[name="class_room"]');
  const qInput = document.querySelector('input[name="q"]');
  
  if (colorSelect) localStorage.setItem('students_filter_color', colorSelect.value);
  if (levelSelect) localStorage.setItem('students_filter_level', levelSelect.value);
  if (roomInput) localStorage.setItem('students_filter_room', roomInput.value);
  if (qInput) localStorage.setItem('students_filter_q', qInput.value);
}

// โหลดค่า filter จาก localStorage
function loadFilters() {
  const savedColor = localStorage.getItem('students_filter_color');
  const savedLevel = localStorage.getItem('students_filter_level');
  const savedRoom = localStorage.getItem('students_filter_room');
  const savedQ = localStorage.getItem('students_filter_q');
  
  // ถ้ามีค่าจาก URL (GET) ให้ใช้ค่านั้น และบันทึกลง localStorage
  const urlParams = new URLSearchParams(window.location.search);
  const hasFilterParams = urlParams.has('color') || urlParams.has('class_level') || urlParams.has('class_room') || urlParams.has('q');
  
  // ยกเว้น action พิเศษที่ไม่ต้อง redirect (template, export)
  const action = urlParams.get('action');
  const skipActions = ['template', 'export_csv'];
  
  if (hasFilterParams) {
    // มีการกรอง → บันทึกค่าปัจจุบัน
    saveFilters();
  } else if (!skipActions.includes(action)) {
    // ไม่มีการกรองและไม่ใช่ action พิเศษ → โหลดค่าจาก localStorage และ redirect
    if (savedColor || savedLevel || savedRoom || savedQ) {
      const params = new URLSearchParams();
      if (savedColor) params.set('color', savedColor);
      if (savedLevel) params.set('class_level', savedLevel);
      if (savedRoom) params.set('class_room', savedRoom);
      if (savedQ) params.set('q', savedQ);
      
      window.location.href = '<?php echo BASE_URL; ?>/students.php?' + params.toString();
      return; // หยุดการทำงานเพื่อรอ redirect
    }
  }
}

// เรียกใช้เมื่อโหลดหน้า
loadFilters();

// บันทึกค่าทุกครั้งที่มีการเปลี่ยนแปลง filter
const filterForm = document.querySelector('form[method="get"]');
if (filterForm) {
  const filterInputs = filterForm.querySelectorAll('select, input[type="text"], input[type="number"]');
  filterInputs.forEach(input => {
    input.addEventListener('change', saveFilters);
    input.addEventListener('input', saveFilters);
  });
}

// บันทึก filter ก่อน submit form ทุกแบบ (POST forms)
document.querySelectorAll('form[method="post"]').forEach(form => {
  form.addEventListener('submit', function(e) {
    // บันทึกค่า filter ปัจจุบันก่อน submit
    saveFilters();
    
    // เพิ่ม hidden input สำหรับ filter ลงใน form
    const savedColor = localStorage.getItem('students_filter_color') || '';
    const savedLevel = localStorage.getItem('students_filter_level') || '';
    const savedRoom = localStorage.getItem('students_filter_room') || '';
    const savedQ = localStorage.getItem('students_filter_q') || '';
    
    // ลบ hidden input เก่าถ้ามี
    form.querySelectorAll('input[name^="filter_"]').forEach(inp => inp.remove());
    
    // เพิ่ม hidden input ใหม่
    if (savedColor) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'filter_color';
      input.value = savedColor;
      form.appendChild(input);
    }
    if (savedLevel) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'filter_level';
      input.value = savedLevel;
      form.appendChild(input);
    }
    if (savedRoom) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'filter_room';
      input.value = savedRoom;
      form.appendChild(input);
    }
    if (savedQ) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'filter_q';
      input.value = savedQ;
      form.appendChild(input);
    }
  });
});

// SweetAlert2 สำหรับปุ่มลบ
document.addEventListener('click', function(e) {
  if (e.target && e.target.classList.contains('delete-student-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    
    Swal.fire({
      title: 'ยืนยันการลบ',
      text: 'ต้องการลบนักเรียนคนนี้ใช่หรือไม่?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'ใช่, ลบเลย',
      cancelButtonText: 'ยกเลิก'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  }
});
</script>
