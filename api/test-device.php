<?php
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mikrotik.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validasyon
    if (empty($input['ip_address'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IP adresi gereklidir'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($input['username'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gereklidir'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // If device_id provided but no password, fetch from database
    $password = null;
    if (!empty($input['device_id'])) {
        $db = Database::getInstance();
        $device = $db->fetchOne("SELECT password FROM devices WHERE id = ?", [$input['device_id']]);
        if ($device) {
            $password = decrypt($device['password']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cihaz bulunamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else if (!empty($input['password'])) {
        $password = trim($input['password']);
    }
    
    if (empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Şifre gereklidir'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $ip = trim($input['ip_address']);
    $port = !empty($input['port']) ? intval($input['port']) : 8728;
    $username = trim($input['username']);
    
    error_log("Test device: $ip:$port user:$username");

    // MikroTik'e bağlan (Evilfreelancer RouterOS Client)
    try {
        // Config array ile client oluştur
        $client = new \RouterOS\Client([
            'host' => $ip,
            'user' => $username,
            'pass' => $password,
            'port' => $port,
            'timeout' => 5,  // 5 second timeout
            'attempts' => 2, // 2 connection attempts
        ]);
        
        // System resource bilgilerini al
        $query = new \RouterOS\Query('/system/resource/print');
        $response = $client->query($query)->read();
        
        // Identity bilgisi al
        $query2 = new \RouterOS\Query('/system/identity/print');
        $identity = $client->query($query2)->read();
        
        // Board info (may not exist on non-RouterBOARD devices like CHR, x86, Cloud)
        $model = 'Unknown';
        $serial = 'Unknown';
        try {
            $query3 = new \RouterOS\Query('/system/routerboard/print');
            $board = $client->query($query3)->read();
            $model = $board[0]['model'] ?? 'Unknown';
            $serial = $board[0]['serial-number'] ?? 'Unknown';
        } catch (Exception $e) {
            // RouterBOARD info not available on this platform
            error_log("RouterBOARD info not available: " . $e->getMessage());
            $model = 'N/A'; // Not available for non-RouterBOARD devices
            $serial = 'N/A';
        }
        
        $deviceInfo = [
            'identity' => $identity[0]['name'] ?? 'Unknown',
            'version' => $response[0]['version'] ?? 'Unknown',
            'board_name' => $response[0]['board-name'] ?? 'Unknown',
            'model' => $model,
            'serial_number' => $serial,
            'cpu' => $response[0]['cpu'] ?? 'Unknown',
            'uptime' => $response[0]['uptime'] ?? 'Unknown',
            'architecture' => $response[0]['architecture-name'] ?? 'Unknown',
            'cpu_load' => $response[0]['cpu-load'] ?? 0,
            'memory_total' => $response[0]['total-memory'] ?? 0,
            'memory_free' => $response[0]['free-memory'] ?? 0,
            'free_memory' => formatBytes($response[0]['free-memory'] ?? 0),
            'total_memory' => formatBytes($response[0]['total-memory'] ?? 0),
        ];
        
        error_log("Test successful: " . $deviceInfo['identity']);
        
        // Update database if device_id is provided
        if (!empty($input['device_id'])) {
            try {
                $db = Database::getInstance();
                $db->update('devices', [
                    'identity' => $deviceInfo['identity'],
                    'routeros_version' => $deviceInfo['version'],
                    'board_name' => $deviceInfo['board_name'],
                    'model' => $deviceInfo['model'],
                    'serial_number' => $deviceInfo['serial_number'],
                    'cpu_load' => $deviceInfo['cpu_load'],
                    'uptime' => $deviceInfo['uptime'],
                    'memory_total' => $deviceInfo['memory_total'],
                    'memory_free' => $deviceInfo['memory_free'],
                    'last_seen' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $input['device_id']]);
                error_log("Device info updated in database for device ID: " . $input['device_id']);
            } catch (Exception $e) {
                error_log("Failed to update device info in database: " . $e->getMessage());
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bağlantı başarılı!',
            'device' => $deviceInfo
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (\RouterOS\Exceptions\ClientException $e) {
        error_log("RouterOS Client error: " . $e->getMessage());
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'Bağlantı hatası: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (\RouterOS\Exceptions\QueryException $e) {
        error_log("RouterOS Query error: " . $e->getMessage());
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'Sorgu hatası: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Test device error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
