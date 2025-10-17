<?php
// ตั้งค่าแอพ
define('APP_NAME', 'SPORT-EVENT');

// *** แก้ path ให้ตรงโปรเจกต์ของคุณบน XAMPP ***
define('BASE_URL', '/sport-event/public'); // << สำคัญ: มีสแลชนำหน้า และไม่มีสแลชท้าย

define('APP_ENV', 'local'); // local | production

// ตั้งค่า DB (แก้ให้ตรงกับเครื่องของคุณ)
define('DB_DRIVER', 'mysql');  // mysql | sqlite | pgsql
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'school_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// START: session inactivity timeout handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SESSION_INACTIVITY_TIMEOUT')) {
    define('SESSION_INACTIVITY_TIMEOUT', 15 * 60); // 15 นาที (วินาที)
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
