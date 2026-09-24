<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($full_name === '' || $email === '' || $password === '') {
            $errors[] = 'Fill in every field.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An admin with that email already exists.';
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO admins (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash_set('admin_success', 'New admin added — email: ' . $email . ', password: ' . $password . ' (share this with them; it will not be shown again).');
            header('Location: ' . BASE_URL . '/admin/admins.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['admin_id']) {
            $errors[] = 'You cannot delete your own account while logged in as it.';
        } else {
            $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
            header('Location: ' . BASE_URL . '/admin/admins.php');
            exit;
        }
    }
}

$admins = $pdo->query('SELECT id, full_name, email FROM admins ORDER BY full_name')->fetchAll();

$pageTitle = 'Admin accounts';
require __DIR__ . '/includes/admin_header.php';
$adminSuccess = flash_get('admin_success');
?>
<div class="admin-topbar"><h1>Admin accounts</h1></div>

<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<?php if ($adminSuccess): ?><div class="alert alert-success"><?= e($adminSuccess) ?></div><?php endif; ?>

<div class="card">
  <h3>Add a new admin</h3>
  <form method="post">
    <input type="hidden" name="action" value="add">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
      <div class="field">
        <label>Full name</label>
        <input type="text" name="full_name" required>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="text" name="password" required placeholder="At least 6 characters">
      </div>
    </div>
    <button type="submit" class="btn btn-dark">Add admin</button>
  </form>
</div>

<div class="card" style="display:flex; justify-content:space-between; align-items:center;">
  <span>Download every admin account's name and email for your records.</span>
  <a href="<?= BASE_URL ?>/admin/export.php?type=admins" class="btn btn-outline" style="color:var(--ink-900); border-color:var(--border);">Export admins (CSV)</a>
</div>

<table class="data-table">
  <thead><tr><th>Name</th><th>Email</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($admins as $a): ?>
      <tr>
        <td><?= e($a['full_name']) ?></td>
        <td><?= e($a['email']) ?></td>
        <td>
          <?php if ((int)$a['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
            <form method="post" style="display:inline;" onsubmit="return confirm('Remove this admin account?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= e($a['id']) ?>">
              <button type="submit" class="btn btn-small btn-danger">Delete</button>
            </form>
          <?php else: ?>
            <span style="font-size:0.8rem; color:var(--ink-600);">This is you</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
