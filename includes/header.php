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

    <style>
      body { font-family: 'Kanit', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif; }
    </style>
  </head>
  <body class="bg-light">
