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

$address = $input['address'] ?? '';
$interface = $input['interface'] ?? '';
$comment = $input['comment'] ?? '';
$deviceId = $input['device_id'] ?? null;

if (empty($address) || empty($interface)) {
    jsonResponse(['success' => false, 'message' => 'IP adresi ve interface gereklidir']);
}

try {
    $mikrotik = new MikroTikHelper($deviceId);
    
    if ($mikrotik->addIPAddress($address, $interface, $comment)) {
        logAction('ip_address_added', "IP adresi eklendi: {$address}", $mikrotik->device['id'] ?? null);
        jsonResponse([
            'success' => true,
            'message' => 'IP adresi başarıyla eklendi'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
} catch (Exception $e) {
    error_log("IP address add error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
