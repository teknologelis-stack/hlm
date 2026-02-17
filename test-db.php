<?php
/**
 * Database Connection Test
 * Tests MySQL connection and basic database operations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== HLM Database Connection Test ===\n\n";

// Test 1: Include config
echo "1. Loading configuration...\n";
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
    echo "   ✓ Connected to database\n";
    echo "   - Driver: {$driver}\n";
    
    // Get database name
    $stmt = $db->query("SELECT DATABASE() as dbname");
    $result = $stmt->fetch();
    echo "   - Database: {$result['dbname']}\n\n";
    
    if ($driver !== 'mysql') {
        echo "   ⚠ WARNING: Expected MySQL driver, got: {$driver}\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check tables exist
echo "3. Checking required tables...\n";
$requiredTables = ['roles', 'users', 'settings', 'system_updates', 'system_backups'];
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "   ✓ Table '{$table}' exists\n";
        } else {
            echo "   ✗ Table '{$table}' MISSING\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to check tables: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check system_updates columns
echo "4. Checking system_updates table schema...\n";
try {
    $stmt = $db->query("DESCRIBE system_updates");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'version', 'status', 'changelog', 'download_url', 'backup_id', 'error_message'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✓ Column '{$col}' exists\n";
        } else {
            echo "   ✗ Column '{$col}' MISSING\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to check system_updates schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Check system_backups columns
echo "5. Checking system_backups table schema...\n";
try {
    $stmt = $db->query("DESCRIBE system_backups");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'filename', 'filepath', 'backup_type', 'size_bytes'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✓ Column '{$col}' exists\n";
        } else {
            echo "   ✗ Column '{$col}' MISSING\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to check system_backups schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Check admin user
echo "6. Checking admin user...\n";
try {
    $stmt = $db->prepare("SELECT id, username, email, is_active FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "   ✓ Admin user found\n";
        echo "   - ID: {$user['id']}\n";
        echo "   - Username: {$user['username']}\n";
        echo "   - Email: {$user['email']}\n";
        echo "   - Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ✗ Admin user not found\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to check admin user: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== All Tests Completed ===\n";
echo "Database connection test: SUCCESS\n";
