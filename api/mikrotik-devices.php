<?php
/**
 * API: Mikrotik Devices Management
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all devices - GET
    if ($method === 'GET') {
        $stmt = $db->query("SELECT 
            id, 
            name, 
            ip_address, 
            port, 
            description, 
            status, 
            cpu_usage, 
            ram_usage, 
            uptime, 
            ros_version, 
            pppoe_count, 
            interface_count, 
            last_connect 
            FROM mikrotik_devices ORDER BY id DESC");
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $devices]);
        exit();
    }
    
    // Add new device - POST
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['name']) || !isset($input['ip_address']) || !isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Eksik parametreler: name, ip_address, username, password gereklidir']);
            exit();
        }
        
        // Store password as plain text (socket server needs it)
        $plainPassword = $input['password'];
        
        $stmt = $db->prepare("INSERT INTO mikrotik_devices (name, ip_address, username, password, port, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['ip_address'],
            $input['username'],
            $plainPassword,
            $input['port'] ?? 8728,
            $input['description'] ?? ''
        ]);
        
        $newId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cihaz başarıyla eklendi', 
            'id' => $newId
        ]);
        exit();
    }
    
    // Delete device - DELETE
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit();
        }
        
        $stmt = $db->prepare("DELETE FROM mikrotik_devices WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Cihaz silindi']);
        exit();
    }
    
    // Update device - PUT
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit();
        }
        
        $updates = [];
        $params = [];
        
        if (isset($input['name'])) { 
            $updates[] = 'name = ?'; 
            $params[] = $input['name']; 
        }
        if (isset($input['ip_address'])) { 
            $updates[] = 'ip_address = ?'; 
            $params[] = $input['ip_address']; 
        }
        if (isset($input['username'])) { 
            $updates[] = 'username = ?'; 
            $params[] = $input['username']; 
        }
        if (isset($input['password']) && !empty($input['password'])) { 
            $updates[] = 'password = ?'; 
            $params[] = $input['password']; 
        }
        if (isset($input['port'])) { 
            $updates[] = 'port = ?'; 
            $params[] = $input['port']; 
        }
        if (isset($input['description'])) { 
            $updates[] = 'description = ?'; 
            $params[] = $input['description']; 
        }
        
        if (count($updates) > 0) {
            $params[] = $input['id'];
            $sql = "UPDATE mikrotik_devices SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cihaz güncellendi']);
        exit();
    }
    
    // Method not allowed
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    
} catch (Exception $e) {
    error_log("[API:mikrotik-devices] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
