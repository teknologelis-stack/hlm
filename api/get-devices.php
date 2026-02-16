<?php
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    
    $devices = $db->fetchAll("
        SELECT 
            id, name, ip_address, port, username, 
            identity, routeros_version, board_name, uptime, 
            cpu_load, memory_total, memory_free, 
            last_seen, device_type, is_main
        FROM devices 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'devices' => $devices
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    error_log("Get devices error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
