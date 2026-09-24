<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/book.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        header('Location: ' . BASE_URL . '/book.php');
        exit;
    }
    $error = 'That email and password combination doesn\'t match our records.';
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-wrap">
    <h1>Log in</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <div class="auth-switch">New to PickRide? <a href="<?= BASE_URL ?>/register.php">Create an account</a></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
