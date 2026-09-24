<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — PickRide' : 'PickRide — Book a tuk, car or van' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a href="<?= BASE_URL ?>/index.php" class="brand">Pick<span class="dot">Ride</span></a>
    <nav class="nav-links">
      <a href="<?= BASE_URL ?>/index.php#how-it-works">How it works</a>
      <a href="<?= BASE_URL ?>/index.php#vehicles">Vehicles</a>
      <?php if (is_logged_in()): ?>
        <a href="<?= BASE_URL ?>/book.php">Book a ride</a>
        <a href="<?= BASE_URL ?>/my-rides.php">My rides</a>
        <a href="<?= BASE_URL ?>/logout.php">Log out</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php">Log in</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline btn-small">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
