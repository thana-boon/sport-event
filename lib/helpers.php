<?php
// lib/helpers.php
// UTF-8 (no BOM) และห้ามมีช่องว่างก่อน <?php

/* Escape HTML */
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * คืนค่า id ของปีการศึกษาที่ Active (กำลังใช้งาน)
 * ใช้ year_be เป็นเกณฑ์หลัก เผื่อมีมากกว่า 1 แถวที่ is_active=1 จะเลือกปีที่มากที่สุด
 */
if (!function_exists('active_year_id')) {
    function active_year_id(PDO $pdo) {
        $stmt = $pdo->query("SELECT id FROM academic_years WHERE is_active=1 ORDER BY year_be DESC, id DESC LIMIT 1");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }
}

/**
 * คืนค่า id ของ "ปีการศึกษาก่อนหน้า" จากปี active ปัจจุบัน
 * - เปรียบเทียบ year_be เป็นหลัก (ค่าปี พ.ศ.)
 * - ถ้าไม่มี year_be (หรือข้อมูลไม่สม่ำเสมอ) จะ fallback เทียบ created_at
 */
if (!function_exists('previous_year_id')) {
    function previous_year_id(PDO $pdo) {
        // หา year_be ของปีที่ active ปัจจุบัน
        $stmt = $pdo->query("SELECT id, year_be, created_at FROM academic_years WHERE is_active=1 ORDER BY year_be DESC, id DESC LIMIT 1");
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) return null;

        // พยายามหา by year_be ที่น้อยกว่า
        $stmt = $pdo->prepare("
            SELECT id FROM academic_years
            WHERE year_be < :cur_year
            ORDER BY year_be DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([':cur_year' => (int)$current['year_be']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prev) return (int)$prev['id'];

        // Fallback: ใช้ created_at ที่เก่ากว่า
        $stmt = $pdo->prepare("
            SELECT id FROM academic_years
            WHERE created_at < :cur_created
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([':cur_created' => $current['created_at']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);
        return $prev ? (int)$prev['id'] : null;
    }
}

function generate_pairs_by_color($color, $players) {
    shuffle($players);
    $pairs = [];
    for ($i=0; $i<count($players); $i+=2) {
        $a = $players[$i];
        $b = $players[$i+1] ?? null;
        $pairs[] = [$a, $b];
    }
    return $pairs;
}

// ดึงชื่อปีการศึกษาปัจจุบัน
function active_year_name(PDO $pdo) {
    $stmt = $pdo->query("SELECT title FROM academic_years WHERE is_active = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['title'] : '';
}

/**
 * บันทึก Activity Log เข้า Database
 */
function log_activity($action, $table = '', $recordId = null, $details = '') {
  try {
    $pdo = db();
    
    $userId = null;
    $username = 'guest';
    $userType = 'guest';
    
    if (!empty($_SESSION['admin'])) {
      $userId = $_SESSION['admin']['id'] ?? null;
      $username = $_SESSION['admin']['username'] ?? 'admin';
      $userType = 'admin';
    } elseif (!empty($_SESSION['staff'])) {
      $userId = $_SESSION['staff']['id'] ?? null;
      $username = $_SESSION['staff']['username'] ?? 'staff';
      $userType = 'staff';
      if (!empty($_SESSION['staff']['color'])) {
        $username .= ' (สี' . $_SESSION['staff']['color'] . ')';
      }
    } elseif (!empty($_SESSION['referee'])) {
      $userId = $_SESSION['referee']['id'] ?? null;
      $username = $_SESSION['referee']['username'] ?? 'referee';
      $userType = 'referee';
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $pdo->prepare("
      INSERT INTO activity_logs 
      (user_id, username, user_type, action, table_name, record_id, details, ip_address, user_agent)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
      $userId,
      $username,
      $userType,
      $action,
      $table,
      $recordId,
      $details,
      $ip,
      $userAgent
    ]);
    
    return true;
  } catch (Exception $e) {
    error_log("Log activity failed: " . $e->getMessage());
    return false;
  }
}
/**
 * ตรวจสอบว่าระบบเปิดรับลงทะเบียนหรือไม่
 * - เช็คสถานะ registration_is_open
 * - เช็คช่วงเวลาเริ่ม (registration_start)
 * - เช็คเวลาสิ้นสุด (registration_end)
 */
if (!function_exists('registration_open')) {
    function registration_open(PDO $pdo): bool {
        $stmt = $pdo->query("
            SELECT registration_is_open, registration_start, registration_end 
            FROM academic_years 
            WHERE is_active=1 
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['registration_is_open'])) {
            return false;
        }
        
        // ตรวจสอบช่วงเวลา
        $now = date('Y-m-d H:i:s');
        
        // ถ้ามีเวลาเริ่ม และยังไม่ถึงเวลาเริ่ม
        if (!empty($row['registration_start']) && $now < $row['registration_start']) {
            return false;
        }
        
        // ถ้ามีเวลาสิ้นสุด และเกินเวลาสิ้นสุดไปแล้ว
        if (!empty($row['registration_end']) && $now > $row['registration_end']) {
            return false;
        }
        
        return true;
    }
}

/**
 * DB schema helpers (best-effort)
 */
if (!function_exists('db_table_has_column')) {
  function db_table_has_column(PDO $pdo, string $table, string $column): bool {
    try {
      $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
      if ($driver === 'sqlite') {
        $st = $pdo->query("PRAGMA table_info($table)");
        $cols = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($cols as $c) {
          if (isset($c['name']) && $c['name'] === $column) return true;
        }
        return false;
      }

      // MySQL/MariaDB
      $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
      $st->execute([$table, $column]);
      return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
      return false;
    }
  }
}

if (!function_exists('ensure_match_pairs_schedule_no')) {
  function ensure_match_pairs_schedule_no(PDO $pdo): void {
    if (db_table_has_column($pdo, 'match_pairs', 'schedule_no')) return;

    try {
      $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
      if ($driver === 'sqlite') {
        // SQLite: ALTER TABLE ADD COLUMN is supported
        $pdo->exec("ALTER TABLE match_pairs ADD COLUMN schedule_no INTEGER NULL");
      } else {
        // MySQL/MariaDB
        $pdo->exec("ALTER TABLE match_pairs ADD COLUMN schedule_no INT NULL AFTER match_no");
      }
    } catch (Throwable $e) {
      // Best-effort: if no privilege, we just won't have schedule_no.
    }
  }
}