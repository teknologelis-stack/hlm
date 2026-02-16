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

$auth->requirePermission('users_manage');

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? null;

if (!$userId) {
    jsonResponse(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
}

if ($userId == $_SESSION['user_id']) {
    jsonResponse(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz']);
}

$db = Database::getInstance();

try {
    $user = $db->fetchOne("SELECT username FROM users WHERE id = ?", [$userId]);
    $db->delete('users', 'id = ?', [$userId]);
    $auth->logActivity($_SESSION['user_id'], 'user_deleted', "Kullanıcı silindi: " . $user['username']);
    
    jsonResponse(['success' => true, 'message' => 'Kullanıcı başarıyla silindi']);
} catch (Exception $e) {
    error_log("User delete error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Silme işlemi başarısız']);
}
?>
