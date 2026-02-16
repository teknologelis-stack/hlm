<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('devices_manage');

$pageTitle = 'Yeni Cihaz Ekle';
$db = Database::getInstance();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-download me-2"></i>MikroTik Cihaz Bağlantı Testi</h5>
            </div>
            <div class="card-body">
                <form id="fetchDeviceForm">
                    <div class="mb-3">
                        <label class="form-label">Cihaz Adı *</label>
                        <input type="text" class="form-control" name="device_name" id="deviceName" placeholder="Örn: Ana Router" required>
                        <small class="text-muted">Tanımlayıcı bir isim verin</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">IP Adresi *</label>
                        <input type="text" class="form-control" name="ip_address" id="ipAddress" placeholder="192.168.1.1" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı *</label>
                                <input type="text" class="form-control" name="username" id="username" placeholder="admin" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="port" id="port" value="8728" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Şifre *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField()">
                                <i class="bi bi-eye" id="passwordToggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_main" id="isMain">
                            <label class="form-check-label" for="isMain">
                                <strong>Ana Cihaz Olarak Ayarla</strong>
                                <br><small class="text-muted">Diğer tüm işlemler bu cihaz üzerinden yapılacak</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-info btn-lg" id="testConnectionBtn">
                            <i class="bi bi-wifi me-2"></i>Bağlantıyı Test Et
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="saveDeviceBtn" disabled>
                            <i class="bi bi-save me-2"></i>Cihazı Kaydet
                        </button>
                    </div>
                </form>
                
                <div id="deviceInfoCard" class="mt-4" style="display: none;">
                    <hr>
                    <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Cihaz Bilgileri</h6>
                    <div class="device-info-grid">
                        <div class="info-item">
                            <label><i class="bi bi-hdd-network text-primary"></i> Identity</label>
                            <p id="deviceIdentity">--</p>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-cpu text-primary"></i> RouterOS Versiyon</label>
                            <p id="deviceVersion">--</p>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-tag text-primary"></i> Model</label>
                            <p id="deviceModel">--</p>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-fingerprint text-primary"></i> Serial Number</label>
                            <p id="deviceSerial">--</p>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-clock text-primary"></i> Uptime</label>
                            <p id="deviceUptime">--</p>
                        </div>
                        <div class="info-item">
                            <label><i class="bi bi-memory text-primary"></i> RAM</label>
                            <p id="deviceRam">--</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <h6 class="alert-heading"><i class="bi bi-lightbulb me-2"></i>Bilgi</h6>
            <ul class="mb-0 small">
                <li>MikroTik cihazınızda API servisinin aktif olduğundan emin olun</li>
                <li>Varsayılan API portu: 8728 (SSL için 8729)</li>
                <li>Firewall kurallarınızı kontrol edin</li>
                <li>Admin yetkisine sahip bir kullanıcı kullanın</li>
            </ul>
        </div>
    </div>
</div>

<style>
.device-info-grid {
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
    margin-bottom: 8px;
    font-weight: 500;
}

.info-item p {
    margin: 0;
    font-size: 15px;
    color: var(--text-dark);
    font-weight: 600;
}

.alert-info {
    background: linear-gradient(135deg, #e0f2fe 0%, #ddd6fe 100%);
    border: none;
    border-radius: 12px;
}

.alert-info .alert-heading {
    color: #1e40af;
}
</style>

<script>
let deviceInfo = null;

document.getElementById('testConnectionBtn').addEventListener('click', function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Bağlanıyor...';
    
    const formData = new FormData(document.getElementById('fetchDeviceForm'));
    
    fetch('<?php echo BASE_URL; ?>/api/test-device.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Bağlantı başarılı!', 'success');
            displayDeviceInfo(data.device);
            document.getElementById('saveDeviceBtn').disabled = false;
            deviceInfo = data.device;
        } else {
            showToast(data.message || 'Bağlantı başarısız', 'error');
            document.getElementById('deviceInfoCard').style.display = 'none';
            document.getElementById('saveDeviceBtn').disabled = true;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Bağlantı hatası', 'error');
        document.getElementById('deviceInfoCard').style.display = 'none';
        document.getElementById('saveDeviceBtn').disabled = true;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

document.getElementById('fetchDeviceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!deviceInfo) {
        showToast('Önce bağlantıyı test edin', 'warning');
        return;
    }
    
    const btn = document.getElementById('saveDeviceBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Kaydediliyor...';
    
    const formData = new FormData(this);
    formData.append('device_info', JSON.stringify(deviceInfo));
    
    fetch('<?php echo BASE_URL; ?>/api/save-device.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cihaz başarıyla kaydedildi!', 'success');
            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/pages/device-settings/devices.php';
            }, 1500);
        } else {
            showToast(data.message || 'Kayıt başarısız', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Kayıt sırasında hata oluştu', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

function displayDeviceInfo(device) {
    document.getElementById('deviceInfoCard').style.display = 'block';
    document.getElementById('deviceIdentity').textContent = device.identity || 'N/A';
    document.getElementById('deviceVersion').textContent = device.version || 'N/A';
    document.getElementById('deviceModel').textContent = device.model || 'N/A';
    document.getElementById('deviceSerial').textContent = device.serial || 'N/A';
    document.getElementById('deviceUptime').textContent = device.uptime || 'N/A';
    document.getElementById('deviceRam').textContent = device.ram || 'N/A';
}

function togglePasswordField() {
    const passwordField = document.getElementById('password');
    const icon = document.getElementById('passwordToggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>