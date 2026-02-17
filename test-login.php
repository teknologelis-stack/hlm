<?php
/**
 * Login Test Script
 * Tests database connection, user retrieval, and password verification
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== HLM Login Test ===\n\n";

// Test 1: Include required files
echo "1. Loading configuration files...\n";
try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/app.php';
    echo "   ✓ Configuration files loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed to load configuration: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Database connection
echo "2. Testing database connection...\n";
try {
    $db = Database::getInstance()->getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "   ✓ Connected to database (Driver: {$driver})\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check users table
echo "3. Checking users table...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "   ✓ Users table exists with {$result['count']} users\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed to query users table: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Find admin user
echo "4. Finding admin user...\n";
try {
    $stmt = $db->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.username = ?
    ");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "   ✓ Admin user found\n";
        echo "   - ID: {$user['id']}\n";
        echo "   - Username: {$user['username']}\n";
        echo "   - Email: {$user['email']}\n";
        echo "   - Role: {$user['role_name']}\n";
        echo "   - Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        echo "   - Password hash: {$user['password']}\n\n";
    } else {
        echo "   ✗ Admin user not found!\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Failed to find admin user: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verify password
echo "5. Testing password verification...\n";
$testPassword = 'admin';
echo "   Testing password: '{$testPassword}'\n";

// Test bcrypt
$bcryptResult = password_verify($testPassword, $user['password']);
echo "   - BCrypt verification: " . ($bcryptResult ? "✓ SUCCESS" : "✗ FAILED") . "\n";

// Test plain text
$plainTextResult = ($testPassword === $user['password']);
echo "   - Plain text match: " . ($plainTextResult ? "✓ MATCHES (INSECURE!)" : "✗ No match") . "\n\n";

// Test 6: Test Auth class
echo "6. Testing Auth class...\n";
try {
    require_once __DIR__ . '/includes/auth.php';
    
    // Start a clean session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    session_start();
    
    $auth = new Auth();
    echo "   ✓ Auth class instantiated\n";
    
    // Try login
    echo "   Attempting login with username='admin', password='admin'...\n";
    $loginResult = $auth->login('admin', 'admin');
    
    if ($loginResult) {
        echo "   ✓ LOGIN SUCCESSFUL!\n";
        echo "   - Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
        echo "   - Session username: " . ($_SESSION['username'] ?? 'not set') . "\n";
        echo "   - Session role_name: " . ($_SESSION['role_name'] ?? 'not set') . "\n";
        echo "   - Session logged_in: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'true' : 'false') . "\n";
    } else {
        echo "   ✗ LOGIN FAILED!\n";
        echo "   Please check the error_log for details.\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Auth test failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Summary
echo "===========================================\n";
echo "Test Summary:\n";
echo "===========================================\n";
if ($loginResult) {
    echo "✓ All tests passed!\n";
    echo "✓ Login system is working correctly.\n";
    echo "\nYou can now login with:\n";
    echo "  Username: admin\n";
    echo "  Password: admin\n";
} else {
    echo "✗ Login test failed!\n";
    echo "\nPossible issues:\n";
    echo "1. Password hash mismatch\n";
    echo "2. User is inactive\n";
    echo "3. Database connection issues\n";
    echo "\nCheck the error_log file in logs/ directory for details.\n";
}
echo "===========================================\n";
