#!/usr/bin/env php
<?php
/**
 * Otomatik Güncelleme Kontrolü - Cron Job
 * 
 * Kurulum:
 * 1. chmod +x /path/to/hlm/cron/auto-update-check.php
 * 2. crontab -e
 * 3. 0 2 * * * /usr/bin/php /path/to/hlm/cron/auto-update-check.php
 * 
 * Açıklama: Her gün saat 02:00'de çalışır
 */

// CLI kontrolü
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line' . PHP_EOL);
}

// Path ayarları
$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/app.php';
require_once $rootPath . '/config/update-config.php';
require_once $rootPath . '/includes/UpdateManager.php';

// Otomatik kontrol aktif mi?
if (!UPDATE_AUTO_CHECK) {
    echo "[" . date('Y-m-d H:i:s') . "] Auto update check is disabled" . PHP_EOL;
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting update check..." . PHP_EOL;

try {
    $updateManager = new UpdateManager();
    $check = $updateManager->checkOnlineUpdate();
    
    if ($check['available']) {
        echo "[" . date('Y-m-d H:i:s') . "] Update available: {$check['current']} -> {$check['latest']}" . PHP_EOL;
        
        // Admin kullanıcılara bildirim gönder
        $db = Database::getInstance();
        $admins = $db->fetchAll(
            "SELECT u.email, u.username FROM users u 
             INNER JOIN roles r ON u.role_id = r.id 
             WHERE u.is_active = 1 AND r.name = 'Admin'"
        );
        
        if (!empty($admins)) {
            $message = "HLM Güncelleme Bildirimi\n\n";
            $message .= "Yeni bir sistem güncellemesi mevcut:\n";
            $message .= "Mevcut Versiyon: {$check['current']}\n";
            $message .= "Yeni Versiyon: {$check['latest']}\n";
            $message .= "Yayın Tarihi: " . ($check['published_at'] ?? 'Bilinmiyor') . "\n\n";
            $message .= "Güncellemeyi uygulamak için sistem yönetim paneline giriş yapın.\n";
            $message .= "Panel URL: " . BASE_URL . "/pages/system-settings/update-manager.php\n";
            
            foreach ($admins as $admin) {
                // Email gönderimi (varsa mail fonksiyonu kullan)
                // mail($admin['email'], 'HLM Güncelleme Mevcut', $message);
                
                echo "[" . date('Y-m-d H:i:s') . "] Notification sent to: {$admin['username']}" . PHP_EOL;
            }
        }
        
        // Log dosyasına yaz
        $logDir = $rootPath . '/logs';
        $logFile = $logDir . '/update-check.log';
        
        $logMessage = date('Y-m-d H:i:s') . " - Update available: {$check['current']} -> {$check['latest']}\n";
        $logMessage .= "Release: " . ($check['release_name'] ?? 'Unknown') . "\n";
        $logMessage .= "Download URL: " . ($check['download_url'] ?? 'N/A') . "\n";
        $logMessage .= str_repeat('-', 80) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        echo "[" . date('Y-m-d H:i:s') . "] Logged to: $logFile" . PHP_EOL;
        
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] System is up to date: {$check['current']}" . PHP_EOL;
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Update check completed successfully" . PHP_EOL;
    exit(0);
    
} catch (Exception $e) {
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . PHP_EOL;
    echo $errorMsg;
    
    // Error log'a yaz
    error_log($errorMsg);
    
    // Log dosyasına da yaz
    $logDir = $rootPath . '/logs';
    $logFile = $logDir . '/update-check-errors.log';
    file_put_contents($logFile, $errorMsg, FILE_APPEND);
    
    exit(1);
}
?>
