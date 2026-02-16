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

$roleId = $_POST['role_id'] ?? '';
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$permissions = $_POST['permissions'] ?? [];

if (empty($roleId) || empty($name)) {
    jsonResponse(['success' => false, 'message' => 'Rol ID ve adı gereklidir']);
}

$permissionsArray = [
    'dashboard_view' => isset($permissions['dashboard_view']),
    'users_manage' => isset($permissions['users_manage']),
    'devices_manage' => isset($permissions['devices_manage']),
    'router_config' => isset($permissions['router_config']),
    'logs_view' => isset($permissions['logs_view']),
    'settings_manage' => isset($permissions['settings_manage']),
    'roles_manage' => isset($permissions['roles_manage'])
];

$db = Database::getInstance();

try {
    $data = [
        'name' => $name,
        'description' => $description,
        'permissions' => json_encode($permissionsArray)
    ];
    
    $db->update('roles', $data, 'id = :id', ['id' => $roleId]);
    $auth->logActivity($_SESSION['user_id'], 'role_updated', "Rol güncellendi: $name");
    
    jsonResponse(['success' => true, 'message' => 'Rol başarıyla güncellendi']);
} catch (Exception $e) {
    error_log("Role update error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Rol güncellenirken hata oluştu']);
}
?>
