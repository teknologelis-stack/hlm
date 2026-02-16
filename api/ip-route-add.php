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

$auth->requirePermission('router_config');

$input = json_decode(file_get_contents('php://input'), true);

$dstAddress = $input['dst_address'] ?? '';
$gateway = $input['gateway'] ?? '';
$distance = $input['distance'] ?? 1;
$deviceId = $input['device_id'] ?? null;

if (empty($dstAddress) || empty($gateway)) {
    jsonResponse(['success' => false, 'message' => 'Hedef adres ve gateway gereklidir']);
}

try {
    $mikrotik = new MikroTikHelper($deviceId);
    
    if ($mikrotik->addIPRoute($dstAddress, $gateway, $distance)) {
        logAction('ip_route_added', "IP route eklendi: {$dstAddress} via {$gateway}", $mikrotik->device['id'] ?? null);
        jsonResponse([
            'success' => true,
            'message' => 'IP route başarıyla eklendi'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
} catch (Exception $e) {
    error_log("IP route add error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
