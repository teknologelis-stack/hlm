<?php
/**
 * Yedek Dosyası İndirme API
 * 
 * Method: GET
 * Permission: system_manage
 * Query: ?id=backup_id
 */

require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $auth = new Auth();
    
    // Oturum kontrolü
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        die('Oturum geçersiz');
    }
    
    // Yetki kontrolü
    if (!$auth->hasPermission('system_manage')) {
        http_response_code(403);
        die('Bu işlem için yetkiniz yok');
    }
    
    // Backup ID kontrolü
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        die('Geçersiz yedek ID');
    }
    
    $backupId = intval($_GET['id']);
    $db = Database::getInstance();
    
    // Yedek kaydını getir
    $backup = $db->fetchOne(
        "SELECT * FROM system_backups WHERE id = ?",
        [$backupId]
    );
    
    if (!$backup) {
        http_response_code(404);
        die('Yedek bulunamadı');
    }
    
    // Dosya var mı kontrol et
    if (!file_exists($backup['file_path'])) {
        http_response_code(404);
        die('Yedek dosyası bulunamadı');
    }
    
    // Dosyayı indir
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $backup['backup_name'] . '"');
    header('Content-Length: ' . $backup['file_size']);
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($backup['file_path']);
    exit;
    
} catch (Exception $e) {
    error_log("Download backup error: " . $e->getMessage());
    http_response_code(500);
    die('Sistem hatası: ' . $e->getMessage());
}
?>
