<?php
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/WinBoxParser.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!isset($_FILES['winbox_file'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dosya seçilmedi'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $file = $_FILES['winbox_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $debugInfo = WinBoxParser::debugFile($file['tmp_name']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'debug' => $debugInfo,
        'file_name' => $file['name'],
        'file_type' => $file['type'],
        'file_size_mb' => round($file['size'] / 1024 / 1024, 2)
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    error_log("Debug WinBox error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
