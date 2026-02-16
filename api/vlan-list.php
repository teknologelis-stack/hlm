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
    $vlans = $mikrotik->getVLANs();
    
    if ($vlans === false) {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
    logAction('vlan_list_viewed', 'VLAN listesi görüntülendi', $mikrotik->device['id'] ?? null);
    
    jsonResponse([
        'success' => true,
        'vlans' => $vlans
    ]);
    
} catch (Exception $e) {
    error_log("VLAN list error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
