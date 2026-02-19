<?php
/**
 * API: MikroTik VLAN Management
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
        $query = new Query('/interface/vlan/print');
        $vlans = $client->query($query)->read();
        
        echo json_encode(['success' => true, 'data' => $vlans ?? []]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $name = $input['name'] ?? '';
        $vlanId = $input['vlan_id'] ?? '';
        $interface = $input['interface'] ?? '';
        $comment = $input['comment'] ?? '';
        
        if (empty($name) || empty($vlanId) || empty($interface)) {
            echo json_encode(['success' => false, 'error' => 'Ad, VLAN ID ve Arayüz gerekli']);
            exit;
        }
        
        $query = new Query('/interface/vlan/add');
        $query->equal('name', $name);
        $query->equal('vlan-id', $vlanId);
        $query->equal('interface', $interface);
        
        if (!empty($comment)) {
            $query->equal('comment', $comment);
        }
        
        $client->query($query)->read();
        
        echo json_encode(['success' => true, 'message' => 'VLAN eklendi']);
        exit;
    }
    
    if ($method === 'PUT') {
        $id = $input['id'] ?? '';
        $name = $input['name'] ?? '';
        $vlanId = $input['vlan_id'] ?? '';
        $interface = $input['interface'] ?? '';
        $comment = $input['comment'] ?? '';
        $disabled = $input['disabled'] ?? null;
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit;
        }
        
        $query = new Query('/interface/vlan/set');
        $query->equal('.id', $id);
        
        if (!empty($name)) {
            $query->equal('name', $name);
        }
        if (!empty($vlanId)) {
            $query->equal('vlan-id', $vlanId);
        }
        if (!empty($interface)) {
            $query->equal('interface', $interface);
        }
        if (isset($comment)) {
            $query->equal('comment', $comment);
        }
        if (isset($disabled)) {
            $query->equal('disabled', $disabled ? 'true' : 'false');
        }
        
        $client->query($query)->read();
        
        echo json_encode(['success' => true, 'message' => 'VLAN güncellendi']);
        exit;
    }
    
    if ($method === 'DELETE') {
        $id = $input['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit;
        }
        
        $query = new Query('/interface/vlan/remove');
        $query->equal('.id', $id);
        $client->query($query)->read();
        
        echo json_encode(['success' => true, 'message' => 'VLAN silindi']);
        exit;
    }
    
} catch (Exception $e) {
    error_log("[API:mikrotik-vlan] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
