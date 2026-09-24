<?php
// Database connection settings.
// Update these to match your MySQL setup.
define('DB_HOST', 'localhost');
define('DB_NAME', 'pickride');
define('DB_USER', 'root');
define('DB_PASS', 'root');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Could not connect to the database. Check config/db.php — ' . $e->getMessage());
}
