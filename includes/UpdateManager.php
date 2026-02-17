<?php
/**
 * Update Manager Class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class UpdateManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Check for available updates (simulated)
     */
    public function checkForUpdates() {
        // Get current version
        $currentVersion = APP_VERSION;
        
        // Simulate checking for updates
        // In production, this would query a remote API
        $availableUpdates = [
            [
                'version' => '1.0.1',
                'released_at' => '2026-02-17',
                'description' => 'Bug fixes and performance improvements',
                'changes' => [
                    'Fixed login session timeout issue',
                    'Improved backup compression',
                    'Updated security patches'
                ]
            ]
        ];
        
        // Filter updates newer than current version
        $newUpdates = array_filter($availableUpdates, function($update) use ($currentVersion) {
            return version_compare($update['version'], $currentVersion, '>');
        });
        
        return [
            'current_version' => $currentVersion,
            'updates_available' => !empty($newUpdates),
            'updates' => array_values($newUpdates)
        ];
    }
    
    /**
     * Create system backup
     */
    public function createBackup($userId, $type = 'manual') {
        try {
            $timestamp = date('Y-m-d_His');
            $filename = "backup_{$timestamp}.sql";
            $filepath = BACKUPS_PATH . '/' . $filename;
            
            // Get database configuration
            $stmt = $this->db->prepare("SELECT DATABASE() as dbname");
            $stmt->execute();
            $result = $stmt->fetch();
            $dbname = $result['dbname'];
            
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
     * Apply update
     */
    public function applyUpdate($version, $userId) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Create pre-update backup
            $backup = $this->createBackup($userId, 'pre-update');
            if (!$backup['success']) {
                throw new Exception('Failed to create pre-update backup');
            }
            
            // Simulate update process
            // In production, this would download and apply actual update files
            sleep(2); // Simulate update time
            
            // Update version in settings
            $stmt = $this->db->prepare("
                UPDATE settings 
                SET setting_value = ? 
                WHERE setting_key = 'app_version'
            ");
            $stmt->execute([$version]);
            
            // Record update in database
            $stmt = $this->db->prepare("
                INSERT INTO system_updates (version, description, applied_at, applied_by, status)
                VALUES (?, ?, NOW(), ?, 'applied')
            ");
            $stmt->execute([
                $version,
                'System updated to version ' . $version,
                $userId
            ]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Update applied successfully',
                'new_version' => $version,
                'backup_id' => $backup['backup_id']
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to apply update: ' . $e->getMessage()
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
                ORDER BY u.applied_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get update history error: " . $e->getMessage());
            return [];
        }
    }
}
