<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

$auth->requirePermission('devices_manage');

$deviceId = $_GET['id'] ?? null;

if (!$deviceId) {
    jsonResponse(['success' => false, 'message' => 'Device ID gerekli']);
}

$db = Database::getInstance();

try {
    $device = $db->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
    
    if (!$device) {
        jsonResponse(['success' => false, 'message' => 'Cihaz bulunamadı']);
    }
    
    // Don't send encrypted password to client
    unset($device['password']);
    
    jsonResponse([
        'success' => true,
        'device' => $device
    ]);
} catch (Exception $e) {
    error_log("Get device error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Cihaz bilgileri alınırken hata oluştu']);
}
?>
