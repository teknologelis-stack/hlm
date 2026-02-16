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
    // Get count before deletion
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM detected_devices");
    $deviceCount = $count['count'] ?? 0;
    
    // Delete all detected devices
    $result = $db->query("DELETE FROM detected_devices");
    
    if ($result !== false) {
        logAction('detected_devices_cleared', "Cleared all detected devices (count: $deviceCount)");
        jsonResponse(['success' => true, 'message' => "$deviceCount cihaz silindi"]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cihazlar silinemedi']);
    }
    
} catch (Exception $e) {
    logAction('detected_devices_clear_failed', 'Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
