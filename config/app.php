<?php
/**
 * Application Configuration
 */

// Application constants
define('APP_NAME', 'HLM - MikroTik Panel');
define('APP_VERSION', '1.0.0');

// Base URL - Auto-detect (always points to application root)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('BASE_URL', $protocol . '://' . $host);

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('BACKUPS_PATH', ROOT_PATH . '/backups');
define('TEMP_PATH', ROOT_PATH . '/temp');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
