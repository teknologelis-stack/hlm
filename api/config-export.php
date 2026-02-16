<?php
/**
 * Yapılandırma Export API
 * 
 * Method: GET
 * Permission: system_manage
 * Query: ?format=json|csv&type=settings|users|roles|devices
 * Response: File download
 */

require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ConfigurationManager.php';

try {
    $auth = new Auth();
    
    // Oturum kontrolü
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        die('Oturum geçersiz');
    }
    
    // Yetki kontrolü
    if (!$auth->hasPermission('system_manage')) {
        http_response_code(403);
        die('Bu işlem için yetkiniz yok');
    }
    
    $format = $_GET['format'] ?? 'json';
    $type = $_GET['type'] ?? 'settings';
    
    $configManager = new ConfigurationManager($_SESSION['user_id']);
    
    if ($format === 'json') {
        $result = $configManager->exportJSON();
        
        if (!$result['success']) {
            http_response_code(500);
            die($result['message']);
        }
        
        // Dosyayı indir
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . $result['file_size']);
        
        readfile($result['file_path']);
        
        // Geçici dosyayı temizle
        @unlink($result['file_path']);
        
    } elseif ($format === 'csv') {
        $result = $configManager->exportCSV($type);
        
        if (!$result['success']) {
            http_response_code(500);
            die($result['message']);
        }
        
        // Dosyayı indir
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . $result['file_size']);
        
        readfile($result['file_path']);
        
        // Geçici dosyayı temizle
        @unlink($result['file_path']);
        
    } else {
        http_response_code(400);
        die('Geçersiz format');
    }
    
} catch (Exception $e) {
    error_log("Config export API error: " . $e->getMessage());
    http_response_code(500);
    die('Sistem hatası: ' . $e->getMessage());
}
?>
