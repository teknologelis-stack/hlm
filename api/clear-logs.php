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
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM logs")['count'];
    $db->query("TRUNCATE TABLE logs");
    
    $auth->logActivity($_SESSION['user_id'], 'logs_cleared', "$count adet log silindi");
    
    jsonResponse(['success' => true, 'message' => "$count adet log başarıyla temizlendi"]);
} catch (Exception $e) {
    error_log("Clear logs error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Loglar temizlenirken hata oluştu']);
}
?>
