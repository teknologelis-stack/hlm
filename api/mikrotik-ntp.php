<?php
/**
 * API: MikroTik NTP Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();
    $deviceId = $_GET['device_id'] ?? null;
    
    if (!$deviceId) {
        echo json_encode(['success' => false, 'error' => 'Device ID gerekli']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM mikrotik_devices WHERE id = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        echo json_encode(['success' => false, 'error' => 'Cihaz bulunamadı']);
        exit;
    }
    
    $client = new Client([
        'host' => $device['ip_address'],
        'user' => $device['username'],
        'pass' => $device['password'],
        'port' => (int)$device['port'] ?: 8728,
        'timeout' => 10,
    ]);
    
    if ($method === 'GET') {
        // NTP Client status
        $query = new Query('/system/ntp/client/print');
        $ntpClient = $client->query($query)->read();
        
        // NTP Server status
        $query = new Query('/system/ntp/server/print');
        $ntpServer = $client->query($query)->read();
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'client' => $ntpClient[0] ?? null,
                'server' => $ntpServer[0] ?? null
            ]
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'client';
    
    if ($type === 'client') {
        if ($method === 'POST' || $method === 'PUT') {
            $enabled = $input['enabled'] ?? 'yes';
            $primaryNtp = $input['primary_ntp'] ?? '';
            $secondaryNtp = $input['secondary_ntp'] ?? '';
            
            $query = new Query('/system/ntp/client/set');
            $query->equal('enabled', $enabled);
            
            if (!empty($primaryNtp)) {
                $query->equal('primary-ntp', $primaryNtp);
            }
            if (!empty($secondaryNtp)) {
                $query->equal('secondary-ntp', $secondaryNtp);
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'NTP Client ayarlandı']);
            exit;
        }
    } elseif ($type === 'server') {
        if ($method === 'POST' || $method === 'PUT') {
            $enabled = $input['enabled'] ?? 'yes';
            
            $query = new Query('/system/ntp/server/set');
            $query->equal('enabled', $enabled);
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'NTP Server ayarlandı']);
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("[API:mikrotik-ntp] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
