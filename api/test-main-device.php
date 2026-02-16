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
$result = $mikrotik->testConnection();

if ($result) {
    jsonResponse([
        'success' => true,
        'device' => $result,
        'message' => 'Bağlantı başarılı'
    ]);
} else {
    jsonResponse([
        'success' => false,
        'message' => $mikrotik->error ?: 'Bağlantı başarısız'
    ]);
}
?>
