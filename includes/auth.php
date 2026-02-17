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
                
                // Update last login (with error handling)
                try {
                    $updateStmt = $this->db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                    $updateStmt->execute([date('Y-m-d H:i:s'), $user['id']]);
                } catch (Exception $e) {
                    // Log error but don't fail login if last_login update fails
                    error_log("[Auth] Warning: Could not update last_login: " . $e->getMessage());
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['logged_in'] = true;
                
                error_log("[Auth] Login successful for user: {$username}");
                return true;
            }
            
            // Fallback: Check for plain text password (ONLY if explicitly enabled for migration)
            if (defined('ALLOW_PLAIN_TEXT_PASSWORD_MIGRATION') && ALLOW_PLAIN_TEXT_PASSWORD_MIGRATION === true) {
                if ($password === $user['password']) {
                    error_log("[Auth] WARNING: Password matched as plain text - INSECURE! Upgrading to hash.");
                    
                    // Update to hashed password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    try {
                        $updateStmt = $this->db->prepare("UPDATE users SET password = ?, last_login = ? WHERE id = ?");
                        $updateStmt->execute([$hashedPassword, date('Y-m-d H:i:s'), $user['id']]);
                    } catch (Exception $e) {
                        // Log error but don't fail login
                        error_log("[Auth] Warning: Could not update password/last_login: " . $e->getMessage());
                    }
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['role_name'];
                    $_SESSION['logged_in'] = true;
                    
                    error_log("[Auth] Login successful (plain text converted to hash)");
                    return true;
                }
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
        if ($this->isAdmin()) {
            return true;
        }
        
        // Check specific permission
        $role = $this->getUserRole();
        if (!$role) {
            return false;
        }
        
        $permissions = json_decode($role['permissions'], true);
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $roleId = $_SESSION['role_id'] ?? 0;
        $roleName = $_SESSION['role_name'] ?? '';
        
        return ($roleId == 1 || strtolower($roleName) === 'admin');
    }
    
    /**
     * Get user role details
     */
    public function getUserRole() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("
            SELECT r.* FROM roles r
            INNER JOIN users u ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            'role_id' => $_SESSION['role_id'] ?? null,
            'role_name' => $_SESSION['role_name'] ?? null
        ];
    }
}
