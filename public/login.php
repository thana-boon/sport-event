<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// หากล็อกอินแล้วและเป็น admin เปลี่ยนเส้นทาง
if (!empty($_SESSION['admin']) && ($_SESSION['admin']['role'] ?? '') === 'admin') {
  header('Location: ' . BASE_URL . '/index.php');
  exit;
}

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password_hash'])) {
    if ($user['role'] === 'admin') {
      $_SESSION['admin'] = [
        'id'           => (int)$user['id'],
        'username'     => $user['username'],
        'display_name' => $user['display_name'],
        'role'         => $user['role'],
        'staff_color'  => $user['staff_color'],
      ];
      $_SESSION['last_activity'] = time();
      
      // 🔥 LOG: Login สำเร็จ
      log_activity('LOGIN', 'users', $user['id'], 
        'เข้าสู่ระบบสำเร็จ (admin) | Display: ' . ($user['display_name'] ?: $user['username']));
      
      header('Location: ' . BASE_URL . '/index.php');
      exit;
    } else {
      // 🔥 LOG: พยายาม login ด้วย role ที่ไม่ใช่ admin
      log_activity('LOGIN_DENIED', 'users', $user['id'] ?? null, 
        'พยายามเข้าสู่ระบบด้วยบัญชีที่ไม่ใช่ admin | Username: ' . $username . ' | Role: ' . ($user['role'] ?? 'unknown'));
      
      $error = 'บัญชีนี้ไม่มีสิทธิ์เข้าแดชบอร์ด (admin เท่านั้น)';
    }
  } else {
    // 🔥 LOG: Login ไม่สำเร็จ (username/password ผิด)
    log_activity('LOGIN_FAILED', 'users', null, 
      'พยายามเข้าสู่ระบบไม่สำเร็จ | Username: ' . $username);
    
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ระบบดูแล (admin)</title>

  <!-- Kanit font -->
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg-start: #f4f7ff;
      --bg-end: #eef4ff;
      --card-bg: #ffffff;
      --accent-1: #0d6efd;
      --accent-2: #0a58ca;
      --muted: #6b7280;
    }
    body { font-family: 'Kanit', system-ui, -apple-system, "Segoe UI", Roboto, Arial; }

    /* center modal-like area while keeping navbar */
    main.login-wrap {
      min-height: calc(100vh - 76px); /* account for navbar height */
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 3rem 1rem;
      background: linear-gradient(180deg,var(--bg-start),var(--bg-end));
    }

    .login-modal {
      width:100%;
      max-width:760px;
      border-radius:12px;
      overflow:hidden;
      box-shadow: 0 18px 50px rgba(12,38,70,0.08);
      display:flex;
      background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.95));
      border: 1px solid rgba(13,110,253,0.06);
    }

    .login-side {
      flex:1 1 260px;
      padding: 2rem;
      background: linear-gradient(135deg, rgba(13,110,253,0.06), rgba(10,88,202,0.02));
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:12px;
    }
    .logo {
      width:56px; height:56px; border-radius:10px;
      background: linear-gradient(135deg,var(--accent-1),var(--accent-2));
      color:#fff; display:inline-flex; align-items:center; justify-content:center;
      font-weight:700; box-shadow:0 8px 24px rgba(13,110,253,0.12);
      font-size:1.05rem;
    }
    .login-form {
      flex:0 0 380px;
      padding: 2rem;
      background: var(--card-bg);
    }

    .login-form h4{ margin-bottom:0.6rem; font-weight:700; color:#0b1a2b; }
    .form-label { font-weight:500; font-size:0.95rem; color:#223; }
    .form-control { border-radius:10px; padding:0.6rem 0.75rem; box-shadow:none; border:1px solid rgba(15,23,42,0.06); }
    .btn-primary { border-radius:10px; padding:0.56rem 0.9rem; font-weight:600; background:var(--accent-1); border-color:var(--accent-1); }
    .small-note { color:var(--muted); font-size:0.87rem; }
    .alert { border-radius:8px; padding:0.5rem 0.75rem; margin-bottom:1rem; }

    @media (max-width:720px){
      .login-modal { flex-direction:column; }
      .login-side, .login-form { padding:1rem; }
    }
  </style>
</head>
<body>
  <main class="login-wrap">
    <div class="login-modal" role="dialog" aria-modal="true" aria-label="Login dialog">
      <div class="login-side">
        <div style="display:flex; align-items:center; gap:12px;">
          <div class="logo">SE</div>
          <div>
            <div style="font-weight:700; font-size:1.05rem;">ราชพฤกษ์เกม</div>
            <div class="small-note">ระบบจัดการการแข่งขัน</div>
          </div>
        </div>

        <div style="margin-top:10px; color:var(--muted);">
          ยินดีต้อนรับสู่หน้าเข้าสู่ระบบผู้ดูแล (admin) — กรุณาใช้บัญชีที่มีสิทธิ์ admin เท่านั้น
        </div>

        <div style="margin-top:auto; font-size:0.85rem; color:#94a3b8;">
          © <?= date('Y') ?> SPORT‑EVENT
        </div>
      </div>

      <div class="login-form">
        <h4>ระบบดูแล (admin)</h4>

        <?php if (!empty($_GET['denied'])): ?>
          <div class="alert alert-warning">คุณไม่มีสิทธิ์เข้าหน้าแดชบอร์ด</div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(BASE_URL . '/login.php', ENT_QUOTES, 'UTF-8') ?>" class="row g-3" autocomplete="on" novalidate>
          <div class="col-12">
            <label class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" name="username" required autofocus>
          </div>
          <div class="col-12">
            <label class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" name="password" required>
          </div>

          <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
              <button class="btn btn-primary">เข้าสู่ระบบ</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
