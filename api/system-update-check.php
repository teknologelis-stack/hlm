<?php
// Hataları logla, ekrana gösterme (JSON bozulmasın)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = new Auth();
    
    if (!$auth->isLoggedIn()) {
        jsonResponse([
            'success' => false,
            'message' => 'Oturum geçersiz'
        ], 401);
    }

    $updateManager = new UpdateManager();
    $result = $updateManager->checkForUpdates();

    jsonResponse($result, $result['success'] ? 200 : 500);

} catch (Exception $e) {
    logError('Update check error: ' . $e->getMessage(), [
        'file' => __FILE__,
        'trace' => $e->getTraceAsString()
    ]);
    
    jsonResponse([
        'success' => false,
        'message' => 'Güncelleme kontrolü başarısız',
        'error' => $e->getMessage()
    ], 500);
}
