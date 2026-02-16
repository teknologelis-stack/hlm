<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mikrotik.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

try {
    $deviceId = $_GET['device_id'] ?? null;
    
    $mikrotik = new MikroTikHelper($deviceId);
    $servers = $mikrotik->getDHCPServers();
    
    if ($servers === false) {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
    logAction('dhcp_servers_viewed', 'DHCP sunucular görüntülendi', $mikrotik->device['id'] ?? null);
    
    jsonResponse([
        'success' => true,
        'servers' => $servers
    ]);
    
} catch (Exception $e) {
    error_log("DHCP servers error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
