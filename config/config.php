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
