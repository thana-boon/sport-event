<?php
header('Content-Type: application/json; charset=utf-8');
// ใช้สีมาตรฐานของโรงเรียน พร้อมโทนพื้นหลัง
echo json_encode([
  'ok' => true,
  'colors' => [
    ['name'=>'ส้ม','hex'=>'#FFA726'],
    ['name'=>'เขียว','hex'=>'#4CAF50'],
    ['name'=>'ชมพู','hex'=>'#EC407A'],
    ['name'=>'ฟ้า','hex'=>'#29B6F6'],
  ]
], JSON_UNESCAPED_UNICODE);
