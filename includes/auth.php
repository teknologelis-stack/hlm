<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($username, $password, $remember = false) {
        $user = $this->db->fetchOne(
            "SELECT u.*, r.permissions FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.username = ? AND u.is_active = 1",
            [$username]
        );
        
        if (!$user) {
            error_log("User not found: " . $username);
            return false;
        }
        
        error_log("DB Password Hash: " . $user['password']);
        error_log("Input Password: " . $password);
        error_log("Verify Result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true);
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $user['id']]
        );
        
        return true;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            redirect('index.php');
        }
    }
    
    public function hasPermission($permission) {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'];
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            die('Bu işlem için yetkiniz yok.');
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT u.*, r.name as role_name FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    public function logActivity($userId, $action, $details = '', $deviceId = null) {
        logActivity($userId, $action, $details, $deviceId);
    }
}
?>