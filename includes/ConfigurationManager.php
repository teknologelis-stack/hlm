<?php
/**
 * ConfigurationManager - Yapılandırma içe/dışa aktarma
 * 
 * Desteklenen formatlar:
 * - JSON (sistem yapılandırması)
 * - CSV (settings, roles, permissions)
 * - SQL (veritabanı dump)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/update-config.php';

class ConfigurationManager {
    private $db;
    private $userId;
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId ?? ($_SESSION['user_id'] ?? null);
    }
    
    /**
     * JSON sistem snapshot oluştur
     * İçerik: settings, roles, permissions, system_info
     * @return array
     */
    public function createSnapshot() {
        try {
            $snapshot = [
                'version' => APP_VERSION,
                'created_at' => date('Y-m-d H:i:s'),
                'snapshot_type' => 'full',
                'data' => []
            ];
            
            // Settings tablosunu al (varsa)
            $settings = $this->db->fetchAll("SELECT * FROM settings ORDER BY setting_key");
            $snapshot['data']['settings'] = $settings;
            
            // Roller ve izinler
            $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY id");
            $snapshot['data']['roles'] = $roles;
            
            // Kullanıcılar (şifreler hariç)
            $users = $this->db->fetchAll(
                "SELECT id, username, email, full_name, role_id, is_active, 
                 created_at, last_login FROM users ORDER BY id"
            );
            $snapshot['data']['users'] = $users;
            
            // Cihaz listesi (hassas bilgiler hariç)
            $devices = $this->db->fetchAll(
                "SELECT id, name, ip_address, port, device_type, is_main, 
                 is_active FROM devices ORDER BY name"
            );
            $snapshot['data']['devices'] = $devices;
            
            // Sistem bilgisi
            $snapshot['system_info'] = [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'database_version' => $this->db->fetchOne("SELECT VERSION() as version")['version']
            ];
            
            return [
                'success' => true,
                'snapshot' => $snapshot,
                'message' => 'Snapshot oluşturuldu'
            ];
            
        } catch (Exception $e) {
            error_log("Create snapshot error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Snapshot oluşturulamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Snapshot'ı JSON dosya olarak export et
     * @return array ['success' => bool, 'file_path' => string]
     */
    public function exportJSON() {
        try {
            $snapshot = $this->createSnapshot();
            
            if (!$snapshot['success']) {
                return $snapshot;
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $filename = 'config-snapshot-' . $timestamp . '.json';
            $filepath = CONFIG_EXPORT_DIR . $filename;
            
            $json = json_encode($snapshot['snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($filepath, $json);
            
            return [
                'success' => true,
                'file_path' => $filepath,
                'filename' => $filename,
                'file_size' => filesize($filepath),
                'message' => 'JSON export başarılı'
            ];
            
        } catch (Exception $e) {
            error_log("Export JSON error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'JSON export başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * CSV export - Settings için
     * @param string $type 'settings', 'users', 'roles', 'devices'
     * @return array
     */
    public function exportCSV($type = 'settings') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "export-{$type}-{$timestamp}.csv";
            $filepath = CONFIG_EXPORT_DIR . $filename;
            
            $fp = fopen($filepath, 'w');
            
            // UTF-8 BOM ekle (Excel için)
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
            
            switch ($type) {
                case 'settings':
                    $data = $this->db->fetchAll("SELECT * FROM settings ORDER BY setting_key");
                    if (!empty($data)) {
                        fputcsv($fp, array_keys($data[0]));
                        foreach ($data as $row) {
                            fputcsv($fp, $row);
                        }
                    }
                    break;
                    
                case 'users':
                    $data = $this->db->fetchAll(
                        "SELECT id, username, email, full_name, role_id, is_active 
                         FROM users ORDER BY id"
                    );
                    if (!empty($data)) {
                        fputcsv($fp, array_keys($data[0]));
                        foreach ($data as $row) {
                            fputcsv($fp, $row);
                        }
                    }
                    break;
                    
                case 'roles':
                    $data = $this->db->fetchAll("SELECT * FROM roles ORDER BY id");
                    if (!empty($data)) {
                        fputcsv($fp, array_keys($data[0]));
                        foreach ($data as $row) {
                            fputcsv($fp, $row);
                        }
                    }
                    break;
                    
                case 'devices':
                    $data = $this->db->fetchAll("SELECT * FROM devices ORDER BY name");
                    if (!empty($data)) {
                        fputcsv($fp, array_keys($data[0]));
                        foreach ($data as $row) {
                            fputcsv($fp, $row);
                        }
                    }
                    break;
                    
                default:
                    fclose($fp);
                    @unlink($filepath);
                    throw new Exception("Geçersiz export tipi: $type");
            }
            
            fclose($fp);
            
            return [
                'success' => true,
                'file_path' => $filepath,
                'filename' => $filename,
                'file_size' => filesize($filepath),
                'message' => 'CSV export başarılı'
            ];
            
        } catch (Exception $e) {
            error_log("Export CSV error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'CSV export başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * CSV import - Ayarlar için
     * @param string $filePath
     * @param string $type
     * @return array
     */
    public function importCSV($filePath, $type = 'settings') {
        try {
            if (!file_exists($filePath)) {
                throw new Exception('Dosya bulunamadı');
            }
            
            $fp = fopen($filePath, 'r');
            
            // BOM'u atla
            $bom = fread($fp, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($fp);
            }
            
            $headers = fgetcsv($fp);
            $imported = 0;
            $skipped = 0;
            $errors = [];
            
            while (($row = fgetcsv($fp)) !== false) {
                try {
                    $data = array_combine($headers, $row);
                    
                    switch ($type) {
                        case 'settings':
                            // Setting var mı kontrol et
                            $existing = $this->db->fetchOne(
                                "SELECT id FROM settings WHERE setting_key = ?",
                                [$data['setting_key']]
                            );
                            
                            if ($existing) {
                                $this->db->update('settings',
                                    ['setting_value' => $data['setting_value']],
                                    'setting_key = :key',
                                    ['key' => $data['setting_key']]
                                );
                            } else {
                                $this->db->insert('settings', $data);
                            }
                            $imported++;
                            break;
                            
                        default:
                            $errors[] = "Desteklenmeyen tip: $type";
                            $skipped++;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $skipped++;
                }
            }
            
            fclose($fp);
            
            // Import kaydı oluştur
            $this->db->insert('config_imports', [
                'import_type' => 'csv',
                'file_name' => basename($filePath),
                'imported_items' => $imported,
                'skipped_items' => $skipped,
                'imported_by' => $this->userId,
                'import_summary' => json_encode([
                    'type' => $type,
                    'errors' => $errors
                ], JSON_UNESCAPED_UNICODE),
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'message' => "$imported öğe içe aktarıldı, $skipped atlandı"
            ];
            
        } catch (Exception $e) {
            error_log("Import CSV error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'CSV import başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * JSON import
     * @param string $jsonData JSON string
     * @return array
     */
    public function importJSON($jsonData) {
        try {
            $data = json_decode($jsonData, true);
            
            if (!$data || !isset($data['data'])) {
                throw new Exception('Geçersiz JSON formatı');
            }
            
            $imported = 0;
            $skipped = 0;
            $errors = [];
            
            // Settings
            if (isset($data['data']['settings'])) {
                foreach ($data['data']['settings'] as $setting) {
                    try {
                        $existing = $this->db->fetchOne(
                            "SELECT id FROM settings WHERE setting_key = ?",
                            [$setting['setting_key']]
                        );
                        
                        if ($existing) {
                            $this->db->update('settings',
                                ['setting_value' => $setting['setting_value']],
                                'setting_key = :key',
                                ['key' => $setting['setting_key']]
                            );
                        } else {
                            $this->db->insert('settings', $setting);
                        }
                        $imported++;
                    } catch (Exception $e) {
                        $errors[] = "Setting error: " . $e->getMessage();
                        $skipped++;
                    }
                }
            }
            
            // Roles
            if (isset($data['data']['roles'])) {
                foreach ($data['data']['roles'] as $role) {
                    try {
                        $existing = $this->db->fetchOne(
                            "SELECT id FROM roles WHERE name = ?",
                            [$role['name']]
                        );
                        
                        if ($existing) {
                            $this->db->update('roles',
                                ['permissions' => $role['permissions']],
                                'name = :name',
                                ['name' => $role['name']]
                            );
                        } else {
                            $this->db->insert('roles', $role);
                        }
                        $imported++;
                    } catch (Exception $e) {
                        $errors[] = "Role error: " . $e->getMessage();
                        $skipped++;
                    }
                }
            }
            
            // Import kaydı
            $this->db->insert('config_imports', [
                'import_type' => 'json',
                'file_name' => 'snapshot-import',
                'imported_items' => $imported,
                'skipped_items' => $skipped,
                'imported_by' => $this->userId,
                'import_summary' => json_encode([
                    'errors' => $errors,
                    'version' => $data['version'] ?? 'unknown'
                ], JSON_UNESCAPED_UNICODE),
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'message' => "$imported öğe içe aktarıldı"
            ];
            
        } catch (Exception $e) {
            error_log("Import JSON error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'JSON import başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Snapshot geri yükleme
     * @param string $snapshotData JSON data
     * @return array
     */
    public function restoreSnapshot($snapshotData) {
        return $this->importJSON($snapshotData);
    }
    
    /**
     * Import geçmişini getir
     */
    public function getImportHistory($limit = 20) {
        return $this->db->fetchAll(
            "SELECT ci.*, u.username as imported_by_name 
             FROM config_imports ci
             LEFT JOIN users u ON ci.imported_by = u.id
             ORDER BY ci.imported_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}
?>
