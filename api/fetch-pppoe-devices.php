<?php
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\Config;

header('Content-Type: application/json');

try {
    // AUTH CHECK
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // PERMISSION CHECK
    try {
        $auth->requirePermission('devices_manage');
    } catch (Exception $e) {
        error_log("PPPoE Fetch: Permission denied - " . $e->getMessage());
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Yetkiniz yok: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    
    // ANA CİHAZ BUL
    error_log("PPPoE Fetch: Looking for main device...");
    $mainDevice = $db->fetchOne("SELECT * FROM devices WHERE is_main = 1 AND is_active = 1");
    
    if (!$mainDevice) {
        error_log("PPPoE Fetch: No main device found");
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Ana cihaz bulunamadı. Lütfen önce bir cihazı ana cihaz olarak ayarlayın.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("PPPoE Fetch: Main device found - " . $mainDevice['name'] . " (" . $mainDevice['ip_address'] . ")");
    
    // MİKROTİK'E BAĞLAN
    try {
        error_log("PPPoE Fetch: Connecting to MikroTik...");
        
        $config = (new Config())
            ->set('host', $mainDevice['ip_address'])
            ->set('port', $mainDevice['port'])
            ->set('user', $mainDevice['username'])
            ->set('pass', decrypt($mainDevice['password']))
            ->set('timeout', 10);
        
        $client = new Client($config);
        
        error_log("PPPoE Fetch: Connected! Querying /ppp/secret/print...");
        
        // PPPoE secrets çek (profile=default, service=pppoe)
        $query = (new Query('/ppp/secret/print'))
            ->where('profile', 'default')
            ->where('service', 'pppoe');
        
        $response = $client->query($query)->read();
        
        error_log("PPPoE Fetch: Got " . count($response) . " secrets");
        
        $devices = [];
        $filteredCount = 0;
        
        foreach ($response as $secret) {
            $name = trim($secret['name'] ?? '');
            
            // Boş isim kontrolü
            if (empty($name)) {
                $filteredCount++;
                continue;
            }
            
            // @ içeren isimleri atla (RADIUS kullanıcıları)
            if (strpos($name, '@') !== false) {
                error_log("PPPoE Fetch: Skipping RADIUS user: $name");
                $filteredCount++;
                continue;
            }
            
            // Remote address bilgisi (IP)
            $address = $secret['remote-address'] ?? '';
            
            if (empty($address) || $address === '0.0.0.0') {
                error_log("PPPoE Fetch: Skipping $name - no valid IP");
                $filteredCount++;
                continue;
            }
            
            // Device type detection from name
            $nameLower = strtolower($name);
            $deviceType = 'other';
            
            if (strpos($nameLower, 'router') !== false || strpos($nameLower, 'rtr') !== false) {
                $deviceType = 'router';
            } elseif (strpos($nameLower, 'switch') !== false || strpos($nameLower, 'sw') !== false) {
                $deviceType = 'switch';
            } elseif (strpos($nameLower, 'ap') !== false || strpos($nameLower, 'wifi') !== false || strpos($nameLower, 'wlan') !== false) {
                $deviceType = 'ap';
            }
            
            // Zaten kayıtlı mı kontrol et
            $exists = $db->fetchOne(
                "SELECT id FROM devices WHERE ip_address = ? AND is_active = 1", 
                [$address]
            );
            
            $devices[] = [
                'name' => $name,
                'ip_address' => $address,
                'device_type' => $deviceType,
                'username' => $name, // Use PPPoE username as default
                'exists' => $exists ? true : false
            ];
        }
        
        error_log("PPPoE Fetch: Found " . count($devices) . " valid devices, filtered $filteredCount");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'devices' => $devices,
            'count' => count($devices),
            'filtered' => $filteredCount,
            'total' => count($response)
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        error_log("PPPoE Fetch: MikroTik connection error - " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Ana cihaza bağlanılamadı: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    error_log("PPPoE Fetch: Fatal error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
