<?php
/**
 * API: Create System Backup
 */

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

$currentUser = $auth->getCurrentUser();

try {
    $updateManager = new UpdateManager();
    $result = $updateManager->createBackup($currentUser['id'], 'manual');
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'data' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create backup: ' . $e->getMessage()
    ]);
}
