-- Güncelleme ve Yapılandırma Yönetimi Sistemı Tabloları
-- Versiyon: 1.0.0
-- Tarih: 2026-02-16

-- Güncelleme geçmişi tablosu
CREATE TABLE IF NOT EXISTS `system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version_from` varchar(20) NOT NULL,
  `version_to` varchar(20) NOT NULL,
  `update_type` enum('online','offline','manual') DEFAULT 'online',
  `status` enum('pending','in_progress','completed','failed','rolled_back') DEFAULT 'pending',
  `changelog` text,
  `backup_path` varchar(255) DEFAULT NULL,
  `started_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `error_message` text,
  PRIMARY KEY (`id`),
  KEY `applied_by` (`applied_by`),
  KEY `status` (`status`),
  KEY `update_type` (`update_type`),
  FOREIGN KEY (`applied_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yedekleme kayıtları
CREATE TABLE IF NOT EXISTS `system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(255) NOT NULL,
  `backup_type` enum('full','config','database') DEFAULT 'full',
  `file_path` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `backup_type` (`backup_type`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yapılandırma import geçmişi
CREATE TABLE IF NOT EXISTS `config_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_type` enum('json','csv','sql') DEFAULT 'json',
  `file_name` varchar(255) NOT NULL,
  `imported_items` int(11) DEFAULT 0,
  `skipped_items` int(11) DEFAULT 0,
  `imported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `imported_by` int(11) DEFAULT NULL,
  `import_summary` text,
  PRIMARY KEY (`id`),
  KEY `imported_by` (`imported_by`),
  KEY `import_type` (`import_type`),
  FOREIGN KEY (`imported_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for performance
CREATE INDEX idx_system_updates_dates ON system_updates(started_at, completed_at);
CREATE INDEX idx_system_backups_created ON system_backups(created_at);
CREATE INDEX idx_config_imports_date ON config_imports(imported_at);
