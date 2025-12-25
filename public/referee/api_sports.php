<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  
  // สร้างตาราง history สำหรับเก็บประวัติสถิติเก่า
  $pdo->exec("CREATE TABLE IF NOT EXISTS athletics_record_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    athletics_event_id INT UNSIGNED NOT NULL,
    old_best_time VARCHAR(32) DEFAULT NULL,
    old_best_year_be INT DEFAULT NULL,
    old_notes VARCHAR(255) DEFAULT NULL,
    broken_by_time VARCHAR(32) DEFAULT NULL,
    broken_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event (athletics_event_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  
  // ปีปัจจุบัน
  $yr = $pdo->query("SELECT id, year_be FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if(!$yr) throw new Exception('no active year');
  $year_id = (int)$yr['id']; $year_be = (int)$yr['year_be'];

  // รายการกีฬาในปีนี้
  $st = $pdo->prepare("SELECT s.id, s.name, s.category_id, c.name AS category_name, s.grade_levels, 
                              ae.event_code,
                              CASE WHEN ae.event_code IS NOT NULL THEN CAST(ae.event_code AS UNSIGNED) ELSE 9999 END AS sort_order
                       FROM sports s
                       JOIN sport_categories c ON c.id = s.category_id
                       LEFT JOIN athletics_events ae ON ae.sport_id = s.id
                       WHERE s.is_active = 1 AND s.year_id = :y
                         AND (c.name NOT LIKE '%กรีฑ%' OR ae.event_code IS NOT NULL)
                       ORDER BY c.name, sort_order, s.name");
  $st->execute([':y'=>$year_id]);
  $sports = $st->fetchAll(PDO::FETCH_ASSOC);

  // คำนวณสถานะบันทึกแล้ว และตรวจสอบการทำลายสถิติ
  $chkTrack = $pdo->prepare("SELECT COUNT(*) FROM track_results r JOIN track_heats h ON h.id=r.heat_id WHERE h.year_id=? AND h.sport_id=?");
  $chkOther = $pdo->prepare("SELECT COUNT(*) FROM referee_results WHERE year_id=? AND sport_id=?");
  $chkRecordBroken = $pdo->prepare("SELECT COUNT(*) FROM athletics_events ae
                                    JOIN athletics_record_history arh ON arh.athletics_event_id = ae.id
                                    WHERE ae.year_id=? AND ae.sport_id=? 
                                    AND arh.broken_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

  foreach ($sports as &$sp) {
    $isAth = mb_strpos($sp['category_name'], 'กรีฑ') !== false;
    if ($isAth) {
      $chkTrack->execute([$year_id, $sp['id']]);
      $sp['saved'] = $chkTrack->fetchColumn() > 0 ? 1 : 0;
      
      // ตรวจสอบว่ามีการทำลายสถิติภายใน 7 วันหรือไม่
      $chkRecordBroken->execute([$year_id, $sp['id']]);
      $sp['record_broken'] = $chkRecordBroken->fetchColumn() > 0 ? 1 : 0;
    } else {
      $chkOther->execute([$year_id, $sp['id']]);
      $sp['saved'] = $chkOther->fetchColumn() > 0 ? 1 : 0;
      $sp['record_broken'] = 0; // กีฬาอื่นไม่มีการทำลายสถิติ
    }
  } unset($sp);

  echo json_encode(['ok'=>true,'sports'=>$sports], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
