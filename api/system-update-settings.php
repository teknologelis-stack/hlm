<?php
/**
 * API: Save Update Settings
 */

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user has admin role
if (!$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yönetici yetkisi gereklidir']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['autoBackup']) || !isset($input['updateChannel'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Eksik parametreler']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Save autoBackup setting
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES ('auto_backup', ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$input['autoBackup'] ? '1' : '0', $input['autoBackup'] ? '1' : '0']);
    
    // Save updateChannel setting
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES ('update_channel', ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$input['updateChannel'], $input['updateChannel']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ayarlar kaydedildi'
    ]);
} catch (Exception $e) {
    error_log("[API:save-settings] Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ayarlar kaydedilemedi: ' . $e->getMessage()
    ]);
}
