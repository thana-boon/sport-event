<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try {
  if (empty($_SESSION['referee']) || (($_SESSION['referee']['role'] ?? '') !== 'referee')) throw new Exception('forbidden');
  $pdo = db();

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
  // Consider it a relay / team race when team_size>1 or participant_type contains 'ทีม' or name contains 'ผลัด'
  $isRelay = ($team_size > 1)
            || (mb_stripos($participant_type, 'ทีม') !== false)
            || (mb_stripos($sport_name, 'ผลัด') !== false);

  // First heat
  $h = $pdo->prepare("SELECT id, heat_no, lanes_used FROM track_heats WHERE year_id=? AND sport_id=? ORDER BY heat_no ASC LIMIT 1");
  $h->execute([$year_id, $sport_id]);
  $heat = $h->fetch(PDO::FETCH_ASSOC);
  if (!$heat) { echo json_encode(['ok'=>true,'lanes'=>[],'best'=>null,'is_relay'=>$isRelay], JSON_UNESCAPED_UNICODE); exit; }

  // Init lanes
  $lanes = [];
  for ($i=1; $i <= (int)$heat['lanes_used']; $i++) {
    $lanes[$i] = ['lane_no'=>$i, 'color'=>null, 'display_name'=>'ยังไม่เลือกผู้เล่น', 'time_sec'=>null, 'rank'=>null, 'is_record'=>0];
  }

  // Get assignments
  $q = $pdo->prepare("SELECT la.lane_no, la.color, la.registration_id, r.student_id, s.first_name, s.last_name
                      FROM track_lane_assignments la
                      LEFT JOIN registrations r ON r.id = la.registration_id
                      LEFT JOIN students s ON s.id = r.student_id
                      WHERE la.heat_id=? ORDER BY la.lane_no");
  $q->execute([$heat['id']]);
  $assign = $q->fetchAll(PDO::FETCH_ASSOC);

  // Track used students to avoid duplicates across lanes
  $usedStudentIds = [];

  foreach ($assign as $r) {
    $i = (int)$r['lane_no'];
    if (!isset($lanes[$i])) continue;
    $lanes[$i]['color'] = $r['color'] ?: null;

    if (!empty($r['first_name'])) {
      $name = trim($r['first_name'].' '.$r['last_name']);
      $lanes[$i]['display_name'] = $name;
      if (!empty($r['student_id'])) $usedStudentIds[(int)$r['student_id']] = true;
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

  // Best record
  $best = null;
  $b = $pdo->prepare("SELECT best_time, best_year_be, notes FROM athletics_events WHERE year_id=? AND sport_id=? ORDER BY id DESC LIMIT 1");
  $b->execute([$year_id, $sport_id]);
  if ($r = $b->fetch(PDO::FETCH_ASSOC)) {
    $best = ['holder'=>$r['notes']?:'', 'time_sec'=>$r['best_time'], 'year'=>$r['best_year_be']];
  }

  echo json_encode(['ok'=>true,'lanes'=>array_values($lanes),'best'=>$best,'is_relay'=>$isRelay], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
