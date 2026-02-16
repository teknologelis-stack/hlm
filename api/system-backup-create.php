<?php
/**
 * Sistem Yedeği Oluşturma API
 * 
 * Method: POST
 * Permission: system_manage
 * Body: {'description': '...'} (optional)
 * Response: {'success': bool, 'backup_file': 'backup-2026-02-16.zip', 'download_url': '...', 'size': 1234567}
 */

require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    
    // Oturum kontrolü
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Oturum geçersiz'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Yetki kontrolü
    if (!$auth->hasPermission('system_manage')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bu işlem için yetkiniz yok'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $updateManager = new UpdateManager($_SESSION['user_id']);
    
    // POST verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    $description = $input['description'] ?? 'Manuel yedekleme';
    
    $result = $updateManager->createSystemBackup($description);
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Backup create API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
