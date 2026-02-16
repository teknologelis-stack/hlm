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
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası: ' . $file['error']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Dosya boyutu kontrolü
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dosya çok büyük (Max: 5MB)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Parse et
    try {
        $devices = WinBoxParser::parse($file['tmp_name']);
    } catch (Exception $parseError) {
        error_log("Parse error: " . $parseError->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Parse hatası: ' . $parseError->getMessage(),
            'file' => basename($parseError->getFile()),
            'line' => $parseError->getLine()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($devices)) {
        // Debug bilgisi ekle
        $debugInfo = WinBoxParser::debugFile($file['tmp_name']);
        error_log("Parse debug: " . json_encode($debugInfo));
        
        http_response_code(200);
        echo json_encode([
            'success' => false, 
            'message' => 'Dosyada geçerli cihaz bulunamadı.',
            'debug' => $debugInfo
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Her cihaz için mevcut kontrol yap
    $validDevices = [];
    $invalidCount = 0;
    
    foreach ($devices as $device) {
        // IP formatı kontrolü
        if (!filter_var($device['ip_address'], FILTER_VALIDATE_IP)) {
            error_log("Invalid IP skipped: " . $device['ip_address']);
            $invalidCount++;
            continue;
        }
        
        // Port kontrolü
        if ($device['port'] < 1 || $device['port'] > 65535) {
            $device['port'] = 8728;
        }
        
        // Mevcut cihaz kontrolü
        $existing = $db->fetchOne(
            "SELECT id, name FROM devices WHERE ip_address = ? AND is_active = 1",
            [$device['ip_address']]
        );
        
        $device['exists'] = $existing ? true : false;
        $device['existing_name'] = $existing ? $existing['name'] : null;
        
        $validDevices[] = $device;
    }
    
    $groups = WinBoxParser::getGroups($validDevices);
    
    $message = count($validDevices) . ' geçerli cihaz bulundu';
    if ($invalidCount > 0) {
        $message .= " ({$invalidCount} geçersiz kayıt atlandı)";
    }
    
    error_log("Parse success: " . count($validDevices) . " devices, " . count($groups) . " groups");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'devices' => $validDevices,
        'groups' => $groups,
        'total' => count($validDevices),
        'invalid_count' => $invalidCount,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    error_log("Parse WinBox fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Sistem hatası: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>