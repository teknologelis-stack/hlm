<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mikrotik.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

$mikrotik = new MikroTikHelper();
$activeUsers = $mikrotik->getActivePPP();

if ($activeUsers === false) {
    jsonResponse([
        'success' => false,
        'message' => $mikrotik->error ?: 'Veri alınamadı'
    ]);
}

$totalRx = 0;
$totalTx = 0;

$users = [];
foreach ($activeUsers as $user) {
    $totalRx += (int)($user['bytes-in'] ?? 0);
    $totalTx += (int)($user['bytes-out'] ?? 0);
    
    $users[] = [
        'id' => $user['.id'],
        'name' => $user['name'],
        'address' => $user['address'],
        'uptime' => $user['uptime'] ?? '0s',
        'bytes_in' => (int)($user['bytes-in'] ?? 0),
        'bytes_out' => (int)($user['bytes-out'] ?? 0),
        'caller_id' => $user['caller-id'] ?? '',
        'service' => $user['service'] ?? 'pppoe'
    ];
}

// Note: Average uptime calculation is simplified - returns placeholder
$stats = [
    'total' => count($users),
    'total_rx' => $totalRx,
    'total_tx' => $totalTx,
    'avg_uptime' => count($users) > 0 ? 'N/A' : '0d'  // Simplified, proper calculation would require parsing MikroTik uptime format
];

jsonResponse([
    'success' => true,
    'users' => $users,
    'stats' => $stats
]);
?>
