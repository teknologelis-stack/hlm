<?php
/**
 * Update Manager Class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/GitHubUpdateService.php';

class UpdateManager {
    private $db;
    private $github;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->github = new GitHubUpdateService();
    }
    
    /**
     * Check for available updates from GitHub
     */
    public function checkForUpdates() {
        $currentVersion = $this->getCurrentVersion();
        
        // Get latest release from GitHub
        $latestRelease = $this->github->getLatestRelease();
        
        if (!$latestRelease) {
            return [
                'success' => false,
                'message' => 'GitHub API bağlantısı kurulamadı',
                'available' => false,
                'current' => $currentVersion
            ];
        }
        
        // Compare versions
        $hasUpdate = $this->github->compareVersions($currentVersion, $latestRelease['version']) < 0;
        
        return [
            'success' => true,
            'available' => $hasUpdate,
            'current' => $currentVersion,
            'latest' => $latestRelease['version'],
            'changelog' => $latestRelease['changelog'],
            'release_date' => $latestRelease['published_at'],
            'download_url' => $latestRelease['zipball_url'],
            'release_notes_url' => $latestRelease['html_url'],
            'release_name' => $latestRelease['name']
        ];
    }
    
    /**
     * Get current version from database or config
     */
    private function getCurrentVersion() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'app_version'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                return $result['setting_value'];
            }
        } catch (Exception $e) {
            error_log("[UpdateManager] Error getting version: " . $e->getMessage());
        }
        
        return APP_VERSION;
    }
    
    /**
     * Create full system backup (database + files)
     */
    public function createBackup($userId, $type = 'manual') {
        try {
            $timestamp = date('Y-m-d_His');
            
            // Create a ZIP backup instead of just SQL
            $zipFilename = "backup_full_{$timestamp}.zip";
            $zipFilepath = BACKUPS_PATH . '/' . $zipFilename;
            
            // Create ZIP file
            $zip = new ZipArchive();
            if ($zip->open($zipFilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('ZIP dosyası oluşturulamadı');
            }
            
            // 1. Add database dump to ZIP
            $tables = ['roles', 'users', 'settings', 'system_updates', 'system_backups'];
            $sqlDump = "-- HLM Full Backup - " . date('Y-m-d H:i:s') . "\n";
            $sqlDump .= "-- Version: " . $this->getCurrentVersion() . "\n\n";
            
            foreach ($tables as $table) {
                $sqlDump .= "-- Table: $table\n";
                $stmt = $this->db->query("SELECT * FROM $table");
                $rows = $stmt->fetchAll();
                
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $sqlDump .= "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $escapedValues = array_map(function($val) {
                            return $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                        }, array_values($row));
                        $values[] = "(" . implode(', ', $escapedValues) . ")";
                    }
                    $sqlDump .= implode(",\n", $values) . ";\n\n";
                }
            }
            
            $zip->addFromString('database.sql', $sqlDump);
            
            // 2. Add config files to ZIP (excluding database.php for security)
            $configFiles = ['app.php'];
            foreach ($configFiles as $configFile) {
                $configPath = CONFIG_PATH . '/' . $configFile;
                if (file_exists($configPath)) {
                    $zip->addFile($configPath, 'config/' . $configFile);
                }
            }
            
            // 3. Add important application files
            $includeFiles = glob(ROOT_PATH . '/*.php');
            foreach ($includeFiles as $file) {
                if (is_file($file) && basename($file) !== 'index.php') {
                    $zip->addFile($file, 'root/' . basename($file));
                }
            }
            
            // Add includes directory
            $includeDirFiles = $this->rglob(ROOT_PATH . '/includes/*.php');
            foreach ($includeDirFiles as $file) {
                $zip->addFile($file, 'includes/' . basename($file));
            }
            
            // Add pages directory
            $pagesDirFiles = $this->rglob(ROOT_PATH . '/pages/*.php');
            foreach ($pagesDirFiles as $file) {
                $zip->addFile($file, 'pages/' . basename($file));
            }
            
            // Add api directory
            $apiDirFiles = $this->rglob(ROOT_PATH . '/api/*.php');
            foreach ($apiDirFiles as $file) {
                $zip->addFile($file, 'api/' . basename($file));
            }
            
            // Add assets (CSS, JS)
            $assetsFiles = $this->rglob(ROOT_PATH . '/assets/css/*.css');
            foreach ($assetsFiles as $file) {
                $zip->addFile($file, 'assets/css/' . basename($file));
            }
            
            $assetsJsFiles = $this->rglob(ROOT_PATH . '/assets/js/*.js');
            foreach ($assetsJsFiles as $file) {
                $zip->addFile($file, 'assets/js/' . basename($file));
            }
            
            // Add backup info JSON
            $backupInfo = [
                'created_at' => date('Y-m-d H:i:s'),
                'version' => $this->getCurrentVersion(),
                'type' => $type,
                'created_by' => $userId,
                'includes' => ['database', 'config', 'includes', 'pages', 'api', 'assets']
            ];
            $zip->addFromString('backup_info.json', json_encode($backupInfo, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            $fileSize = filesize($zipFilepath);
            
            // Record backup in database
            $stmt = $this->db->prepare("
                INSERT INTO system_backups (filename, filepath, size_bytes, backup_type, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$zipFilename, $zipFilepath, $fileSize, $type, $userId]);
            
            return [
                'success' => true,
                'backup_id' => $this->db->lastInsertId(),
                'filename' => $zipFilename,
                'size' => $fileSize,
                'type' => 'full'
            ];
        } catch (Exception $e) {
            error_log("Backup error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Recursive glob helper
     */
    private function rglob($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
        }
        return $files ?? [];
    }
    
    /**
     * Apply update from GitHub
     */
    public function applyUpdate($version, $userId) {
        error_log("[UpdateManager] ========================================");
        error_log("[UpdateManager] Starting update to version: {$version}");
        error_log("[UpdateManager] User ID: {$userId}");
        error_log("[UpdateManager] ========================================");
        
        try {
            // Start transaction
            error_log("[UpdateManager] STEP 1: Starting database transaction");
            $this->db->beginTransaction();
            
            // 1. Create pre-update backup
            error_log("[UpdateManager] STEP 2: Creating pre-update backup");
            $backup = $this->createBackup($userId, 'pre-update');
            if (!$backup['success']) {
                error_log("[UpdateManager] ERROR: Backup failed - {$backup['error']}");
                throw new Exception('Yedekleme başarısız: ' . $backup['error']);
            }
            error_log("[UpdateManager] SUCCESS: Backup created with ID: {$backup['backup_id']}");
            
            // 2. Get release information from GitHub
            error_log("[UpdateManager] STEP 3: Fetching release information from GitHub");
            $release = $this->github->getReleaseByTag($version);
            if (!$release) {
                error_log("[UpdateManager] ERROR: Failed to get release info from GitHub");
                throw new Exception('GitHub release bilgisi alınamadı');
            }
            error_log("[UpdateManager] SUCCESS: Release info retrieved - URL: {$release['zipball_url']}");
            
            // 3. Download ZIP file
            error_log("[UpdateManager] STEP 4: Downloading release ZIP file");
            $tempDir = TEMP_PATH . '/';
            $zipPath = $tempDir . "update-{$version}.zip";
            error_log("[UpdateManager] Download path: {$zipPath}");
            
            $downloaded = $this->github->downloadRelease($release['zipball_url'], $zipPath);
            if (!$downloaded) {
                error_log("[UpdateManager] ERROR: Download failed");
                throw new Exception('Güncelleme dosyası indirilemedi');
            }
            error_log("[UpdateManager] SUCCESS: ZIP file downloaded, size: " . filesize($zipPath) . " bytes");
            
            // 4. Extract ZIP
            error_log("[UpdateManager] STEP 5: Extracting ZIP file");
            $extractPath = $tempDir . "update-{$version}/";
            $this->extractZip($zipPath, $extractPath);
            error_log("[UpdateManager] SUCCESS: ZIP extracted to: {$extractPath}");
            
            // 5. Apply files safely
            error_log("[UpdateManager] STEP 6: Applying files to application");
            $this->applyFiles($extractPath);
            error_log("[UpdateManager] SUCCESS: Files applied");
            
            // 6. Run migrations if they exist
            error_log("[UpdateManager] STEP 7: Running migrations");
            $this->runMigrations($extractPath);
            error_log("[UpdateManager] SUCCESS: Migrations completed");
            
            // 7. Update version in database
            error_log("[UpdateManager] STEP 8: Updating version in database");
            $this->updateVersion($version);
            error_log("[UpdateManager] SUCCESS: Version updated to: {$version}");
            
            // 8. Record update in database
            error_log("[UpdateManager] STEP 9: Recording update in database");
            $stmt = $this->db->prepare("
                INSERT INTO system_updates (version, changelog, download_url, backup_id, applied_at, applied_by, status)
                VALUES (?, ?, ?, ?, ?, ?, 'applied')
            ");
            $stmt->execute([
                $version,
                json_encode($release['changelog']),
                $release['zipball_url'],
                $backup['backup_id'],
                date('Y-m-d H:i:s'),
                $userId
            ]);
            error_log("[UpdateManager] SUCCESS: Update recorded in database");
            
            // 9. Cleanup temp files
            error_log("[UpdateManager] STEP 10: Cleaning up temporary files");
            $this->cleanupTempFiles($zipPath, $extractPath);
            error_log("[UpdateManager] SUCCESS: Cleanup completed");
            
            // Commit transaction
            error_log("[UpdateManager] STEP 11: Committing database transaction");
            $this->db->commit();
            error_log("[UpdateManager] SUCCESS: Transaction committed");
            
            error_log("[UpdateManager] ========================================");
            error_log("[UpdateManager] Update completed successfully!");
            error_log("[UpdateManager] ========================================");
            
            return [
                'success' => true,
                'message' => "Sistem başarıyla {$version} versiyonuna güncellendi",
                'version' => $version,
                'backup_id' => $backup['backup_id']
            ];
        } catch (Exception $e) {
            error_log("[UpdateManager] ========================================");
            error_log("[UpdateManager] UPDATE FAILED!");
            error_log("[UpdateManager] Error: " . $e->getMessage());
            error_log("[UpdateManager] File: " . $e->getFile() . " Line: " . $e->getLine());
            error_log("[UpdateManager] Trace: " . $e->getTraceAsString());
            error_log("[UpdateManager] ========================================");
            
            // Rollback transaction
            error_log("[UpdateManager] Rolling back database transaction");
            $this->db->rollBack();
            
            // Rollback to backup if it was created
            if (isset($backup) && $backup['success']) {
                error_log("[UpdateManager] Attempting to restore from backup ID: {$backup['backup_id']}");
                $restoreResult = $this->restoreBackup($backup['backup_id'], $userId);
                if ($restoreResult['success']) {
                    error_log("[UpdateManager] Backup restored successfully");
                } else {
                    error_log("[UpdateManager] Backup restore failed: {$restoreResult['error']}");
                }
            }
            
            // Record failed update
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO system_updates (version, error_message, applied_by, status)
                    VALUES (?, ?, ?, 'failed')
                ");
                $stmt->execute([$version, $e->getMessage(), $userId]);
                error_log("[UpdateManager] Failed update recorded in database");
            } catch (Exception $dbError) {
                error_log("[UpdateManager] Failed to record error in database: " . $dbError->getMessage());
            }
            
            return [
                'success' => false,
                'error' => 'Güncelleme hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Apply manual update from uploaded ZIP file
     */
    public function applyManualUpdate($zipPath, $userId) {
        error_log("[UpdateManager] ========================================");
        error_log("[UpdateManager] Starting manual update from: {$zipPath}");
        error_log("[UpdateManager] User ID: {$userId}");
        error_log("[UpdateManager] ========================================");
        
        try {
            // Validate ZIP file exists
            if (!file_exists($zipPath)) {
                throw new Exception('ZIP dosyası bulunamadı');
            }
            
            // Start transaction
            error_log("[UpdateManager] STEP 1: Starting database transaction");
            $this->db->beginTransaction();
            
            // 1. Create pre-update backup
            error_log("[UpdateManager] STEP 2: Creating pre-update backup");
            $backup = $this->createBackup($userId, 'pre-update');
            if (!$backup['success']) {
                error_log("[UpdateManager] ERROR: Backup failed - {$backup['error']}");
                throw new Exception('Yedekleme başarısız: ' . $backup['error']);
            }
            error_log("[UpdateManager] SUCCESS: Backup created with ID: {$backup['backup_id']}");
            
            // 2. Extract ZIP file
            error_log("[UpdateManager] STEP 3: Extracting ZIP file");
            $extractPath = TEMP_PATH . '/manual_update_' . time() . '/';
            $this->extractZip($zipPath, $extractPath);
            error_log("[UpdateManager] SUCCESS: ZIP extracted to: {$extractPath}");
            
            // 3. Apply files safely
            error_log("[UpdateManager] STEP 4: Applying files to application");
            $this->applyFiles($extractPath);
            error_log("[UpdateManager] SUCCESS: Files applied");
            
            // 4. Run migrations if they exist
            error_log("[UpdateManager] STEP 5: Running migrations");
            $this->runMigrations($extractPath);
            error_log("[UpdateManager] SUCCESS: Migrations completed");
            
            // 5. Update version - try to get from backup_info.json
            $version = $this->detectVersionFromExtract($extractPath);
            if ($version) {
                error_log("[UpdateManager] STEP 6: Updating version to: {$version}");
                $this->updateVersion($version);
            }
            
            // 6. Record update in database
            error_log("[UpdateManager] STEP 7: Recording update in database");
            $stmt = $this->db->prepare("
                INSERT INTO system_updates (version, changelog, download_url, backup_id, applied_at, applied_by, status)
                VALUES (?, ?, ?, ?, ?, ?, 'applied')
            ");
            $stmt->execute([
                $version ?? 'manual',
                'Manuel güncelleme',
                'manual_upload',
                $backup['backup_id'],
                date('Y-m-d H:i:s'),
                $userId
            ]);
            error_log("[UpdateManager] SUCCESS: Update recorded in database");
            
            // 7. Cleanup temp files
            error_log("[UpdateManager] STEP 8: Cleaning up temporary files");
            $this->cleanupTempFiles($zipPath, $extractPath);
            error_log("[UpdateManager] SUCCESS: Cleanup completed");
            
            // Commit transaction
            error_log("[UpdateManager] STEP 9: Committing database transaction");
            $this->db->commit();
            error_log("[UpdateManager] SUCCESS: Transaction committed");
            
            error_log("[UpdateManager] ========================================");
            error_log("[UpdateManager] Manual update completed successfully!");
            error_log("[UpdateManager] ========================================");
            
            return [
                'success' => true,
                'message' => $version ? "Sistem başarıyla {$version} versiyonuna güncellendi" : 'Sistem başarıyla güncellendi',
                'version' => $version
            ];
        } catch (Exception $e) {
            error_log("[UpdateManager] ========================================");
            error_log("[UpdateManager] MANUAL UPDATE FAILED!");
            error_log("[UpdateManager] Error: " . $e->getMessage());
            error_log("[UpdateManager] ========================================");
            
            // Rollback transaction
            $this->db->rollBack();
            
            // Record failed update
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO system_updates (version, error_message, applied_by, status)
                    VALUES (?, ?, ?, 'failed')
                ");
                $stmt->execute(['manual', $e->getMessage(), $userId]);
            } catch (Exception $dbError) {
                error_log("[UpdateManager] Failed to record error: " . $dbError->getMessage());
            }
            
            return [
                'success' => false,
                'error' => 'Manuel güncelleme hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Detect version from extracted update folder
     */
    private function detectVersionFromExtract($extractPath) {
        // Try to find version from backup_info.json
        $infoFile = $extractPath . 'backup_info.json';
        if (file_exists($infoFile)) {
            $info = json_decode(file_get_contents($infoFile), true);
            if (isset($info['version'])) {
                return $info['version'];
            }
        }
        
        // Try to find from config/app.php in the update
        $appFile = $extractPath . '*/config/app.php';
        $files = glob($appFile);
        if (!empty($files)) {
            $content = file_get_contents($files[0]);
            if (preg_match("/define\s*\(\s*['\"]APP_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get backup history
     */
    public function getBackupHistory($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, u.username 
                FROM system_backups b
                LEFT JOIN users u ON b.created_by = u.id
                ORDER BY b.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get backup history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get update history
     */
    public function getUpdateHistory($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, us.username 
                FROM system_updates u
                LEFT JOIN users us ON u.applied_by = us.id
                ORDER BY COALESCE(u.applied_at, u.created_at) DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get update history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extract ZIP file
     */
    private function extractZip($zipPath, $extractPath) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive extension not available');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($zipPath);
        
        if ($result !== true) {
            throw new Exception("ZIP dosyası açılamadı (Error code: {$result})");
        }
        
        // Create extract directory
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }
        
        $zip->extractTo($extractPath);
        $zip->close();
        
        return true;
    }
    
    /**
     * Apply files from update to application
     */
    private function applyFiles($sourcePath) {
        // GitHub zips have a root folder, find it
        $items = scandir($sourcePath);
        $rootFolder = null;
        
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($sourcePath . $item)) {
                $rootFolder = $item;
                break;
            }
        }
        
        if (!$rootFolder) {
            throw new Exception('Invalid update package structure');
        }
        
        $updateRoot = $sourcePath . $rootFolder . '/';
        $appRoot = ROOT_PATH . '/';
        
        // Copy files recursively, excluding certain files
        $this->copyDirectory($updateRoot, $appRoot);
    }
    
    /**
     * Copy directory recursively with exclusions
     */
    private function copyDirectory($source, $destination) {
        if (!is_dir($source)) {
            return;
        }
        
        $dir = opendir($source);
        
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $source . $file;
            $destPath = $destination . $file;
            
            // Get relative path for exclusion checking
            $relativePath = str_replace(ROOT_PATH . '/', '', $destPath);
            
            // Check if file should be excluded
            if ($this->isFileExcluded($relativePath)) {
                continue;
            }
            
            if (is_dir($sourcePath)) {
                // Create directory if it doesn't exist
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
                // Recursively copy subdirectory
                $this->copyDirectory($sourcePath . '/', $destPath . '/');
            } else {
                // Copy file
                copy($sourcePath, $destPath);
            }
        }
        
        closedir($dir);
    }
    
    /**
     * Check if file should be excluded from update
     */
    private function isFileExcluded($relativePath) {
        $excludePatterns = [
            'config/database.php',
            '.git/',
            '.gitignore',
            'backups/',
            'temp/',
            'logs/',
            '.env',
            'uploads/'
        ];
        
        foreach ($excludePatterns as $pattern) {
            // Check if the path starts with or contains the pattern
            if (strpos($relativePath, $pattern) === 0 || strpos($relativePath, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Run database migrations if they exist
     */
    private function runMigrations($updatePath) {
        $migrationsPath = $updatePath . 'migrations/';
        
        if (!is_dir($migrationsPath)) {
            return; // No migrations to run
        }
        
        $migrations = glob($migrationsPath . '*.sql');
        
        foreach ($migrations as $migration) {
            try {
                $sql = file_get_contents($migration);
                $this->db->exec($sql);
                error_log("[UpdateManager] Migration executed: " . basename($migration));
            } catch (Exception $e) {
                error_log("[UpdateManager] Migration error in " . basename($migration) . ": " . $e->getMessage());
                // Continue with other migrations
            }
        }
    }
    
    /**
     * Update version in database
     */
    private function updateVersion($version) {
        $stmt = $this->db->prepare("
            UPDATE settings 
            SET setting_value = ? 
            WHERE setting_key = 'app_version'
        ");
        $stmt->execute([$version]);
    }
    
    /**
     * Cleanup temporary files
     */
    private function cleanupTempFiles($zipPath, $extractPath) {
        try {
            // Delete ZIP file
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            
            // Delete extracted directory
            if (is_dir($extractPath)) {
                $this->deleteDirectory($extractPath);
            }
        } catch (Exception $e) {
            error_log("[UpdateManager] Cleanup error: " . $e->getMessage());
            // Non-fatal, just log
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Restore from backup
     */
    public function restoreBackup($backupId, $userId) {
        try {
            // Get backup info
            $stmt = $this->db->prepare("SELECT * FROM system_backups WHERE id = ?");
            $stmt->execute([$backupId]);
            $backup = $stmt->fetch();
            
            if (!$backup) {
                throw new Exception('Backup not found');
            }
            
            if (!file_exists($backup['filepath'])) {
                throw new Exception('Backup file not found');
            }
            
            $filepath = $backup['filepath'];
            
            // Check if it's a ZIP backup (new format) or SQL (old format)
            if (pathinfo($filepath, PATHINFO_EXTENSION) === 'zip') {
                // ZIP backup - extract and restore
                error_log("[UpdateManager] Restoring from ZIP backup");
                
                $extractPath = TEMP_PATH . '/restore_' . time() . '/';
                
                // Extract ZIP
                $zip = new ZipArchive();
                if ($zip->open($filepath) !== true) {
                    throw new Exception('ZIP dosyası açılamadı');
                }
                
                if (!is_dir($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }
                
                $zip->extractTo($extractPath);
                $zip->close();
                
                // Restore database
                $sqlFile = $extractPath . 'database.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    $this->db->exec($sql);
                    error_log("[UpdateManager] Database restored from ZIP");
                }
                
                // Restore files if they exist
                $this->restoreFilesFromExtract($extractPath);
                
                // Cleanup
                $this->deleteDirectory($extractPath);
                
                error_log("[UpdateManager] ZIP backup restored successfully");
            } else {
                // Old SQL backup
                $sql = file_get_contents($filepath);
                $this->db->exec($sql);
                error_log("[UpdateManager] SQL backup restored successfully");
            }
            
            return [
                'success' => true,
                'message' => 'Yedek başarıyla geri yüklendi'
            ];
        } catch (Exception $e) {
            error_log("[UpdateManager] Restore error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to restore backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore files from extracted backup
     */
    private function restoreFilesFromExtract($extractPath) {
        // Check for root files
        $rootFiles = ['index.php'];
        foreach ($rootFiles as $file) {
            $source = $extractPath . 'root/' . $file;
            if (file_exists($source)) {
                copy($source, ROOT_PATH . '/' . $file);
            }
        }
        
        // Restore includes
        $includesPath = $extractPath . 'includes/';
        if (is_dir($includesPath)) {
            $files = glob($includesPath . '*.php');
            foreach ($files as $file) {
                copy($file, INCLUDES_PATH . '/' . basename($file));
            }
        }
        
        // Restore pages
        $pagesPath = $extractPath . 'pages/';
        if (is_dir($pagesPath)) {
            $files = glob($pagesPath . '*.php');
            foreach ($files as $file) {
                $dest = ROOT_PATH . '/pages/' . basename($file);
                if (!file_exists($dest) || basename($file) !== 'update-manager.php') {
                    copy($file, $dest);
                }
            }
        }
        
        // Restore API
        $apiPath = $extractPath . 'api/';
        if (is_dir($apiPath)) {
            $files = glob($apiPath . '*.php');
            foreach ($files as $file) {
                copy($file, ROOT_PATH . '/api/' . basename($file));
            }
        }
        
        // Restore assets
        $assetsCssPath = $extractPath . 'assets/css/';
        if (is_dir($assetsCssPath)) {
            $files = glob($assetsCssPath . '*.css');
            foreach ($files as $file) {
                copy($file, ROOT_PATH . '/assets/css/' . basename($file));
            }
        }
        
        $assetsJsPath = $extractPath . 'assets/js/';
        if (is_dir($assetsJsPath)) {
            $files = glob($assetsJsPath . '*.js');
            foreach ($files as $file) {
                copy($file, ROOT_PATH . '/assets/js/' . basename($file));
            }
        }
        
        error_log("[UpdateManager] Files restored from backup");
    }
}
