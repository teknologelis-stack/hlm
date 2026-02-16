<?php
session_start();

// Root path tanımla (PHP 7.0+ syntax)
$rootPath = dirname(__DIR__, 2);

// Dosyaların varlığını kontrol et
$requiredFiles = [
    $rootPath . '/config/database.php',
    $rootPath . '/config/app.php',
    $rootPath . '/includes/auth.php',
    $rootPath . '/includes/functions.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        error_log("HATA: Gerekli dosya bulunamadı: " . basename($file));
        die("Sistem hatası: Gerekli dosyalar yüklenemedi. Lütfen sistem yöneticisiyle iletişime geçin.");
    }
    require_once $file;
}

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('settings_manage');

$pageTitle = 'Panel Ayarları';
$db = Database::getInstance();

// Settings verilerini çek
$settings = [];
try {
    $settingsData = $db->fetchAll("SELECT * FROM settings");
    foreach ($settingsData as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    error_log("Settings error: " . $e->getMessage());
}

// Cihazları çek
$devices = [];
try {
    $devices = $db->fetchAll("SELECT id, name, ip_address, is_main FROM devices WHERE is_active = 1");
} catch (Exception $e) {
    error_log("Devices error: " . $e->getMessage());
}

include $rootPath . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Genel Ayarlar</h5>
            </div>
            <div class="card-body">
                <form id="generalSettingsForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Panel Adı</label>
                        <input type="text" class="form-control" name="panel_name" value="<?php echo clean($settings['panel_name'] ?? ''); ?>" placeholder="MikroTik Yönetim Paneli">
                        <small class="text-muted">Panelin üst kısmında görünen başlık</small>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Oturum Süresi (saniye)</label>
                            <input type="number" class="form-control" name="session_timeout" value="<?php echo clean($settings['session_timeout'] ?? '3600'); ?>" min="300">
                            <small class="text-muted">Varsayılan: 3600 (1 saat)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sayfa Başına Kayıt</label>
                            <input type="number" class="form-control" name="records_per_page" value="<?php echo clean($settings['records_per_page'] ?? '25'); ?>" min="10" max="100">
                            <small class="text-muted">Tablolarda gösterilecek kayıt sayısı</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tema</label>
                            <select class="form-select" name="theme">
                                <option value="light" <?php echo ($settings['theme'] ?? 'light') == 'light' ? 'selected' : ''; ?>>Açık</option>
                                <option value="dark" <?php echo ($settings['theme'] ?? 'light') == 'dark' ? 'selected' : ''; ?>>Koyu</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dil</label>
                            <select class="form-select" name="language">
                                <option value="tr" <?php echo ($settings['language'] ?? 'tr') == 'tr' ? 'selected' : ''; ?>>Türkçe</option>
                                <option value="en" <?php echo ($settings['language'] ?? 'tr') == 'en' ? 'selected' : ''; ?>>English</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i>Ayarları Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Ana Cihaz Ayarı</h5>
                <a href="<?php echo BASE_URL; ?>/pages/router-settings/device-management.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-gear me-2"></i>Cihazları Yönet
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Ana Cihaz</label>
                        <select class="form-select" id="mainDeviceSelect" disabled>
                            <option value="">Cihaz seçiniz...</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo $device['id']; ?>" <?php echo $device['is_main'] ? 'selected' : ''; ?>>
                                    <?php echo clean($device['name']); ?> (<?php echo clean($device['ip_address']); ?>)
                                    <?php echo $device['is_main'] ? ' ⭐' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            Tüm router işlemleri bu cihaz üzerinden yapılır. 
                            Ana cihazı değiştirmek için "Cihazları Yönet" sayfasını kullanın.
                        </small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <?php 
                        $mainDevice = $db->fetchOne("SELECT * FROM devices WHERE is_main = 1 AND is_active = 1");
                        if ($mainDevice): 
                        ?>
                            <button type="button" class="btn btn-info w-100" id="testMainDeviceBtn">
                                <i class="bi bi-wifi me-2"></i>Bağlantıyı Test Et
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0 w-100 py-2">
                                <i class="bi bi-exclamation-triangle me-2"></i>Ana cihaz tanımlı değil
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="connectionTestResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Güvenlik Ayarları</h5>
            </div>
            <div class="card-body">
                <form id="securitySettingsForm">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Maksimum Giriş Denemesi</label>
                            <input type="number" class="form-control" name="max_login_attempts" value="<?php echo clean($settings['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                            <small class="text-muted">Hatalı giriş deneme sayısı</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kilitleme Süresi (saniye)</label>
                            <input type="number" class="form-control" name="login_lockout_time" value="<?php echo clean($settings['login_lockout_time'] ?? '600'); ?>" min="60">
                            <small class="text-muted">Hesap kilitleme süresi</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Bilgi</h6>
                        <ul class="mb-0 small">
                            <li>Güvenlik ayarları tüm kullanıcılar için geçerlidir</li>
                            <li>Kilitleme süresi sonunda otomatik olarak açılır</li>
                            <li>Admin kullanıcıları manuel olarak kilit açabilir</li>
                        </ul>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-shield-check me-2"></i>Güvenlik Ayarlarını Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-image me-2"></i>Görünüm Ayarları</h5>
            </div>
            <div class="card-body">
                <form id="appearanceSettingsForm" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Panel Logosu</label>
                        <div class="logo-upload-area">
                            <div class="current-logo mb-3">
                                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" id="logoPreview" onerror="this.src='https://via.placeholder.com/200x80?text=Logo'">
                            </div>
                            <input type="file" class="form-control" name="logo" accept="image/*" id="logoInput">
                            <small class="text-muted">Önerilen boyut: 200x80px, Max: 2MB</small>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Renk Şeması</label>
                        <div class="color-scheme-grid">
                            <div class="color-option">
                                <input type="radio" name="color_scheme" id="color1" value="blue" checked>
                                <label for="color1">
                                    <div class="color-preview" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                    <span>Mavi</span>
                                </label>
                            </div>
                            <div class="color-option">
                                <input type="radio" name="color_scheme" id="color2" value="green">
                                <label for="color2">
                                    <div class="color-preview" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"></div>
                                    <span>Yeşil</span>
                                </label>
                            </div>
                            <div class="color-option">
                                <input type="radio" name="color_scheme" id="color3" value="purple">
                                <label for="color3">
                                    <div class="color-preview" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);"></div>
                                    <span>Mor</span>
                                </label>
                            </div>
                            <div class="color-option">
                                <input type="radio" name="color_scheme" id="color4" value="orange">
                                <label for="color4">
                                    <div class="color-preview" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"></div>
                                    <span>Turuncu</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-info btn-lg">
                            <i class="bi bi-palette me-2"></i>Görünüm Ayarlarını Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Tehlikeli Bölge</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Uyarı!</strong> Aşağıdaki işlemler geri alınamaz ve sistemi etkileyebilir.
                </div>
                
                <div class="dangerous-actions">
                    <div class="action-item">
                        <div>
                            <h6>Tüm Logları Temizle</h6>
                            <p class="text-muted small mb-0">Sistemdeki tüm aktivite logları silinecektir</p>
                        </div>
                        <button class="btn btn-outline-danger" onclick="clearLogs()">
                            <i class="bi bi-trash me-2"></i>Temizle
                        </button>
                    </div>
                    
                    <div class="action-item">
                        <div>
                            <h6>Oturumları Sonlandır</h6>
                            <p class="text-muted small mb-0">Tüm kullanıcıların oturumları kapatılacak</p>
                        </div>
                        <button class="btn btn-outline-warning" onclick="clearSessions()">
                            <i class="bi bi-box-arrow-right me-2"></i>Sonlandır
                        </button>
                    </div>
                    
                    <div class="action-item">
                        <div>
                            <h6>Veritabanını Sıfırla</h6>
                            <p class="text-muted small mb-0">Tüm veriler silinip varsayılan ayarlara dönülecek</p>
                        </div>
                        <button class="btn btn-outline-danger" onclick="resetDatabase()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Sıfırla
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Sistem Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="system-info-grid">
                    <div class="info-item">
                        <label>PHP Versiyonu</label>
                        <span><?php echo phpversion(); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Panel Versiyonu</label>
                        <span><?php echo APP_VERSION; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Veritabanı</label>
                        <span>MySQL <?php 
                            $version = $db->fetchOne("SELECT VERSION() as version");
                            echo $version['version'];
                        ?></span>
                    </div>
                    <div class="info-item">
                        <label>Sunucu Zamanı</label>
                        <span><?php echo date('d.m.Y H:i:s'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Toplam Kullanıcı</label>
                        <span><?php echo $db->fetchOne("SELECT COUNT(*) as count FROM users")['count']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Toplam Cihaz</label>
                        <span><?php echo $db->fetchOne("SELECT COUNT(*) as count FROM devices")['count']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.logo-upload-area {
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.current-logo img {
    max-width: 200px;
    max-height: 80px;
    object-fit: contain;
}

.color-scheme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.color-option input[type="radio"] {
    display: none;
}

.color-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 15px;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.color-option input[type="radio"]:checked + label {
    border-color: var(--primary-color);
    background: rgba(79, 70, 229, 0.05);
}

.color-preview {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.dangerous-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.action-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: var(--bg-light);
    border-radius: 10px;
}

.action-item h6 {
    margin: 0 0 5px 0;
    font-size: 15px;
    font-weight: 600;
}

.system-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item {
    background: var(--bg-light);
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid var(--primary-color);
}

.info-item label {
    display: block;
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 5px;
}

.info-item span {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
}
</style>

<script>
document.getElementById('generalSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>/api/settings-update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Ayarlar kaydedildi', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    });
});

