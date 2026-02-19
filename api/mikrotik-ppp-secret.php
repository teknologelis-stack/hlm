<?php
/**
 * API: MikroTik PPP Secret Management
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
        $query = new Query('/ppp/secret/print');
        $secrets = $client->query($query)->read();
        
        echo json_encode(['success' => true, 'data' => $secrets ?? []]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $name = $input['name'] ?? '';
        $password = $input['password'] ?? '';
        $service = $input['service'] ?? 'any';
        $profile = $input['profile'] ?? 'default';
        $remoteAddress = $input['remote_address'] ?? '';
        $localAddress = $input['local_address'] ?? '';
        $comment = $input['comment'] ?? '';
        
        if (empty($name) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Kullanıcı adı ve şifre gerekli']);
            exit;
        }
        
        $query = new Query('/ppp/secret/add');
        $query->equal('name', $name);
        $query->equal('password', $password);
        $query->equal('service', $service);
        $query->equal('profile', $profile);
        
        if (!empty($remoteAddress)) {
            $query->equal('remote-address', $remoteAddress);
        }
        if (!empty($localAddress)) {
            $query->equal('local-address', $localAddress);
        }
        if (!empty($comment)) {
            $query->equal('comment', $comment);
        }
        
        $client->query($query)->read();
        
        echo json_encode(['success' => true, 'message' => 'Kullanıcı eklendi']);
        exit;
    }
    
    if ($method === 'PUT') {
        $id = $input['id'] ?? '';
        $name = $input['name'] ?? '';
        $password = $input['password'] ?? '';
        $service = $input['service'] ?? '';
        $profile = $input['profile'] ?? '';
        $remoteAddress = $input['remote_address'] ?? '';
        $localAddress = $input['local_address'] ?? '';
        $comment = $input['comment'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit;
        }
        
        $query = new Query('/ppp/secret/set');
        $query->equal('.id', $id);
        
        if (!empty($name)) {
            $query->equal('name', $name);
        }
        if (!empty($password)) {
            $query->equal('password', $password);
        }
        if (!empty($service)) {
            $query->equal('service', $service);
        }
        if (!empty($profile)) {
            $query->equal('profile', $profile);
        }
        if (!empty($remoteAddress)) {
            $query->equal('remote-address', $remoteAddress);
        }
        if (!empty($localAddress)) {
            $query->equal('local-address', $localAddress);
        }
        if (isset($comment)) {
            $query->equal('comment', $comment);
        }
        
        $client->query($query)->read();
        
        echo json_encode(['success' => true, 'message' => 'Kullanıcı güncellendi']);
        exit;
    }
    
    if ($method === 'DELETE') {
        $id = $input['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit;
        }
        
        $query = new Query('/ppp/secret/remove');
        $query->equal('.id', $id);
        $client->query($query)->read();
        
        echo json_encode(['success' => true, 'message' => 'Kullanıcı silindi']);
        exit;
    }
    
} catch (Exception $e) {
    error_log("[API:mikrotik-ppp-secret] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
