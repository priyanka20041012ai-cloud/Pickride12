<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$totalRides = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$pendingRides = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$activeDrivers = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status != 'offline'")->fetchColumn();
$totalRiders = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(estimated_fare),0) FROM bookings WHERE status = 'completed'")->fetchColumn();

$recent = $pdo->query(
    'SELECT b.*, u.full_name AS rider_name, v.name AS vehicle_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN vehicle_types v ON v.id = b.vehicle_type_id
     ORDER BY b.requested_at DESC LIMIT 8'
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-topbar">
  <h1>Dashboard</h1>
  <span>Welcome, </span>
</div>


<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int)$totalRides ?></div><div class="label">Total rides</div></div>
  <div class="stat-card"><div class="num"><?= (int)$pendingRides ?></div><div class="label">Awaiting a driver</div></div>
  <div class="stat-card"><div class="num"><?= (int)$activeDrivers ?></div><div class="label">Active drivers</div></div>
  <div class="stat-card"><div class="num">LKR <?= number_format($revenue, 0) ?></div><div class="label">Completed-ride revenue</div></div>
</div>

<h2>Recent bookings</h2>
<table class="data-table">
  <thead>
    <tr><th>Rider</th><th>Route</th><th>Vehicle</th><th>Fare</th><th>Status</th><th>Requested</th></tr>
  </thead>
  <tbody>
    <?php foreach ($recent as $r): ?>
      <tr>
        <td><?= e($r['rider_name']) ?></td>
        <td><?= e($r['pickup_location']) ?> &rarr; <?= e($r['dropoff_location']) ?></td>
        <td><?= e($r['vehicle_name']) ?></td>
        <td>LKR <?= number_format($r['estimated_fare'], 0) ?></td>
        <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
        <td><?= date('d M, g:i A', strtotime($r['requested_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="6">No bookings yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
