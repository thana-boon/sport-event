<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = db();
header('Content-Type: application/json; charset=utf-8');
try {
  $st = $pdo->query("SELECT id, name FROM houses ORDER BY id");
  $houses = $st->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true, 'houses'=>$houses]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
