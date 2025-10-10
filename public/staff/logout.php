<?php
// public/staff/logout.php
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
unset($_SESSION['staff']);
header('Location: ' . BASE_URL . '/staff/login.php');
exit;
