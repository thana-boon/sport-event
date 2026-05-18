<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dotenv->required(['APP_NAME', 'APP_ENV', 'BASE_URL', 'DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS']);

// ตั้งค่าแอพ
define('APP_NAME', $_ENV['APP_NAME']);
define('BASE_URL',  $_ENV['BASE_URL']);
define('APP_ENV',   $_ENV['APP_ENV']);

// ตั้งค่า DB
define('DB_DRIVER', $_ENV['DB_DRIVER']);
define('DB_HOST',   $_ENV['DB_HOST']);
define('DB_PORT',   $_ENV['DB_PORT']);
define('DB_NAME',   $_ENV['DB_NAME']);
define('DB_USER',   $_ENV['DB_USER']);
define('DB_PASS',   $_ENV['DB_PASS']);

// START: session inactivity timeout handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SESSION_INACTIVITY_TIMEOUT')) {
    define('SESSION_INACTIVITY_TIMEOUT', 60 * 60); // 60 นาที (วินาที)
}

if (!empty($_SESSION['admin'])) {
    $now = time();
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
    } elseif ($now - (int)$_SESSION['last_activity'] > SESSION_INACTIVITY_TIMEOUT) {
        // หมดเวลาแล้ว -> ทำลาย session และบังคับไปหน้า login
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/') . '/login.php?timeout=1');
        exit;
    } else {
        // รีเฟรชเวลาการใช้งานครั้งล่าสุด
        $_SESSION['last_activity'] = $now;
    }
}
// END: session inactivity timeout
