<?php
/**
 * API: Check for System Updates
 */

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $updateManager = new UpdateManager();
    $result = $updateManager->checkForUpdates();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['message']
        ]);
    }
} catch (Exception $e) {
    error_log("[API:update-check] Exception: " . $e->getMessage());
    error_log("[API:update-check] Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Güncelleme kontrolü hatası: ' . $e->getMessage()
    ]);
}
