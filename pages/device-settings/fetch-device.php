<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('devices_manage');

$pageTitle = 'Cihaz Çek';
$db = Database::getInstance();

// Get main device info
$mainDevice = $db->fetchOne("SELECT * FROM devices WHERE is_main = 1 AND is_active = 1");

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-lg-8 mx-auto">
        <?php if (!$mainDevice): ?>
            <div class="alert alert-warning">
                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Ana Cihaz Bulunamadı</h6>
                <p class="mb-0">DHCP lease çekmek için önce bir ana cihaz tanımlamanız gerekiyor.</p>
                <hr>
                <a href="<?php echo BASE_URL; ?>/pages/device-settings/devices.php" class="btn btn-sm btn-warning">
                    <i class="bi bi-hdd-network me-2"></i>Cihaz Yönetimine Git
                </a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-download me-2"></i>Ana Cihazdan DHCP Lease Çek</h5>
                </div>
                <div class="card-body">
                    <div class="main-device-info mb-4">
                        <h6 class="mb-3"><i class="bi bi-hdd-network text-primary me-2"></i>Ana Cihaz Bilgisi</h6>
                        <div class="device-card">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Cihaz Adı:</strong> <?php echo clean($mainDevice['name']); ?></p>
                                    <p><strong>IP Adresi:</strong> <code><?php echo clean($mainDevice['ip_address']); ?></code></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Identity:</strong> <?php echo $mainDevice['identity'] ? clean($mainDevice['identity']) : '-'; ?></p>
                                    <p><strong>Model:</strong> <?php echo $mainDevice['model'] ? clean($mainDevice['model']) : '-'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-4">
                        <button type="button" class="btn btn-primary btn-lg" id="fetchLeasesBtn">
                            <i class="bi bi-download me-2"></i>DHCP Lease'leri Çek
                        </button>
                    </div>
                    
                    <div id="statsCard" class="stats-card" style="display: none;">
                        <h6 class="mb-3"><i class="bi bi-bar-chart me-2"></i>İstatistikler</h6>
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="stat-box bg-primary">
                                    <h3 id="statTotal">0</h3>
                                    <p>Toplam Cihaz</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="stat-box bg-success">
                                    <h3 id="statNew">0</h3>
                                    <p>Yeni Eklenen</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="stat-box bg-info">
                                    <h3 id="statUpdated">0</h3>
                                    <p>Güncellenen</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="devicesList" class="devices-list" style="display: none;">
                        <h6 class="mb-3"><i class="bi bi-list-ul me-2"></i>Çekilen Cihazlar</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>MAC Adresi</th>
                                        <th>IP Adresi</th>
                                        <th>Hostname</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody id="devicesTableBody">
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/pages/device-settings/device-list.php" class="btn btn-primary">
                                <i class="bi bi-list-ul me-2"></i>Tüm Cihazları Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info mt-3">
            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Bilgi</h6>
            <ul class="mb-0 small">
                <li>Bu işlem ana cihazınızdan DHCP sunucu lease kayıtlarını çeker</li>
                <li>Çekilen cihazlar MAC adreslerine göre veritabanına kaydedilir</li>
                <li>Daha önce kaydedilmiş cihazlar güncellenir, yeni cihazlar eklenir</li>
                <li>Tüm cihazları görmek için "Cihaz Listele" sayfasını kullanın</li>
            </ul>
        </div>
    </div>
</div>

<style>
.device-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
}

.device-card p {
    margin-bottom: 8px;
    font-size: 14px;
}

.device-card code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 4px;
}

.stats-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.stat-box {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: #ffffff;
    padding: 20px;
    border-radius: 10px;
}

.stat-box.bg-success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-box.bg-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.stat-box h3 {
    margin: 0;
    font-size: 36px;
    font-weight: 700;
}

.stat-box p {
    margin: 5px 0 0 0;
    font-size: 13px;
    opacity: 0.9;
}

.devices-list {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
}

.devices-list .table {
    background: #ffffff;
    margin-bottom: 0;
}

.devices-list .table code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
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
document.getElementById('fetchLeasesBtn')?.addEventListener('click', function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Çekiliyor...';
    
    // Hide previous results
    document.getElementById('statsCard').style.display = 'none';
    document.getElementById('devicesList').style.display = 'none';
    
    fetch('<?php echo BASE_URL; ?>/api/fetch-dhcp-leases.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'DHCP lease\'ler başarıyla çekildi', 'success');
            
            // Update statistics
            document.getElementById('statTotal').textContent = data.stats.total;
            document.getElementById('statNew').textContent = data.stats.new;
            document.getElementById('statUpdated').textContent = data.stats.updated;
            document.getElementById('statsCard').style.display = 'block';
            
            // Display devices
            if (data.devices && data.devices.length > 0) {
                const tbody = document.getElementById('devicesTableBody');
                tbody.innerHTML = '';
                
                // Show first 10 devices
                const devicesToShow = data.devices.slice(0, 10);
                devicesToShow.forEach(device => {
                    const row = document.createElement('tr');
                    
                    // MAC address cell
                    const macCell = document.createElement('td');
                    const macCode = document.createElement('code');
                    macCode.textContent = device.mac_address;
                    macCell.appendChild(macCode);
                    row.appendChild(macCell);
                    
                    // IP address cell
                    const ipCell = document.createElement('td');
                    const ipCode = document.createElement('code');
                    ipCode.textContent = device.ip_address;
                    ipCell.appendChild(ipCode);
                    row.appendChild(ipCell);
                    
                    // Hostname cell
                    const hostnameCell = document.createElement('td');
                    hostnameCell.textContent = device.hostname || '-';
                    row.appendChild(hostnameCell);
                    
                    // Status cell
                    const statusCell = document.createElement('td');
                    const statusBadge = document.createElement('span');
                    statusBadge.className = device.status === 'bound' ? 'badge bg-success' : 'badge bg-warning';
                    statusBadge.textContent = device.status;
                    statusCell.appendChild(statusBadge);
                    row.appendChild(statusCell);
                    
                    tbody.appendChild(row);
                });
                
                document.getElementById('devicesList').style.display = 'block';
            }
        } else {
            showToast(data.message || 'Cihazlar çekilemedi', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Bağlantı hatası', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
