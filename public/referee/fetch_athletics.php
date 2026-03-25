<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  // ✅ อนุญาตทั้ง 'referee' และ 'admin'
  if (empty($_SESSION['referee']) || !in_array(($_SESSION['referee']['role'] ?? ''), ['referee', 'admin'], true)) {
    throw new Exception('forbidden');
  }
  
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

  // Active year
  $yr = $pdo->query("SELECT id, year_be FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if (!$yr) throw new Exception('no active year');
  $year_id = (int)$yr['id'];
  $year_be = (int)$yr['year_be'];

  $sport_id = (int)($_GET['sport_id'] ?? 0);
  if ($sport_id <= 0) throw new Exception('missing sport_id');

  // Determine relay/team event from sports table:
  $sn = $pdo->prepare("SELECT name, participant_type, team_size FROM sports WHERE id=?");
  $sn->execute([$sport_id]);
  $srow = $sn->fetch(PDO::FETCH_ASSOC) ?: [];
  $sport_name = (string)($srow['name'] ?? '');
  $participant_type = (string)($srow['participant_type'] ?? '');
  $team_size = (int)($srow['team_size'] ?? 0);
  // Consider it a relay / team race ONLY when name contains 'ผลัด' or participant_type contains 'ทีม'
  // ไม่ใช้ team_size เป็นเงื่อนไข เพราะวิ่งเดี่ยวก็อาจส่งได้หลายคนต่อสี
  $isRelay = (mb_stripos($sport_name, 'ผลัด') !== false)
            || (mb_stripos($participant_type, 'ทีม') !== false);

  // First heat
  $h = $pdo->prepare("SELECT id, heat_no, lanes_used FROM track_heats WHERE year_id=? AND sport_id=? ORDER BY heat_no ASC LIMIT 1");
  $h->execute([$year_id, $sport_id]);
  $heat = $h->fetch(PDO::FETCH_ASSOC);
  if (!$heat) { echo json_encode(['ok'=>true,'lanes'=>[],'best'=>null,'is_relay'=>$isRelay], JSON_UNESCAPED_UNICODE); exit; }

  // Init lanes
  $lanes = [];
  for ($i=1; $i <= (int)$heat['lanes_used']; $i++) {
    $lanes[$i] = ['lane_no'=>$i, 'color'=>null, 'display_name'=>'ยังไม่เลือกผู้เล่น', 'assigned_name'=>'', 'time_sec'=>null, 'rank'=>null, 'is_record'=>0];
  }

  // Get assignments - ดึงทั้งหมดแล้วประมวลผลเองเพื่อจัดการชื่อให้ถูกต้อง
  $q = $pdo->prepare("SELECT la.lane_no, la.color, la.registration_id, r.student_id, s.first_name, s.last_name
                      FROM track_lane_assignments la
                      LEFT JOIN registrations r ON r.id = la.registration_id
                      LEFT JOIN students s ON s.id = r.student_id
                      WHERE la.heat_id=? 
                      ORDER BY la.lane_no, la.id ASC");
  $q->execute([$heat['id']]);
  $assign = $q->fetchAll(PDO::FETCH_ASSOC);

  // Track used students to avoid duplicates across lanes
  $usedStudentIds = [];
  
  // จัดกลุ่ม assignment ตาม lane_no
  $laneAssignments = [];
  foreach ($assign as $r) {
    $laneNo = (int)$r['lane_no'];
    if (!isset($laneAssignments[$laneNo])) {
      $laneAssignments[$laneNo] = [];
    }
    $laneAssignments[$laneNo][] = $r;
  }

  // ประมวลผลแต่ละลู่ - เก็บเฉพาะคนแรกสำหรับ assigned_name
  foreach ($laneAssignments as $laneNo => $assignments) {
    if (!isset($lanes[$laneNo])) continue;
    
    // เอาคนแรก
    $firstAssignment = $assignments[0];
    $lanes[$laneNo]['color'] = $firstAssignment['color'] ?: null;
    
    if (!empty($firstAssignment['first_name'])) {
      $name = trim($firstAssignment['first_name'].' '.$firstAssignment['last_name']);
      // เก็บชื่อคนแรกเท่านั้น (สำหรับการทำลายสถิติ)
      $lanes[$laneNo]['assigned_name'] = $name;
      $lanes[$laneNo]['display_name'] = $name;
      if (!empty($firstAssignment['student_id'])) {
        $usedStudentIds[(int)$firstAssignment['student_id']] = true;
      }
    }
  }

  if ($isRelay) {
    // For relay lanes: show all members per color (ไม่จำกัด 4 คน)
    $limit = $team_size > 0 ? $team_size : 100; // ใช้ team_size จาก sports หรือ 100 (unlimited)
    $limitSafe = (int)$limit; // แปลงเป็น integer เพื่อป้องกัน SQL injection
    $relayMembers = $pdo->prepare("SELECT CONCAT(s.first_name,' ',s.last_name) AS n
                                   FROM registrations rg
                                   JOIN students s ON s.id = rg.student_id
                                   WHERE rg.year_id=? AND rg.sport_id=? AND rg.color=?
                                   ORDER BY rg.id ASC LIMIT {$limitSafe}");
    foreach ($lanes as &$L) {
      if ($L['color']) {
        $relayMembers->execute([$year_id, $sport_id, $L['color']]);
        $names = array_column($relayMembers->fetchAll(PDO::FETCH_ASSOC), 'n');
        if ($names) {
          // ทำความสะอาด HTML tags และใช้ line break แทน <br>
          $cleanNames = array_map(fn($n) => str_replace(['<br>', '<br/>', '<br />'], ' ', strip_tags($n)), $names);
          $L['display_name'] = implode(", ", $cleanNames); // ใช้ ", " (comma + space) แยกชื่อ
          // สำหรับวิ่งผลัด ใช้ชื่อสี (เพราะเป็นทีม)
          $L['assigned_name'] = $L['color'] ?: '';
        }
      }
    } unset($L);
  } else {
    // Single races: if lane has no registration name but has color, fallback to a registrant of same color
    // but skip students already used in other lanes in this heat (to avoid duplicates).
    $cand = $pdo->prepare("SELECT rg.student_id, CONCAT(s.first_name,' ',s.last_name) AS n
                           FROM registrations rg
                           JOIN students s ON s.id = rg.student_id
                           WHERE rg.year_id=? AND rg.sport_id=? AND rg.color=?
                           ORDER BY rg.id ASC");
    foreach ($lanes as &$L) {
      if ($L['display_name'] === 'ยังไม่เลือกผู้เล่น' && $L['color']) {
        $cand->execute([$year_id, $sport_id, $L['color']]);
        while ($row = $cand->fetch(PDO::FETCH_ASSOC)) {
          $sid = (int)$row['student_id'];
          if (!isset($usedStudentIds[$sid])) {
            $L['display_name'] = $row['n'];
            $usedStudentIds[$sid] = true;
            break;
          }
        }
      }
    } unset($L);
  }

  // Load prior times/ranks
  try {
    $tr = $pdo->prepare("SELECT lane_no, time_str, rank, is_record FROM track_results WHERE heat_id=?");
    $tr->execute([$heat['id']]);
    while ($rr = $tr->fetch(PDO::FETCH_ASSOC)) {
      $i = (int)$rr['lane_no'];
      if (!isset($lanes[$i])) continue;
      $lanes[$i]['time_sec'] = $rr['time_str'];
      $lanes[$i]['rank'] = isset($rr['rank']) ? (int)$rr['rank'] : null;
      $lanes[$i]['is_record'] = (int)($rr['is_record'] ?? 0);
    }
  } catch (Throwable $e) {}

  // ⭐ เพิ่มคอลัมน์ sport_id, sport_name, year_id, restored_at, result_id ถ้ายังไม่มี (ทำก่อนทุกครั้ง)
  try {
    $pdo->exec("ALTER TABLE athletics_record_history ADD COLUMN sport_id INT DEFAULT 0 AFTER athletics_event_id");
  } catch (PDOException $e) {}
  
  try {
    $pdo->exec("ALTER TABLE athletics_record_history ADD COLUMN sport_name VARCHAR(255) DEFAULT NULL AFTER sport_id");
  } catch (PDOException $e) {}
  
  try {
    $pdo->exec("ALTER TABLE athletics_record_history ADD COLUMN year_id INT DEFAULT 0 AFTER sport_name");
  } catch (PDOException $e) {}
  
  try {
    $pdo->exec("ALTER TABLE athletics_record_history ADD COLUMN restored_at TIMESTAMP NULL DEFAULT NULL AFTER broken_at");
  } catch (PDOException $e) {}
  
  try {
    $pdo->exec("ALTER TABLE athletics_record_history ADD COLUMN result_id INT DEFAULT NULL AFTER athletics_event_id");
  } catch (PDOException $e) {}
  
  // ⭐ Migration: อัพเดท sport_id, sport_name และ year_id จาก athletics_events และ sports (สำหรับข้อมูลเก่า)
  try {
    $migrated = $pdo->exec("UPDATE athletics_record_history arh
                JOIN athletics_events ae ON ae.id = arh.athletics_event_id
                JOIN sports s ON s.id = ae.sport_id
                SET arh.sport_id = ae.sport_id, 
                    arh.sport_name = s.name,
                    arh.year_id = ae.year_id
                WHERE arh.sport_name IS NULL OR arh.sport_id = 0 OR arh.year_id = 0");
    if ($migrated > 0) {
      error_log("Migrated {$migrated} athletics_record_history rows with sport_name");
    }
  } catch (PDOException $e) {
    error_log("Migration error: " . $e->getMessage());
  }
  
  // ดึงชื่อกีฬาปัจจุบัน
  $sportStmt = $pdo->prepare("SELECT name FROM sports WHERE id=?");
  $sportStmt->execute([$sport_id]);
  $currentSportName = $sportStmt->fetchColumn();

  // Best record
  $best = null;
  $recordBroken = false;
  $history = [];
  $eventId = null;
  
  $b = $pdo->prepare("SELECT id, best_time, best_year_be, notes FROM athletics_events WHERE year_id=? AND sport_id=? ORDER BY id DESC LIMIT 1");
  $b->execute([$year_id, $sport_id]);
  if ($r = $b->fetch(PDO::FETCH_ASSOC)) {
    $eventId = $r['id'];
    // ถ้า best_year_be เป็น null หรือ 0 ให้ใช้ปีการศึกษาปัจจุบันแทน
    $bestYear = $r['best_year_be'] && (int)$r['best_year_be'] > 0 ? $r['best_year_be'] : $year_be;
    $best = ['holder'=>$r['notes']?:'', 'time_sec'=>$r['best_time'], 'year'=>$bestYear];
  }
  
  // ⭐ ตรวจสอบว่ามีการทำลายสถิติภายใน 7 วันหรือไม่ (ใช้ sport_name)
  $checkHistory = $pdo->prepare("SELECT COUNT(*) FROM athletics_record_history 
                                 WHERE sport_name=? 
                                 AND restored_at IS NULL
                                 AND broken_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
  $checkHistory->execute([$currentSportName]);
  if ($checkHistory->fetchColumn() > 0) {
    $recordBroken = true;
  }
  
  // ⭐ ดึงประวัติสถิติเก่าจาก sport_name (ข้ามปีการศึกษา) พร้อมชื่อปีการศึกษา
  $h = $pdo->prepare("SELECT arh.id, arh.athletics_event_id, arh.sport_id, arh.sport_name, 
                             arh.year_id, arh.result_id,
                             arh.old_best_time, arh.old_best_year_be, arh.old_notes, 
                             arh.broken_by_time, arh.broken_at, arh.restored_at,
                             ay.year_be as year_name
                      FROM athletics_record_history arh
                      LEFT JOIN academic_years ay ON ay.id = arh.year_id
                      WHERE arh.sport_name=?
                      ORDER BY arh.broken_at DESC LIMIT 20");
  $h->execute([$currentSportName]);
  $history = $h->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'lanes'=>array_values($lanes),'best'=>$best,'is_relay'=>$isRelay,'record_broken'=>$recordBroken,'history'=>$history,'event_id'=>$eventId,'year_be'=>$year_be], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
