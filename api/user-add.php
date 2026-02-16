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

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$roleId = $_POST['role_id'] ?? '';
$isActive = isset($_POST['is_active']) ? 1 : 0;

if (empty($username) || empty($email) || empty($password) || empty($roleId)) {
    jsonResponse(['success' => false, 'message' => 'Tüm alanlar gereklidir']);
}

$db = Database::getInstance();

$exists = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
if ($exists) {
    jsonResponse(['success' => false, 'message' => 'Kullanıcı adı veya e-posta zaten kullanılıyor']);
}

try {
    $data = [
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role_id' => $roleId,
        'is_active' => $isActive
    ];
    
    $userId = $db->insert('users', $data);
    $auth->logActivity($_SESSION['user_id'], 'user_added', "Kullanıcı eklendi: $username");
    
    jsonResponse(['success' => true, 'message' => 'Kullanıcı başarıyla eklendi', 'user_id' => $userId]);
} catch (Exception $e) {
    error_log("User add error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Kullanıcı eklenirken hata oluştu']);
}
?>
