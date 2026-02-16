<?php
/**
 * Güncelleme Uygulama API
 * 
 * Method: POST
 * Permission: system_manage
 * Body: {'source': 'online'|'offline', 'package_url': '...' or 'package_file': uploaded file}
 * Response: {'success': bool, 'message': '...', 'new_version': '1.1.0'}
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
    
    if (!isset($input['source'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'source parametresi gerekli'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $source = $input['source'];
    $result = null;
    
    if ($source === 'online') {
        // Online güncelleme
        if (!isset($input['package_url']) || !isset($input['version'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'package_url ve version parametreleri gerekli'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Önce paketi indir
        $download = $updateManager->downloadUpdate($input['package_url']);
        
        if (!$download['success']) {
            http_response_code(500);
            echo json_encode($download, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Güncellemeyi uygula
        $result = $updateManager->applyUpdate($download['file_path'], $input['version']);
        
    } elseif ($source === 'offline') {
        // Offline güncelleme - Dosya yükleme
        if (!isset($_FILES['package_file']) || $_FILES['package_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Geçerli bir güncelleme paketi yükleyin'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $uploadedFile = $_FILES['package_file']['tmp_name'];
        $targetFile = UPDATE_TEMP_DIR . 'offline-update-' . time() . '.zip';
        
        if (!move_uploaded_file($uploadedFile, $targetFile)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Dosya yüklenemedi'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Versiyon bilgisini kontrol et
        $version = $input['version'] ?? 'unknown';
        
        // Güncellemeyi uygula
        $result = $updateManager->applyUpdate($targetFile, $version);
        
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz source değeri'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Update apply API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
