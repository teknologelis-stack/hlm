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

$input = json_decode(file_get_contents('php://input'), true);
$deviceId = $input['device_id'] ?? null;

if (!$deviceId) {
    jsonResponse(['success' => false, 'message' => 'Cihaz ID gerekli']);
}

$db = Database::getInstance();

try {
    // Tüm cihazları is_main = 0 yap
    $db->query("UPDATE devices SET is_main = 0");
    
    // Seçilen cihazı is_main = 1 yap
    $db->update('devices', ['is_main' => 1], 'id = :id', ['id' => $deviceId]);
    
    $device = $db->fetchOne("SELECT name FROM devices WHERE id = ?", [$deviceId]);
    $auth->logActivity($_SESSION['user_id'], 'main_device_changed', "Ana cihaz değiştirildi: " . $device['name']);
    
    jsonResponse(['success' => true, 'message' => 'Ana cihaz güncellendi']);
} catch (Exception $e) {
    error_log("Set main device error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Ana cihaz güncellenirken hata oluştu']);
}
?>
