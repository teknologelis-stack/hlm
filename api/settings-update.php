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

$auth->requirePermission('settings_manage');

$db = Database::getInstance();

try {
    if (isset($_POST['panel_name'])) {
        $db->update('settings', ['setting_value' => $_POST['panel_name']], 'setting_key = :key', ['key' => 'panel_name']);
    }
    
    if (isset($_POST['session_timeout'])) {
        $db->update('settings', ['setting_value' => $_POST['session_timeout']], 'setting_key = :key', ['key' => 'session_timeout']);
    }
    
    if (isset($_POST['records_per_page'])) {
        $db->update('settings', ['setting_value' => $_POST['records_per_page']], 'setting_key = :key', ['key' => 'records_per_page']);
    }
    
    if (isset($_POST['theme'])) {
        $db->update('settings', ['setting_value' => $_POST['theme']], 'setting_key = :key', ['key' => 'theme']);
    }
    
    if (isset($_POST['language'])) {
        $db->update('settings', ['setting_value' => $_POST['language']], 'setting_key = :key', ['key' => 'language']);
    }
    
    if (isset($_POST['max_login_attempts'])) {
        $db->update('settings', ['setting_value' => $_POST['max_login_attempts']], 'setting_key = :key', ['key' => 'max_login_attempts']);
    }
    
    if (isset($_POST['login_lockout_time'])) {
        $db->update('settings', ['setting_value' => $_POST['login_lockout_time']], 'setting_key = :key', ['key' => 'login_lockout_time']);
    }
    
    if (isset($_POST['main_device'])) {
        $db->query("UPDATE devices SET is_main = 0");
        $db->update('devices', ['is_main' => 1], 'id = :id', ['id' => $_POST['main_device']]);
    }
    
    $auth->logActivity($_SESSION['user_id'], 'settings_updated', 'Panel ayarları güncellendi');
    
    jsonResponse(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi']);
    
} catch (Exception $e) {
    error_log("Settings update error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Ayarlar kaydedilirken hata oluştu']);
}
?>
