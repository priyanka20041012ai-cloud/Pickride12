<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $base_fare = (float)$_POST['base_fare'];
        $rate_per_km = (float)$_POST['rate_per_km'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE vehicle_types SET name=?, description=?, base_fare=?, rate_per_km=?, is_active=? WHERE id=?'
            );
            $stmt->execute([$name, $description, $base_fare, $rate_per_km, $is_active, $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO vehicle_types (name, description, base_fare, rate_per_km, is_active) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $description, $base_fare, $rate_per_km, $is_active]);
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM vehicle_types WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
    }
    header('Location: ' . BASE_URL . '/admin/vehicle-types.php');
    exit;
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM vehicle_types WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$vehicleTypes = $pdo->query('SELECT * FROM vehicle_types ORDER BY base_fare')->fetchAll();

$pageTitle = 'Vehicle types';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-topbar"><h1>Vehicle types</h1></div>

<div class="card">
  <h3><?= $editing ? 'Edit vehicle type' : 'Add a vehicle type' ?></h3>
  <form method="post">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
      <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Description</label>
        <input type="text" name="description" value="<?= e($editing['description'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Base fare (LKR)</label>
        <input type="number" step="0.01" name="base_fare" value="<?= e($editing['base_fare'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Rate per km (LKR)</label>
        <input type="number" step="0.01" name="rate_per_km" value="<?= e($editing['rate_per_km'] ?? '') ?>" required>
      </div>
    </div>
    <div class="field" style="display:flex; align-items:center; gap:8px;">
      <input type="checkbox" name="is_active" id="is_active" style="width:auto;" <?= (!isset($editing) || $editing['is_active']) ? 'checked' : '' ?>>
      <label for="is_active" style="margin:0;">Active (shown to riders)</label>
    </div>
    <button type="submit" class="btn btn-dark"><?= $editing ? 'Save changes' : 'Add vehicle type' ?></button>
    <?php if ($editing): ?><a href="<?= BASE_URL ?>/admin/vehicle-types.php" class="btn btn-outline" style="color:var(--ink-900); border-color:var(--border);">Cancel</a><?php endif; ?>
  </form>
</div>

<table class="data-table">
  <thead>
    <tr><th>Name</th><th>Description</th><th>Base fare</th><th>Per km</th><th>Active</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($vehicleTypes as $v): ?>
      <tr>
        <td><?= e($v['name']) ?></td>
        <td><?= e($v['description']) ?></td>
        <td>LKR <?= number_format($v['base_fare'], 2) ?></td>
        <td>LKR <?= number_format($v['rate_per_km'], 2) ?></td>
        <td><?= $v['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
          <a href="?edit=<?= e($v['id']) ?>" class="btn btn-small btn-outline" style="color:var(--ink-900); border-color:var(--border);">Edit</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this vehicle type?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($v['id']) ?>">
            <button type="submit" class="btn btn-small btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
