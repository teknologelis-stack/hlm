-- =====================================================
-- MIGRATION: Create Update System Tables
-- Date: 2026-02-17
-- =====================================================

USE mikrotik_panel;

-- Drop existing tables
DROP TABLE IF EXISTS system_updates;
DROP TABLE IF EXISTS system_backups;

-- Create system_updates table
CREATE TABLE system_updates (
  id int(11) NOT NULL AUTO_INCREMENT,
  version varchar(20) NOT NULL,
  status enum('pending','downloading','applying','applied','failed','rolled_back') DEFAULT 'pending',
  changelog TEXT DEFAULT NULL,
  download_url varchar(500) DEFAULT NULL,
  backup_id int(11) DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  applied_by int(11) DEFAULT NULL,
  applied_at datetime DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY status (status),
  KEY applied_by (applied_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system_backups table
CREATE TABLE system_backups (
  id int(11) NOT NULL AUTO_INCREMENT,
  filename varchar(255) NOT NULL,
  filepath varchar(500) DEFAULT NULL,
  backup_type enum('manual','auto','pre-update') DEFAULT 'manual',
  size_bytes bigint(20) DEFAULT 0,
  created_by int(11) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY backup_type (backup_type),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add/update settings
INSERT INTO settings (setting_key, setting_value) VALUES
('app_version', '1.0.0'),
('github_repo_owner', 'teknologelis-stack'),
('github_repo_name', 'hlm'),
('update_channel', 'stable'),
('auto_backup_before_update', 'true')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Fix admin password (bcrypt hash for 'admin')
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    is_active = 1
WHERE username = 'admin';

-- Verification
SELECT 'Tables created:' as info;
SHOW TABLES LIKE 'system_%';

SELECT 'Admin user:' as info;
SELECT id, username, role_id, is_active FROM users WHERE username = 'admin';

SELECT 'Settings:' as info;
SELECT * FROM settings WHERE setting_key IN ('app_version', 'github_repo_owner', 'github_repo_name');
