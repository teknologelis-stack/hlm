-- FIX: Database Schema for Update System
-- This migration adds missing columns and ensures proper table structure
-- Preserves existing data by using ALTER TABLE instead of DROP/CREATE

-- FIX: system_updates table - Add missing columns if they don't exist
ALTER TABLE `system_updates` 
  ADD COLUMN IF NOT EXISTS `changelog` TEXT DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `download_url` varchar(500) DEFAULT NULL AFTER `changelog`,
  ADD COLUMN IF NOT EXISTS `backup_id` int(11) DEFAULT NULL AFTER `download_url`,
  ADD COLUMN IF NOT EXISTS `error_message` TEXT DEFAULT NULL AFTER `backup_id`;

-- Add foreign key if it doesn't exist (MySQL 5.7+ compatible)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'system_updates' 
                  AND CONSTRAINT_NAME = 'system_updates_backup_fk');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `system_updates` ADD CONSTRAINT `system_updates_backup_fk` FOREIGN KEY (`backup_id`) REFERENCES `system_backups`(`id`) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS info');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure status enum has all required values
ALTER TABLE `system_updates` 
  MODIFY COLUMN `status` enum('pending','downloading','applying','applied','failed','rolled_back') DEFAULT 'pending';

-- FIX: system_backups table - Add missing columns if they don't exist
-- Note: filepath should be added but some old records might have it as NOT NULL
ALTER TABLE `system_backups` 
  ADD COLUMN IF NOT EXISTS `filepath` varchar(500) DEFAULT NULL AFTER `filename`;

-- Update existing records to set filepath based on filename if NULL
UPDATE `system_backups` 
SET `filepath` = CONCAT('/home/runner/work/hlm/hlm/backups/', `filename`)
WHERE `filepath` IS NULL AND `filename` IS NOT NULL;

-- Ensure backup_type enum has all required values
ALTER TABLE `system_backups` 
  MODIFY COLUMN `backup_type` enum('manual','auto','pre-update') DEFAULT 'manual';

-- FIX: settings - Add required update system settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_version', '1.0.0', 'Current application version'),
('github_repo_owner', 'teknologelis-stack', 'GitHub repository owner'),
('github_repo_name', 'hlm', 'GitHub repository name'),
('update_channel', 'stable', 'Update channel (stable or beta)'),
('auto_backup_before_update', 'true', 'Automatically backup before updates')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
