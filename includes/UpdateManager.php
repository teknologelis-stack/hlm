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
     * Create system backup
     */
    public function createBackup($userId, $type = 'manual') {
        try {
            $timestamp = date('Y-m-d_His');
            $filename = "backup_{$timestamp}.sql";
            $filepath = BACKUPS_PATH . '/' . $filename;
            
            // Create SQL dump (simulated - in production use mysqldump)
            $tables = ['roles', 'users', 'settings', 'system_updates', 'system_backups'];
            $sqlDump = "-- HLM Backup - " . date('Y-m-d H:i:s') . "\n\n";
            
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
            
            // Save backup file
            file_put_contents($filepath, $sqlDump);
            $fileSize = filesize($filepath);
            
            // Record backup in database
            $stmt = $this->db->prepare("
                INSERT INTO system_backups (filename, filepath, size_bytes, backup_type, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$filename, $filepath, $fileSize, $type, $userId]);
            
            return [
                'success' => true,
                'backup_id' => $this->db->lastInsertId(),
                'filename' => $filename,
                'size' => $fileSize
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
     * Apply update from GitHub
     */
    public function applyUpdate($version, $userId) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // 1. Create pre-update backup
            $backup = $this->createBackup($userId, 'pre-update');
            if (!$backup['success']) {
                throw new Exception('Yedekleme başarısız: ' . $backup['error']);
            }
            
            // 2. Get release information from GitHub
            $release = $this->github->getReleaseByTag($version);
            if (!$release) {
                throw new Exception('GitHub release bilgisi alınamadı');
            }
            
            // 3. Download ZIP file
            $tempDir = TEMP_PATH . '/';
            $zipPath = $tempDir . "update-{$version}.zip";
            
            $downloaded = $this->github->downloadRelease($release['zipball_url'], $zipPath);
            if (!$downloaded) {
                throw new Exception('Güncelleme dosyası indirilemedi');
            }
            
            // 4. Extract ZIP
            $extractPath = $tempDir . "update-{$version}/";
            $this->extractZip($zipPath, $extractPath);
            
            // 5. Apply files safely
            $this->applyFiles($extractPath);
            
            // 6. Run migrations if they exist
            $this->runMigrations($extractPath);
            
            // 7. Update version in database
            $this->updateVersion($version);
            
            // 8. Record update in database
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
            
            // 9. Cleanup temp files
            $this->cleanupTempFiles($zipPath, $extractPath);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "Sistem başarıyla {$version} versiyonuna güncellendi",
                'version' => $version,
                'backup_id' => $backup['backup_id']
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("[UpdateManager] Update error: " . $e->getMessage());
            error_log("[UpdateManager] Trace: " . $e->getTraceAsString());
            
            // Rollback to backup if it was created
            if (isset($backup) && $backup['success']) {
                $this->restoreBackup($backup['backup_id'], $userId);
            }
            
            // Record failed update
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO system_updates (version, error_message, applied_by, status)
                    VALUES (?, ?, ?, 'failed')
                ");
                $stmt->execute([$version, $e->getMessage(), $userId]);
            } catch (Exception $dbError) {
                error_log("[UpdateManager] Failed to record error: " . $dbError->getMessage());
            }
            
            return [
                'success' => false,
                'error' => 'Güncelleme hatası: ' . $e->getMessage()
            ];
        }
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
        $excludePatterns = UPDATE_EXCLUDE_PATTERNS;
        
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
            
            // Read backup SQL
            $sql = file_get_contents($backup['filepath']);
            
            // Execute restore
            $this->db->exec($sql);
            
            error_log("[UpdateManager] Backup restored: {$backup['filename']}");
            
            return [
                'success' => true,
                'message' => 'Backup restored successfully'
            ];
        } catch (Exception $e) {
            error_log("[UpdateManager] Restore error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to restore backup: ' . $e->getMessage()
            ];
        }
    }
}
