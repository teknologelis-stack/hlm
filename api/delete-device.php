<?php
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
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
    
    if (empty($input['device_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cihaz ID gerekli'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $deviceId = intval($input['device_id']);
    
    $db = Database::getInstance();
    
    // Cihaz kontrolü
    $device = $db->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cihaz bulunamadı'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Ana cihaz silinemesin
    if ($device['is_main'] == 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ana cihaz silinemez'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Cihazı sil (fiziksel silme)
    // Note: Related logs will have device_id set to NULL due to ON DELETE SET NULL constraint
    $deleted = $db->delete('devices', 'id = :id', ['id' => $deviceId]);
    
    if ($deleted) {
        logActivity($_SESSION['user_id'], 'device_delete', "Cihaz silindi: {$device['name']} ({$device['ip_address']})");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Cihaz başarıyla silindi'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Silme işlemi başarısız'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Delete device error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