document.getElementById('securitySettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>/api/settings-update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Güvenlik ayarları kaydedildi', 'success');
        } else {
            showToast(data.message, 'error');
        }
    });
});

document.getElementById('appearanceSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>/api/settings-update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Görünüm ayarları kaydedildi', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    });
});

document.getElementById('logoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

function clearLogs() {
    if (!confirm('Tüm logları silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/clear-logs.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Loglar temizlendi', 'success');
        } else {
            showToast(data.message, 'error');
        }
    });
}

function clearSessions() {
    if (!confirm('Tüm oturumları sonlandırmak istediğinize emin misiniz?')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/clear-sessions.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Oturumlar sonlandırıldı, yönlendiriliyorsunuz...', 'success');
            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/logout.php';
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    });
}

function resetDatabase() {
    const confirmation = prompt('Veritabanını sıfırlamak için "SIFIRLA" yazın:');
    if (confirmation !== 'SIFIRLA') {
        showToast('İşlem iptal edildi', 'info');
        return;
    }
    
    if (!confirm('SON UYARI! Tüm veriler silinecek. Devam etmek istediğinize emin misiniz?')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/reset-database.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Veritabanı sıfırlandı, yönlendiriliyorsunuz...', 'success');
            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/index.php';
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    });
}

// Main device test functionality
document.getElementById('testMainDeviceBtn')?.addEventListener('click', function() {
    const btn = this;
    const resultDiv = document.getElementById('connectionTestResult');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Test ediliyor...';
    resultDiv.innerHTML = '';
    
    fetch('<?php echo BASE_URL; ?>/api/test-main-device.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Bağlantı Başarılı</h6>
                        <div class="row">
                            <div class="col-md-3"><strong>Identity:</strong> ${data.device.identity || 'N/A'}</div>
                            <div class="col-md-3"><strong>Version:</strong> ${data.device.version || 'N/A'}</div>
                            <div class="col-md-3"><strong>Model:</strong> ${data.device.model || 'N/A'}</div>
                            <div class="col-md-3"><strong>Uptime:</strong> ${data.device.uptime || 'N/A'}</div>
                        </div>
                    </div>
                `;
                showToast('Ana cihaz bağlantısı başarılı', 'success');
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle me-2"></i><strong>Bağlantı Hatası:</strong> ${data.message}
                    </div>
                `;
                showToast('Bağlantı başarısız', 'error');
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Test sırasında hata oluştu
                </div>
            `;
            showToast('Test hatası', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi me-2"></i>Bağlantıyı Test Et';
        });
});
</script>

<?php include $rootPath . '/includes/footer.php'; ?>