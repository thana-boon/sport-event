<?php
/**
 * includes/navbar.php
 * แถบนำทางหลักของระบบ (สำหรับฝั่ง admin + หน้า public ทั้งหมด)
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
  define('BASE_URL', '/sport-event/public');
}

$current = basename($_SERVER['PHP_SELF']);

// ✅ เปลี่ยนจาก 900 (15 นาที) เป็น 1800 (30 นาที)
$timeout = 1800; // 30 นาที
if (!empty($_SESSION['admin'])) {
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    // 🔥 LOG: Session timeout
    log_activity('LOGOUT', 'users', $_SESSION['admin']['id'] ?? null, 
      'ออกจากระบบอัตโนมัติ (session timeout 30 นาที) | Username: ' . ($_SESSION['admin']['username'] ?? 'unknown'));
    
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php?timeout=1');
    exit;
  }
  $_SESSION['last_activity'] = time();
}

// ----- สร้างข้อความชื่อระบบ (ดึงปีการศึกษาที่ Active) -----
$brandText = 'ระบบจัดการกีฬาสี';
try {
  $pdo = db();
  $yid = active_year_id($pdo);
  if ($yid) {
    $stmt = $pdo->prepare("SELECT year_be FROM academic_years WHERE id=? LIMIT 1");
    $stmt->execute([(int)$yid]);
    $yy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($yy['year_be'])) {
      $brandText .= ' ปีการศึกษา ' . e($yy['year_be']);
    }
  }
} catch (Throwable $th) {
  // ถ้า DB ยังไม่พร้อม ไม่ต้องทำอะไร
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

// ซ่อนทั้ง navbar เมื่อ URI ชี้ไปยังหน้า login
$uri = $_SERVER['REQUEST_URI'] ?? '';
$hideNav = (strpos($uri, '/login.php') !== false)
         || (strpos($uri, '/referee/login.php') !== false)
         || (strpos($uri, '/staff/login.php') !== false);

if (!$hideNav):
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?php echo BASE_URL; ?>/index.php">
      🏆 <?php echo e($brandText); ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

        <?php if (!empty($_SESSION['admin'])): ?>
          <!-- เมนูสำหรับผู้ดูแลระบบ -->
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('index.php',$current); ?>" href="<?php echo BASE_URL; ?>/index.php">📊 แดชบอร์ด</a>
          </li>

          <!-- กลุ่ม: จัดการข้อมูล -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['years.php','students.php','categories.php']) ? 'active' : ''; ?>" href="#" id="dataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              📂 จัดการข้อมูล
            </a>
            <ul class="dropdown-menu" aria-labelledby="dataDropdown">
              <li><a class="dropdown-item <?php echo nav_active('years.php',$current); ?>" href="<?php echo BASE_URL; ?>/years.php">📅 กำหนดปีการศึกษา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('students.php',$current); ?>" href="<?php echo BASE_URL; ?>/students.php">👨‍🎓 จัดการนักเรียน</a></li>
              <li><a class="dropdown-item <?php echo nav_active('categories.php',$current); ?>" href="<?php echo BASE_URL; ?>/categories.php">🏅 กำหนดประเภทกีฬา</a></li>
            </ul>
          </li>

          <!-- กลุ่ม: จัดการกีฬา -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['sports.php','matches.php','athletics.php','users.php']) ? 'active' : ''; ?>" href="#" id="sportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              ⚽ จัดการกีฬา
            </a>
            <ul class="dropdown-menu" aria-labelledby="sportDropdown">
              <li><a class="dropdown-item <?php echo nav_active('sports.php',$current); ?>" href="<?php echo BASE_URL; ?>/sports.php">🎯 กำหนดรายการกีฬา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('matches.php',$current); ?>" href="<?php echo BASE_URL; ?>/matches.php">🤝 จับคู่การแข่งขัน</a></li>
              <li><a class="dropdown-item <?php echo nav_active('athletics.php',$current); ?>" href="<?php echo BASE_URL; ?>/athletics.php">🏃 จัดลู่กรีฑา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('users.php',$current); ?>" href="<?php echo BASE_URL; ?>/users.php">👥 จัดการผู้ใช้</a></li>
            </ul>
          </li>

          <!-- กลุ่ม: การลงทะบียน -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['regis.php','referee.php']) ? 'active' : ''; ?>" href="#" id="godmodeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              ⚡ GodMode
            </a>
            <ul class="dropdown-menu" aria-labelledby="godmodeDropdown">
              <li><a class="dropdown-item <?php echo nav_active('regis.php',$current); ?>" href="<?php echo BASE_URL; ?>/regis.php">✍️ จัดการลงทะเบียนนักกีฬา</a></li>
              <li><a class="dropdown-item <?php echo nav_active('referee.php',$current); ?>" href="<?php echo BASE_URL; ?>/referee.php">🎖️ บันทึกผลการแข่งขัน</a></li>
            </ul>
          </li>

          <!-- เมนูรายงานและ Logs แบบ dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo in_array($current, ['reports.php','reports_booklet.php','reports_athletics.php','logs.php']) ? 'active' : ''; ?>" href="#" id="reportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              📋 รายงาน
            </a>
            <ul class="dropdown-menu" aria-labelledby="reportDropdown">
              <li>
                <a class="dropdown-item<?php echo $current === 'reports.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports.php">
                  📄 รายงานและเอกสาร
                </a>
              </li>
              <li>
                <a class="dropdown-item<?php echo $current === 'reports_booklet.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports_booklet.php">
                  📖 สูจิบัตรนักกีฬา (กีฬา)
                </a>
              </li>
              <li>
                <a class="dropdown-item<?php echo $current === 'reports_athletics.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/reports_athletics.php">
                  📗 สูจิบัตรนักกีฬา (กรีฑา)
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item<?php echo $current === 'logs.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/logs.php">
                  📜 Activity Logs
                </a>
              </li>
            </ul>
          </li>

          <!-- ขวาสุด: ชื่อผู้ใช้ / ออกจากระบบ -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              👤 <?php echo e($displayName ?? 'ผู้ดูแลระบบ'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><span class="dropdown-item-text small text-muted"><?php echo '@'.e($_SESSION['admin']['username'] ?? 'admin'); ?></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
          </li>

        <?php else: ?>
          <!-- ยังไม่ได้ล็อกอิน -->
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('login.php',$current); ?>" href="<?php echo BASE_URL; ?>/login.php">🔑 เข้าสู่ระบบ</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
<?php
endif;
