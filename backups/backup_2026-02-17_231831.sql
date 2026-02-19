-- HLM Backup - 2026-02-17 23:18:31

-- Table: roles
INSERT INTO roles (id, name, permissions) VALUES
('1', 'Admin', '{\"dashboard_view\":true,\"system_manage\":true}');

-- Table: users
INSERT INTO users (id, username, password, role_id, is_active, last_login) VALUES
('1', 'admin', '$2y$10$CHAGE6zXBANhWLhuhFHZU.2lWytu89a.GLlX/1yGhivNuSU7A816S', '1', '1', '2026-02-18 00:56:24');

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
