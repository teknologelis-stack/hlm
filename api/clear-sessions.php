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
    $db->query("TRUNCATE TABLE sessions");
    
    $auth->logActivity($_SESSION['user_id'], 'sessions_cleared', 'Tüm oturumlar sonlandırıldı');
    
    jsonResponse(['success' => true, 'message' => 'Tüm oturumlar başarıyla sonlandırıldı']);
} catch (Exception $e) {
    error_log("Clear sessions error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Oturumlar sonlandırılırken hata oluştu']);
}
?>
