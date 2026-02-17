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
            error_log("[Auth] Login attempt for user: {$username}");
            
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                error_log("[Auth] User not found or inactive: {$username}");
                return false;
            }
            
            error_log("[Auth] User found: {$username}, role: {$user['role_name']}, is_active: {$user['is_active']}");
            error_log("[Auth] Password hash from DB: {$user['password']}");
            
            // Try password_verify first (bcrypt)
            if (password_verify($password, $user['password'])) {
                error_log("[Auth] Password verified successfully using bcrypt");
                
                // Update last login
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                $updateStmt->execute([date('Y-m-d H:i:s'), $user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['logged_in'] = true;
                
                error_log("[Auth] Login successful for user: {$username}");
                return true;
            }
            
            // Fallback: Check for plain text password (for debugging/migration)
            if ($password === $user['password']) {
                error_log("[Auth] Password matched as plain text - INSECURE! Please update hash.");
                
                // Update to hashed password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $this->db->prepare("UPDATE users SET password = ?, last_login = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, date('Y-m-d H:i:s'), $user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['logged_in'] = true;
                
                error_log("[Auth] Login successful (plain text converted to hash)");
                return true;
            }
            
            error_log("[Auth] Password verification failed for user: {$username}");
            return false;
        } catch (Exception $e) {
            error_log("[Auth] Login error: " . $e->getMessage());
            error_log("[Auth] Trace: " . $e->getTraceAsString());
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
