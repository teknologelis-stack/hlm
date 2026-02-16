<?php
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/DeviceInfo.php';

// Composer autoload for RouterOS library
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Tek cihaz mı yoksa tüm cihazlar mı?
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['device_id'])) {
        // Tek cihaz güncelle
        $device = $db->fetchOne("SELECT * FROM devices WHERE id = ? AND is_active = 1", [$input['device_id']]);
        
        if (!$device) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cihaz bulunamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $info = DeviceInfo::fetchDeviceInfo($device);
        
        if ($info) {
            $db->update('devices', $info, 'id = :id', ['id' => $device['id']]);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Cihaz bilgileri güncellendi',
                'device' => array_merge($device, $info)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'Cihaza bağlanılamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
    } else {
        // Tüm cihazları güncelle (background task)
        $stats = DeviceInfo::updateAllDevices($db);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "{$stats['updated']} cihaz güncellendi, {$stats['failed']} başarısız",
            'stats' => $stats
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Update device info error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
