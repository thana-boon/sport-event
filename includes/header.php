<?php
if (!defined('APP_NAME')) { define('APP_NAME', 'SPORT-EVENT'); }
if (!defined('BASE_URL')) { define('BASE_URL', '/sport-event/public'); }
?>
<!doctype html>
<html lang="th">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo APP_NAME; ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS (ต้องชี้ผ่าน BASE_URL เสมอ) -->
    <link href="<?php echo BASE_URL; ?>/assets/css/custom.css" rel="stylesheet">

    <!-- Favicon: รองรับหลายขนาด/ตำแหน่ง เพื่อให้เบราว์เซอร์ต่างกันโหลดได้เสมอ -->
    <!-- PNG source inside public -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">
    <link rel="shortcut icon" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">

    <!-- Apple / mobile -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= rtrim(BASE_URL, '/') ?>/assets/icon.png">

    <!-- Root favicon (fallback) - points to project root /favicon.ico -->
    <link rel="icon" href="<?= rtrim(str_replace('/public','',BASE_URL), '/') ?>/favicon.ico">
    <link rel="manifest" href="<?= rtrim(str_replace('/public','',BASE_URL), '/') ?>/site.webmanifest">
    <meta name="theme-color" content="#0d6efd">

    <style>
      body { font-family: 'Kanit', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif; }
    </style>
  </head>
  <body class="bg-light">
