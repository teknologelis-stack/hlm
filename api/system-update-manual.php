<?php
/**
 * API: Manual Update Upload
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
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yönetici yetkisi gereklidir']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['update_file']) || $_FILES['update_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dosya yüklenemedi']);
    exit();
}

$file = $_FILES['update_file'];

// Validate file type
if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Sadece ZIP dosyaları kabul edilir']);
    exit();
}

// Validate file size (max 100MB)
if ($file['size'] > 100 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dosya boyutu çok büyük (max 100MB)']);
    exit();
}

try {
    $currentUser = $auth->getCurrentUser();
    $updateManager = new UpdateManager();
    
    // Store progress in session
    $_SESSION['update_progress'] = [
        'status' => 'uploading',
        'progress' => 10,
        'message' => 'Dosya yükleniyor...'
    ];
    
    // Save uploaded file to temp
    $tempPath = TEMP_PATH . '/manual_update_' . time() . '.zip';
    
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        throw new Exception('Dosya kaydedilemedi');
    }
    
    $_SESSION['update_progress'] = [
        'status' => 'applying',
        'progress' => 30,
        'message' => 'Güncelleme uygulanıyor...'
    ];
    
    // Apply manual update
    $result = $updateManager->applyManualUpdate($tempPath, $currentUser['id']);
    
    // Cleanup temp file
    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
    
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
    error_log("[API:manual-update] Exception: " . $e->getMessage());
    error_log("[API:manual-update] Trace: " . $e->getTraceAsString());
    
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
