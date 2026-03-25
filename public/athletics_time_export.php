<?php
// athletics_time_export.php — Export template สำหรับใส่เวลาแข่งขัน

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

if (empty($_SESSION['admin'])) { 
    header('Location: ' . BASE_URL . '/login.php'); 
    exit; 
}

$pdo = db();
$yearId = active_year_id($pdo);

// ดึงรายการกรีฑาทั้งหมด
$sql = "
  SELECT
    ae.id,
    ae.event_code,
    ae.competition_time,
    s.name AS sport_name,
    s.gender,
    s.participant_type,
    s.grade_levels
  FROM athletics_events ae
  LEFT JOIN sports s ON s.id = ae.sport_id
  WHERE ae.year_id = :y
  ORDER BY 
    CASE WHEN ae.event_code REGEXP '^[0-9]+$' THEN CAST(ae.event_code AS UNSIGNED) ELSE 999999 END,
    ae.event_code
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':y' => $yearId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตั้งค่า header สำหรับ CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="athletics_time_template_' . date('Y-m-d') . '.csv"');

// เพิ่ม BOM สำหรับ UTF-8
echo "\xEF\xBB\xBF";

// สร้าง CSV
$output = fopen('php://output', 'w');

// Header
fputcsv($output, ['ID', 'รหัสรายการ', 'ชื่อรายการ', 'เพศ', 'ประเภท', 'ระดับชั้น', 'เวลาแข่งขัน (HH:MM)']);

// ข้อมูล
foreach ($events as $event) {
    fputcsv($output, [
        $event['id'],
        $event['event_code'] ?? '',
        $event['sport_name'] ?? '',
        $event['gender'] ?? '',
        $event['participant_type'] ?? '',
        $event['grade_levels'] ?? '',
        $event['competition_time'] ? substr($event['competition_time'], 0, 5) : ''
    ]);
}

fclose($output);
exit;
