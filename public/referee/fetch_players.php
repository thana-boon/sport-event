<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ตรวจสอบสิทธิ์
if (empty($_SESSION['referee']) || !in_array(($_SESSION['referee']['role'] ?? ''), ['referee', 'admin'], true)) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $yearId = active_year_id($pdo);
  
  // ดึงข้อมูลประเภทกีฬา
  $categoriesStmt = $pdo->prepare("
    SELECT DISTINCT sc.id, sc.name
    FROM sport_categories sc
    JOIN sports s ON s.category_id = sc.id
    WHERE s.year_id = ?
    ORDER BY sc.id
  ");
  $categoriesStmt->execute([$yearId]);
  $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
  
  // ดึงข้อมูลรายการกีฬา
  $sportsStmt = $pdo->prepare("
    SELECT id, name, category_id
    FROM sports
    WHERE year_id = ?
    ORDER BY name
  ");
  $sportsStmt->execute([$yearId]);
  $sports = $sportsStmt->fetchAll(PDO::FETCH_ASSOC);
  
  // ดึงข้อมูลนักกีฬาทั้งหมดที่ลงทะเบียน
  $playersStmt = $pdo->prepare("
    SELECT 
      r.id as registration_id,
      r.student_id,
      r.sport_id,
      s.student_code,
      CONCAT(s.first_name, ' ', s.last_name) as student_name,
      s.class_level,
      s.class_room,
      s.color,
      sp.name as sport_name,
      sp.category_id,
      sc.name as category_name,
      ps.id as substitution_id,
      ps.old_student_id,
      CONCAT(old_s.first_name, ' ', old_s.last_name) as old_student_name
    FROM registrations r
    JOIN students s ON r.student_id = s.id
    JOIN sports sp ON r.sport_id = sp.id
    LEFT JOIN sport_categories sc ON sp.category_id = sc.id
    LEFT JOIN player_substitutions ps ON ps.registration_id = r.id
    LEFT JOIN students old_s ON ps.old_student_id = old_s.id
    WHERE r.year_id = ?
    ORDER BY s.color, sp.name, s.class_level, s.class_room, CAST(s.number_in_room AS UNSIGNED)
  ");
  $playersStmt->execute([$yearId]);
  $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
  
  // เพิ่มข้อมูลว่ามีการเปลี่ยนตัวหรือไม่
  foreach ($players as &$player) {
    $player['is_substituted'] = !empty($player['substitution_id']);
  }
  
  echo json_encode([
    'success' => true,
    'players' => $players,
    'categories' => $categories,
    'sports' => $sports
  ], JSON_UNESCAPED_UNICODE);
  
} catch (PDOException $e) {
  error_log("Database error in fetch_players.php: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล'
  ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  error_log("Error in fetch_players.php: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
