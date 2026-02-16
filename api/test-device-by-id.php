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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['device_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cihaz ID gerekli'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $deviceId = intval($input['device_id']);
    $db = Database::getInstance();
    
    $device = $db->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cihaz bulunamadı'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        $client = new \RouterOS\Client([
            'host' => $device['ip_address'],
            'user' => $device['username'],
            'pass' => decrypt($device['password']),
            'port' => $device['port'],
            'timeout' => 5
        ]);
        
        // Identity
        $identityQuery = new \RouterOS\Query('/system/identity/print');
        $identityResponse = $client->query($identityQuery)->read();
        
        // Resource
        $resourceQuery = new \RouterOS\Query('/system/resource/print');
        $resourceResponse = $client->query($resourceQuery)->read();
        
        $identity = $identityResponse[0]['name'] ?? 'Unknown';
        $version = $resourceResponse[0]['version'] ?? 'Unknown';
        $boardName = $resourceResponse[0]['board-name'] ?? 'Unknown';
        $uptime = $resourceResponse[0]['uptime'] ?? '0s';
        $cpuLoad = intval($resourceResponse[0]['cpu-load'] ?? 0);
        $memoryTotal = intval($resourceResponse[0]['total-memory'] ?? 0);
        $memoryFree = intval($resourceResponse[0]['free-memory'] ?? 0);
        
        // DB'ye kaydet
        $updateData = [
            'identity' => $identity,
            'routeros_version' => $version,
            'board_name' => $boardName,
            'uptime' => $uptime,
            'cpu_load' => $cpuLoad,
            'memory_total' => $memoryTotal,
            'memory_free' => $memoryFree,
            'last_seen' => date('Y-m-d H:i:s')
        ];
        
        $db->update('devices', $updateData, 'id = :id', ['id' => $deviceId]);
        
        // Uptime formatla
        $uptimeFormatted = formatUptimeDisplay($uptime);
        
        // Log activity
        logActivity($_SESSION['user_id'], 'device_test', "Cihaz test edildi: " . $device['name'], $deviceId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bağlantı başarılı',
            'data' => [
                'identity' => $identity,
                'version' => $version,
                'board' => $boardName,
                'uptime' => $uptime,
                'uptime_formatted' => $uptimeFormatted,
                'cpu_load' => $cpuLoad,
                'memory_total' => $memoryTotal,
                'memory_free' => $memoryFree
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        error_log("Device connection error for device ID {$deviceId}: " . $e->getMessage());
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'Cihaza bağlanılamadı. Lütfen bağlantı ayarlarını kontrol edin.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Test device error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
