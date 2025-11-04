<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🔥 LOG: ออกจากระบบ
if (!empty($_SESSION['referee'])) {
  $refereeName = $_SESSION['referee']['name'] ?? $_SESSION['referee']['username'] ?? 'referee';
  $refereeId = $_SESSION['referee']['id'] ?? null;
  
  log_activity('LOGOUT', 'users', $refereeId, 
    'ออกจากระบบ (referee) | Display: ' . $refereeName);
}

session_destroy();
header('Location: ' . BASE_URL . '/referee/login.php');
exit;
