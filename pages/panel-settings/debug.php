<?php
session_start();

// Root path tanımla
$rootPath = dirname(dirname(dirname(__FILE__)));

// Require authentication
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/app.php';
require_once $rootPath . '/includes/auth.php';

$auth = new Auth();
$auth->requireLogin();
// Only admin can access debug page
$auth->requirePermission('settings_manage');

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Test</h1>";
echo "<p><strong>⚠️ Bu sayfa sadece yetkili kullanıcılar tarafından erişilebilir.</strong></p>";

echo "<h2>1. PHP Version</h2>";
echo phpversion();

echo "<h2>2. Root Path</h2>";
echo $rootPath;

echo "<h2>3. File Checks</h2>";
$files = [
    '/config/database.php',
    '/config/app.php',
    '/includes/auth.php',
    '/includes/functions.php'
];

foreach ($files as $file) {
    $fullPath = $rootPath . $file;
    echo $file . ': ' . (file_exists($fullPath) ? '✅ EXISTS' : '❌ NOT FOUND') . '<br>';
}

echo "<h2>4. Try Loading Files</h2>";
try {
    require_once $rootPath . '/config/database.php';
    echo "database.php: ✅ OK<br>";
} catch (Exception $e) {
    echo "database.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once $rootPath . '/config/app.php';
    echo "app.php: ✅ OK<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "app.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once $rootPath . '/includes/functions.php';
    echo "functions.php: ✅ OK<br>";
    
    // Test new functions
    echo "<h3>Function Tests:</h3>";
    echo "isValidIP('192.168.1.1'): " . (isValidIP('192.168.1.1') ? '✅ TRUE' : '❌ FALSE') . "<br>";
    echo "isValidMAC('00:11:22:33:44:55'): " . (isValidMAC('00:11:22:33:44:55') ? '✅ TRUE' : '❌ FALSE') . "<br>";
    echo "formatBytes(1048576): " . formatBytes(1048576) . "<br>";
} catch (Exception $e) {
    echo "functions.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once $rootPath . '/includes/auth.php';
    echo "auth.php: ✅ OK (already loaded)<br>";
} catch (Exception $e) {
    echo "auth.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

echo "<h2>5. Database Connection</h2>";
try {
    $db = Database::getInstance();
    echo "✅ Database connection OK<br>";
} catch (Exception $e) {
    echo "❌ Database ERROR: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Completed</h2>";
?>
