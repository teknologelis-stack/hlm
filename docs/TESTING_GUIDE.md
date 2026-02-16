# Test ve Doğrulama Rehberi

## Veritabanı Migration Test

### 1. Tabloları Oluştur

```bash
# MySQL/MariaDB kullanarak
mysql -u root -p mikrotik_panel < config/migrations/update_system.sql

# veya phpMyAdmin üzerinden SQL sekmesinden çalıştır
```

### 2. Tabloların Oluştuğunu Doğrula

```sql
SHOW TABLES LIKE 'system_%';
-- Beklenen sonuç: system_updates, system_backups

SHOW TABLES LIKE 'config_%';
-- Beklenen sonuç: config_imports

-- Tablo yapılarını kontrol et
DESCRIBE system_updates;
DESCRIBE system_backups;
DESCRIBE config_imports;
```

## Yetki Kontrolü Testi

### Mevcut Roller Tablosuna system_manage Yetkisi Ekleme

```sql
-- Admin rolüne system_manage yetkisi ekle (role_id=1 genelde Admin)
UPDATE roles 
SET permissions = JSON_SET(
    COALESCE(permissions, '{}'),
    '$.system_manage',
    true
)
WHERE id = 1;

-- Yetkinin eklendiğini doğrula
SELECT id, name, permissions FROM roles WHERE id = 1;
```

## Fonksiyon Testleri

### 1. Online Güncelleme Kontrolü

Panel üzerinden test:
1. Giriş yap (Admin yetkili kullanıcı)
2. Panel Ayarları → Güncelleme Yöneticisi
3. "Online Güncelleme" sekmesi
4. "Güncelleme Kontrol Et" butonuna tıkla

Beklenen sonuç:
- GitHub API'den güncel versiyon bilgisi gelir
- Eğer yeni versiyon varsa, changelog gösterilir
- "Güncellemeyi Uygula" butonu görünür

cURL ile test:
```bash
curl -X GET "http://localhost/api/system-update-check.php" \
  -b "PHPSESSID=your-session-cookie" \
  -H "Content-Type: application/json"
```

### 2. Yedek Oluşturma

Panel üzerinden:
1. "Yedekleme" sekmesi
2. Açıklama gir (opsiyonel)
3. "Yedek Oluştur" butonuna tıkla

Beklenen sonuç:
- `backups/` dizininde ZIP dosyası oluşur
- Veritabanında `system_backups` tablosuna kayıt eklenir
- Dosya boyutu ve tarih bilgileri gösterilir

Manuel test:
```bash
# Backups dizinini kontrol et
ls -lh backups/

# Veritabanı kaydını kontrol et
mysql -u root -p mikrotik_panel -e "SELECT * FROM system_backups ORDER BY created_at DESC LIMIT 1;"
```

### 3. Yapılandırma Export

Panel üzerinden:
1. "Yapılandırma" sekmesi
2. "JSON İndir" veya "Settings CSV" butonuna tıkla

Beklenen sonuç:
- JSON/CSV dosyası indirilir
- Dosya içeriği doğru formatladır

cURL ile test:
```bash
# JSON export
curl -X GET "http://localhost/api/config-export.php?format=json" \
  -b "PHPSESSID=your-session-cookie" \
  -o config-backup.json

# CSV export
curl -X GET "http://localhost/api/config-export.php?format=csv&type=settings" \
  -b "PHPSESSID=your-session-cookie" \
  -o settings.csv
```

### 4. Yapılandırma Import

Panel üzerinden:
1. "Yapılandırma" sekmesi
2. Daha önce export edilen JSON/CSV dosyasını seç
3. "İçe Aktar" butonuna tıkla

Beklenen sonuç:
- İçe aktarılan öğe sayısı gösterilir
- Veritabanı güncellenir
- `config_imports` tablosuna kayıt eklenir

### 5. Cron Job Testi

Manuel çalıştırma:
```bash
php cron/auto-update-check.php
```

