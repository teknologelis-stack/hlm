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

$auth->requirePermission('settings_manage');

$db = Database::getInstance();

try {
    $db->query("TRUNCATE TABLE logs");
    $db->query("TRUNCATE TABLE sessions");
    $db->query("DELETE FROM users WHERE id != 1");
    $db->query("DELETE FROM devices");
    
    $auth->logActivity($_SESSION['user_id'], 'database_reset', 'Veritabanı sıfırlandı');
    
    jsonResponse(['success' => true, 'message' => 'Veritabanı başarıyla sıfırlandı']);
} catch (Exception $e) {
    error_log("Reset database error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Veritabanı sıfırlanırken hata oluştu']);
}
?>
