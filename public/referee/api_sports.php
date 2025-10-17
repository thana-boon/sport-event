<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  // ปีปัจจุบัน
  $yr = $pdo->query("SELECT id, year_be FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if(!$yr) throw new Exception('no active year');
  $year_id = (int)$yr['id']; $year_be = (int)$yr['year_be'];

  // รายการกีฬาในปีนี้
  $st = $pdo->prepare("SELECT s.id, s.name, s.category_id, c.name AS category_name, s.grade_levels
                       FROM sports s
                       JOIN sport_categories c ON c.id = s.category_id
                       WHERE s.is_active = 1 AND s.year_id = :y
                       ORDER BY c.name, s.name");
  $st->execute([':y'=>$year_id]);
  $sports = $st->fetchAll(PDO::FETCH_ASSOC);

  // คำนวณสถานะบันทึกแล้ว
  $chkTrack = $pdo->prepare("SELECT COUNT(*) FROM track_results r JOIN track_heats h ON h.id=r.heat_id WHERE h.year_id=? AND h.sport_id=?");
  $chkOther = $pdo->prepare("SELECT COUNT(*) FROM referee_results WHERE year_id=? AND sport_id=?");

  foreach ($sports as &$sp) {
    $isAth = mb_strpos($sp['category_name'], 'กรีฑ') !== false;
    if ($isAth) {
      $chkTrack->execute([$year_id, $sp['id']]);
      $sp['saved'] = $chkTrack->fetchColumn() > 0 ? 1 : 0;
    } else {
      $chkOther->execute([$year_id, $sp['id']]);
      $sp['saved'] = $chkOther->fetchColumn() > 0 ? 1 : 0;
    }
  } unset($sp);

  echo json_encode(['ok'=>true,'sports'=>$sports], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
