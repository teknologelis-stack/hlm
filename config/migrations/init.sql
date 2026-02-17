-- HLM Database Schema
-- Version: 1.0.0

-- Create database (run manually if needed)
-- CREATE DATABASE IF NOT EXISTS hlm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE hlm_db;

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System updates table
CREATE TABLE IF NOT EXISTS system_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    changelog TEXT,
    download_url VARCHAR(500),
    backup_id INT,
    error_message TEXT,
    applied_at DATETIME,
    applied_by INT,
    status ENUM('pending', 'downloading', 'applying', 'applied', 'failed', 'rolled_back') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (backup_id) REFERENCES system_backups(id) ON DELETE SET NULL,
    KEY idx_status (status),
    KEY idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System backups table
CREATE TABLE IF NOT EXISTS system_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    size_bytes BIGINT DEFAULT 0,
    backup_type ENUM('manual', 'auto', 'pre-update') DEFAULT 'manual',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_backup_type (backup_type),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO roles (name, description) VALUES 
('admin', 'System administrator with full access'),
('user', 'Regular user with limited access')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Insert default admin user (password: admin)
-- Note: The password hash below is for 'admin'. In production, change this immediately!
INSERT INTO users (username, password, email, role_id) VALUES 
('admin', '$2y$10$vFfD6MUyrQUH3QgqH9XlDuzUUObfrFq3ch.3l9psojvR.9mQL96Q.', 'admin@hlm.local', 1)
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('app_version', '1.0.0', 'Current application version'),
('update_check_url', 'https://api.github.com/repos/teknologelis-stack/hlm/releases/latest', 'URL to check for updates'),
('auto_backup', '1', 'Automatic backup before updates'),
('backup_retention_days', '30', 'Number of days to keep backups'),
('github_repo_owner', 'teknologelis-stack', 'GitHub repository owner'),
('github_repo_name', 'hlm', 'GitHub repository name'),
('update_channel', 'stable', 'Update channel (stable or beta)'),
('auto_backup_before_update', 'true', 'Automatically backup before updates'),
('exclude_files_on_update', 'config/database.php,backups/*,temp/*,.git/*,.env', 'Files to exclude during updates')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
