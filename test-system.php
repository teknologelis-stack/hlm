<?php
/**
 * System Test - Verify Database and Update System Configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== HLM System Test ===\n\n";

// Test 1: Check required files
echo "1. Checking Required Files...\n";
$requiredFiles = [
    'config/database.php',
    'config/app.php',
    'includes/UpdateManager.php',
    'includes/GitHubUpdateService.php',
    'config/migrations/init.sql',
    'config/migrations/fix_update_tables.sql'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file - MISSING\n";
    }
}

// Test 2: Check required directories
echo "\n2. Checking Required Directories...\n";
$requiredDirs = ['temp', 'backups', 'logs'];

foreach ($requiredDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path) ? 'writable' : 'NOT writable';
        echo "   ✅ $dir/ - $writable\n";
    } else {
        echo "   ❌ $dir/ - MISSING\n";
    }
}

// Test 3: Check constants
echo "\n3. Checking Constants...\n";
require_once __DIR__ . '/config/app.php';

$requiredConstants = ['ROOT_PATH', 'TEMP_PATH', 'BACKUPS_PATH', 'LOGS_PATH', 'APP_VERSION'];
foreach ($requiredConstants as $const) {
    if (defined($const)) {
        echo "   ✅ $const = " . constant($const) . "\n";
    } else {
        echo "   ❌ $const - NOT DEFINED\n";
    }
}

// Test 4: Database connection
echo "\n4. Testing Database Connection...\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    
    // Get database name
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "   ✅ Connected to database: $dbName\n";
    
    // Check driver
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "   ✅ PDO Driver: $driver\n";
    
    if ($driver !== 'mysql') {
        echo "   ⚠️  WARNING: Should be using MySQL, not $driver\n";
    }
    
    // Check tables
    echo "\n5. Checking Database Tables...\n";
    $expectedTables = ['users', 'roles', 'settings', 'system_updates', 'system_backups'];
    $existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "   ✅ Table: $table\n";
        } else {
            echo "   ❌ Table: $table - MISSING\n";
        }
    }
    
    // Check system_updates columns
    echo "\n6. Checking system_updates Table Schema...\n";
    if (in_array('system_updates', $existingTables)) {
        $columns = $db->query("DESCRIBE system_updates")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $requiredColumns = ['id', 'version', 'status', 'changelog', 'download_url', 'backup_id', 'error_message', 'applied_by', 'applied_at', 'created_at'];
        
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columnNames)) {
                echo "   ✅ Column: $col\n";
            } else {
                echo "   ❌ Column: $col - MISSING (run fix_update_tables.sql migration)\n";
            }
        }
    }
    
    // Check system_backups columns
    echo "\n7. Checking system_backups Table Schema...\n";
    if (in_array('system_backups', $existingTables)) {
        $columns = $db->query("DESCRIBE system_backups")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $requiredColumns = ['id', 'filename', 'filepath', 'size_bytes', 'backup_type', 'created_by', 'created_at'];
        
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columnNames)) {
                echo "   ✅ Column: $col\n";
            } else {
                echo "   ❌ Column: $col - MISSING (run fix_update_tables.sql migration)\n";
            }
        }
    }
    
    // Check settings
    echo "\n8. Checking System Settings...\n";
    $requiredSettings = ['app_version', 'github_repo_owner', 'github_repo_name'];
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN (?, ?, ?)");
    $stmt->execute($requiredSettings);
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($requiredSettings as $key) {
        if (isset($settings[$key])) {
            echo "   ✅ $key = " . $settings[$key] . "\n";
        } else {
            echo "   ❌ $key - NOT SET\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Database Error: " . $e->getMessage() . "\n";
    echo "   Please check your config/database.php settings\n";
}

// Test 5: Check PHP extensions
echo "\n9. Checking PHP Extensions...\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'zip'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext\n";
    } else {
        echo "   ❌ $ext - NOT LOADED\n";
    }
}

// Test 6: GitHub Update Service
echo "\n10. Testing GitHub Update Service...\n";
try {
    require_once __DIR__ . '/includes/GitHubUpdateService.php';
    $github = new GitHubUpdateService();
    echo "   ✅ GitHubUpdateService class loaded\n";
    
    // Test version comparison
    $result = $github->compareVersions('1.0.0', '1.0.1');
    if ($result < 0) {
        echo "   ✅ Version comparison works correctly\n";
    } else {
        echo "   ❌ Version comparison issue\n";
    }
} catch (Exception $e) {
    echo "   ❌ GitHubUpdateService Error: " . $e->getMessage() . "\n";
}

// Test 7: UpdateManager
echo "\n11. Testing UpdateManager...\n";
try {
    require_once __DIR__ . '/includes/UpdateManager.php';
    $updateManager = new UpdateManager();
    echo "   ✅ UpdateManager class loaded\n";
} catch (Exception $e) {
    echo "   ❌ UpdateManager Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nIf you see any ❌ marks above, please:\n";
echo "1. Run: mysql -u root -p mikrotik_panel < config/migrations/fix_update_tables.sql\n";
echo "2. Check config/database.php settings\n";
echo "3. Ensure temp/, backups/, and logs/ directories exist and are writable\n";
