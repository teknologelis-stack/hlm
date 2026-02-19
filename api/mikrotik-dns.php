<?php
/**
 * API: MikroTik DNS Management
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
        $query = new Query('/ip/dns/print');
        $dns = $client->query($query)->read();
        
        // Static DNS entries
        $query = new Query('/ip/dns/static/print');
        $static = $client->query($query)->read();
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'settings' => $dns[0] ?? null,
                'static' => $static ?? []
            ]
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'settings';
    
    if ($action === 'settings') {
        if ($method === 'POST' || $method === 'PUT') {
            $servers = $input['servers'] ?? '';
            $allowRemoteRequests = $input['allow_remote_requests'] ?? 'no';
            $cacheSize = $input['cache_size'] ?? '';
            $cacheMaxTtl = $input['cache_max_ttl'] ?? '';
            
            $query = new Query('/ip/dns/set');
            
            if (!empty($servers)) {
                $query->equal('servers', $servers);
            }
            if (!empty($allowRemoteRequests)) {
                $query->equal('allow-remote-requests', $allowRemoteRequests);
            }
            if (!empty($cacheSize)) {
                $query->equal('cache-size', $cacheSize);
            }
            if (!empty($cacheMaxTtl)) {
                $query->equal('cache-max-ttl', $cacheMaxTtl);
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'DNS ayarlandı']);
            exit;
        }
    } elseif ($action === 'static') {
        if ($method === 'POST') {
            $name = $input['name'] ?? '';
            $address = $input['address'] ?? '';
            $comment = $input['comment'] ?? '';
            
            if (empty($name) || empty($address)) {
                echo json_encode(['success' => false, 'error' => 'Adres ve IP gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dns/static/add');
            $query->equal('name', $name);
            $query->equal('address', $address);
            
            if (!empty($comment)) {
                $query->equal('comment', $comment);
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'DNS kaydı eklendi']);
            exit;
        }
        
        if ($method === 'DELETE') {
            $id = $input['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dns/static/remove');
            $query->equal('.id', $id);
            $client->query($query)->read();
            
            echo json_encode(['success' => true, 'message' => 'DNS kaydı silindi']);
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("[API:mikrotik-dns] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
