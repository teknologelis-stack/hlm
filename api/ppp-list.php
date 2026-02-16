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
$secrets = $mikrotik->getPPPSecrets();

if ($secrets === false) {
    jsonResponse([
        'success' => false,
        'message' => $mikrotik->error ?: 'Veri alınamadı'
    ]);
}

$users = [];
foreach ($secrets as $secret) {
    $users[] = [
        'id' => $secret['.id'],
        'name' => $secret['name'],
        'password' => $secret['password'] ?? '',
        'service' => $secret['service'] ?? 'any',
        'local_address' => $secret['local-address'] ?? '',
        'remote_address' => $secret['remote-address'] ?? '',
        'profile' => $secret['profile'] ?? 'default',
        'disabled' => isset($secret['disabled']) && $secret['disabled'] === 'true'
    ];
}

jsonResponse([
    'success' => true,
    'users' => $users
]);
?>
