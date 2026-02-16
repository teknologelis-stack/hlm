# Sistem Güncelleme ve Yapılandırma Yönetimi

Bu modül, HLM sisteminin güncelleme, yedekleme ve yapılandırma yönetimi için geliştirilmiş bağımsız bir sistemdir.

## 📋 Özellikler

- Online/Offline güncelleme desteği
- Sistem yedekleme ve geri yükleme
- Yapılandırma import/export (JSON/CSV)
- Otomatik güncelleme kontrolü (cron)
- Güncelleme geçmişi takibi

## 🚀 Kurulum

### Veritabanı Tablolarını Oluştur
```bash
mysql -u [user] -p [database] < config/migrations/update_system.sql
```

### Dizin İzinlerini Ayarla
```bash
chmod 755 backups/ temp/updates/
chmod +x cron/auto-update-check.php
```

### Cron Job Kurulumu
```
0 2 * * * /usr/bin/php /path/to/hlm/cron/auto-update-check.php
```

## 📖 Kullanım

**Panel Ayarları → Güncelleme Yöneticisi** menüsünden erişim.

---
**Versiyon**: 1.0.0 | **Tarih**: 2026-02-16
