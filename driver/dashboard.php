<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_driver();

$driverId = $_SESSION['driver_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_availability') {
        $avail = $_POST['available'] === '1' ? 'available' : 'offline';
        // Don't let a driver mark themselves available/offline mid-trip.
        $stmt = $pdo->prepare("SELECT status FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $current = $stmt->fetchColumn();
        if ($current !== 'on_trip') {
            $pdo->prepare('UPDATE drivers SET status = ? WHERE id = ?')->execute([$avail, $driverId]);
        }
    } elseif ($action === 'set_status') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['on_trip', 'completed'];

        // Only allow the driver to update bookings assigned to them.
        $stmt = $pdo->prepare('SELECT driver_id FROM bookings WHERE id = ?');
        $stmt->execute([$bookingId]);
        $ownerId = $stmt->fetchColumn();

        if ($ownerId == $driverId && in_array($status, $allowed, true)) {
            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $bookingId]);
            if ($status === 'completed') {
                $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = ?")->execute([$driverId]);
            }
        }
    }
    header('Location: ' . BASE_URL . '/driver/dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM drivers WHERE id = ?');
$stmt->execute([$driverId]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$driver) {
    // The session points at a driver that no longer exists (deleted
    // driver, or a stale login from before the DB was reset). Rather
    // than dead-ending the page, clear the stale session and send them
    // back to log in again.
    unset($_SESSION['driver_id'], $_SESSION['driver_name']);
    flash_set('driver_login_error', 'Your driver session has expired. Please log in again.');
    header('Location: ' . BASE_URL . '/driver/login.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT b.*, u.full_name AS rider_name, u.phone AS rider_phone
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     WHERE b.driver_id = ? AND b.status IN ('confirmed','on_trip')
     ORDER BY b.requested_at ASC"
);
$stmt->execute([$driverId]);
$activeRides = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT b.*, u.full_name AS rider_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     WHERE b.driver_id = ? AND b.status IN ('completed','cancelled')
     ORDER BY b.requested_at DESC LIMIT 10"
);
$stmt->execute([$driverId]);
$pastRides = $stmt->fetchAll();

$pageTitle = 'Driver dashboard';
require __DIR__ . '/includes/driver_header.php';
?>
<div class="container" style="padding-top:40px; padding-bottom:64px;">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <h1 style="margin:0;">Welcome, <?= e($driver['full_name']) ?></h1>
    <span class="badge badge-<?= $driver['status'] === 'on_trip' ? 'on_trip_driver' : e($driver['status']) ?>"><?= ucfirst(str_replace('_', ' ', $driver['status'])) ?></span>
  </div>

  <div class="card" style="margin-top:20px;">
    <h3>Availability</h3>
    <?php if ($driver['status'] === 'on_trip'): ?>
      <p style="margin:0;">You're currently on a trip — availability updates once it's completed.</p>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="action" value="set_availability">
        <input type="hidden" name="available" value="<?= $driver['status'] === 'available' ? '0' : '1' ?>">
        <button type="submit" class="btn <?= $driver['status'] === 'available' ? 'btn-outline' : 'btn-dark' ?>" style="<?= $driver['status'] === 'available' ? 'color:var(--ink-900); border-color:var(--border);' : '' ?>">
          <?= $driver['status'] === 'available' ? 'Go offline' : 'Go available' ?>
        </button>
      </form>
    <?php endif; ?>
  </div>

  <h2 style="margin-top:32px;">Your active rides</h2>
  <?php if (!$activeRides): ?>
    <p>No rides assigned to you right now.</p>
  <?php endif; ?>
  <?php foreach ($activeRides as $ride): ?>
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
          <h3 style="margin-bottom:4px;"><?= e($ride['pickup_location']) ?> &rarr; <?= e($ride['dropoff_location']) ?></h3>
          <p style="margin:0; font-size:0.86rem;">
            Rider: <?= e($ride['rider_name']) ?> &middot; <?= e($ride['rider_phone']) ?><br>
            <?= number_format($ride['distance_km'], 1) ?> km &middot; requested <?= date('d M, g:i A', strtotime($ride['requested_at'])) ?>
          </p>
        </div>
        <div style="text-align:right;">
          <div class="amount" style="font-family:var(--font-display); font-size:1.3rem; font-weight:700;">LKR <?= number_format($ride['estimated_fare'], 0) ?></div>
          <span class="badge badge-<?= e($ride['status']) ?>"><?= e(status_label($ride['status'])) ?></span>
        </div>
      </div>
      <div style="margin-top:14px; display:flex; gap:8px;">
        <?php if ($ride['status'] === 'confirmed'): ?>
          <form method="post">
            <input type="hidden" name="action" value="set_status">
            <input type="hidden" name="booking_id" value="<?= e($ride['id']) ?>">
            <input type="hidden" name="status" value="on_trip">
            <button type="submit" class="btn btn-dark btn-small">Start trip (on the way)</button>
          </form>
        <?php elseif ($ride['status'] === 'on_trip'): ?>
          <form method="post">
            <input type="hidden" name="action" value="set_status">
            <input type="hidden" name="booking_id" value="<?= e($ride['id']) ?>">
            <input type="hidden" name="status" value="completed">
            <button type="submit" class="btn btn-dark btn-small">Mark completed</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <h2 style="margin-top:32px;">Recent history</h2>
  <table class="data-table">
    <thead><tr><th>Route</th><th>Rider</th><th>Fare</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($pastRides as $r): ?>
        <tr>
          <td><?= e($r['pickup_location']) ?> &rarr; <?= e($r['dropoff_location']) ?></td>
          <td><?= e($r['rider_name']) ?></td>
          <td>LKR <?= number_format($r['estimated_fare'], 0) ?></td>
          <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$pastRides): ?><tr><td colspan="4">No completed rides yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/driver_footer.php'; ?>
