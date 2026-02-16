<?php
/**
 * UpdateManager - Sistem güncelleme ve yapılandırma yönetimi
 * 
 * Özellikler:
 * - Online güncelleme kontrolü (GitHub releases API)
 * - Offline güncelleme (zip/tar.gz upload)
 * - Sistem yedeği oluşturma (SQL + config files)
 * - Yapılandırma import/export (JSON format)
 * - Güncelleme geçmişi takibi
 * - Rollback mekanizması
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/update-config.php';

class UpdateManager {
    private $db;
    private $currentVersion;
    private $updateUrl;
    private $userId;
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->currentVersion = APP_VERSION;
        $this->updateUrl = UPDATE_CHECK_URL;
        $this->userId = $userId ?? ($_SESSION['user_id'] ?? null);
    }
    
    /**
     * Online güncelleme kontrolü
     * @return array ['available' => bool, 'version' => string, 'download_url' => string, 'changelog' => string]
     */
    public function checkOnlineUpdate() {
        try {
            $ch = curl_init($this->updateUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => UPDATE_VERIFY_SSL,
                CURLOPT_USERAGENT => 'HLM-Update-Manager/' . APP_VERSION,
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return [
                    'available' => false,
                    'message' => 'Güncelleme sunucusuna ulaşılamadı',
                    'current' => $this->currentVersion
                ];
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['tag_name'])) {
                return [
                    'available' => false,
                    'message' => 'Geçersiz yanıt',
                    'current' => $this->currentVersion
                ];
            }
            
            $latestVersion = ltrim($data['tag_name'], 'v');
            $available = version_compare($latestVersion, $this->currentVersion, '>');
            
            $result = [
                'available' => $available,
                'current' => $this->currentVersion,
                'latest' => $latestVersion,
                'message' => $available ? 'Yeni güncelleme mevcut' : 'Sistem güncel'
            ];
            
            if ($available) {
                $result['download_url'] = $data['zipball_url'] ?? null;
                $result['changelog'] = $data['body'] ?? 'Değişiklik notları mevcut değil';
                $result['published_at'] = $data['published_at'] ?? null;
                $result['release_name'] = $data['name'] ?? "Versiyon $latestVersion";
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Update check error: " . $e->getMessage());
            return [
                'available' => false,
                'message' => 'Hata: ' . $e->getMessage(),
                'current' => $this->currentVersion
            ];
        }
    }
    
    /**
     * Güncelleme paketini indir ve doğrula
     * @param string $downloadUrl
     * @return array ['success' => bool, 'file_path' => string, 'message' => string]
     */
    public function downloadUpdate($downloadUrl) {
        try {
            $tempFile = UPDATE_TEMP_DIR . 'update-' . time() . '.zip';
            
            $ch = curl_init($downloadUrl);
            $fp = fopen($tempFile, 'w+');
            
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => UPDATE_VERIFY_SSL,
                CURLOPT_USERAGENT => 'HLM-Update-Manager/' . APP_VERSION,
                CURLOPT_TIMEOUT => 300
            ]);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            
            if ($httpCode !== 200) {
                @unlink($tempFile);
                return [
                    'success' => false,
                    'message' => 'İndirme başarısız (HTTP ' . $httpCode . ')'
                ];
            }
            
            // Dosya boyutu kontrolü
            $fileSize = filesize($tempFile);
            if ($fileSize > UPDATE_MAX_FILE_SIZE) {
                @unlink($tempFile);
                return [
                    'success' => false,
                    'message' => 'Güncelleme paketi çok büyük'
                ];
            }
            
            // ZIP doğrulama
            $zip = new ZipArchive();
            if ($zip->open($tempFile) !== true) {
                @unlink($tempFile);
                return [
                    'success' => false,
                    'message' => 'Geçersiz ZIP dosyası'
                ];
            }
            $zip->close();
            
            return [
                'success' => true,
                'file_path' => $tempFile,
                'file_size' => $fileSize,
                'message' => 'Güncelleme paketi indirildi'
            ];
            
        } catch (Exception $e) {
            error_log("Download update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Güncellemeyi uygula
     * @param string $packagePath
     * @param string $versionTo
     * @return array ['success' => bool, 'message' => string]
     */
    public function applyUpdate($packagePath, $versionTo) {
        $updateId = null;
        
        try {
            // Güncelleme kaydı oluştur
            $updateId = $this->db->insert('system_updates', [
                'version_from' => $this->currentVersion,
                'version_to' => $versionTo,
                'update_type' => 'online',
                'status' => 'in_progress',
                'applied_by' => $this->userId,
                'started_at' => date('Y-m-d H:i:s')
            ]);
            
            // Otomatik yedek oluştur
            $backup = $this->createSystemBackup('Pre-update backup v' . $versionTo);
            
            if (!$backup['success']) {
                throw new Exception('Yedek oluşturulamadı: ' . $backup['message']);
            }
            
            // Yedek yolunu güncelle
            $this->db->update('system_updates',
                ['backup_path' => $backup['backup_path']],
                'id = :id',
                ['id' => $updateId]
            );
            
            // ZIP'i aç ve dosyaları çıkar
            $zip = new ZipArchive();
            if ($zip->open($packagePath) !== true) {
                throw new Exception('ZIP açılamadı');
            }
            
            $extractPath = UPDATE_TEMP_DIR . 'extract-' . time() . '/';
            mkdir($extractPath, 0755, true);
            
            $zip->extractTo($extractPath);
            $zip->close();
            
            // İlk klasörü bul (GitHub zipball formatı)
            $files = scandir($extractPath);
            $mainDir = null;
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_dir($extractPath . $file)) {
                    $mainDir = $extractPath . $file . '/';
                    break;
                }
            }
            
            if (!$mainDir) {
                throw new Exception('Güncelleme paketi yapısı geçersiz');
            }
            
            // Dosyaları kopyala (güvenli dosyalar)
            $rootPath = __DIR__ . '/../';
            $this->copyUpdateFiles($mainDir, $rootPath);
            
            // Güncelleme başarılı
            $this->db->update('system_updates',
                [
                    'status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $updateId]
            );
            
            // Temizlik
            @unlink($packagePath);
            $this->deleteDirectory($extractPath);
            
            return [
                'success' => true,
                'message' => 'Güncelleme başarıyla uygulandı',
                'new_version' => $versionTo,
                'backup_path' => $backup['backup_path']
            ];
            
        } catch (Exception $e) {
            error_log("Apply update error: " . $e->getMessage());
            
            // Başarısız olarak işaretle
            if ($updateId) {
                $this->db->update('system_updates',
                    [
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'completed_at' => date('Y-m-d H:i:s')
                    ],
                    'id = :id',
                    ['id' => $updateId]
                );
            }
            
            return [
                'success' => false,
                'message' => 'Güncelleme başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Dosyaları güvenli şekilde kopyala
     */
    private function copyUpdateFiles($source, $dest) {
        // Güncellenmeyecek dosya ve klasörler
        $exclude = ['.git', 'node_modules', 'vendor', 'logs', 'backups', 'temp', 'uploads', '.env'];
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            
            // Hariç tutulanları atla
            if (in_array($file, $exclude)) continue;
            
            $srcPath = $source . $file;
            $destPath = $dest . $file;
            
            if (is_dir($srcPath)) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
                $this->copyUpdateFiles($srcPath . '/', $destPath . '/');
            } else {
                // Sadece güvenli dosya uzantılarını kopyala
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $allowedExts = ['php', 'js', 'css', 'html', 'json', 'sql', 'txt', 'md'];
                
                if (in_array($ext, $allowedExts)) {
                    copy($srcPath, $destPath);
                }
            }
        }
        closedir($dir);
    }
    
    /**
     * Sistem yedeği oluştur
     * @param string $description
     * @return array ['success' => bool, 'backup_path' => string, 'backup_name' => string]
     */
    public function createSystemBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = 'backup-' . $timestamp . '.zip';
            $backupPath = UPDATE_BACKUP_DIR . $backupName;
            
            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
                throw new Exception('ZIP oluşturulamadı');
            }
            
            $rootPath = __DIR__ . '/../';
            
            // Veritabanı yedeği
            if (BACKUP_INCLUDE_DATABASE) {
                $sqlBackup = $this->exportDatabase();
                $zip->addFromString('database-backup.sql', $sqlBackup);
            }
            
            // Yapılandırma dosyaları
            if (BACKUP_INCLUDE_CONFIG) {
                $this->addDirectoryToZip($zip, $rootPath . 'config/', 'config/');
                $this->addDirectoryToZip($zip, $rootPath . 'includes/', 'includes/');
            }
            
            // API ve Pages
            $this->addDirectoryToZip($zip, $rootPath . 'api/', 'api/');
            $this->addDirectoryToZip($zip, $rootPath . 'pages/', 'pages/');
            
            // Assets
            $this->addDirectoryToZip($zip, $rootPath . 'assets/', 'assets/');
            
            // Root dosyaları
            $rootFiles = ['index.php', 'logout.php', 'composer.json'];
            foreach ($rootFiles as $file) {
                if (file_exists($rootPath . $file)) {
                    $zip->addFile($rootPath . $file, $file);
                }
            }
            
            $zip->close();
            
            $fileSize = filesize($backupPath);
            
            // Veritabanına kaydet
            $backupId = $this->db->insert('system_backups', [
                'backup_name' => $backupName,
                'backup_type' => 'full',
                'file_path' => $backupPath,
                'file_size' => $fileSize,
                'created_by' => $this->userId,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Eski yedekleri temizle
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'backup_id' => $backupId,
                'file_size' => $fileSize,
                'download_url' => BASE_URL . '/api/download-backup.php?id=' . $backupId,
                'message' => 'Yedek başarıyla oluşturuldu'
            ];
            
        } catch (Exception $e) {
            error_log("Create backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Yedek oluşturulamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Veritabanını SQL olarak export et
     */
    private function exportDatabase() {
        $sql = "-- HLM Database Backup\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        $tables = $this->db->fetchAll("SHOW TABLES");
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            
            // Tablo yapısı
            $createTable = $this->db->fetchOne("SHOW CREATE TABLE `$tableName`");
            $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Veri
            $rows = $this->db->fetchAll("SELECT * FROM `$tableName`");
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array_map(function($val) use ($tableName) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        // MySQL gerçek escape kullan
                        $conn = $this->db->getConnection();
                        return "'" . $conn->quote($val) . "'";
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }
    
    /**
     * ZIP'e klasör ekle
     */
    private function addDirectoryToZip($zip, $sourcePath, $zipPath) {
        if (!is_dir($sourcePath)) return;
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen($sourcePath));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Yedeği geri yükle
     * @param string $backupPath
     * @return array ['success' => bool, 'message' => string, 'restored_items' => array]
     */
    public function restoreBackup($backupPath) {
        try {
            if (!file_exists($backupPath)) {
                throw new Exception('Yedek dosyası bulunamadı');
            }
            
            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new Exception('Yedek dosyası açılamadı');
            }
            
            $extractPath = UPDATE_TEMP_DIR . 'restore-' . time() . '/';
            mkdir($extractPath, 0755, true);
            
            $zip->extractTo($extractPath);
            $zip->close();
            
            $restoredItems = [];
            
            // Veritabanı geri yükle
            $dbBackup = $extractPath . 'database-backup.sql';
            if (file_exists($dbBackup)) {
                $sql = file_get_contents($dbBackup);
                
                // SQL dosyasının bizim backup sistemimizden geldiğini doğrula
                if (strpos($sql, '-- HLM Database Backup') !== 0) {
                    throw new Exception('Geçersiz yedek dosyası formatı');
                }
                
                // SQL'i satır satır çalıştır (güvenlik için)
                $conn = $this->db->getConnection();
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && strpos($statement, '--') !== 0) {
                        $conn->exec($statement);
                    }
                }
                $restoredItems[] = 'database';
            }
            
            // Dosyaları geri yükle
            $rootPath = __DIR__ . '/../';
            $this->copyUpdateFiles($extractPath, $rootPath);
            $restoredItems[] = 'files';
            
            // Temizlik
            $this->deleteDirectory($extractPath);
            
            return [
                'success' => true,
                'message' => 'Yedek başarıyla geri yüklendi',
                'restored_items' => $restoredItems
            ];
            
        } catch (Exception $e) {
            error_log("Restore backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Geri yükleme başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Eski yedekleri temizle
     */
    private function cleanOldBackups() {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . UPDATE_BACKUP_RETENTION_DAYS . ' days'));
            
            $oldBackups = $this->db->fetchAll(
                "SELECT * FROM system_backups WHERE created_at < ? ORDER BY created_at ASC",
                [$cutoffDate]
            );
            
            foreach ($oldBackups as $backup) {
                if (file_exists($backup['file_path'])) {
                    @unlink($backup['file_path']);
                }
                
                $this->db->delete('system_backups', 'id = :id', ['id' => $backup['id']]);
            }
            
            // Maksimum yedek sayısını kontrol et
            $backupCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM system_backups");
            if ($backupCount['count'] > UPDATE_MAX_BACKUPS) {
                $excess = intval($backupCount['count'] - UPDATE_MAX_BACKUPS);
                $oldestBackups = $this->db->fetchAll(
                    "SELECT * FROM system_backups ORDER BY created_at ASC LIMIT ?",
                    [$excess]
                );
                
                foreach ($oldestBackups as $backup) {
                    if (file_exists($backup['file_path'])) {
                        @unlink($backup['file_path']);
                    }
                    $this->db->delete('system_backups', 'id = :id', ['id' => $backup['id']]);
                }
            }
            
        } catch (Exception $e) {
            error_log("Clean old backups error: " . $e->getMessage());
        }
    }
    
    /**
     * Klasörü ve içeriğini sil
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
    
    /**
     * Güncelleme geçmişini getir
     */
    public function getUpdateHistory($limit = 10) {
        return $this->db->fetchAll(
            "SELECT su.*, u.username as applied_by_name 
             FROM system_updates su
             LEFT JOIN users u ON su.applied_by = u.id
             ORDER BY su.started_at DESC
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Yedek listesini getir
     */
    public function getBackupList($limit = 20) {
        return $this->db->fetchAll(
            "SELECT sb.*, u.username as created_by_name 
             FROM system_backups sb
             LEFT JOIN users u ON sb.created_by = u.id
             ORDER BY sb.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}
?>
