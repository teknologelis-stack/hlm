<?php
/**
 * Güncelleme Sistemi Ayarları
 * 
 * Bu dosya güncelleme ve yedekleme sistemi için gerekli tüm
 * yapılandırma ayarlarını içerir.
 */

// GitHub API Ayarları
define('UPDATE_CHECK_URL', 'https://api.github.com/repos/teknologelis-stack/hlm/releases/latest');
define('UPDATE_RELEASES_URL', 'https://api.github.com/repos/teknologelis-stack/hlm/releases');

// Dizin Yapılandırması
define('UPDATE_BACKUP_DIR', __DIR__ . '/../backups/');
define('UPDATE_TEMP_DIR', __DIR__ . '/../temp/updates/');
define('CONFIG_EXPORT_DIR', __DIR__ . '/../backups/configs/');

// Otomatik Güncelleme Ayarları
define('UPDATE_AUTO_CHECK', true);  // Otomatik kontrol açık mı?
define('UPDATE_AUTO_APPLY', false); // Otomatik uygulama KAPALI (güvenlik)
define('UPDATE_CHECK_INTERVAL', 86400); // 24 saat (saniye cinsinden)

// Yedek Saklama Ayarları
define('UPDATE_BACKUP_RETENTION_DAYS', 30); // Yedekleri 30 gün sakla
define('UPDATE_MAX_BACKUPS', 10); // Maximum yedek sayısı
define('CONFIG_BACKUP_RETENTION_DAYS', 90); // Yapılandırma yedekleri 90 gün

// Güvenlik Ayarları
define('UPDATE_VERIFY_SSL', true); // SSL sertifikası doğrulama
define('UPDATE_MAX_FILE_SIZE', 52428800); // 50 MB maksimum güncelleme boyutu

// Yedekleme Ayarları
define('BACKUP_INCLUDE_DATABASE', true); // Veritabanını yedekle
define('BACKUP_INCLUDE_CONFIG', true); // Yapılandırma dosyalarını yedekle
define('BACKUP_INCLUDE_UPLOADS', true); // Yüklenen dosyaları yedekle
define('BACKUP_COMPRESSION', true); // ZIP sıkıştırma kullan

// İzin Yapılandırması
define('UPDATE_PERMISSION', 'system_manage'); // Gerekli izin

// Klasörleri oluştur (yoksa)
$directories = [
    UPDATE_BACKUP_DIR,
    UPDATE_TEMP_DIR,
    CONFIG_EXPORT_DIR
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    // .htaccess ile doğrudan erişimi engelle
    $htaccess = $dir . '.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n");
    }
}

// Log dizini kontrolü
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
?>
