<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ถ้าเข้าเพจนี้ทั้งที่ล็อกอินแล้ว และเป็น admin → ส่งไปหน้า dashboard
if (!empty($_SESSION['admin']) && ($_SESSION['admin']['role'] ?? '') === 'admin') {
  header('Location: ' . BASE_URL . '/index.php');
  exit;
}

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  // ดึงจากตาราง users (ทุก admin ใช้ได้) และต้องเปิดใช้งาน
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password_hash'])) {
    if ($user['role'] === 'admin') {
      // เก็บ session สำหรับ admin (ทุกคนที่ role=admin ผ่านหมด)
      $_SESSION['admin'] = [
        'id'           => (int)$user['id'],
        'username'     => $user['username'],
        'display_name' => $user['display_name'],
        'role'         => $user['role'],
        'staff_color'  => $user['staff_color'],
      ];
      header('Location: ' . BASE_URL . '/index.php');
      exit;
    } else {
      // ล็อกอินถูก แต่ไม่ใช่ admin → ไม่ให้เข้าหน้า admin
      $error = 'บัญชีนี้ไม่มีสิทธิ์เข้าแดชบอร์ด (admin เท่านั้น)';
    }
  } else {
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="container py-5" style="max-width: 560px;">
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
      <h5 class="card-title mb-3">เข้าสู่ระบบ (สำหรับผู้ดูแลระบบ)</h5>

      <?php if (!empty($_GET['denied'])): ?>
        <div class="alert alert-warning">คุณไม่มีสิทธิ์เข้าหน้าแดชบอร์ด</div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" action="<?php echo BASE_URL; ?>/login.php" class="row g-3">
        <div class="col-12">
          <label class="form-label">ชื่อผู้ใช้</label>
          <input type="text" class="form-control" name="username" required autofocus>
        </div>
        <div class="col-12">
          <label class="form-label">รหัสผ่าน</label>
          <input type="password" class="form-control" name="password" required>
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-primary">เข้าสู่ระบบ</button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
