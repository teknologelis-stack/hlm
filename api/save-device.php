<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

$db = Database::getInstance();

$deviceName = $_POST['device_name'] ?? '';
$ipAddress = $_POST['ip_address'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$port = $_POST['port'] ?? 8728;
$isMain = isset($_POST['is_main']) ? 1 : 0;
$deviceInfo = json_decode($_POST['device_info'] ?? '{}', true);

if (empty($deviceName) || empty($ipAddress) || empty($username) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Tüm alanlar gereklidir']);
}

try {
    if ($isMain) {
        $db->query("UPDATE devices SET is_main = 0");
    }
    
    $data = [
        'name' => $deviceName,
        'ip_address' => $ipAddress,
        'username' => $username,
        'password' => encrypt($password),
        'port' => $port,
        'is_main' => $isMain,
        'is_active' => 1,
        'identity' => $deviceInfo['identity'] ?? null,
        'routeros_version' => $deviceInfo['version'] ?? null,
        'model' => $deviceInfo['model'] ?? null,
        'serial_number' => $deviceInfo['serial'] ?? null,
        'last_connection' => date('Y-m-d H:i:s')
    ];
    
    $deviceId = $db->insert('devices', $data);
    
    $auth->logActivity($_SESSION['user_id'], 'device_added', "Cihaz eklendi: $deviceName", $deviceId);
    
    jsonResponse(['success' => true, 'message' => 'Cihaz başarıyla kaydedildi', 'device_id' => $deviceId]);
    
} catch (Exception $e) {
    error_log("Device save error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Cihaz kaydedilirken hata oluştu']);
}
?>
