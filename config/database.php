<?php
/**
 * Database Connection Class (Singleton Pattern)
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host = 'localhost';
    private $dbname = 'hlm_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    // Use SQLite for testing if MySQL is not available
    private $useSQLite = false;
    private $sqlitePath = __DIR__ . '/../hlm_db.sqlite'; // Relative path
    
    private function __construct() {
        try {
            // Try MySQL first
            if (!$this->useSQLite && getenv('USE_SQLITE') !== 'true') {
                try {
                    $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ];
                    
                    $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                    return;
                } catch (PDOException $e) {
                    // Fall back to SQLite if MySQL fails
                    $this->useSQLite = true;
                }
            }
            
            // Use SQLite
            $dsn = "sqlite:{$this->sqlitePath}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            $this->connection = new PDO($dsn, null, null, $options);
            $this->connection->exec('PRAGMA foreign_keys = ON;');
            
            // Initialize database if needed
            $this->initializeSQLiteDatabase();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    private function initializeSQLiteDatabase() {
        // Check if tables exist
        try {
            $result = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            if ($result->fetch()) {
                return; // Database already initialized
            }
        } catch (PDOException $e) {
            // Continue with initialization
        }
        
        // Create tables for SQLite
        // Create roles table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create users table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                role_id INTEGER NOT NULL,
                is_active INTEGER DEFAULT 1,
                last_login TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            )
        ");
        
        // Create settings table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                description TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create system_updates table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS system_updates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                version VARCHAR(20) NOT NULL,
                description TEXT,
                applied_at TEXT,
                applied_by INTEGER,
                status TEXT DEFAULT 'pending',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (applied_by) REFERENCES users(id)
            )
        ");
        
        // Create system_backups table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS system_backups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename VARCHAR(255) NOT NULL,
                filepath VARCHAR(500) NOT NULL,
                size_bytes INTEGER,
                backup_type TEXT DEFAULT 'manual',
                created_by INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ");

        
        // Insert default data manually
        $this->connection->exec("INSERT OR IGNORE INTO roles (id, name, description) VALUES (1, 'admin', 'System administrator with full access')");
        $this->connection->exec("INSERT OR IGNORE INTO roles (id, name, description) VALUES (2, 'user', 'Regular user with limited access')");
        // Use a verified password hash for 'admin' password
        // SECURITY WARNING: Change this password immediately after first login!
        // This is only for initial setup and testing purposes.
        $adminHash = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $this->connection->prepare("INSERT OR IGNORE INTO users (id, username, password, email, role_id) VALUES (1, 'admin', ?, 'admin@hlm.local', 1)");
        $stmt->execute([$adminHash]);
        $this->connection->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value, description) VALUES ('app_version', '1.0.0', 'Current application version')");
        $this->connection->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value, description) VALUES ('update_check_url', 'https://api.github.com/repos/teknologelis-stack/hlm/releases/latest', 'URL to check for updates')");
        $this->connection->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value, description) VALUES ('auto_backup', '1', 'Automatic backup before updates')");
        $this->connection->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value, description) VALUES ('backup_retention_days', '30', 'Number of days to keep backups')");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
