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

$auth->requirePermission('roles_manage');

$input = json_decode(file_get_contents('php://input'), true);
$roleId = $input['role_id'] ?? null;

if (!$roleId) {
    jsonResponse(['success' => false, 'message' => 'Rol ID gerekli']);
}

$db = Database::getInstance();

$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role_id = ?", [$roleId])['count'];
if ($userCount > 0) {
    jsonResponse(['success' => false, 'message' => 'Bu role atanmış kullanıcılar var, silinemez']);
}

try {
    $role = $db->fetchOne("SELECT name FROM roles WHERE id = ?", [$roleId]);
    $db->delete('roles', 'id = ?', [$roleId]);
    $auth->logActivity($_SESSION['user_id'], 'role_deleted', "Rol silindi: " . $role['name']);
    
    jsonResponse(['success' => true, 'message' => 'Rol başarıyla silindi']);
} catch (Exception $e) {
    error_log("Role delete error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Silme işlemi başarısız']);
}
?>
