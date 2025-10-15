<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$pdo    = db();
$yearId = active_year_id($pdo);
if (!$yearId) {
  $_SESSION['flash_error'] = 'ยังไม่ได้ตั้งปีการศึกษาให้ Active';
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['logo'])) {
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

$allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/jpg'=>'jpg','image/webp'=>'webp','image/svg+xml'=>'svg'];
$type = $_FILES['logo']['type'] ?? '';
$ext  = $allowed[$type] ?? null;
if (!$ext) {
  $_SESSION['flash_error'] = 'ชนิดไฟล์ไม่รองรับ (รองรับ: PNG, JPG, WEBP, SVG)';
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
  $_SESSION['flash_error'] = 'อัปโหลดไฟล์ไม่สำเร็จ (error code: ' . (int)$_FILES['logo']['error'] . ')';
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

$destDir = __DIR__ . '/uploads/logo';
if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
if (!is_dir($destDir) || !is_writable($destDir)) {
  $_SESSION['flash_error'] = 'ไม่สามารถเขียนไฟล์ไปยังโฟลเดอร์ ' . $destDir;
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

$baseName = 'logo_year_' . $yearId . '_' . date('Ymd_His') . '.' . $ext;
$destPath = $destDir . '/' . $baseName;
if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destPath)) {
  $_SESSION['flash_error'] = 'บันทึกไฟล์โลโก้ไม่สำเร็จ';
  header('Location: ' . BASE_URL . '/reports_booklet.php');
  exit;
}

$relative = 'uploads/logo/' . $baseName;

$chk = $pdo->prepare("SELECT 1 FROM competition_meta WHERE year_id=:y LIMIT 1");
$chk->execute([':y'=>$yearId]);
if ($chk->fetchColumn()) {
  $up = $pdo->prepare("UPDATE competition_meta SET logo_path=:p WHERE year_id=:y");
  $up->execute([':p'=>$relative, ':y'=>$yearId]);
} else {
  $ins = $pdo->prepare("INSERT INTO competition_meta (year_id, logo_path) VALUES (:y, :p)");
  $ins->execute([':y'=>$yearId, ':p'=>$relative]);
}

$_SESSION['flash'] = 'อัปโหลดโลโก้เรียบร้อย';
header('Location: ' . BASE_URL . '/reports_booklet.php');
exit;
