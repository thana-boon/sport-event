<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = db();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = trim($_POST['password'] ?? '');
  if ($u === '' || $p === '') {
    $err = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    
    // 🔥 LOG: กรอกข้อมูลไม่ครบ
    log_activity('LOGIN_FAILED', 'users', null, 
      'พยายามเข้าสู่ระบบโดยไม่กรอกข้อมูล (referee)');
  } else {
    // ตาราง users: id, username, password_hash, display_name, role, staff_color, is_active, created_at
    $st = $pdo->prepare("SELECT id, username, password_hash, display_name, role, is_active 
                         FROM users 
                         WHERE username = :u LIMIT 1");
    $st->execute([':u'=>$u]);
    $user = $st->fetch(PDO::FETCH_ASSOC);

    $ok = false;
    if ($user && (int)$user['is_active'] === 1) {
      if (password_verify($p, $user['password_hash'])) {
        if (($user['role'] ?? '') === 'referee') {
          $ok = true;
        } else {
          // 🔥 LOG: พยายาม login ด้วย role ที่ไม่ใช่ referee
          log_activity('LOGIN_DENIED', 'users', $user['id'], 
            'พยายามเข้าสู่ระบบด้วยบัญชีที่ไม่ใช่ referee | Username: ' . $u . ' | Role: ' . ($user['role'] ?? 'unknown'));
          
          $err = 'บัญชีนี้ไม่ได้รับสิทธิ์ผู้ตัดสิน (role ต้องเป็น referee)';
        }
      } else {
        // 🔥 LOG: รหัสผ่านผิด
        log_activity('LOGIN_FAILED', 'users', $user['id'], 
          'พยายามเข้าสู่ระบบด้วยรหัสผ่านผิด (referee) | Username: ' . $u);
        
        $err = 'รหัสผ่านไม่ถูกต้อง';
      }
    } else {
      if ($user && (int)$user['is_active'] === 0) {
        // 🔥 LOG: บัญชีถูกปิดใช้งาน
        log_activity('LOGIN_DISABLED', 'users', $user['id'], 
          'พยายามเข้าสู่ระบบด้วยบัญชีที่ถูกปิดใช้งาน (referee) | Username: ' . $u);
      } else {
        // 🔥 LOG: ไม่พบ username
        log_activity('LOGIN_FAILED', 'users', null, 
          'พยายามเข้าสู่ระบบด้วย username ที่ไม่มีในระบบ (referee) | Username: ' . $u);
      }
      
      $err = 'ไม่พบบัญชีผู้ใช้ หรือถูกปิดการใช้งาน';
    }

    if ($ok) {
      $_SESSION['referee'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'name' => $user['display_name'] ?: $user['username'],
        'role' => 'referee'
      ];
      
      // 🔥 LOG: Login สำเร็จ
      log_activity('LOGIN', 'users', $user['id'], 
        'เข้าสู่ระบบสำเร็จ (referee) | Display: ' . ($user['display_name'] ?: $user['username']));
      
      header('Location: ' . BASE_URL . '/referee/index.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบผู้ตัดสิน</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Kanit font -->
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
    <link rel="shortcut icon" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
  <style>
    :root {
      --bg-1: #f6f9ff;
      --bg-2: #eef6f9;
      --card-radius: 14px;
      --accent: #0d6efd;
    }
    html,body { height:100%; }
    body {
      font-family: 'Kanit', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(135deg, var(--bg-1), var(--bg-2));
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
    .login-wrap {
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:3rem 1rem;
    }
    .card.login-card {
      width:100%;
      max-width:520px;
      border-radius:var(--card-radius);
      box-shadow: 0 10px 30px rgba(18,38,63,0.08);
      overflow:hidden;
      border:0;
    }
    .brand {
      background: linear-gradient(90deg, rgba(13,110,253,0.08), rgba(13,110,253,0.03));
      padding: 1.25rem 1.5rem;
      display:flex;
      align-items:center;
      gap:12px;
      border-bottom: 1px solid rgba(0,0,0,0.03);
    }
    .brand-logo {
      width:48px; height:48px; border-radius:10px;
      background: linear-gradient(135deg, var(--accent), #6610f2);
      display:flex; align-items:center; justify-content:center; color:#fff;
      font-weight:700; box-shadow: 0 6px 18px rgba(13,110,253,0.12);
    }
    .brand h1 { margin:0; font-size:1.05rem; font-weight:600; color:#0b1a2b; }
    .card-body { padding:1.6rem; }
    .form-label { font-size:0.92rem; font-weight:500; color:#223;}
    .form-control { border-radius:8px; padding:0.6rem 0.75rem; }
    .btn-primary { border-radius:8px; padding:0.55rem 0.9rem; font-weight:600; }
    .small-muted { color:#6b7280; font-size:0.85rem; }
    .note { font-size:0.86rem; color:#6b7280; margin-top:0.8rem; text-align:center; }
    .footer-links { margin-top:0.8rem; text-align:center; font-size:0.85rem; }
    a.btn-light { border-radius:8px; }
    .alert { border-radius:8px; padding:0.55rem 0.75rem; }
  </style>
</head>
<body class="bg-light">
  <div class="login-wrap">
    <div class="card login-card shadow-sm">
      <div class="brand">
        <div class="brand-logo">SE</div>
        <div>
          <h1>ผู้ตัดสิน - โรงเรียนสุคนธีรวิทย์</h1>
          <div class="small-muted">ระบบบันทึกผลการแข่งขัน</div>
        </div>
      </div>
      <div class="card-body">
        <h2 class="h6 mb-3">เข้าสู่ระบบ (ผู้ตัดสิน)</h2>
        <?php if ($err): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="on" novalidate>
          <div class="mb-3">
            <label class="form-label">ชื่อผู้ใช้</label>
            <input name="username" class="form-control" autocomplete="username" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">รหัสผ่าน</label>
            <input type="password" name="password" class="form-control" autocomplete="current-password" required>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
              <button class="btn btn-primary">เข้าสู่ระบบ</button>
            </div>
          </div>
        </form>

        <div class="note">
          Powered by เทคโนโลยี โรงเรียนสุคนธีรวิทย์
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
