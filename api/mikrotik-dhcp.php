<?php
/**
 * API: MikroTik DHCP Server Management
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
        // DHCP Servers
        $query = new Query('/ip/dhcp-server/print');
        $servers = $client->query($query)->read();
        
        // DHCP Networks
        $query = new Query('/ip/dhcp-server/network/print');
        $networks = $client->query($query)->read();
        
        // DHCP Leases
        $query = new Query('/ip/dhcp-server/lease/print');
        $leases = $client->query($query)->read();
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'servers' => $servers ?? [],
                'networks' => $networks ?? [],
                'leases' => $leases ?? []
            ]
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'server';
    
    if ($action === 'server') {
        if ($method === 'POST') {
            $name = $input['name'] ?? '';
            $interface = $input['interface'] ?? '';
            $relay = $input['relay'] ?? '';
            $addressPool = $input['address_pool'] ?? 'static-only';
            $leaseTime = $input['lease_time'] ?? '10m';
            
            if (empty($name) || empty($interface)) {
                echo json_encode(['success' => false, 'error' => 'Ad ve Arayüz gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dhcp-server/add');
            $query->equal('name', $name);
            $query->equal('interface', $interface);
            $query->equal('address-pool', $addressPool);
            $query->equal('lease-time', $leaseTime);
            
            if (!empty($relay)) {
                $query->equal('relay', $relay);
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'DHCP Server eklendi']);
            exit;
        }
        
        if ($method === 'PUT') {
            $id = $input['id'] ?? '';
            $disabled = $input['disabled'] ?? null;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dhcp-server/set');
            $query->equal('.id', $id);
            
            if (isset($disabled)) {
                $query->equal('disabled', $disabled ? 'yes' : 'no');
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'DHCP Server güncellendi']);
            exit;
        }
        
        if ($method === 'DELETE') {
            $id = $input['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dhcp-server/remove');
            $query->equal('.id', $id);
            $client->query($query)->read();
            
            echo json_encode(['success' => true, 'message' => 'DHCP Server silindi']);
            exit;
        }
    }
    
    if ($action === 'network') {
        if ($method === 'POST') {
            $address = $input['address'] ?? '';
            $gateway = $input['gateway'] ?? '';
            $dnsServers = $input['dns_servers'] ?? '';
            $domain = $input['domain'] ?? '';
            
            if (empty($address) || empty($gateway)) {
                echo json_encode(['success' => false, 'error' => 'Ağ adresi ve Gateway gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dhcp-server/network/add');
            $query->equal('address', $address);
            $query->equal('gateway', $gateway);
            
            if (!empty($dnsServers)) {
                $query->equal('dns-servers', $dnsServers);
            }
            if (!empty($domain)) {
                $query->equal('domain', $domain);
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'DHCP Ağ eklendi']);
            exit;
        }
        
        if ($method === 'DELETE') {
            $id = $input['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dhcp-server/network/remove');
            $query->equal('.id', $id);
            $client->query($query)->read();
            
            echo json_encode(['success' => true, 'message' => 'DHCP Ağ silindi']);
            exit;
        }
    }
    
    if ($action === 'lease') {
        if ($method === 'PUT') {
            $id = $input['id'] ?? '';
            $address = $input['address'] ?? '';
            $macAddress = $input['mac_address'] ?? '';
            $comment = $input['comment'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID gerekli']);
                exit;
            }
            
            $query = new Query('/ip/dhcp-server/lease/set');
            $query->equal('.id', $id);
            
            if (!empty($address)) {
                $query->equal('address', $address);
            }
            if (!empty($macAddress)) {
                $query->equal('mac-address', $macAddress);
            }
            if (isset($comment)) {
                $query->equal('comment', $comment);
            }
            
            $client->query($query)->read();
            echo json_encode(['success' => true, 'message' => 'Lease güncellendi']);
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("[API:mikrotik-dhcp] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
