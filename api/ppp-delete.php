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
$id = $input['id'] ?? '';
$deviceId = $input['device_id'] ?? null;

if (empty($id)) {
    jsonResponse(['success' => false, 'message' => 'PPP secret ID gereklidir']);
}

try {
    $mikrotik = new MikroTikHelper($deviceId);
    
    if ($mikrotik->removePPPSecret($id)) {
        logAction('ppp_user_deleted', "PPP kullanıcı silindi: {$id}", $mikrotik->device['id'] ?? null);
        jsonResponse([
            'success' => true,
            'message' => 'PPP kullanıcısı başarıyla silindi'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
} catch (Exception $e) {
    error_log("PPP delete error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
