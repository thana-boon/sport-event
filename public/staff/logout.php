<?php
// public/staff/logout.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🔥 LOG: ออกจากระบบ (staff)
if (!empty($_SESSION['staff'])) {
  $staffName = $_SESSION['staff']['display_name'] ?? $_SESSION['staff']['username'] ?? 'staff';
  $staffId = $_SESSION['staff']['id'] ?? null;
  $staffColor = $_SESSION['staff']['color'] ?? null;
  
  $logDetail = 'ออกจากระบบ (staff) | Display: ' . $staffName;
  if ($staffColor) {
    $logDetail .= ' | สี: ' . $staffColor;
  }
  
  log_activity('LOGOUT', 'users', $staffId, $logDetail);
}

unset($_SESSION['staff']);
header('Location: ' . BASE_URL . '/staff/login.php');
exit;
