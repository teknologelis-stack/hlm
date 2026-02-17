<?php
/**
 * Database Connection Class (Singleton Pattern)
 * MySQL-only version for production use
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration - Using SQLite
    private $dbPath;
    
    private function __construct() {
        try {
            // Determine database path
            $this->dbPath = dirname(__DIR__) . '/hlm_db.sqlite';
            
            // Create DSN for SQLite
            $dsn = "sqlite:{$this->dbPath}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->connection = new PDO($dsn, null, null, $options);
            
            // Enable foreign keys for SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON;');
            
            error_log("[Database] Connected to SQLite database: {$this->dbPath}");
        } catch (PDOException $e) {
            error_log("[Database] Connection failed: " . $e->getMessage());
            die("Database connection failed. Please check configuration.");
        }
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
