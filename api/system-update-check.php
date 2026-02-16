<?php
/**
 * Online Güncelleme Kontrolü API
 * 
 * Method: GET
 * Permission: system_manage
 * Response: {'available': bool, 'current': '1.0.0', 'latest': '1.1.0', 'download_url': '...', 'changelog': '...'}
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
    $result = $updateManager->checkOnlineUpdate();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Update check API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
