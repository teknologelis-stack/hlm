<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mikrotik.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

$auth->requirePermission('router_config');

$input = json_decode(file_get_contents('php://input'), true);

$name = $input['name'] ?? '';
$password = $input['password'] ?? '';
$service = $input['service'] ?? 'any';
$localAddress = $input['local_address'] ?? '';
$remoteAddress = $input['remote_address'] ?? '';
$profile = $input['profile'] ?? 'default';
$deviceId = $input['device_id'] ?? null;

if (empty($name) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Kullanıcı adı ve şifre gereklidir']);
}

try {
    $mikrotik = new MikroTikHelper($deviceId);
    
    if ($mikrotik->addPPPSecret($name, $password, $service, $localAddress, $remoteAddress, $profile)) {
        logAction('ppp_user_added', "PPP kullanıcı eklendi: {$name}", $mikrotik->device['id'] ?? null);
        jsonResponse([
            'success' => true,
            'message' => 'PPP kullanıcısı başarıyla eklendi'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $mikrotik->error
        ]);
    }
    
} catch (Exception $e) {
    error_log("PPP add error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