Beklenen sonuç:
- Konsola güncelleme kontrol mesajı yazdırılır
- `logs/update-check.log` dosyasına kayıt yapılır
- Yeni versiyon varsa bildirim mesajı gösterilir

Log kontrolü:
```bash
tail -f logs/update-check.log
```

## Güvenlik Testleri

### 1. Yetki Kontrolü

Yetkisiz kullanıcı ile test:
```bash
# Yetkisiz kullanıcı ile API'ye istek at
curl -X GET "http://localhost/api/system-update-check.php" \
  -b "PHPSESSID=non-admin-session"
```

Beklenen sonuç: 403 Forbidden

### 2. Dosya Upload Güvenliği

Geçersiz dosya türü ile test:
- .exe, .sh, .php dosyalarını yüklemeyi dene
- Sadece .zip dosyaları kabul edilmeli

### 3. SQL Injection Koruması

Manuel test (güvenli olmalı):
```sql
-- Tüm API endpoint'lerinde parameterized query kullanıldığını doğrula
-- Örnekte gösterilen girdi test edilebilir ancak zararsız olmalı
```

### 4. XSS Koruması

JavaScript'te changelog rendering:
- Kötü amaçlı HTML içeren changelog test et
- Output escape edilmeli

## Performans Testleri

### 1. Büyük Yedek Dosyası

```bash
# Backup boyutunu kontrol et
ls -lh backups/backup-*.zip

# Backup süresi test et
time php -r "
require 'includes/UpdateManager.php';
\$manager = new UpdateManager(1);
\$result = \$manager->createSystemBackup('Performance test');
print_r(\$result);
"
```

### 2. Veritabanı Export Performansı

Büyük veritabanı ile test:
- 10,000+ kayıt ekle
- Export süresini ölç

## Hata Senaryoları

### 1. Disk Alanı Yetersiz

```bash
# Disk kullanımını kontrol et
df -h

# Test için geçici olarak disk alanı doldurmayı dene
```

### 2. Bozuk ZIP Dosyası

```bash
# Bozuk ZIP dosyası oluştur
echo "invalid zip content" > test-invalid.zip

# Upload et ve hata mesajını kontrol et
```

### 3. GitHub API Hata Durumu

- İnternet bağlantısını kes
- API timeout kontrolü
- Rate limit aşımı

## Rollback Testi

### Güncelleme Sonrası Geri Dönüş

1. Önce yedek oluştur
2. Bir güncelleme uygula (veya test dosyası değiştir)
3. Yedeği geri yükle
4. Değişikliklerin geri döndüğünü doğrula

## Dokümantasyon Doğrulama

### README Kontrolü

- [ ] Tüm kurulum adımları açık
- [ ] Gereksinimler listelenmiş
- [ ] Örnek komutlar çalışıyor
- [ ] Ekran görüntüleri eklenmiş (isteğe bağlı)

### API Dokümantasyonu

- [ ] Her endpoint için method açıklaması var
- [ ] Request/Response formatları belirtilmiş
- [ ] Hata kodları dokümante edilmiş

## Checklist

- [ ] Database migration başarılı
- [ ] Tüm tablolar oluşturulmuş
- [ ] Yetkiler doğru ayarlanmış
- [ ] Online güncelleme kontrolü çalışıyor
- [ ] Offline güncelleme yükleme çalışıyor
- [ ] Yedek oluşturma çalışıyor
- [ ] Yedek geri yükleme çalışıyor
- [ ] JSON export/import çalışıyor
- [ ] CSV export/import çalışıyor
- [ ] Cron job çalışıyor
- [ ] Log dosyaları oluşturuluyor
- [ ] Güvenlik kontrolleri geçiyor
- [ ] Hata durumları düzgün yönetiliyor
- [ ] UI responsive (mobil uyumlu)
- [ ] Tüm butonlar çalışıyor
- [ ] Form validasyonları aktif

## Notlar

- Test öncesi production veritabanının yedeğini al
- Test ortamında çalış, production'da test yapma
- Her önemli değişiklikten sonra yedek al
- Log dosyalarını düzenli kontrol et
