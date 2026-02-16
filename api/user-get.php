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

$userId = $_GET['id'] ?? '';

if (empty($userId)) {
    jsonResponse(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
}

$db = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    jsonResponse(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
}

jsonResponse(['success' => true, 'user' => $user]);
?>
