<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
    }
    
    $auth->requirePermission('devices_manage');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validasyon
    if (empty($input['ip_address'])) {
        jsonResponse(['success' => false, 'message' => 'IP adresi gereklidir']);
    }
    
    if (empty($input['username'])) {
        jsonResponse(['success' => false, 'message' => 'Kullanıcı adı gereklidir']);
    }
    
    if (empty($input['password'])) {
        jsonResponse(['success' => false, 'message' => 'Şifre gereklidir']);
    }
    
    if (empty($input['name'])) {
        jsonResponse(['success' => false, 'message' => 'Cihaz adı gereklidir']);
    }
    
    $db = Database::getInstance();
    
    // DUPLICATE KONTROLÜ - IP Adresi
    $existingIP = $db->fetchOne("SELECT id FROM devices WHERE ip_address = ? AND is_active = 1", [$input['ip_address']]);
    if ($existingIP) {
        jsonResponse(['success' => false, 'message' => 'Bu IP adresi zaten kayıtlı']);
    }
    
    // DUPLICATE KONTROLÜ - Cihaz Adı
    $existingName = $db->fetchOne("SELECT id FROM devices WHERE name = ? AND is_active = 1", [$input['name']]);
    if ($existingName) {
        jsonResponse(['success' => false, 'message' => 'Bu cihaz adı zaten kullanılıyor']);
    }
    
    // Port default 8728
    $port = !empty($input['port']) ? intval($input['port']) : 8728;
    
    // Device type validation
    $validTypes = ['router', 'switch', 'ap', 'other'];
    $deviceType = !empty($input['device_type']) && in_array($input['device_type'], $validTypes) 
        ? $input['device_type'] 
        : 'other';
    
    $data = [
        'ip_address' => trim($input['ip_address']),
        'port' => $port,
        'username' => trim($input['username']),
        'password' => encrypt(trim($input['password'])),
        'name' => trim($input['name']),
        'device_type' => $deviceType,
        'is_main' => !empty($input['is_main']) ? 1 : 0,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Eğer ana cihaz seçildiyse diğerlerini pasif yap
    if ($data['is_main']) {
        $db->query("UPDATE devices SET is_main = 0");
    }
    
    $deviceId = $db->insert('devices', $data);
    
    logActivity($_SESSION['user_id'], 'device_added', 
        "Yeni cihaz eklendi: {$input['name']} ({$input['ip_address']})"
    );
    
    // Eklenen cihazı döndür
    $newDevice = $db->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
    
    jsonResponse([
        'success' => true, 
        'message' => 'Cihaz başarıyla eklendi',
        'device' => $newDevice
    ]);
    
} catch (Exception $e) {
    error_log("Add device error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
