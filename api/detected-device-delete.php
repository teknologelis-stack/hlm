<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

if (!$auth->hasPermission('devices_manage')) {
    jsonResponse(['success' => false, 'message' => 'Yetkiniz yok'], 403);
}

$db = Database::getInstance();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Cihaz ID gerekli']);
    }
    
    // Check if device exists
    $device = $db->fetchOne("SELECT * FROM detected_devices WHERE id = ?", [$id]);
    
    if (!$device) {
        jsonResponse(['success' => false, 'message' => 'Cihaz bulunamadı']);
    }
    
    // Delete device
    $result = $db->delete('detected_devices', 'id = :id', ['id' => $id]);
    
    if ($result) {
        logAction('detected_device_deleted', "Detected device deleted: " . $device['mac_address']);
        jsonResponse(['success' => true, 'message' => 'Cihaz silindi']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cihaz silinemedi']);
    }
    
} catch (Exception $e) {
    logAction('detected_device_delete_failed', 'Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
