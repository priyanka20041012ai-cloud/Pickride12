<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$users = $pdo->query(
    'SELECT u.*, COUNT(b.id) AS ride_count
     FROM users u
     LEFT JOIN bookings b ON b.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC'
)->fetchAll();

$pageTitle = 'Riders';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-topbar"><h1>Riders</h1></div>

<table class="data-table">
  <thead>
    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Rides taken</th><th>Joined</th></tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['phone']) ?></td>
        <td><?= (int)$u['ride_count'] ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="5">No riders yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
