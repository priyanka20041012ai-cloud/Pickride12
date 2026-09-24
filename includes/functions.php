<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL of the app itself, worked out from the request — so links and
// redirects work whether this is served from the domain root
// (e.g. http://localhost/) or from a subfolder (e.g. http://localhost/pickride/,
// which is what you get by default with XAMPP/WAMP/MAMP's htdocs). Every
// internal link/redirect in this app is built as BASE_URL . '/something.php'
// instead of a hardcoded '/something.php', so it never jumps to the web
// server's document root by mistake.
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $projectRoot = preg_replace('#/(admin|driver)$#', '', $scriptDir);
    define('BASE_URL', rtrim($projectRoot, '/'));
}
function require_login(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

function is_driver_logged_in(): bool {
    return isset($_SESSION['driver_id']);
}

// function require_login(): void {
//     if (!is_logged_in()) {
//         header('Location: ' . BASE_URL . '/login.php');
//         exit;
//     }
// }

function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function require_driver(): void {
    if (!is_driver_logged_in()) {
        header('Location: ' . BASE_URL . '/driver/login.php');
        exit;
    }
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function status_label(string $status): string {
    $labels = [
        'pending'   => 'Looking for a driver',
        'confirmed' => 'Driver confirmed',
        'on_trip'   => 'On the way',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function status_step(string $status): int {
    $steps = [
        'pending' => 1,
        'confirmed' => 2,
        'on_trip' => 3,
        'completed' => 4,
        'cancelled' => 0,
    ];
    return $steps[$status] ?? 0;
}
