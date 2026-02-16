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
    
    $auth->requirePermission('devices_manage');
    
    $db = Database::getInstance();
    $devices = $db->fetchAll("SELECT * FROM devices WHERE is_active = 1");
    
    $stats = [
        'total' => count($devices),
        'success' => 0,
        'offline' => 0,
        'failed' => 0
    ];
    
    foreach ($devices as $device) {
        try {
            $client = new \RouterOS\Client([
                'host' => $device['ip_address'],
                'user' => $device['username'],
                'pass' => decrypt($device['password']),
                'port' => $device['port'],
                'timeout' => 5
            ]);
            
            $identityQuery = new \RouterOS\Query('/system/identity/print');
            $identityResponse = $client->query($identityQuery)->read();
            
            $resourceQuery = new \RouterOS\Query('/system/resource/print');
            $resourceResponse = $client->query($resourceQuery)->read();
            
            $updateData = [
                'identity' => $identityResponse[0]['name'] ?? $device['name'],
                'routeros_version' => $resourceResponse[0]['version'] ?? 'Unknown',
                'board_name' => $resourceResponse[0]['board-name'] ?? 'Unknown',
                'uptime' => $resourceResponse[0]['uptime'] ?? '0s',
                'cpu_load' => intval($resourceResponse[0]['cpu-load'] ?? 0),
                'memory_total' => intval($resourceResponse[0]['total-memory'] ?? 0),
                'memory_free' => intval($resourceResponse[0]['free-memory'] ?? 0),
                'last_seen' => date('Y-m-d H:i:s')
            ];
            
            $db->update('devices', $updateData, 'id = :id', ['id' => $device['id']]);
            $stats['success']++;
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'timed out') !== false || strpos($e->getMessage(), 'connection') !== false) {
                $stats['offline']++;
            } else {
                $stats['failed']++;
            }
        }
    }
    
    logActivity($_SESSION['user_id'], 'devices_update_all', "Toplu güncelleme: {$stats['success']}/{$stats['total']}");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "{$stats['success']} cihaz güncellendi, {$stats['offline']} çevrimdışı, {$stats['failed']} hata",
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    error_log("Update all devices error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
