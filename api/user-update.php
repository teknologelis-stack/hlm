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

$userId = $_POST['user_id'] ?? '';
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$roleId = $_POST['role_id'] ?? '';
$isActive = isset($_POST['is_active']) ? 1 : 0;

if (empty($userId) || empty($username) || empty($email) || empty($roleId)) {
    jsonResponse(['success' => false, 'message' => 'Tüm alanlar gereklidir']);
}

$db = Database::getInstance();

try {
    $data = [
        'username' => $username,
        'email' => $email,
        'role_id' => $roleId,
        'is_active' => $isActive
    ];
    
    $db->update('users', $data, 'id = :id', ['id' => $userId]);
    $auth->logActivity($_SESSION['user_id'], 'user_updated', "Kullanıcı güncellendi: $username");
    
    jsonResponse(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi']);
} catch (Exception $e) {
    error_log("User update error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Kullanıcı güncellenirken hata oluştu']);
}
?>
