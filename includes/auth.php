<?php
/**
 * Authentication Class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login - Use compatible SQL for both MySQL and SQLite
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Require authentication (redirect to login if not authenticated)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Admin has all permissions
        if ($_SESSION['role_name'] === 'admin') {
            return true;
        }
        
        // Add more granular permission checks as needed
        return false;
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role_name' => $_SESSION['role_name']
        ];
    }
}
