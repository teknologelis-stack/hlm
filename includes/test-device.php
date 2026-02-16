<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lib/RouterOS.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

$ipAddress = $_POST['ip_address'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$port = $_POST['port'] ?? 8728;

if (empty($ipAddress) || empty($username) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Tüm alanlar gereklidir']);
}

$api = new RouterOS();

if (!$api->connect($ipAddress, $port)) {
    jsonResponse(['success' => false, 'message' => 'Bağlantı hatası: ' . $api->error]);
}

if (!$api->login($username, $password)) {
    $api->disconnect();
    jsonResponse(['success' => false, 'message' => 'Giriş başarısız. Kullanıcı adı veya şifre hatalı']);
}

$identityResponse = $api->comm('/system/identity/print');
$resourceResponse = $api->comm('/system/resource/print');

$identity = $api->parseResponse($identityResponse);
$resource = $api->parseResponse($resourceResponse);

$api->disconnect();

if (empty($identity) || empty($resource)) {
    jsonResponse(['success' => false, 'message' => 'Cihaz bilgileri alınamadı']);
}

$deviceInfo = [
    'identity' => $identity[0]['name'] ?? 'Unknown',
    'version' => $resource[0]['version'] ?? 'Unknown',
    'model' => $resource[0]['board-name'] ?? 'Unknown',
    'serial' => $resource[0]['serial-number'] ?? 'Unknown',
    'uptime' => $resource[0]['uptime'] ?? 'Unknown',
    'ram' => isset($resource[0]['total-memory']) ? formatBytes($resource[0]['total-memory']) : 'Unknown'
];

jsonResponse([
    'success' => true,
    'device' => $deviceInfo,
    'message' => 'Bağlantı başarılı'
]);
?>