<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  if (empty($_SESSION['referee']) || (($_SESSION['referee']['role'] ?? '') !== 'referee')) {
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
    foreach ($ranks as $color => $rk) {
      $r = (int)$rk;
      if ($r < 1 || $r > 4) continue;
      $ins->execute([':y' => $year_id, ':s' => $sport_id, ':c' => $color, ':r' => $r]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($type === 'athletics') {
    $sport_id = (int)($data['sport_id'] ?? 0);
    $lanes = $data['lanes'] ?? [];
    if ($sport_id <= 0) throw new Exception('sport_id invalid');

    // First heat
    $h = $pdo->prepare("SELECT id FROM track_heats WHERE year_id=? AND sport_id=? ORDER BY heat_no ASC LIMIT 1");
    $h->execute([$year_id, $sport_id]);
    $heat_id = $h->fetchColumn();
    if (!$heat_id) throw new Exception('no heat for sport');

    // Ensure track_results
    $pdo->exec("CREATE TABLE IF NOT EXISTS track_results (
      id INT AUTO_INCREMENT PRIMARY KEY,
      heat_id INT NOT NULL,
      lane_no INT NOT NULL,
      time_str VARCHAR(32) NULL,
      rank TINYINT NULL,
      UNIQUE KEY uniq_lane (heat_id, lane_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $up = $pdo->prepare("INSERT INTO track_results (heat_id,lane_no,time_str,rank)
                         VALUES (:h,:lane,:t,:r)
                         ON DUPLICATE KEY UPDATE time_str=VALUES(time_str), rank=VALUES(rank)");
    foreach ($lanes as $L) {
      $lane = (int)($L['lane_no'] ?? 0);
      if ($lane <= 0) continue;
      $t = trim($L['time'] ?? '');
      $r = strlen(trim((string)($L['rank'] ?? ''))) ? (int)$L['rank'] : null;
      $up->execute([':h' => $heat_id, ':lane' => $lane, ':t' => ($t !== '' ? $t : null), ':r' => $r]);
    }

    // Best record
    $best_name = trim($data['best_name'] ?? '');
    $best_time = trim($data['best_time'] ?? '');
    $best_year = (int)($data['best_year'] ?? 0);
    if ($best_time !== '') {
      $q = $pdo->prepare("SELECT id, best_time FROM athletics_events WHERE year_id=? AND sport_id=? ORDER BY id DESC LIMIT 1");
      $q->execute([$year_id, $sport_id]);
      $row = $q->fetch(PDO::FETCH_ASSOC);
      $new = (float)$best_time;
      $cur = $row && $row['best_time'] !== '' ? (float)$row['best_time'] : null;
      if ($row) {
        if ($cur === null || $new < $cur) {
          $u = $pdo->prepare("UPDATE athletics_events SET best_time=?, best_year_be=?, notes=? WHERE id=?");
          $u->execute([$best_time, ($best_year ?: $year_be), $best_name, $row['id']]);
        }
      } else {
        $i = $pdo->prepare("INSERT INTO athletics_events (year_id, sport_id, event_code, best_student_id, best_time, best_year_be, notes)
                            VALUES (?,?,?,?,?,?,?)");
        $i->execute([$year_id, $sport_id, '', null, $best_time, ($best_year ?: $year_be), $best_name]);
      }
    }

    echo json_encode(['ok' => true]);
    exit;
  }

  // ADD: delete single sport result (with debug logging)
  if (isset($data['type']) && $data['type'] === 'delete_result') {
      // debug: log incoming payload
      error_log('DEBUG delete_result payload: ' . substr(json_encode($data, JSON_UNESCAPED_UNICODE),0,1000));
      try {
          $sport_id = (int)($data['sport_id'] ?? 0);
          if ($sport_id <= 0) throw new Exception('missing sport_id');

          // active year
          $yr = $pdo->query("SELECT id FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
          if (!$yr) throw new Exception('no active year');
          $year_id = (int)$yr['id'];

          $pdo->beginTransaction();

          // 1) athletics: delete track_results for heats of this sport + athletics_events record
          $h = $pdo->prepare("SELECT id FROM track_heats WHERE year_id=? AND sport_id=?");
          $h->execute([$year_id, $sport_id]);
          $heatIds = $h->fetchAll(PDO::FETCH_COLUMN, 0);
          if ($heatIds) {
              $in = implode(',', array_fill(0, count($heatIds), '?'));
              $delTr = $pdo->prepare("DELETE FROM track_results WHERE heat_id IN ($in)");
              $delTr->execute($heatIds);
              error_log('DEBUG delete_result: deleted track_results for heat_ids: ' . implode(',', $heatIds));
          }

          $delAth = $pdo->prepare("DELETE FROM athletics_events WHERE year_id=? AND sport_id=?");
          $delAth->execute([$year_id, $sport_id]);
          error_log('DEBUG delete_result: deleted athletics_events rows, affected=' . $delAth->rowCount());

          // 2) non-athletics: try to delete from likely result tables if they exist
          $candidateTables = ['non_athletics_results','sport_results','results','match_results','referee_results'];
          foreach ($candidateTables as $tbl) {
              $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
              $chk->execute([$tbl]);
              if ((int)$chk->fetchColumn() > 0) {
                  $del = $pdo->prepare("DELETE FROM `$tbl` WHERE sport_id=? AND year_id=?");
                  try { 
                      $del->execute([$sport_id, $year_id]); 
                      error_log("DEBUG delete_result: deleted from $tbl affected=" . $del->rowCount());
                  } catch(Throwable $ignore) { error_log("DEBUG delete_result: skip $tbl (schema mismatch)"); }
              }
          }

          $pdo->commit();
          echo json_encode(['ok'=>true,'deleted'=>true]);
      } catch (Throwable $e) {
          if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
          error_log('ERROR delete_result: ' . $e->getMessage());
          echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
      }
      exit;
  }

  echo json_encode(['ok' => false, 'error' => 'unknown type']);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
