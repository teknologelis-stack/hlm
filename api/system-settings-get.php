<?php
/**
 * API: Get Update Settings
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

try {
    $db = Database::getInstance()->getConnection();
    
    // Get settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('auto_backup', 'update_channel')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $result = [];
    if (isset($settings['auto_backup'])) {
        $result['auto_backup'] = $settings['auto_backup'];
    }
    if (isset($settings['update_channel'])) {
        $result['update_channel'] = $settings['update_channel'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} catch (Exception $e) {
    error_log("[API:get-settings] Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ayarlar alınamadı'
    ]);
}
