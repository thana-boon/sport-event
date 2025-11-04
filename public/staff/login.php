<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

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
      'role'         => 'staff'
    ];
    // เริ่มจับเวลา inactivity
    $_SESSION['last_activity'] = time();

    // 🔥 LOG: Login สำเร็จ (staff)
    log_activity('LOGIN', 'users', $u['id'], 
      'เข้าสู่ระบบสำเร็จ (staff) | Display: ' . ($u['display_name'] ?: $u['username']) . ' | สี: ' . ($u['staff_color'] ?: '-'));

    header('Location: ' . BASE_URL . '/staff/index.php');
    exit;
  } else {
    // ตรวจสอบว่ามี username นี้อยู่แต่ไม่ใช่ staff หรือถูกปิดใช้งาน
    $checkStmt = $pdo->prepare("SELECT id, username, role, is_active FROM users WHERE username=? LIMIT 1");
    $checkStmt->execute([$username]);
    $checkUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($checkUser) {
      if ($checkUser['role'] !== 'staff') {
        // 🔥 LOG: พยายาม login ด้วย role ที่ไม่ใช่ staff
        log_activity('LOGIN_DENIED', 'users', $checkUser['id'], 
          'พยายามเข้าสู่ระบบด้วยบัญชีที่ไม่ใช่ staff | Username: ' . $username . ' | Role: ' . ($checkUser['role'] ?? 'unknown'));
      } elseif ($checkUser['is_active'] == 0) {
        // 🔥 LOG: บัญชีถูกปิดใช้งาน
        log_activity('LOGIN_DISABLED', 'users', $checkUser['id'], 
          'พยายามเข้าสู่ระบบด้วยบัญชีที่ถูกปิดใช้งาน | Username: ' . $username);
      } else {
        // 🔥 LOG: รหัสผ่านผิด
        log_activity('LOGIN_FAILED', 'users', $checkUser['id'], 
          'พยายามเข้าสู่ระบบด้วยรหัสผ่านผิด | Username: ' . $username);
      }
    } else {
      // 🔥 LOG: ไม่พบ username
      log_activity('LOGIN_FAILED', 'users', null, 
        'พยายามเข้าสู่ระบบด้วย username ที่ไม่มีในระบบ | Username: ' . $username);
    }
    
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (หรือบัญชีถูกปิดใช้งาน)';
  }
}
include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . '/../../includes/navbar.php';
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบเจ้าหน้าที่ประจำสี</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Kanit font -->
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="icon" type="image/png" sizes="32x32" href="/sport-event/public/assets/icon.png">
  <link rel="shortcut icon" href="/sport-event/public/assets/icon.png">
  <link rel="icon" href="/sport-event/favicon.ico"> <!-- fallback root -->

  <style>
    :root {
      --card-radius: 12px;
      --accent: #7b3fe4; /* purple accent */
      --accent-2: #9b5cf5;
      --bg-light: #f6f0ff;
      --bg-deep: #f0e7ff;
    }
    html,body { height:100%; }
    body {
      font-family: 'Kanit', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg, rgba(123,63,228,0.12), rgba(155,92,245,0.06));
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
    .wrap {
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:2rem;
    }
    .login-card {
      width:100%;
      max-width:760px;
      border-radius:var(--card-radius);
      overflow:hidden;
      border:0;
      display:flex;
      gap:0;
      box-shadow: 0 10px 30px rgba(11,20,35,0.06);
    }
    .login-left {
      flex:1 1 320px;
      padding:2rem;
      background: linear-gradient(180deg, rgba(123,63,228,0.16), rgba(155,92,245,0.06));
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:0.75rem;
    }
    .brand-mark {
      display:inline-flex;
      align-items:center;
      gap:10px;
    }
    .logo {
      width:56px; height:56px; border-radius:10px;
      display:inline-flex; align-items:center; justify-content:center;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      box-shadow: 0 6px 18px rgba(13,110,253,0.12);
    }
    .logo img {
      width:48px;
      height:48px;
      object-fit:contain;
      border-radius:8px; /* ถ้าอยากได้มุมโค้ง */
      background: transparent;
     }
    .login-right {
      flex:0 0 380px;
      padding:2rem;
      background:#fff;
    }
    h3 { margin:0; font-weight:600; color:#0b1a2b; }
    .muted { color:#6b7280; font-size:0.95rem; }
    .form-control { border-radius:8px; padding:0.6rem 0.75rem; }
    .btn-primary { border-radius:8px; padding:0.56rem 0.9rem; font-weight:600; }
    .alt-btn { border-radius:8px; }
    .small-note { font-size:0.86rem; color:#6b7280; }
    .alert { border-radius:8px; padding:0.5rem 0.75rem; }
    @media (max-width: 720px) {
      .login-card { flex-direction:column; }
      .login-right { padding:1.2rem; }
      .login-left { padding:1.2rem; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card login-card">
      <div class="login-left">
        <div class="brand-mark">
          <div class="logo">
            <img src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/school-logo.png" alt="โลโก้โรงเรียน">
          </div>
           <div>
             <h3>โรงเรียนสุคนธีรวิทย์</h3>
             <div class="muted">ระบบจัดการการลงทะเบียนและบันทึกผล</div>
           </div>
        </div>

        <div style="margin-top:12px;">
          <div class="small-note">เข้าสู่ระบบสำหรับเจ้าหน้าที่ประจำสี — สามารถลงทะเบียนนักกีฬาได้</div>
        </div>

        <div style="margin-top:auto; font-size:0.85rem; color:#94a3b8;">
          © <?= date('Y') ?> เทคโนโลยี โรงเรียนสุคนธีรวิทย์
        </div>
      </div>

      <div class="login-right">
        <h5 class="mb-3">เข้าสู่ระบบ (เจ้าหน้าที่ประจำสี)</h5>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars(BASE_URL . '/staff/login.php', ENT_QUOTES, 'UTF-8'); ?>" class="row g-3">
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

        <div class="mt-3 muted">หากมีปัญหาในการเข้าสู่ระบบ ติดต่อผู้ดูแลระบบ</div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
