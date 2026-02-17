<?php
/**
 * API: Restore System Backup
 */

// Disable error display for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

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

// Check if user has admin role
$currentUser = $auth->getCurrentUser();
if ($currentUser['role_name'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['backup_id']) || empty($input['backup_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Backup ID is required']);
    exit();
}

try {
    $updateManager = new UpdateManager();
    $result = $updateManager->restoreBackup($input['backup_id'], $currentUser['id']);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
} catch (Exception $e) {
    error_log("[API:backup-restore] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to restore backup'
    ]);
}
