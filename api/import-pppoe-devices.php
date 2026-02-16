<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $auth->requirePermission('devices_manage');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['devices']) || !is_array($input['devices'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cihaz listesi gereklidir'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Get common credentials
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $port = intval($input['port'] ?? 8728);
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı ve şifre gereklidir'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($input['devices'] as $device) {
        $name = trim($device['name'] ?? '');
        $ipAddress = trim($device['ip_address'] ?? '');
        $deviceType = $device['device_type'] ?? 'other';
        
        // Validation
        if (empty($name) || empty($ipAddress)) {
            $skipped++;
            $errors[] = "Eksik bilgi: $name";
            continue;
        }
        
        // Check if already exists
        $exists = $db->fetchOne(
            "SELECT id FROM devices WHERE ip_address = ? AND is_active = 1", 
            [$ipAddress]
        );
        
        if ($exists) {
            $skipped++;
            $errors[] = "Zaten kayıtlı: $name ($ipAddress)";
            continue;
        }
        
        try {
            $data = [
                'ip_address' => $ipAddress,
                'port' => $port,
                'username' => $username,
                'password' => encrypt($password),
                'name' => $name,
                'device_type' => $deviceType,
                'is_main' => 0,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $deviceId = $db->insert('devices', $data);
            
            if ($deviceId) {
                $imported++;
                
                // Log activity (user_id is guaranteed to exist after auth check)
                $userId = $_SESSION['user_id'] ?? null;
                if ($userId) {
                    logActivity(
                        $userId,
                        'device_imported',
                        "PPPoE'den cihaz eklendi: $name ($ipAddress)"
                    );
                }
                
                error_log("PPPoE Import: Added device $name ($ipAddress)");
            } else {
                $skipped++;
                $errors[] = "Ekleme hatası: $name";
            }
            
        } catch (Exception $e) {
            $skipped++;
            $errors[] = "Hata ($name): " . $e->getMessage();
            error_log("PPPoE Import error: " . $e->getMessage());
        }
    }
    
    $message = "$imported cihaz eklendi";
    if ($skipped > 0) {
        $message .= ", $skipped atlandı";
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'stats' => [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Import PPPoE devices error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Hata: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>