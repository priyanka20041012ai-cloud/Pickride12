<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $driver_id = (int)$_POST['driver_id'];
        $stmt = $pdo->prepare("UPDATE bookings SET driver_id = ?, status = 'confirmed' WHERE id = ?");
        $stmt->execute([$driver_id, $booking_id]);
        $stmt = $pdo->prepare("UPDATE drivers SET status = 'on_trip' WHERE id = ?");
        $stmt->execute([$driver_id]);
    } elseif ($action === 'set_status') {
        $status = $_POST['status'];
        $allowed = ['pending', 'confirmed', 'on_trip', 'completed', 'cancelled'];
        if (in_array($status, $allowed, true)) {
            $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->execute([$status, $booking_id]);

            if (in_array($status, ['completed', 'cancelled'], true)) {
                $stmt = $pdo->prepare('SELECT driver_id FROM bookings WHERE id = ?');
                $stmt->execute([$booking_id]);
                $driver_id = $stmt->fetchColumn();
                if ($driver_id) {
                    $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = ?")->execute([$driver_id]);
                }
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/bookings.php');
    exit;
}

$bookings = $pdo->query(
    'SELECT b.*, u.full_name AS rider_name, u.phone AS rider_phone, v.name AS vehicle_name,
            d.full_name AS driver_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN vehicle_types v ON v.id = b.vehicle_type_id
     LEFT JOIN drivers d ON d.id = b.driver_id
     ORDER BY b.requested_at DESC'
)->fetchAll();

$availableDrivers = $pdo->query("SELECT * FROM drivers WHERE status = 'available' ORDER BY full_name")->fetchAll();

$pageTitle = 'Bookings';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-topbar"><h1>Bookings</h1></div>

<table class="data-table">
  <thead>
    <tr><th>Rider</th><th>Route</th><th>Vehicle</th><th>Fare</th><th>Driver</th><th>Status</th><th>Action</th></tr>
  </thead>
  <tbody>
    <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?= e($b['rider_name']) ?><br><span style="color:var(--ink-600); font-size:0.8rem;"><?= e($b['rider_phone']) ?></span></td>
        <td><?= e($b['pickup_location']) ?> &rarr; <?= e($b['dropoff_location']) ?></td>
        <td><?= e($b['vehicle_name']) ?></td>
        <td>LKR <?= number_format($b['estimated_fare'], 0) ?></td>
        <td><?= $b['driver_name'] ? e($b['driver_name']) : '&mdash;' ?></td>
        <td><span class="badge badge-<?= e($b['status']) ?>"><?= e(status_label($b['status'])) ?></span></td>
        <td>
          <?php if ($b['status'] === 'pending'): ?>
            <form method="post" style="display:flex; gap:6px;">
              <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
              <input type="hidden" name="action" value="assign">
              <select name="driver_id" required style="padding:6px 8px; font-size:0.82rem;">
                <option value="">Assign driver&hellip;</option>
                <?php foreach ($availableDrivers as $d): ?>
                  <option value="<?= e($d['id']) ?>"><?= e($d['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-dark btn-small">Assign</button>
            </form>
          <?php elseif (in_array($b['status'], ['confirmed', 'on_trip'], true)): ?>
            <form method="post" style="display:flex; gap:6px;">
              <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
              <input type="hidden" name="action" value="set_status">
              <select name="status" style="padding:6px 8px; font-size:0.82rem;">
                <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="on_trip" <?= $b['status'] === 'on_trip' ? 'selected' : '' ?>>On the way</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <button type="submit" class="btn btn-dark btn-small">Update</button>
            </form>
          <?php else: ?>
            &mdash;
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$bookings): ?><tr><td colspan="7">No bookings yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
