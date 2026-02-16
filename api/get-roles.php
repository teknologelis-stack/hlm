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

$roleId = $_GET['id'] ?? '';

if (empty($roleId)) {
    jsonResponse(['success' => false, 'message' => 'Rol ID gerekli']);
}

$db = Database::getInstance();
$role = $db->fetchOne("SELECT * FROM roles WHERE id = ?", [$roleId]);

if (!$role) {
    jsonResponse(['success' => false, 'message' => 'Rol bulunamadı']);
}

jsonResponse(['success' => true, 'role' => $role]);
?>
