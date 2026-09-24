<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$type = $_GET['type'] ?? '';

if (!in_array($type, ['admins', 'drivers'], true)) {
    http_response_code(400);
    exit('Unknown export type.');
}

$filename = 'pickride-' . $type . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

if ($type === 'admins') {
    $rows = $pdo->query('SELECT id, full_name, email, password_hash FROM admins ORDER BY full_name')->fetchAll();
    fputcsv($out, ['ID', 'Full name', 'Email (login)', 'Password (encrypted hash — not the plain password, cannot be reversed)']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'], $r['full_name'], $r['email'], $r['password_hash']]);
    }
} else {
    $rows = $pdo->query(
        "SELECT d.id, d.full_name, d.phone, d.email, d.username, d.status, v.name AS vehicle_name, d.vehicle_plate
         FROM drivers d LEFT JOIN vehicle_types v ON v.id = d.vehicle_type_id
         ORDER BY d.full_name"
    )->fetchAll();
    fputcsv($out, ['ID', 'Full name', 'Phone', 'Email', 'Dashboard username', 'Status', 'Vehicle', 'Plate', 'Note']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['full_name'], $r['phone'], $r['email'], $r['username'],
            $r['status'], $r['vehicle_name'], $r['vehicle_plate'],
            'Password is only shown once, at account creation — reset it from Drivers if lost.',
        ]);
    }
}

fclose($out);
exit;
