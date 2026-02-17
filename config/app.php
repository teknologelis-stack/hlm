<?php
/**
 * Application Configuration
 * HLM - MikroTik Yönetim Paneli
 */

// Uygulama Bilgileri
define('APP_NAME', 'MikroTik Yönetim Paneli');
define('APP_VERSION', '1.0.0');

// URL Yapılandırması
// Not: Proje doğrudan htdocs içinde, alt klasör yok
define('BASE_URL', 'http://localhost');

// Yol Yapılandırması
define('ROOT_PATH', __DIR__ . '/..');  // htdocs klasörü
define('CONFIG_PATH', __DIR__);
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('API_PATH', ROOT_PATH . '/api');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Çalışma Klasörleri
define('TEMP_PATH', ROOT_PATH . '/temp');
define('BACKUPS_PATH', ROOT_PATH . '/backups');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Zaman Ayarları
date_default_timezone_set('Europe/Istanbul');

// Session Güvenliği
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Hata Raporlama (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// GitHub Yapılandırması
define('GITHUB_REPO_OWNER', 'teknologelis-stack');
define('GITHUB_REPO_NAME', 'hlm');
define('GITHUB_API_BASE', 'https://api.github.com');

// Güncelleme Ayarları
define('UPDATE_CHECK_INTERVAL', 86400); // 24 saat (saniye cinsinden)
define('AUTO_BACKUP_BEFORE_UPDATE', true);
define('MAX_BACKUP_AGE_DAYS', 30);

// Exclude Patterns (güncelleme sırasında dokunulmayacak)
define('UPDATE_EXCLUDE_PATTERNS', [
    'config/database.php',
    'config/app.php',  // Güvenlik için
    '.git/',
    '.gitignore',
    'backups/',
    'temp/',
    'logs/',
    'uploads/',
    '.env',
    '.htaccess'
]);

/**
 * Klasörlerin varlığını kontrol et ve oluştur
 */
function ensureDirectories() {
    $dirs = [
        TEMP_PATH,
        BACKUPS_PATH,
        LOGS_PATH,
        UPLOADS_PATH
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            // .gitkeep oluştur
            file_put_contents($dir . '/.gitkeep', '');
        }
    }
}

// Klasörleri otomatik oluştur
ensureDirectories();

/**
 * JSON Response Helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Log Helper
 */
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    
    error_log($logMessage . PHP_EOL, 3, LOGS_PATH . '/app.log');
}

/**
 * Debug Helper
 */
function debugLog($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] DEBUG: $message";
        
        if ($data !== null) {
            $logMessage .= PHP_EOL . print_r($data, true);
        }
        
        error_log($logMessage . PHP_EOL, 3, LOGS_PATH . '/debug.log');
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
