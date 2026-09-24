<?php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['driver_id'], $_SESSION['driver_name']);
header('Location: ' . BASE_URL . '/driver/login.php');
exit;
