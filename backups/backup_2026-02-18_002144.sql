-- HLM Backup - 2026-02-18 00:21:44

-- Table: roles
INSERT INTO roles (id, name, permissions) VALUES
('1', 'Admin', '{\"dashboard_view\":true,\"users_manage\":true,\"devices_manage\":true,\"router_config\":true,\"logs_view\":true,\"settings_manage\":true,\"roles_manage\":true,\"system_manage\":true,\"backup_manage\":true,\"update_manage\":true,\"ppp_manage\":true,\"vlan_manage\":true,\"ip_manage\":true,\"mimosa_manage\":true,\"pppoe_manage\":true,\"ppp_secrets\":true,\"active_users\":true}');

-- Table: users
INSERT INTO users (id, username, password, email, role_id, is_active, last_login, created_at, updated_at) VALUES
('1', 'admin', '$2y$10$zf/XLUd3TbGRE9C3qHNCSO/Hq.CPJLcAi1inIA6idUwWyVebjnnRe', 'admin@mikrotik.local', '1', '1', '2026-02-18 02:15:54', '2026-02-18 01:34:34', '2026-02-18 02:15:54');

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
INSERT INTO system_updates (id, version, status, changelog, download_url, backup_id, error_message, applied_by, applied_at, created_at) VALUES
('1', '1.0.0', 'applied', '[\"G\\u00fcvenlik g\\u00fcncellemeleri\",\"Hata d\\u00fczeltmeleri\",\"Performans iyile\\u015ftirmeleri\",\"\\u2705 Bug fix: Update kontrol\\u00fc\",\"\\u2705 Improvement: UI geli\\u015ftirmeleri\"]', 'https://api.github.com/repos/teknologelis-stack/hlm/zipball/v1.0.1', '1', NULL, '1', '2026-02-17 23:18:33', '2026-02-18 01:18:33');

-- Table: system_backups
INSERT INTO system_backups (id, filename, filepath, backup_type, size_bytes, created_by, created_at) VALUES
('1', 'backup_2026-02-17_231831.sql', 'C:\\xampp\\htdocs/backups/backup_2026-02-17_231831.sql', 'pre-update', '781', '1', '2026-02-18 01:18:31');

