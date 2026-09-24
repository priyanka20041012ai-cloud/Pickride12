<?php if (!defined('BASE_URL')) { require_once __DIR__ . '/../../includes/functions.php'; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — PickRide driver' : 'PickRide driver' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a href="<?= BASE_URL ?>/driver/dashboard.php" class="brand">Pick<span class="dot">Ride</span> <span style="font-size:0.7rem; font-weight:500; opacity:0.7;">driver</span></a>
    <nav class="nav-links">
      <?php if (is_driver_logged_in()): ?>
        <span style="font-size:0.9rem;">Hi, <?= e($_SESSION['driver_name'] ?? 'Driver') ?></span>
        <a href="<?= BASE_URL ?>/driver/logout.php">Log out</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
