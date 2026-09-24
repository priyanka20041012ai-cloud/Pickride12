<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $vehicle_type_id = (int)$_POST['vehicle_type_id'];
        $vehicle_plate = trim($_POST['vehicle_plate']);
        $status = $_POST['status'];
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $saveErrors = [];
        if ($username === '') {
            $saveErrors[] = 'Username is required so the driver can log in.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM drivers WHERE username = ? AND id != ?');
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                $saveErrors[] = 'That username is already taken by another driver.';
            }
        }
        if (!$id && $password === '') {
            $saveErrors[] = 'Set a password for the new driver to log in with.';
        }

        if ($saveErrors) {
            flash_set('driver_error', implode(' ', $saveErrors));
            header('Location: ' . BASE_URL . '/admin/drivers.php' . ($id ? '?edit=' . $id : ''));
            exit;
        }

        if ($id) {
            if ($password !== '') {
                $stmt = $pdo->prepare(
                    'UPDATE drivers SET full_name=?, phone=?, email=?, vehicle_type_id=?, vehicle_plate=?, status=?, username=?, password_hash=? WHERE id=?'
                );
                $stmt->execute([$full_name, $phone, $email, $vehicle_type_id, $vehicle_plate, $status, $username, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE drivers SET full_name=?, phone=?, email=?, vehicle_type_id=?, vehicle_plate=?, status=?, username=? WHERE id=?'
                );
                $stmt->execute([$full_name, $phone, $email, $vehicle_type_id, $vehicle_plate, $status, $username, $id]);
            }
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO drivers (full_name, phone, email, vehicle_type_id, vehicle_plate, status, username, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$full_name, $phone, $email, $vehicle_type_id, $vehicle_plate, $status, $username, password_hash($password, PASSWORD_DEFAULT)]);
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM drivers WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
    }
    header('Location: ' . BASE_URL . '/admin/drivers.php');
    exit;
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM drivers WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$drivers = $pdo->query(
    'SELECT d.*, v.name AS vehicle_name FROM drivers d LEFT JOIN vehicle_types v ON v.id = d.vehicle_type_id ORDER BY d.full_name'
)->fetchAll();
$vehicleTypes = $pdo->query('SELECT * FROM vehicle_types ORDER BY name')->fetchAll();

$pageTitle = 'Drivers';
require __DIR__ . '/includes/admin_header.php';
$driverError = flash_get('driver_error');
$driverSuccess = flash_get('driver_success');
?>
<div class="admin-topbar"><h1>Drivers</h1></div>

<?php if ($driverError): ?><div class="alert alert-error"><?= e($driverError) ?></div><?php endif; ?>
<?php if ($driverSuccess): ?><div class="alert alert-success"><?= e($driverSuccess) ?></div><?php endif; ?>

<div class="card">
  <h3><?= $editing ? 'Edit driver' : 'Add a driver' ?></h3>
  <form method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
      <div class="field">
        <label>Full name</label>
        <input type="text" name="full_name" value="<?= e($editing['full_name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= e($editing['phone'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Email (optional)</label>
        <input type="email" name="email" value="<?= e($editing['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Vehicle plate</label>
        <input type="text" name="vehicle_plate" value="<?= e($editing['vehicle_plate'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Vehicle type</label>
        <select name="vehicle_type_id" required>
          <?php foreach ($vehicleTypes as $vt): ?>
            <option value="<?= e($vt['id']) ?>" <?= (($editing['vehicle_type_id'] ?? null) == $vt['id']) ? 'selected' : '' ?>><?= e($vt['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <?php foreach (['offline', 'available', 'on_trip'] as $s): ?>
            <option value="<?= $s ?>" <?= (($editing['status'] ?? 'offline') === $s) ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Driver dashboard username</label>
        <input type="text" name="username" value="<?= e($editing['username'] ?? '') ?>" placeholder="e.g. driver's phone or short handle" required>
      </div>
      <div class="field">
        <label><?= $editing ? 'New password (leave blank to keep current)' : 'Password' ?></label>
        <input type="text" name="password" placeholder="<?= $editing ? 'Leave blank to keep current password' : 'Set a login password' ?>" <?= $editing ? '' : 'required' ?>>
      </div>
    </div>
    <p style="font-size:0.8rem; color:var(--ink-600); margin-top:-6px;">This username and password are what the driver uses to log in at <?= BASE_URL ?>/driver/login.php — share them with the driver directly.</p>
    <button type="submit" class="btn btn-dark"><?= $editing ? 'Save changes' : 'Add driver' ?></button>
    <?php if ($editing): ?><a href="<?= BASE_URL ?>/admin/drivers.php" class="btn btn-outline" style="color:var(--ink-900); border-color:var(--border);">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card" style="display:flex; justify-content:space-between; align-items:center;">
  <span>Need a copy of every driver's login username for your records?</span>
  <a href="<?= BASE_URL ?>/admin/export.php?type=drivers" class="btn btn-outline" style="color:var(--ink-900); border-color:var(--border);">Export drivers (CSV)</a>
</div>

<table class="data-table">
  <thead>
    <tr><th>Name</th><th>Phone</th><th>Username</th><th>Vehicle</th><th>Plate</th><th>Status</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($drivers as $d): ?>
      <tr>
        <td><?= e($d['full_name']) ?></td>
        <td><?= e($d['phone']) ?></td>
        <td><?= e($d['username'] ?? '—') ?></td>
        <td><?= e($d['vehicle_name'] ?? '—') ?></td>
        <td><?= e($d['vehicle_plate']) ?></td>
        <td><span class="badge badge-<?= $d['status'] === 'on_trip' ? 'on_trip_driver' : e($d['status']) ?>"><?= ucfirst(str_replace('_', ' ', $d['status'])) ?></span></td>
        <td>
          <a href="?edit=<?= e($d['id']) ?>" class="btn btn-small btn-outline" style="color:var(--ink-900); border-color:var(--border);">Edit</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Remove this driver?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($d['id']) ?>">
            <button type="submit" class="btn btn-small btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$drivers): ?><tr><td colspan="7">No drivers yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
