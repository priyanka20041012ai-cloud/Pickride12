<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT b.*, v.name AS vehicle_name, d.full_name AS driver_name, d.phone AS driver_phone
     FROM bookings b
     JOIN vehicle_types v ON v.id = b.vehicle_type_id
     LEFT JOIN drivers d ON d.id = b.driver_id
     WHERE b.user_id = ?
     ORDER BY b.requested_at DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$rides = $stmt->fetchAll();

$pageTitle = 'My rides';
require __DIR__ . '/includes/header.php';
$success = flash_get('success');
?>
<div class="container" style="padding-top:40px; padding-bottom:64px;">
  <h1>My rides</h1>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <?php if (!$rides): ?>
    <p>No rides yet. <a href="<?= BASE_URL ?>/book.php">Book your first ride</a>.</p>
  <?php endif; ?>

  <?php foreach ($rides as $ride): $step = status_step($ride['status']); ?>
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
          <h3 style="margin-bottom:4px;"><?= e($ride['pickup_location']) ?> &rarr; <?= e($ride['dropoff_location']) ?></h3>
          <p style="margin:0; font-size:0.86rem;">
            <?= e($ride['vehicle_name']) ?> &middot; <?= number_format($ride['distance_km'], 1) ?> km &middot;
            requested <?= date('d M, g:i A', strtotime($ride['requested_at'])) ?>
          </p>
        </div>
        <div style="text-align:right;">
          <div class="amount" style="font-family:var(--font-display); font-size:1.3rem; font-weight:700;">LKR <?= number_format($ride['estimated_fare'], 0) ?></div>
          <span class="badge badge-<?= e($ride['status']) ?>"><?= e(status_label($ride['status'])) ?></span>
        </div>
      </div>

      <?php if ($ride['status'] !== 'cancelled'): ?>
        <div class="stepper">
          <div class="step <?= $step >= 1 ? 'done' : '' ?>"><div class="circle">1</div>Requested</div>
          <div class="step <?= $step >= 2 ? 'done' : '' ?>"><div class="circle">2</div>Confirmed</div>
          <div class="step <?= $step >= 3 ? 'done' : '' ?>"><div class="circle">3</div>On the way</div>
          <div class="step <?= $step >= 4 ? 'done' : '' ?>"><div class="circle">4</div>Completed</div>
        </div>
      <?php else: ?>
        <p style="margin-top:14px; color:var(--coral-500); font-weight:600;">This ride was cancelled.</p>
      <?php endif; ?>

      <?php if ($ride['driver_name']): ?>
        <p style="margin-top:12px; margin-bottom:0; font-size:0.88rem;">Driver: <?= e($ride['driver_name']) ?> &middot; <?= e($ride['driver_phone']) ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
