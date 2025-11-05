<?php
// public/staff/navbar.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!defined('BASE_URL')) { define('BASE_URL', '/sport-event/public'); }

// ✅ เพิ่มการโหลด helpers สำหรับ log_activity
require_once __DIR__ . '/../../lib/helpers.php';

$staff = $_SESSION['staff'] ?? null;

// ✅ เช็ค session timeout (30 นาที)
$timeout = 1800; // 30 นาที
if (!empty($staff)) {
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    // 🔥 LOG: Session timeout
    log_activity('LOGOUT', 'users', $staff['id'] ?? null, 
      'ออกจากระบบอัตโนมัติ (session timeout 30 นาที) | Username: ' . ($staff['username'] ?? 'unknown') . ' | สี: ' . ($staff['color'] ?? '-'));
    
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/staff/login.php?timeout=1');
    exit;
  }
  $_SESSION['last_activity'] = time();
}

// ดึงปีการศึกษาที่ Active
$activeYearBe = null;
try {
  require_once __DIR__ . '/../../config/db.php';
  $pdo = db();
  $st = $pdo->query("SELECT year_be FROM academic_years WHERE is_active=1 ORDER BY year_be DESC LIMIT 1");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if ($row) $activeYearBe = (int)$row['year_be'];
} catch (Throwable $e) {}

// Color themes
$staffColor = $staff['color'] ?? null;
$colorThemes = [
  'เขียว' => ['hex' => '#28a745', 'light' => '#d4edda'],
  'ฟ้า'   => ['hex' => '#17a2b8', 'light' => '#d1ecf1'],
  'ชมพู'  => ['hex' => '#e83e8c', 'light' => '#f8d7da'],
  'ส้ม'   => ['hex' => '#fd7e14', 'light' => '#fff3cd'],
];
$currentTheme = $colorThemes[$staffColor] ?? ['hex' => '#0d6efd', 'light' => '#cfe2ff'];
?>

<style>
  .navbar-staff {
    background: linear-gradient(90deg, <?php echo $currentTheme['hex']; ?>, <?php echo $currentTheme['hex']; ?>dd) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }
  .navbar-staff .navbar-brand {
    font-weight: 700;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .navbar-staff .nav-link {
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    border-radius: 0.5rem;
    transition: all 0.2s;
  }
  .navbar-staff .nav-link:hover {
    background: rgba(255,255,255,0.2);
  }
  .navbar-staff .nav-link.active {
    background: rgba(255,255,255,0.25);
    font-weight: 600;
  }
  .color-badge-nav {
    background: white;
    color: <?php echo $currentTheme['hex']; ?>;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }
  .color-dot-nav {
    width: 0.6rem;
    height: 0.6rem;
    border-radius: 50%;
    background: <?php echo $currentTheme['hex']; ?>;
  }
  .year-badge {
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    font-weight: 500;
  }
  .dropdown-menu {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  .dropdown-item {
    border-radius: 0.5rem;
    margin: 0.25rem 0.5rem;
  }
  .dropdown-item:hover {
    background: <?php echo $currentTheme['light']; ?>;
    color: <?php echo $currentTheme['hex']; ?>;
  }
</style>

<nav class="navbar navbar-staff navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="<?php echo BASE_URL; ?>/staff/index.php">
      <span>🏆</span>
      <span>ระบบจัดการนักกีฬา</span>
      <?php if ($activeYearBe): ?>
        <span class="year-badge">📅 <?php echo $activeYearBe; ?></span>
      <?php endif; ?>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarStaff" aria-controls="navbarStaff" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarStaff">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($staff): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='index.php'?'active':''; ?>" 
               href="<?php echo BASE_URL; ?>/staff/index.php">
              📊 แดชบอร์ด
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='register.php'?'active':''; ?>" 
               href="<?php echo BASE_URL; ?>/staff/register.php">
              ✍️ ลงทะเบียนกีฬา
            </a>
          </li>
          
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
              <span class="color-badge-nav">
                <span class="color-dot-nav"></span>
                <span>สี<?php echo htmlspecialchars($staffColor ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
              </span>
              <span>👤 <?php echo htmlspecialchars($staff['display_name'] ?? $staff['username'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <span class="dropdown-item-text small text-muted">
                  🔑 @<?php echo htmlspecialchars($staff['username'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/staff/logout.php">
                  🚪 ออกจากระบบ
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/staff/login.php">
              🔑 เข้าสู่ระบบ
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
