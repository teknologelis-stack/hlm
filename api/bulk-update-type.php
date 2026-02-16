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
        jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
    }
    
    $auth->requirePermission('devices_manage');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validasyon
    if (empty($input['device_ids']) || !is_array($input['device_ids'])) {
        jsonResponse(['success' => false, 'message' => 'Cihaz ID listesi gereklidir']);
    }
    
    if (empty($input['device_type'])) {
        jsonResponse(['success' => false, 'message' => 'Cihaz türü gereklidir']);
    }
    
    $validTypes = ['router', 'switch', 'ap', 'other'];
    if (!in_array($input['device_type'], $validTypes)) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz cihaz türü']);
    }
    
    $db = Database::getInstance();
    
    $deviceIds = array_map('intval', $input['device_ids']);
    $deviceType = $input['device_type'];
    
    $updated = 0;
    
    foreach ($deviceIds as $deviceId) {
        try {
            $result = $db->update(
                'devices',
                ['device_type' => $deviceType],
                'id = :id AND is_active = 1',
                ['id' => $deviceId]
            );
            
            if ($result) {
                $updated++;
            }
        } catch (Exception $e) {
            error_log("Bulk update type error for device $deviceId: " . $e->getMessage());
        }
    }
    
    if ($updated > 0) {
        logActivity(
            $_SESSION['user_id'], 
            'devices_type_updated', 
            "$updated cihazın türü '$deviceType' olarak güncellendi"
        );
    }
    
    jsonResponse([
        'success' => true,
        'message' => "$updated cihaz güncellendi",
        'updated' => $updated
    ]);
    
} catch (Exception $e) {
    error_log("Bulk update type error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
