<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            if (DB_DRIVER === 'sqlite') {
                $pdo = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
            } else {
                $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_DRIVER, DB_HOST, DB_PORT, DB_NAME);
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo "<pre>ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
            exit;
        }
    }
    return $pdo;
}
