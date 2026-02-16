<?php
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/WinBoxParser.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $auth->requirePermission('devices_manage');
    
    // Dosya yükleme kontrolü
    if (!isset($_FILES['winbox_file'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dosya seçilmedi'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $file = $_FILES['winbox_file'];
    
    // Dosya hatası kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Dosya uzantısı kontrolü
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'wbx') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sadece .WBX dosyaları desteklenir'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Geçici dosyayı parse et
    $devices = WinBoxParser::parse($file['tmp_name']);
    
    if (empty($devices)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dosyada geçerli cihaz bulunamadı'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $db = Database::getInstance();
    $stats = [
        'total' => count($devices),
        'added' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];
    
    // Seçilen grupları al (POST ile gönderilir)
    $selectedGroups = isset($_POST['groups']) ? json_decode($_POST['groups'], true) : null;
    $updateExisting = !empty($_POST['update_existing']) && $_POST['update_existing'] === 'true';
    
    foreach ($devices as $device) {
        // Grup filtresi
        if ($selectedGroups && !in_array($device['group'], $selectedGroups)) {
            $stats['skipped']++;
            continue;
        }
        
        try {
            // Mevcut cihaz kontrolü (IP ile)
            $existing = $db->fetchOne(
                "SELECT id FROM devices WHERE ip_address = ? AND is_active = 1", 
                [$device['ip_address']]
            );
            
            $data = [
                'ip_address' => $device['ip_address'],
                'port' => $device['port'],
                'username' => $device['username'],
                'password' => encrypt($device['password']),
                'name' => $device['name'],
                'is_active' => 1
            ];
            
            if ($existing) {
                if ($updateExisting) {
                    // Güncelle
                    $db->update('devices', $data, 'id = :id', ['id' => $existing['id']]);
                    $stats['updated']++;
                } else {
                    // Atla
                    $stats['skipped']++;
                }
            } else {
                // Yeni ekle
                $data['is_main'] = 0;
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('devices', $data);
                $stats['added']++;
            }
            
        } catch (Exception $e) {
            $stats['errors'][] = [
                'device' => $device['name'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    logActivity($_SESSION['user_id'], 'winbox_import', 
        "WinBox import: {$stats['added']} eklendi, {$stats['updated']} güncellendi, {$stats['skipped']} atlandı"
    );
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Import tamamlandı. {$stats['added']} cihaz eklendi, {$stats['updated']} güncellendi.",
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    error_log("WinBox import error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Import hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
