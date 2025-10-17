<?php
// /public/referee_api.php — Backend for referee.php (admin scoring under main site)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

try{
  $pdo = db();
  // active year
  $yr = $pdo->query("SELECT id, year_be FROM academic_years WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if(!$yr) throw new Exception('no active year');
  $year_id = (int)$yr['id'];

  $fn = $_GET['fn'] ?? '';

  if ($fn === 'init') {
    $cats = $pdo->query("SELECT id, name FROM sport_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // ensure table
    $pdo->exec("CREATE TABLE IF NOT EXISTS scoring_rules (
      id INT AUTO_INCREMENT PRIMARY KEY,
      year_id INT NOT NULL,
      category_id INT NOT NULL,
      rank1 INT NOT NULL DEFAULT 5,
      rank2 INT NOT NULL DEFAULT 3,
      rank3 INT NOT NULL DEFAULT 2,
      rank4 INT NOT NULL DEFAULT 1,
      UNIQUE KEY uniq_year_cat (year_id, category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $st = $pdo->prepare("SELECT category_id, rank1, rank2, rank3, rank4 FROM scoring_rules WHERE year_id=?");
    $st->execute([$year_id]);
    $rules = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok'=>true, 'categories'=>$cats, 'rules'=>$rules], JSON_UNESCAPED_UNICODE); exit;
  }

  if ($fn === 'save_rules') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $rules = $data['rules'] ?? [];
    $pdo->exec("CREATE TABLE IF NOT EXISTS scoring_rules (
      id INT AUTO_INCREMENT PRIMARY KEY,
      year_id INT NOT NULL,
      category_id INT NOT NULL,
      rank1 INT NOT NULL DEFAULT 5,
      rank2 INT NOT NULL DEFAULT 3,
      rank3 INT NOT NULL DEFAULT 2,
      rank4 INT NOT NULL DEFAULT 1,
      UNIQUE KEY uniq_year_cat (year_id, category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $up = $pdo->prepare("INSERT INTO scoring_rules (year_id, category_id, rank1, rank2, rank3, rank4)
                         VALUES (:y,:c,:r1,:r2,:r3,:r4)
                         ON DUPLICATE KEY UPDATE rank1=VALUES(rank1), rank2=VALUES(rank2), rank3=VALUES(rank3), rank4=VALUES(rank4)");
    foreach ($rules as $r) {
      $up->execute([
        ':y'=>$year_id, ':c'=>(int)$r['category_id'],
        ':r1'=>(int)($r['rank1'] ?? 5),
        ':r2'=>(int)($r['rank2'] ?? 3),
        ':r3'=>(int)($r['rank3'] ?? 2),
        ':r4'=>(int)($r['rank4'] ?? 1),
      ]);
    }
    echo json_encode(['ok'=>true]); exit;
  }

  if ($fn === 'compute') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $categories = $data['categories'] ?? [];
    $levels = $data['levels'] ?? ['P','S'];

    if (!is_array($categories) || !count($categories)) {
      // default to all categories
      $categories = array_map(function($r){ return (int)$r['id']; }, $pdo->query("SELECT id FROM sport_categories")->fetchAll(PDO::FETCH_ASSOC));
    }
    $catIn = implode(',', array_map('intval', $categories));

    // map rank->points per category
    $ruleStmt = $pdo->prepare("SELECT category_id, rank1, rank2, rank3, rank4 FROM scoring_rules WHERE year_id=? AND category_id IN ($catIn)");
    $ruleStmt->execute([$year_id]);
    $ruleRows = $ruleStmt->fetchAll(PDO::FETCH_ASSOC);
    $rules = [];
    foreach ($ruleRows as $r) $rules[(int)$r['category_id']] = $r;

    // fallback to default if rule missing
    $catAll = $pdo->query("SELECT id, name FROM sport_categories WHERE id IN ($catIn)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($catAll as $c) if (!isset($rules[(int)$c['id']])) $rules[(int)$c['id']] = ['category_id'=>$c['id'],'rank1'=>5,'rank2'=>3,'rank3'=>2,'rank4'=>1];

    // helper function to map grade_levels -> level code
    $levelCode = function($txt){
      $t = trim((string)$txt);
      if ($t !== '' && mb_substr($t,0,1) === 'ป') return 'P';
      if ($t !== '' && mb_substr($t,0,1) === 'ม') return 'S';
      return ''; // unknown
    };

    $summary = ['ส้ม'=>0,'เขียว'=>0,'ชมพู'=>0,'ฟ้า'=>0];
    $breakdown = ['ส้ม'=>[], 'เขียว'=>[], 'ชมพู'=>[], 'ฟ้า'=>[]];

    // A) referee_results (non-athletics + relay athletics)
    $sqlA = "SELECT rr.color, rr.rank, c.id AS category_id, c.name AS category_name, s.grade_levels
             FROM referee_results rr
             JOIN sports s ON s.id = rr.sport_id
             JOIN sport_categories c ON c.id = s.category_id
             WHERE rr.year_id = :y AND c.id IN ($catIn)";
    $stA = $pdo->prepare($sqlA); $stA->execute([':y'=>$year_id]);
    while ($row = $stA->fetch(PDO::FETCH_ASSOC)) {
      $lv = $levelCode($row['grade_levels']);
      if (count($levels) && !in_array($lv, $levels)) continue;
      $rk = (int)$row['rank']; if ($rk<1 || $rk>4) continue;
      $catId = (int)$row['category_id']; $rule = $rules[$catId];
      $pts = (int)$rule['rank'.$rk];
      $color = $row['color'];
      $summary[$color] += $pts;
      $breakdown[$color][$row['category_name']] = ($breakdown[$color][$row['category_name']] ?? 0) + $pts;
    }

    // B) track_results (athletics singles) -> need color via lane_assignments
    $sqlB = "SELECT la.color, tr.rank, c.id AS category_id, c.name AS category_name, s.grade_levels
             FROM track_results tr
             JOIN track_heats h ON h.id = tr.heat_id AND h.year_id = :y
             JOIN sports s ON s.id = h.sport_id
             JOIN sport_categories c ON c.id = s.category_id
             JOIN track_lane_assignments la ON la.heat_id = tr.heat_id AND la.lane_no = tr.lane_no
             WHERE c.id IN ($catIn)";
    $stB = $pdo->prepare($sqlB); $stB->execute([':y'=>$year_id]);
    while ($row = $stB->fetch(PDO::FETCH_ASSOC)) {
      $lv = $levelCode($row['grade_levels']);
      if (count($levels) && !in_array($lv, $levels)) continue;
      $rk = (int)$row['rank']; if ($rk<1 || $rk>4) continue;
      $catId = (int)$row['category_id']; $rule = $rules[$catId];
      $pts = (int)$rule['rank'.$rk];
      $color = $row['color'];
      $summary[$color] += $pts;
      $breakdown[$color][$row['category_name']] = ($breakdown[$color][$row['category_name']] ?? 0) + $pts;
    }

    echo json_encode(['ok'=>true, 'summary'=>$summary, 'breakdown'=>$breakdown], JSON_UNESCAPED_UNICODE); exit;
  }

  echo json_encode(['ok'=>false,'error'=>'unknown fn']);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
