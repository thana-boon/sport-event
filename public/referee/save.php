<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  // ✅ อนุญาตทั้ง 'referee' และ 'admin'
  if (empty($_SESSION['referee']) || !in_array(($_SESSION['referee']['role'] ?? ''), ['referee', 'admin'], true)) {
    throw new Exception('forbidden');
  }
  $pdo = db();

  // Active year
  $yr = $pdo->query("SELECT id, year_be FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if (!$yr) throw new Exception('no active year');
  $year_id = (int)$yr['id'];
  $year_be = (int)$yr['year_be'];

  // Parse JSON or form body
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = $_POST;

  // ---- AJAX preload via GET/POST ----
  $ajax = $_GET['ajax'] ?? ($data['ajax'] ?? '');
  if ($ajax === 'load_non_athletics' || $ajax === 'load_results_non_athletics') {
    $sport_id = (int)($_GET['sport_id'] ?? $data['sport_id'] ?? 0);
    $ranks = [];
    $g = $pdo->prepare("SELECT color, rank FROM referee_results WHERE year_id=? AND sport_id=?");
    $g->execute([$year_id, $sport_id]);
    while ($row = $g->fetch(PDO::FETCH_ASSOC)) {
      $ranks[$row['color']] = (int)$row['rank'];
    }
    echo json_encode(['ok' => true, 'ranks' => $ranks]);
    exit;
  }

  $type = $data['type'] ?? '';

  if ($type === 'non_athletics') {
    $sport_id = (int)($data['sport_id'] ?? 0);
    $ranks = $data['ranks'] ?? [];
    if ($sport_id <= 0) throw new Exception('sport_id invalid');

    // ดึงชื่อกีฬาสำหรับ log
    $sportStmt = $pdo->prepare("SELECT name FROM sports WHERE id=?");
    $sportStmt->execute([$sport_id]);
    $sportName = $sportStmt->fetchColumn() ?: 'ID:' . $sport_id;

    $pdo->exec("CREATE TABLE IF NOT EXISTS referee_results (
      id INT AUTO_INCREMENT PRIMARY KEY,
      year_id INT NOT NULL,
      sport_id INT NOT NULL,
      color ENUM('ส้ม','เขียว','ชมพู','ฟ้า') NOT NULL,
      rank TINYINT NULL,
      updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_year_sport_color (year_id, sport_id, color)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ins = $pdo->prepare("INSERT INTO referee_results (year_id, sport_id, color, rank)
                          VALUES (:y,:s,:c,:r)
                          ON DUPLICATE KEY UPDATE rank=VALUES(rank), updated_at=CURRENT_TIMESTAMP");
    
    // ⭐ ตรวจสอบอันดับซ้ำ
    $rankValues = array_filter(array_map('intval', $ranks), fn($r) => $r >= 1 && $r <= 4);
    if (count($rankValues) !== count(array_unique($rankValues))) {
        throw new Exception('ไม่สามารถบันทึกอันดับซ้ำกันได้');
    }

    // เก็บข้อมูลสำหรับ log
    $details = [];
    foreach ($ranks as $color => $rk) {
      $r = (int)$rk;
      if ($r < 1 || $r > 4) continue;
      $ins->execute([':y' => $year_id, ':s' => $sport_id, ':c' => $color, ':r' => $r]);
      $details[] = "สี{$color}: อันดับ {$r}";
    }

    // 🔥 LOG: บันทึกผลกีฬาสากล
    log_activity('UPDATE', 'referee_results', $sport_id, 
      sprintf("บันทึกผลกีฬาสากล: %s | %s | ปี %d", 
        $sportName, 
        !empty($details) ? implode(', ', $details) : 'ไม่มีอันดับ',
        $year_be));

    echo json_encode(['ok' => true]);
    exit;
  }

  if ($type === 'athletics') {
    try {
      $sport_id = (int)($data['sport_id'] ?? 0);
      $lanes = $data['lanes'] ?? [];
      $best_name = trim($data['best_name'] ?? '');
      $best_time = trim($data['best_time'] ?? '');
      $best_year = (int)($data['best_year'] ?? 0);
      
      if ($sport_id <= 0) throw new Exception('sport_id invalid');

      // ดึงชื่อกีฬาสำหรับ log
      $sportStmt = $pdo->prepare("SELECT name FROM sports WHERE id=?");
      $sportStmt->execute([$sport_id]);
      $sportName = $sportStmt->fetchColumn() ?: 'ID:' . $sport_id;

    // สร้างตาราง history สำหรับเก็บประวัติสถิติเก่า
    $pdo->exec("CREATE TABLE IF NOT EXISTS athletics_record_history (
      id INT AUTO_INCREMENT PRIMARY KEY,
      athletics_event_id INT UNSIGNED NOT NULL,
      sport_id INT DEFAULT 0,
      sport_name VARCHAR(255) DEFAULT NULL,
      year_id INT DEFAULT 0,
      result_id INT DEFAULT NULL,
      old_best_time VARCHAR(32) DEFAULT NULL,
      old_best_year_be INT DEFAULT NULL,
      old_notes VARCHAR(255) DEFAULT NULL,
      broken_by_time VARCHAR(32) DEFAULT NULL,
      broken_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      restored_at TIMESTAMP NULL DEFAULT NULL,
      INDEX idx_event (athletics_event_id),
      INDEX idx_sport (sport_id),
      INDEX idx_sport_name (sport_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // เพิ่มคอลัมน์ถ้ายังไม่มี (สำหรับตารางเก่า)
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
    } catch (PDOException $e) {};

    // First heat
    $h = $pdo->prepare("SELECT id FROM track_heats WHERE year_id=? AND sport_id=? ORDER BY heat_no ASC LIMIT 1");
    $h->execute([$year_id, $sport_id]);
    $heat_id = $h->fetchColumn();
    if (!$heat_id) throw new Exception('no heat for sport');

    // ⭐ ตรวจสอบอันดับซ้ำ (กรีฑา)
    $rankValues = [];
    foreach ($lanes as $L) {
        $r = trim((string)($L['rank'] ?? ''));
        if ($r !== '') {
            $rankValues[] = (int)$r;
        }
    }
    if (count($rankValues) !== count(array_unique($rankValues))) {
        throw new Exception('ไม่สามารถบันทึกอันดับซ้ำกันได้');
    }

    // บันทึก lanes (รวม is_record)
    $up = $pdo->prepare("INSERT INTO track_results (heat_id,lane_no,time_str,rank,is_record)
                         VALUES (:h,:lane,:t,:r,:rec)
                         ON DUPLICATE KEY UPDATE time_str=VALUES(time_str), rank=VALUES(rank), is_record=VALUES(is_record)");
    
    // เก็บข้อมูลสำหรับ log
    $laneDetails = [];
    $recordBreaker = false;
    
    foreach ($lanes as $L) {
      $lane = (int)($L['lane_no'] ?? 0);
      if ($lane <= 0) continue;
      $t = trim($L['time'] ?? '');
      $r = strlen(trim((string)($L['rank'] ?? ''))) ? (int)$L['rank'] : null;
      $isRec = !empty($L['is_record']) ? 1 : 0;
      $up->execute([':h' => $heat_id, ':lane' => $lane, ':t' => ($t !== '' ? $t : null), ':r' => $r, ':rec' => $isRec]);
      
      // เก็บข้อมูลสำหรับ log
      if ($t !== '' || $r !== null) {
        $laneInfo = "ลู่{$lane}";
        if ($t !== '') $laneInfo .= " เวลา:{$t}";
        if ($r !== null) $laneInfo .= " อันดับ:{$r}";
        if ($isRec) {
          $laneInfo .= " 🔥ทำลายสถิติ";
          $recordBreaker = true;
        }
        $laneDetails[] = $laneInfo;
      }
    }

    // อัปเดตสถิติพร้อมเก็บประวัติ (เฉพาะเมื่อทำลายสถิติจริง)
    $recordUpdated = false;
    if ($best_time !== '') {
      $q = $pdo->prepare("SELECT id, best_time, best_year_be, notes FROM athletics_events WHERE year_id=? AND sport_id=? ORDER BY id DESC LIMIT 1");
      $q->execute([$year_id, $sport_id]);
      $row = $q->fetch(PDO::FETCH_ASSOC);
      $new = (float)$best_time;
      $cur = $row && $row['best_time'] !== '' && $row['best_time'] !== null ? (float)$row['best_time'] : null;
      
      if ($row) {
        // เช็คว่าทำลายสถิติจริงหรือไม่ (เวลาน้อยกว่า = ดีกว่า)
        $isBrokenRecord = false;
        if ($cur !== null && $new < $cur) {
          $isBrokenRecord = true;
        }
        
        // ถ้ามีสถิติเดิมอยู่แล้ว และทำลายสถิติจริง → เก็บประวัติก่อนอัปเดต
        if ($isBrokenRecord && $row['best_time'] !== '' && $row['best_time'] !== null) {
          $historyStmt = $pdo->prepare("INSERT INTO athletics_record_history 
            (athletics_event_id, sport_id, sport_name, year_id, old_best_time, old_best_year_be, old_notes, broken_by_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
          $historyStmt->execute([
            $row['id'],
            $sport_id,
            $sportName,
            $year_id,
            $row['best_time'],
            $row['best_year_be'],
            $row['notes'],
            $best_time
          ]);
        }
        
        // อัปเดตสถิติ
        $u = $pdo->prepare("UPDATE athletics_events SET best_time=?, best_year_be=?, notes=? WHERE id=?");
        $u->execute([$best_time, ($best_year ?: $year_be), $best_name, $row['id']]);
        $recordUpdated = true;
      } else {
        // ยังไม่มี → INSERT ใหม่ (ไม่ต้องเก็บประวัติ เพราะยังไม่มีสถิติเก่า)
        $i = $pdo->prepare("INSERT INTO athletics_events (year_id, sport_id, event_code, best_student_id, best_time, best_year_be, notes)
                            VALUES (?,?,?,?,?,?,?)");
        $i->execute([$year_id, $sport_id, '', null, $best_time, ($best_year ?: $year_be), $best_name]);
        $recordUpdated = true;
      }
    }

    // 🔥 LOG: บันทึกผลกรีฑา
    $logDetail = sprintf("บันทึกผลกรีฑา: %s", $sportName);
    if (!empty($laneDetails)) {
      $logDetail .= " | ผล: [" . implode(', ', $laneDetails) . "]";
    }
    if ($best_name || $best_time) {
      $logDetail .= sprintf(" | สถิติ: %s เวลา:%s ปี:%s", 
        $best_name ?: '-', 
        $best_time ?: '-', 
        $best_year ?: $year_be);
    }
    if ($recordBreaker) {
      $logDetail .= " | 🔥มีการทำลายสถิติ";
    }
    $logDetail .= " | ปี {$year_be}";

    log_activity('UPDATE', 'track_results', $sport_id, $logDetail);

      echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
      error_log("Athletics save error: " . $e->getMessage());
      echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
  }

  // DELETE: ลบผลการแข่งขัน (ไม่ลบกีฬา)
  if (isset($data['type']) && $data['type'] === 'delete_result') {
      try {
          $sport_id = (int)($data['sport_id'] ?? 0);
          if ($sport_id <= 0) throw new Exception('missing sport_id');

          // ดึงชื่อกีฬาก่อนลบ
          $sportStmt = $pdo->prepare("SELECT name, category_id FROM sports WHERE id=?");
          $sportStmt->execute([$sport_id]);
          $sportData = $sportStmt->fetch(PDO::FETCH_ASSOC);
          $sportName = $sportData['name'] ?? 'ID:' . $sport_id;
          
          // ดึงชื่อหมวด
          $categoryName = null;
          if (!empty($sportData['category_id'])) {
              $catStmt = $pdo->prepare("SELECT name FROM sport_categories WHERE id=?");
              $catStmt->execute([$sportData['category_id']]);
              $categoryName = $catStmt->fetchColumn();
          }

          $pdo->beginTransaction();

          // 1) ลบผลกรีฑา (track_results) ตาม heat_id
          $h = $pdo->prepare("SELECT id FROM track_heats WHERE year_id=? AND sport_id=?");
          $h->execute([$year_id, $sport_id]);
          $heatIds = $h->fetchAll(PDO::FETCH_COLUMN, 0);
          $deletedTrack = 0;
          if ($heatIds) {
              $in = implode(',', array_fill(0, count($heatIds), '?'));
              $delTr = $pdo->prepare("DELETE FROM track_results WHERE heat_id IN ($in)");
              $delTr->execute($heatIds);
              $deletedTrack = $delTr->rowCount();
          }

          // 2) ลบผลกีฬาอื่น (referee_results)
          $delRef = $pdo->prepare("DELETE FROM referee_results WHERE year_id=? AND sport_id=?");
          $delRef->execute([$year_id, $sport_id]);
          $deletedRef = $delRef->rowCount();

          $pdo->commit();

          // 🔥 LOG: ลบผลสำเร็จ
          $logDetail = sprintf("ลบผลการแข่งขัน: %s", $sportName);
          if ($categoryName) {
              $logDetail .= " | หมวด: {$categoryName}";
          }
          if ($deletedTrack > 0) {
              $logDetail .= " | ลบ track_results: {$deletedTrack} แถว";
          }
          if ($deletedRef > 0) {
              $logDetail .= " | ลบ referee_results: {$deletedRef} แถว";
          }
          if ($deletedTrack === 0 && $deletedRef === 0) {
              $logDetail .= " | ไม่มีผลที่ต้องลบ";
          }
          $logDetail .= " | ปี {$year_be}";
          
          log_activity('DELETE', 'results', $sport_id, $logDetail);

          echo json_encode(['ok'=>true,'deleted'=>true]);
      } catch (Throwable $e) {
          if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
          
          // 🔥 LOG: ลบผลไม่สำเร็จ
          log_activity('ERROR', 'results', $sport_id ?? null, 
            sprintf("ลบผลการแข่งขันไม่สำเร็จ: %s | กีฬา: %s | ปี %d", 
              $e->getMessage(), 
              $sportName ?? 'unknown',
              $year_be));
          
          echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
      }
      exit;
  }

  // RESTORE: คืนค่าสถิติจากประวัติ
  if (isset($data['type']) && $data['type'] === 'restore_record') {
      try {
          $history_id = (int)($data['history_id'] ?? 0);
          if ($history_id <= 0) throw new Exception('missing history_id');

          $pdo->beginTransaction();

          // ดึงข้อมูลจากประวัติ
          $histStmt = $pdo->prepare("SELECT athletics_event_id, old_best_time, old_best_year_be, old_notes 
                                     FROM athletics_record_history WHERE id=?");
          $histStmt->execute([$history_id]);
          $hist = $histStmt->fetch(PDO::FETCH_ASSOC);
          
          if (!$hist) throw new Exception('ไม่พบประวัติที่ต้องการคืนค่า');

          // อัปเดตสถิติกลับไปเป็นค่าเก่า
          $updateStmt = $pdo->prepare("UPDATE athletics_events 
                                       SET best_time=?, best_year_be=?, notes=? 
                                       WHERE id=?");
          $updateStmt->execute([
              $hist['old_best_time'],
              $hist['old_best_year_be'],
              $hist['old_notes'],
              $hist['athletics_event_id']
          ]);

          // ⭐ Reset is_record=0 ใน track_results (เคลียร์ติ๊กถูกที่ค้าง)
          $resetRecordStmt = $pdo->prepare("UPDATE track_results tr
                                             JOIN track_heats th ON th.id = tr.heat_id
                                             JOIN athletics_events ae ON ae.year_id = th.year_id AND ae.sport_id = th.sport_id
                                             SET tr.is_record = 0
                                             WHERE ae.id = ?");
          $resetRecordStmt->execute([$hist['athletics_event_id']]);

          // ลบประวัติที่คืนค่าแล้ว
          $delHistStmt = $pdo->prepare("DELETE FROM athletics_record_history WHERE id=?");
          $delHistStmt->execute([$history_id]);

          $pdo->commit();

          // 🔥 LOG: คืนค่าสถิติสำเร็จ
          log_activity('UPDATE', 'athletics_events', $hist['athletics_event_id'], 
            sprintf("คืนค่าสถิติ: %s | เวลา: %s | ปี: %s", 
              $hist['old_notes'] ?: '-',
              $hist['old_best_time'] ?: '-',
              $hist['old_best_year_be'] ?: '-'));

          echo json_encode(['ok'=>true]);
      } catch (Throwable $e) {
          if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
          
          log_activity('ERROR', 'athletics_record_history', $history_id ?? null, 
            'คืนค่าสถิติไม่สำเร็จ: ' . $e->getMessage());
          
          echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
      }
      exit;
  }

  // DELETE HISTORY: ลบประวัติสถิติ
  if (isset($data['type']) && $data['type'] === 'delete_history') {
      try {
          $history_id = (int)($data['history_id'] ?? 0);
          if ($history_id <= 0) throw new Exception('missing history_id');

          // ดึงข้อมูลก่อนลบเพื่อ log
          $histStmt = $pdo->prepare("SELECT old_best_time, old_best_year_be, old_notes 
                                     FROM athletics_record_history WHERE id=?");
          $histStmt->execute([$history_id]);
          $hist = $histStmt->fetch(PDO::FETCH_ASSOC);
          
          if (!$hist) throw new Exception('ไม่พบประวัติที่ต้องการลบ');

          // ลบประวัติ
          $delStmt = $pdo->prepare("DELETE FROM athletics_record_history WHERE id=?");
          $delStmt->execute([$history_id]);

          // 🔥 LOG: ลบประวัติสถิติสำเร็จ
          log_activity('DELETE', 'athletics_record_history', $history_id, 
            sprintf("ลบประวัติสถิติ: %s | เวลา: %s | ปี: %s", 
              $hist['old_notes'] ?: '-',
              $hist['old_best_time'] ?: '-',
              $hist['old_best_year_be'] ?: '-'));

          echo json_encode(['ok'=>true]);
      } catch (Throwable $e) {
          log_activity('ERROR', 'athletics_record_history', $history_id ?? null, 
            'ลบประวัติสถิติไม่สำเร็จ: ' . $e->getMessage());
          
          echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
      }
      exit;
  }

  echo json_encode(['ok' => false, 'error' => 'unknown type']);
} catch (Throwable $e) {
  // 🔥 LOG: Error ทั่วไป
  log_activity('ERROR', 'referee_save', null, 
    'เกิดข้อผิดพลาดในการบันทึก (referee): ' . $e->getMessage());
  
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
