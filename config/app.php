<?php
/**
 * Application Configuration
 */

// Application constants
define('APP_NAME', 'HLM - MikroTik Panel');
define('APP_VERSION', '1.0.2');

// Base URL - Auto-detect
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detect base directory from SCRIPT_NAME
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$baseDir = '';

// If script is not in root, extract the directory
if (strpos($scriptName, '/') !== false) {
    $parts = explode('/', $scriptName);
    array_pop($parts); // Remove script filename
    
    // Check if we're in a subdirectory (not root, pages, api, etc.)
    $dir = implode('/', $parts);
    if ($dir !== '' && !in_array(basename($dir), ['pages', 'api', 'includes'])) {
        $baseDir = $dir;
    }
}

define('BASE_URL', $protocol . '://' . $host . $baseDir);

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('BACKUPS_PATH', ROOT_PATH . '/backups');
define('TEMP_PATH', ROOT_PATH . '/temp');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Settings
define('DEBUG_MODE', false); // Set to true only during development/migration
define('ALLOW_PLAIN_TEXT_PASSWORD_MIGRATION', false); // Set to true only during password migration

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/error.log');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
