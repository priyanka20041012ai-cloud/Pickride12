<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/book.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '' || $email === '' || $phone === '' || $password === '') {
        $errors[] = 'Please fill in every field.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$full_name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_name'] = $full_name;
        flash_set('success', 'Welcome to PickRide! Your account is ready.');
        header('Location: ' . BASE_URL . '/book.php');
        exit;
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-wrap">
    <h1>Create your account</h1>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" novalidate>
      <div class="field">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label for="phone">Phone number</label>
        <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign up</button>
    </form>
    <div class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
