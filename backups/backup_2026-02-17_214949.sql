-- HLM Backup - 2026-02-17 21:49:49

-- Table: roles
INSERT INTO roles (id, name, permissions) VALUES
('1', 'Admin', '{\"dashboard_view\":true,\"system_manage\":true}');

-- Table: users
INSERT INTO users (id, username, password, role_id, is_active) VALUES
('1', 'admin', '$2y$10$qM/b28DVOL6dzDlvzR12XOMLtZoTU9d9eGFKHYZZntwswfMu2vySq', '1', '1');

-- Table: settings
INSERT INTO settings (id, setting_key, setting_value) VALUES
('1', 'system_version', '1.0.0'),
('2', 'panel_name', 'MikroTik Panel'),
('4', 'github_repo_owner', 'teknologelis-stack'),
('5', 'github_repo_name', 'hlm'),
('6', 'update_channel', 'stable'),
('7', 'auto_backup_before_update', 'true'),
('8', 'app_version', '1.0.0');

-- Table: system_updates
-- Table: system_backups
