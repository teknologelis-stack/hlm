<?php
// Debug Mode - Set to false in production!
if (!defined('DEBUG_MODE')) {
    // Check if environment variable exists, otherwise default to true for development
    define('DEBUG_MODE', getenv('DEBUG_MODE') === 'false' ? false : true);
}

// Session settings - Must be set BEFORE session_start()
@ini_set('session.cookie_httponly', 1);
@ini_set('session.use_only_cookies', 1);
@ini_set('session.cookie_secure', 0);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    @ini_set('display_errors', 1);
    @ini_set('log_errors', 1);
    
    // Log dosyası yolu
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    @ini_set('error_log', $logDir . '/php_errors.log');
} else {
    error_reporting(0);
    @ini_set('display_errors', 0);
    @ini_set('log_errors', 1);
}

date_default_timezone_set('Europe/Istanbul');

define('APP_NAME', 'MikroTik Yönetim Paneli');
define('APP_VERSION', '1.0.0');

// BASE_URL - /hlm klasörü olmadan çalışacak şekilde
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Script path'i al ve normalize et
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // Clean subdirectories (pages, api, etc.)
    $cleanPath = $scriptPath;
    $pagesPos = strpos($scriptPath, '/pages');
    $apiPos = strpos($scriptPath, '/api');
    $configPos = strpos($scriptPath, '/config');
    $includesPos = strpos($scriptPath, '/includes');
    
    if ($pagesPos !== false) {
        $cleanPath = substr($scriptPath, 0, $pagesPos);
    } elseif ($apiPos !== false) {
        $cleanPath = substr($scriptPath, 0, $apiPos);
    } elseif ($configPos !== false) {
        $cleanPath = substr($scriptPath, 0, $configPos);
    } elseif ($includesPos !== false) {
        $cleanPath = substr($scriptPath, 0, $includesPos);
    }
    
    // Remove /hlm if present (for migration period)
    $cleanPath = str_replace('/hlm', '', $cleanPath);
    
    $base = rtrim($cleanPath, '/');
    
    define('BASE_URL', $protocol . '://' . $host . $base);
}

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5242880);

define('ENCRYPTION_KEY', 'your-secret-encryption-key-change-this-now');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

define('ITEMS_PER_PAGE', 25);
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 600);
define('DEVICE_ONLINE_THRESHOLD_MINUTES', 5); // Minutes before a device is considered offline

function encrypt($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt($data) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    if (strpos($url, 'http') === 0) {
        header("Location: " . $url);
    } else {
        header("Location: " . BASE_URL . "/" . $url);
    }
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>