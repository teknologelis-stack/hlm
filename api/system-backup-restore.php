<?php
/**
 * Yedek Geri Yükleme API
 * 
 * Method: POST (multipart/form-data)
 * Permission: system_manage
 * File: backup_file
 * Response: {'success': bool, 'message': '...', 'restored_items': ['settings', 'users', 'roles']}
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
    
    // Dosya yükleme kontrolü
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Geçerli bir yedek dosyası yükleyin'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $uploadedFile = $_FILES['backup_file']['tmp_name'];
    $targetFile = UPDATE_TEMP_DIR . 'restore-' . time() . '.zip';
    
    if (!move_uploaded_file($uploadedFile, $targetFile)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Dosya yüklenemedi'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $updateManager = new UpdateManager($_SESSION['user_id']);
    $result = $updateManager->restoreBackup($targetFile);
    
    // Geçici dosyayı temizle
    @unlink($targetFile);
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Backup restore API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
