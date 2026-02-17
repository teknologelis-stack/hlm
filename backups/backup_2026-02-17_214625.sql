-- HLM Backup - 2026-02-17 21:46:25

-- Table: roles
INSERT INTO roles (id, name, description, created_at) VALUES
('1', 'admin', 'System administrator with full access', '2026-02-17 19:07:07'),
('2', 'user', 'Regular user with limited access', '2026-02-17 19:07:07');

-- Table: users
INSERT INTO users (id, username, password, email, role_id, is_active, last_login, created_at, updated_at) VALUES
('1', 'admin', '$2y$10$svp.IH.hTkndkNgwESblTeK697pAD08.mMNqiEOanRnqDGh2FgbQC', 'admin@hlm.local', '1', '1', '2026-02-17 23:39:57', '2026-02-17 19:07:07', '2026-02-17 19:07:07');

-- Table: settings
INSERT INTO settings (id, setting_key, setting_value, description, created_at, updated_at) VALUES
('1', 'app_version', '1.0.0', 'Current application version', '2026-02-17 19:07:07', '2026-02-17 19:07:07'),
('2', 'update_check_url', 'https://api.github.com/repos/teknologelis-stack/hlm/releases/latest', 'URL to check for updates', '2026-02-17 19:07:07', '2026-02-17 19:07:07'),
('3', 'auto_backup', '1', 'Automatic backup before updates', '2026-02-17 19:07:07', '2026-02-17 19:07:07'),
('4', 'backup_retention_days', '30', 'Number of days to keep backups', '2026-02-17 19:07:07', '2026-02-17 19:07:07');

-- Table: system_updates
-- Table: system_backups
INSERT INTO system_backups (id, filename, filepath, size_bytes, backup_type, created_by, created_at) VALUES
('1', 'backup_2026-02-17_212407.sql', 'C:\\xampp\\htdocs/backups/backup_2026-02-17_212407.sql', '1251', 'manual', '1', '2026-02-17 20:24:07');

