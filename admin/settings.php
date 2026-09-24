<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$errors = [];
$success = null;

$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '') {
        $errors[] = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!$errors && !password_verify($current_password, $admin['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }

    if (!$errors && $email !== $admin['email']) {
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id != ?');
        $stmt->execute([$email, $admin['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Another admin account already uses that email.';
        }
    }

    if (!$errors && $new_password !== '') {
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }
    }

    if (!$errors) {
        if ($new_password !== '') {
            $stmt = $pdo->prepare('UPDATE admins SET full_name=?, email=?, password_hash=? WHERE id=?');
            $stmt->execute([$full_name, $email, password_hash($new_password, PASSWORD_DEFAULT), $admin['id']]);
        } else {
            $stmt = $pdo->prepare('UPDATE admins SET full_name=?, email=? WHERE id=?');
            $stmt->execute([$full_name, $email, $admin['id']]);
        }
        $_SESSION['admin_name'] = $full_name;
        $_SESSION['admin_email'] = $email;
        $admin['full_name'] = $full_name;
        $admin['email'] = $email;
        $success = 'Account details updated.';
    }
}

$pageTitle = 'Account settings';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-topbar"><h1>Account settings</h1></div>

<div class="card" style="max-width:520px;">
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <form method="post">
    <div class="field">
      <label>Full name</label>
      <input type="text" name="full_name" value="<?= e($admin['full_name']) ?>" required>
    </div>
    <div class="field">
      <label>Email (used to log in)</label>
      <input type="email" name="email" value="<?= e($admin['email']) ?>" required>
    </div>
    <hr style="margin:18px 0; border-color:var(--border);">
    <div class="field">
      <label>Current password</label>
      <input type="password" name="current_password" required placeholder="Required to save any change">
    </div>
    <div class="field">
      <label>New password (leave blank to keep current)</label>
      <input type="password" name="new_password" placeholder="At least 6 characters">
    </div>
    <div class="field">
      <label>Confirm new password</label>
      <input type="password" name="confirm_password">
    </div>
    <button type="submit" class="btn btn-dark">Save changes</button>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
