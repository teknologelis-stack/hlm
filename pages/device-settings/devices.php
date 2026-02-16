<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('devices_manage');

$pageTitle = 'Cihazlar';
$db = Database::getInstance();

$devices = $db->fetchAll("SELECT * FROM devices ORDER BY is_main DESC, created_at DESC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-hdd-stack me-2"></i>Kayıtlı Cihazlar</h5>
                <a href="<?php echo BASE_URL; ?>/pages/device-settings/add-device.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Yeni Cihaz Ekle
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 64px; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">Henüz cihaz eklenmemiş</h5>
                        <p class="text-muted">Yeni cihaz eklemek için yukarıdaki butona tıklayın</p>
                        <a href="<?php echo BASE_URL; ?>/pages/device-settings/add-device.php" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle me-2"></i>İlk Cihazı Ekle
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($devices as $device): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="device-card <?php echo $device['is_main'] ? 'main-device' : ''; ?>">
                                <?php if ($device['is_main']): ?>
                                    <div class="main-badge">
                                        <i class="bi bi-star-fill"></i> Ana Cihaz
                                    </div>
                                <?php endif; ?>
                                
                                <div class="device-header">
                                    <div class="device-icon">
                                        <i class="bi bi-router"></i>
                                    </div>
                                    <div class="device-status">
                                        <?php if ($device['is_active']): ?>
                                            <span class="status-dot online"></span>
                                        <?php else: ?>
                                            <span class="status-dot offline"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <h5 class="device-name"><?php echo clean($device['name']); ?></h5>
                                
                                <div class="device-info">
                                    <div class="info-row">
                                        <i class="bi bi-ethernet"></i>
                                        <span><?php echo clean($device['ip_address']); ?>:<?php echo $device['port']; ?></span>
                                    </div>
                                    
                                    <?php if ($device['identity']): ?>
                                    <div class="info-row">
                                        <i class="bi bi-fingerprint"></i>
                                        <span><?php echo clean($device['identity']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($device['model']): ?>
                                    <div class="info-row">
                                        <i class="bi bi-tag"></i>
                                        <span><?php echo clean($device['model']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($device['routeros_version']): ?>
                                    <div class="info-row">
                                        <i class="bi bi-cpu"></i>
                                        <span>RouterOS <?php echo clean($device['routeros_version']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-row">
                                        <i class="bi bi-clock-history"></i>
                                        <span><?php echo $device['last_connection'] ? timeAgo($device['last_connection']) : 'Hiç'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="device-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="testDevice(<?php echo $device['id']; ?>)">
                                        <i class="bi bi-wifi"></i> Test
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editDevice(<?php echo $device['id']; ?>)">
                                        <i class="bi bi-pencil"></i> Düzenle
                                    </button>
                                    <?php if (!$device['is_main']): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDevice(<?php echo $device['id']; ?>, '<?php echo clean($device['name']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="info-box bg-primary">
            <i class="bi bi-hdd-stack"></i>
            <div>
                <h3><?php echo count($devices); ?></h3>
                <p>Toplam Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-success">
            <i class="bi bi-check-circle"></i>
            <div>
                <h3><?php echo count(array_filter($devices, fn($d) => $d['is_active'])); ?></h3>
                <p>Aktif Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-warning">
            <i class="bi bi-star-fill"></i>
            <div>
                <h3><?php echo count(array_filter($devices, fn($d) => $d['is_main'])); ?></h3>
                <p>Ana Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-info">
            <i class="bi bi-clock-history"></i>
            <div>
                <h3><?php echo count(array_filter($devices, fn($d) => $d['last_connection'])); ?></h3>
                <p>Bağlantı Kurulmuş</p>
            </div>
        </div>
    </div>
</div>

<style>
.device-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
    border: 2px solid transparent;
}

.device-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.device-card.main-device {
    border-color: var(--warning);
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.main-badge {
    position: absolute;
    top: -10px;
    right: 20px;
    background: var(--warning);
    color: #ffffff;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);
}

.device-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.device-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #ffffff;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
}

.status-dot.online {
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
}

.status-dot.offline {
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.device-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 15px;
}

.device-info {
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    color: var(--text-light);
    font-size: 14px;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-row i {
    color: var(--primary-color);
    font-size: 16px;
    width: 20px;
}

.device-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.info-box {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: #ffffff;
    padding: 25px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.info-box.bg-success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.info-box.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.info-box.bg-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.info-box i {
    font-size: 48px;
    opacity: 0.8;
}

.info-box h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.info-box p {
    margin: 0;
    font-size: 14px;
}
</style>

<script>
function testDevice(id) {
    showToast('Cihaz bağlantısı test ediliyor...', 'info');
    
    fetch('<?php echo BASE_URL; ?>/api/test-device-connection.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Bağlantı başarılı!', 'success');
            } else {
                showToast(data.message || 'Bağlantı başarısız', 'error');
            }
        })
        .catch(error => {
            showToast('Test sırasında hata oluştu', 'error');
        });
}

function deleteDevice(id, name) {
    if (!confirm(`${name} cihazını silmek istediğinize emin misiniz?`)) return;
    
    fetch('<?php echo BASE_URL; ?>/api/delete-device.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cihaz silindi', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
}

function editDevice(id) {
    showToast('Düzenleme özelliği yakında eklenecek', 'info');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>