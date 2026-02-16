<?php
/**
 * Yapılandırma Import API
 * 
 * Method: POST (multipart/form-data)
 * Permission: system_manage
 * File: config_file (JSON/CSV)
 * Form: type (settings, users, roles, devices)
 * Response: {'success': bool, 'imported': 25, 'skipped': 3, 'errors': []}
 */

require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ConfigurationManager.php';

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
    if (!isset($_FILES['config_file']) || $_FILES['config_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Geçerli bir dosya yükleyin'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $uploadedFile = $_FILES['config_file']['tmp_name'];
    $fileName = $_FILES['config_file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $configManager = new ConfigurationManager($_SESSION['user_id']);
    $result = null;
    
    if ($fileExtension === 'json') {
        // JSON import
        $jsonData = file_get_contents($uploadedFile);
        $result = $configManager->importJSON($jsonData);
        
    } elseif ($fileExtension === 'csv') {
        // CSV import
        $type = $_POST['type'] ?? 'settings';
        $result = $configManager->importCSV($uploadedFile, $type);
        
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Desteklenmeyen dosya formatı. JSON veya CSV yükleyin.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Config import API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
