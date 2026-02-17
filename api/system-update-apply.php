<?php
/**
 * API: Apply System Update
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
    error_log("[API:update-apply] Access denied for user ID: " . ($_SESSION['user_id'] ?? 'N/A'));
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yönetici yetkisi gereklidir']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['version']) || empty($input['version'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Versiyon parametresi eksik']);
    exit();
}

try {
    $currentUser = $auth->getCurrentUser();
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
    
    $result = $updateManager->applyUpdate($input['version'], $currentUser['id']);
    
    if ($result['success']) {
        $_SESSION['update_progress'] = [
            'status' => 'done',
            'progress' => 100,
            'message' => 'Güncelleme tamamlandı!'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'data' => $result
        ]);
    } else {
        $_SESSION['update_progress'] = [
            'status' => 'error',
            'progress' => 0,
            'message' => $result['error']
        ];
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
} catch (Exception $e) {
    error_log("[API:update-apply] Exception: " . $e->getMessage());
    error_log("[API:update-apply] Trace: " . $e->getTraceAsString());
    
    $_SESSION['update_progress'] = [
        'status' => 'error',
        'progress' => 0,
        'message' => 'Güncelleme hatası oluştu'
    ];
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Sistem hatası: ' . $e->getMessage()
    ]);
}
