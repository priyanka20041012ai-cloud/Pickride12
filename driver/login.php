<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['driver_id'], $_SESSION['driver_name']);
}

if (is_driver_logged_in()) {
    header('Location: ' . BASE_URL . '/driver/dashboard.php');
    exit;
}

$error = flash_get('driver_login_error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM drivers WHERE username = ?');
    $stmt->execute([$username]);
    $driver = $stmt->fetch();

    if ($driver && $driver['password_hash'] && password_verify($password, $driver['password_hash'])) {
        $_SESSION['driver_id'] = (int)$driver['id'];
        $_SESSION['driver_name'] = $driver['full_name'];

        header('Location: ' . BASE_URL . '/driver/dashboard.php');
        exit;
    }

    $error = 'Incorrect username or password.';
}

$pageTitle = 'Driver login';
require __DIR__ . '/includes/driver_header.php';
?>
<div class="container">
  <div class="auth-wrap" style="margin-top:60px;">
    <h1>Driver login</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p style="margin-top:14px; font-size:0.85rem; color:var(--ink-600);">Don't have login details? Ask your admin — driver accounts are created and assigned a username/password from the admin panel.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/driver_footer.php'; ?>
