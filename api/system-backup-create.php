<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = new Auth();
    
    if (!$auth->isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
    }
    
    if (!$auth->hasPermission('system_manage')) {
        jsonResponse(['success' => false, 'message' => 'Yetkiniz yok'], 403);
    }

    $userId = $_SESSION['user_id'];
    $updateManager = new UpdateManager();
    
    $result = $updateManager->createBackup($userId, 'manual');

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'backup_name' => $result['filename'],
            'backup_id' => $result['backup_id'],
            'size' => $result['size']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    logError('Backup create error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Yedekleme başarısız',
        'error' => $e->getMessage()
    ], 500);
}
