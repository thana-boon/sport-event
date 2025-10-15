<?php
// lib/helpers.php
// UTF-8 (no BOM) และห้ามมีช่องว่างก่อน <?php

/* Escape HTML */
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * คืนค่า id ของปีการศึกษาที่ Active (กำลังใช้งาน)
 * ใช้ year_be เป็นเกณฑ์หลัก เผื่อมีมากกว่า 1 แถวที่ is_active=1 จะเลือกปีที่มากที่สุด
 */
if (!function_exists('active_year_id')) {
    function active_year_id(PDO $pdo) {
        $stmt = $pdo->query("SELECT id FROM academic_years WHERE is_active=1 ORDER BY year_be DESC, id DESC LIMIT 1");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }
}

/**
 * คืนค่า id ของ "ปีการศึกษาก่อนหน้า" จากปี active ปัจจุบัน
 * - เปรียบเทียบ year_be เป็นหลัก (ค่าปี พ.ศ.)
 * - ถ้าไม่มี year_be (หรือข้อมูลไม่สม่ำเสมอ) จะ fallback เทียบ created_at
 */
if (!function_exists('previous_year_id')) {
    function previous_year_id(PDO $pdo) {
        // หา year_be ของปีที่ active ปัจจุบัน
        $stmt = $pdo->query("SELECT id, year_be, created_at FROM academic_years WHERE is_active=1 ORDER BY year_be DESC, id DESC LIMIT 1");
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) return null;

        // พยายามหา by year_be ที่น้อยกว่า
        $stmt = $pdo->prepare("
            SELECT id FROM academic_years
            WHERE year_be < :cur_year
            ORDER BY year_be DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([':cur_year' => (int)$current['year_be']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prev) return (int)$prev['id'];

        // Fallback: ใช้ created_at ที่เก่ากว่า
        $stmt = $pdo->prepare("
            SELECT id FROM academic_years
            WHERE created_at < :cur_created
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([':cur_created' => $current['created_at']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);
        return $prev ? (int)$prev['id'] : null;
    }
}

function generate_pairs_by_color($color, $players) {
    shuffle($players);
    $pairs = [];
    for ($i=0; $i<count($players); $i+=2) {
        $a = $players[$i];
        $b = $players[$i+1] ?? null;
        $pairs[] = [$a, $b];
    }
    return $pairs;
}

// ดึงชื่อปีการศึกษาปัจจุบัน
function active_year_name(PDO $pdo) {
    $stmt = $pdo->query("SELECT title FROM academic_years WHERE is_active = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['title'] : '';
}
