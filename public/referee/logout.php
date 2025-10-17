<?php
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['referee']);
header('Location: ' . BASE_URL . '/referee/login.php');
exit;
