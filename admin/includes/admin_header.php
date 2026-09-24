<?php
$nav = [
    'dashboard.php' => 'Dashboard',
    'bookings.php' => 'Bookings',
    'drivers.php' => 'Drivers',
    'users.php' => 'Riders',
    'vehicle-types.php' => 'Vehicle types',
    'admins.php' => 'Admin accounts',
    'settings.php' => 'Settings',
];
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — PickRide admin' : 'PickRide admin' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand">Pick<span class="dot">Ride</span></div>
    <?php foreach ($nav as $href => $label): ?>
      <a href="<?= BASE_URL ?>/admin/<?= $href ?>" class="<?= $current === $href ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/admin/logout.php">Log out</a>
  </aside>
  <main class="admin-main">
