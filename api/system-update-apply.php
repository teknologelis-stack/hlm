<?php
// Hataları logla
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
        jsonResponse([
            'success' => false,
            'message' => 'Oturum geçersiz'
        ], 401);
    }
    
    if (!$auth->hasPermission('system_manage')) {
        jsonResponse([
            'success' => false,
            'message' => 'Yetkiniz yok'
        ], 403);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['version']) || empty($input['version'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Versiyon parametresi eksik'
        ], 400);
    }

    $version = trim($input['version']);
    $userId = $_SESSION['user_id'];
    
    logInfo("Update requested by user $userId to version $version");

    // Store progress in session
    $_SESSION['update_progress'] = [
        'status' => 'starting',
        'progress' => 0,
        'message' => 'Güncelleme başlatılıyor...'
    ];
    
    $updateManager = new UpdateManager();
    
    // Update progress
    $_SESSION['update_progress'] = [
        'status' => 'downloading',
        'progress' => 20,
        'message' => 'Güncelleme dosyaları indiriliyor...'
    ];
    
    $result = $updateManager->applyUpdate($version, $userId);

    if ($result['success']) {
        $_SESSION['update_progress'] = [
            'status' => 'done',
            'progress' => 100,
            'message' => 'Güncelleme tamamlandı!'
        ];
        
        logInfo("Update successful: $version");
        jsonResponse($result, 200);
    } else {
        $_SESSION['update_progress'] = [
            'status' => 'error',
            'progress' => 0,
            'message' => $result['error']
        ];
        
        logError("Update failed: " . ($result['error'] ?? 'Unknown error'), [
            'version' => $version,
            'user_id' => $userId
        ]);
        
        jsonResponse($result, 500);
    }

} catch (Exception $e) {
    logError('Update apply error: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    jsonResponse([
        'success' => false,
        'message' => 'Güncelleme uygulanamadı',
        'error' => $e->getMessage()
    ], 500);
}
