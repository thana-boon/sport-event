<?php
// public/staff/navbar.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!defined('BASE_URL')) { define('BASE_URL', '/sport-event/public'); }

$staff = $_SESSION['staff'] ?? null;

// ดึงปีการศึกษาที่ Active
$activeYearBe = null;
try {
  require_once __DIR__ . '/../../config/db.php';
  $pdo = db();
  $st = $pdo->query("SELECT year_be FROM academic_years WHERE is_active=1 ORDER BY year_be DESC LIMIT 1");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if ($row) $activeYearBe = (int)$row['year_be'];
} catch (Throwable $e) {}

$brand = 'Staff' . ($activeYearBe ? ' • ปีการศึกษา '.$activeYearBe : '');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?php echo BASE_URL; ?>/staff/index.php">
      <?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarStaff" aria-controls="navbarStaff" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarStaff">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($staff): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='index.php'?'active':''; ?>" href="<?php echo BASE_URL; ?>/staff/index.php">แดชบอร์ด</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='register.php'?'active':''; ?>" href="<?php echo BASE_URL; ?>/staff/register.php">ลงทะเบียนกีฬา</a>
            </li>
          <!-- เผื่ออนาคต: เมนูไปหน้าลงทะเบียน -->
          <!-- <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/register.php">ลงทะเบียนกีฬา</a></li> -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <?php
                $name = $staff['display_name'] ?? $staff['username'];
                $color = $staff['color'] ?? '-';
                echo htmlspecialchars($name.' • สี'.$color, ENT_QUOTES, 'UTF-8');
              ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><span class="dropdown-item-text small text-muted">@<?php echo htmlspecialchars($staff['username'], ENT_QUOTES, 'UTF-8'); ?></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/staff/logout.php">ออกจากระบบ</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/login.php">เข้าสู่ระบบ</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
