<?php
/**
 * includes/navbar.php
 * แถบนำทางหลักของระบบ (สำหรับฝั่ง admin + หน้า public ทั้งหมด)
 * - กันฟังก์ชัน e() ซ้ำด้วย function_exists
 * - ใช้ BASE_URL เป็นฐานทุกลิงก์
 * - แสดงชื่อระบบ: "ระบบจัดการกีฬาสี ปีการศึกษา xxxx" (ถ้ามีปีที่ active)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ----- โหลด config/DB/helpers ให้พร้อม (safe ด้วย require_once) -----
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

// ----- ฟังก์ชันอรรถประโยชน์ (กันประกาศซ้ำ) -----
if (!function_exists('e')) {
  function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

// ----- ค่าพื้นฐาน -----
if (!defined('BASE_URL')) {
  // fallback เผื่อยังไม่ได้ define (กรณีมีการ include ไฟล์นี้นอก public/)
  define('BASE_URL', '/sport-event/public');
}

$current = basename($_SERVER['PHP_SELF']); // ใช้เช็คเมนู active

// ----- สร้างข้อความชื่อระบบ (ดึงปีการศึกษาที่ Active) -----
$brandText = 'ระบบจัดการกีฬาสี';
try {
  $pdo = db(); // จาก config/db.php
  $yid = active_year_id($pdo); // จาก lib/helpers.php (คุณมีอยู่แล้ว)
  if ($yid) {
    $stmt = $pdo->prepare("SELECT year_be FROM academic_years WHERE id=? LIMIT 1");
    $stmt->execute([(int)$yid]);
    $yy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($yy['year_be'])) {
      $brandText .= ' ปีการศึกษา ' . e($yy['year_be']);
    }
  }
} catch (Throwable $th) {
  // ถ้า DB ยังไม่พร้อม ไม่ต้องทำอะไร ปล่อยชื่อระบบสั้น ๆ ไป
}

// ----- สร้างตัวช่วย active class -----
function nav_active($file, $current) {
  return $current === $file ? 'active' : '';
}

// ----- อ่านชื่อผู้ใช้ (ถ้ามี login) -----
$displayName = null;
if (!empty($_SESSION['admin'])) {
  $displayName = $_SESSION['admin']['display_name']
    ?? $_SESSION['admin']['username']
    ?? 'admin';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?php echo BASE_URL; ?>/index.php">
      <?php echo e($brandText); ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

        <?php if (!empty($_SESSION['admin'])): ?>
          <!-- เมนูสำหรับผู้ดูแลระบบ -->
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('index.php',$current); ?>" href="<?php echo BASE_URL; ?>/index.php">แดชบอร์ด</a>
          </li>

          <!-- กลุ่ม: จัดการข้อมูล -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['years.php','students.php','categories.php']) ? 'active' : ''; ?>" href="#" id="dataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              จัดการข้อมูล
            </a>
            <ul class="dropdown-menu" aria-labelledby="dataDropdown">
              <li><a class="dropdown-item <?php echo nav_active('years.php',$current); ?>" href="<?php echo BASE_URL; ?>/years.php">กำหนดปีการศึกษา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('students.php',$current); ?>" href="<?php echo BASE_URL; ?>/students.php">จัดการนักเรียน</a></li>
              <li><a class="dropdown-item <?php echo nav_active('categories.php',$current); ?>" href="<?php echo BASE_URL; ?>/categories.php">กำหนดประเภทกีฬา</a></li>
            </ul>
          </li>

          <!-- กลุ่ม: จัดการกีฬา -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['sports.php','matches.php','athletics.php']) ? 'active' : ''; ?>" href="#" id="sportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              จัดการกีฬา
            </a>
            <ul class="dropdown-menu" aria-labelledby="sportDropdown">
              <li><a class="dropdown-item <?php echo nav_active('sports.php',$current); ?>" href="<?php echo BASE_URL; ?>/sports.php">กำหนดรายการกีฬา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('matches.php',$current); ?>" href="<?php echo BASE_URL; ?>/matches.php">จับคู่การแข่งขัน</a></li>
              <li><a class="dropdown-item <?php echo nav_active('athletics.php',$current); ?>" href="<?php echo BASE_URL; ?>/athletics.php">จัดลู่กรีฑา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('users.php',$current); ?>" href="<?php echo BASE_URL; ?>/users.php">จัดการผู้ใช้</a></li>
            </ul>
          </li>

          <!-- ควบคุมการลงทะเบียน -->
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('regis.php',$current); ?>" href="<?php echo BASE_URL; ?>/regis.php">จัดการลงทะเบียนนักกีฬา</a>
          </li>

          <!-- เมนูรายงานแบบ dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['reports.php','reports_booklet.php']) ? 'active' : ''; ?>" href="#" id="reportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              รายงาน
            </a>
            <ul class="dropdown-menu" aria-labelledby="reportDropdown">
              <li>
                <a class="dropdown-item<?php echo $current === 'reports.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports.php">
                  ใบเช็คชื่อนักกีฬา
                </a>
              </li>
              <li>
                <a class="dropdown-item<?php echo $current === 'reports_booklet.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports_booklet.php">
                  สูจิบัตรรายชื่อนักกีฬา (กีฬา)
                </a>
                <a class="dropdown-item<?php echo $current === 'reports_athletics.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports_athletics.php">
                  สูจิบัตรรายชื่อนักกีฬา (กรีฑา)
                </a>
              </li>
            </ul>
          </li>

          <!-- ขวาสุด: ชื่อผู้ใช้ / ออกจากระบบ -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo e($displayName ?? 'ผู้ดูแลระบบ'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><span class="dropdown-item-text small text-muted"><?php echo '@'.e($_SESSION['admin']['username'] ?? 'admin'); ?></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">ออกจากระบบ</a></li>
            </ul>
          </li>

        <?php else: ?>
          <!-- ยังไม่ได้ล็อกอิน -->
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('login.php',$current); ?>" href="<?php echo BASE_URL; ?>/login.php">เข้าสู่ระบบ</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
