<?php
/**
 * API: Restore System Backup
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

// Check if user has admin role
if (!$auth->isAdmin()) {
    error_log("[API:backup-restore] Access denied for user ID: " . ($_SESSION['user_id'] ?? 'N/A'));
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yönetici yetkisi gereklidir']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['backup_id']) || empty($input['backup_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Yedekleme ID gerekli']);
    exit();
}

try {
    $currentUser = $auth->getCurrentUser();
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
    error_log("[API:backup-restore] Exception: " . $e->getMessage());
    error_log("[API:backup-restore] Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Yedekleme geri yükleme hatası: ' . $e->getMessage()
    ]);
}
