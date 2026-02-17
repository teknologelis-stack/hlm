-- =====================================================
-- MIGRATION: Fix Update System Tables and Settings (SQLite)
-- Date: 2026-02-17
-- Description: Fix all database schema issues for SQLite
-- =====================================================

-- FIX 1: Recreate system_updates table with all required columns
DROP TABLE IF EXISTS system_updates;

CREATE TABLE system_updates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  version VARCHAR(20) NOT NULL,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending','downloading','applying','applied','failed','rolled_back')),
  changelog TEXT DEFAULT NULL,
  download_url VARCHAR(500) DEFAULT NULL,
  backup_id INTEGER DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  applied_by INTEGER DEFAULT NULL,
  applied_at TEXT DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (applied_by) REFERENCES users(id),
  FOREIGN KEY (backup_id) REFERENCES system_backups(id)
);

CREATE INDEX IF NOT EXISTS idx_system_updates_status ON system_updates(status);
CREATE INDEX IF NOT EXISTS idx_system_updates_applied_by ON system_updates(applied_by);

-- FIX 2: Recreate system_backups table with correct schema
DROP TABLE IF EXISTS system_backups;

CREATE TABLE system_backups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(500) DEFAULT NULL,
  backup_type TEXT DEFAULT 'manual' CHECK(backup_type IN ('manual','auto','pre-update')),
  size_bytes INTEGER DEFAULT 0,
  created_by INTEGER DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_system_backups_type ON system_backups(backup_type);
CREATE INDEX IF NOT EXISTS idx_system_backups_created_at ON system_backups(created_at);

-- FIX 3: Add required settings (insert or update)
INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES
('app_version', '1.0.0'),
('github_repo_owner', 'teknologelis-stack'),
('github_repo_name', 'hlm'),
('update_channel', 'stable'),
('auto_backup_before_update', 'true');

-- FIX 4: Ensure admin user exists with correct password
-- Password: admin (bcrypt hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
UPDATE users SET 
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    is_active = 1
WHERE username = 'admin';

-- Verification queries
-- SELECT 'Settings check:' as info;
-- SELECT * FROM settings WHERE setting_key LIKE '%version%' OR setting_key LIKE 'github%';

-- SELECT 'Admin user check:' as info;
-- SELECT id, username, email, role_id, is_active FROM users WHERE username = 'admin';

-- SELECT 'Tables check:' as info;
-- SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'system_%';
