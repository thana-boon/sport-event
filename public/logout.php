<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🔥 LOG: ออกจากระบบ (admin)
if (!empty($_SESSION['admin'])) {
  $adminName = $_SESSION['admin']['display_name'] ?? $_SESSION['admin']['username'] ?? 'admin';
  $adminId = $_SESSION['admin']['id'] ?? null;
  $adminRole = $_SESSION['admin']['role'] ?? 'unknown';
  
  log_activity('LOGOUT', 'users', $adminId, 
    'ออกจากระบบ (admin) | Display: ' . $adminName . ' | Role: ' . $adminRole);
}

session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
