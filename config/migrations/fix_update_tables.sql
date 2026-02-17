-- FIX: Database Schema for Update System
-- This migration fixes any missing columns and ensures proper table structure

-- FIX: system_updates table
-- Drop and recreate to ensure all columns are present
DROP TABLE IF EXISTS `system_updates`;
CREATE TABLE `system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `status` enum('pending','downloading','applying','applied','failed','rolled_back') DEFAULT 'pending',
  `changelog` TEXT DEFAULT NULL,
  `download_url` varchar(500) DEFAULT NULL,
  `backup_id` int(11) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `applied_by` (`applied_by`),
  FOREIGN KEY (`applied_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FIX: system_backups table
-- Drop and recreate to ensure all columns are present
DROP TABLE IF EXISTS `system_backups`;
CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) DEFAULT NULL,
  `backup_type` enum('manual','auto','pre-update') DEFAULT 'manual',
  `size_bytes` bigint(20) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `backup_type` (`backup_type`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FIX: settings - Add required update system settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_version', '1.0.0', 'Current application version'),
('github_repo_owner', 'teknologelis-stack', 'GitHub repository owner'),
('github_repo_name', 'hlm', 'GitHub repository name'),
('update_channel', 'stable', 'Update channel (stable or beta)'),
('auto_backup_before_update', 'true', 'Automatically backup before updates')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
