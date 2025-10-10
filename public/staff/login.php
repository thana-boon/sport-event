<?php
// public/staff/login.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ถ้า staff ล็อกอินอยู่แล้ว → ไปหน้า dashboard
if (!empty($_SESSION['staff'])) {
  header('Location: ' . BASE_URL . '/staff/index.php');
  exit;
}

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  $stmt = $pdo->prepare("SELECT id, username, password_hash, display_name, role, staff_color, is_active
                         FROM users
                         WHERE username=? AND role='staff' AND is_active=1
                         LIMIT 1");
  $stmt->execute([$username]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($u && password_verify($password, $u['password_hash'])) {
    // เก็บ session ของ staff
    $_SESSION['staff'] = [
      'id'           => (int)$u['id'],
      'username'     => $u['username'],
      'display_name' => $u['display_name'],
      'color'        => $u['staff_color'] ?: null,
    ];
    header('Location: ' . BASE_URL . '/staff/index.php');
    exit;
  } else {
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (หรือบัญชีถูกปิดใช้งาน)';
  }
}

include __DIR__ . '/../../includes/header.php';
?>
<main class="container py-5" style="max-width: 560px;">
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
      <h5 class="card-title mb-3">เข้าสู่ระบบ (เจ้าหน้าที่ประจำสี)</h5>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" action="<?php echo BASE_URL; ?>/staff/login.php" class="row g-3">
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

      <div class="mt-3 small text-muted">
        * เฉพาะบัญชีสิทธิ์ <strong>staff</strong> เท่านั้น
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
