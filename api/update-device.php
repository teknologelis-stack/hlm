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

$auth->requirePermission('devices_manage');

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['device_id'])) {
    jsonResponse(['success' => false, 'message' => 'Device ID gerekli']);
}

$db = Database::getInstance();

try {
    $deviceId = $input['device_id'];
    $isMain = isset($input['is_main']) && $input['is_main'] ? 1 : 0;
    
    // If setting as main device, unset other main devices
    if ($isMain) {
        $db->query("UPDATE devices SET is_main = 0");
    }
    
    // Device type validation
    $validTypes = ['router', 'switch', 'ap', 'other'];
    $deviceType = !empty($input['device_type']) && in_array($input['device_type'], $validTypes) 
        ? $input['device_type'] 
        : null;
    
    $deviceData = [
        'name' => $input['name'],
        'ip_address' => $input['ip_address'],
        'username' => $input['username'],
        'port' => $input['port'] ?? 8728,
        'is_main' => $isMain
    ];
    
    // Only update device_type if provided
    if ($deviceType !== null) {
        $deviceData['device_type'] = $deviceType;
    }
    
    // Only update password if provided
    if (!empty($input['password'])) {
        $deviceData['password'] = encrypt($input['password']);
    }
    
    $updated = $db->update('devices', $deviceData, 'id = :id', ['id' => $deviceId]);
    
    if ($updated) {
        $auth->logActivity($_SESSION['user_id'], 'device_updated', "Cihaz güncellendi: " . $input['name']);
        jsonResponse([
            'success' => true, 
            'message' => 'Cihaz başarıyla güncellendi'
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cihaz güncellenirken hata oluştu']);
    }
} catch (Exception $e) {
    error_log("Update device error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Cihaz güncellenirken hata oluştu']);
}
?>
