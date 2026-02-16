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
$userId = $input['user_id'] ?? null;
$deviceId = $input['device_id'] ?? null;

if (!$userId) {
    jsonResponse(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
}

try {
    $mikrotik = new MikroTikHelper($deviceId);
    
    if ($mikrotik->disconnectPPPUser($userId)) {
        logAction('ppp_user_disconnected', "PPP kullanıcı bağlantısı kesildi: {$userId}", $mikrotik->device['id'] ?? null);
        jsonResponse([
            'success' => true,
            'message' => 'Kullanıcı bağlantısı kesildi'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
} catch (Exception $e) {
    error_log("Disconnect user error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
