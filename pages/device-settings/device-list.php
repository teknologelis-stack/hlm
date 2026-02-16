<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('devices_manage');

$pageTitle = 'Cihaz Listele';
$db = Database::getInstance();

// Search and filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(mac_address LIKE :search OR ip_address LIKE :search OR hostname LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($statusFilter)) {
    $where[] = "status = :status";
    $params['status'] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$devices = $db->fetchAll("SELECT * FROM detected_devices $whereClause ORDER BY last_seen DESC", $params);

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM detected_devices")['count'] ?? 0,
    'waiting' => $db->fetchOne("SELECT COUNT(*) as count FROM detected_devices WHERE status = 'waiting'")['count'] ?? 0,
    'bound' => $db->fetchOne("SELECT COUNT(*) as count FROM detected_devices WHERE status = 'bound'")['count'] ?? 0,
    'recent' => $db->fetchOne("SELECT COUNT(*) as count FROM detected_devices WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['count'] ?? 0
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="info-box bg-primary">
            <i class="bi bi-hdd-stack"></i>
            <div>
                <h3><?php echo $stats['total']; ?></h3>
                <p>Toplam Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-success">
            <i class="bi bi-check-circle"></i>
            <div>
                <h3><?php echo $stats['bound']; ?></h3>
                <p>Bağlı Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-warning">
            <i class="bi bi-clock-history"></i>
            <div>
                <h3><?php echo $stats['waiting']; ?></h3>
                <p>Bekleyen Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-info">
            <i class="bi bi-activity"></i>
            <div>
                <h3><?php echo $stats['recent']; ?></h3>
                <p>Son 1 Saat</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Tespit Edilen Cihazlar</h5>
            <div class="d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>/pages/device-settings/fetch-device.php" class="btn btn-primary">
                    <i class="bi bi-download me-2"></i>Cihaz Çek
                </a>
                <?php if ($stats['total'] > 0): ?>
                <button class="btn btn-danger" onclick="clearAllDevices()" aria-label="Tüm tespit edilen cihazları temizle">
                    <i class="bi bi-trash me-2"></i>Tümünü Temizle
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="MAC, IP veya Hostname ara..." value="<?php echo clean($search); ?>">
                    <button class="btn btn-primary" onclick="performSearch()">
                        <i class="bi bi-search"></i> Ara
                    </button>
                    <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/device-settings/device-list.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Temizle
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="statusFilter" onchange="performSearch()">
                    <option value="">Tüm Durumlar</option>
                    <option value="waiting" <?php echo $statusFilter === 'waiting' ? 'selected' : ''; ?>>Bekleyen</option>
                    <option value="bound" <?php echo $statusFilter === 'bound' ? 'selected' : ''; ?>>Bağlı</option>
                </select>
            </div>
        </div>
        
        <?php if (empty($devices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 64px; color: #ccc;"></i>
                <h5 class="mt-3 text-muted">
                    <?php echo !empty($search) || !empty($statusFilter) ? 'Cihaz bulunamadı' : 'Henüz cihaz tespit edilmemiş'; ?>
                </h5>
                <?php if (empty($search) && empty($statusFilter)): ?>
                <p class="text-muted">Ana cihazdan DHCP lease çekmek için "Cihaz Çek" butonuna tıklayın</p>
                <a href="<?php echo BASE_URL; ?>/pages/device-settings/fetch-device.php" class="btn btn-primary mt-2">
                    <i class="bi bi-download me-2"></i>Cihaz Çek
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>MAC Adresi</th>
                            <th>IP Adresi</th>
                            <th>Hostname</th>
                            <th>Interface</th>
                            <th>Durum</th>
                            <th>Son Görülme</th>
                            <th>İlk Tespit</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><code><?php echo clean($device['mac_address']); ?></code></td>
                            <td><code><?php echo clean($device['ip_address']); ?></code></td>
                            <td><?php echo $device['hostname'] ? clean($device['hostname']) : '<span class="text-muted">-</span>'; ?></td>
                            <td><?php echo $device['interface'] ? clean($device['interface']) : '<span class="text-muted">-</span>'; ?></td>
                            <td>
                                <?php
                                $statusBadge = [
                                    'waiting' => 'bg-warning',
                                    'bound' => 'bg-success',
                                ];
                                $badgeClass = $statusBadge[$device['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo clean($device['status']); ?></span>
                            </td>
                            <td>
                                <?php echo $device['last_seen'] ? timeAgo($device['last_seen']) : '<span class="text-muted">-</span>'; ?>
                            </td>
                            <td>
                                <?php echo $device['first_detected'] ? date('d.m.Y H:i', strtotime($device['first_detected'])) : '<span class="text-muted">-</span>'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" 
                                        data-device-id="<?php echo $device['id']; ?>" 
                                        data-device-mac="<?php echo clean($device['mac_address']); ?>"
                                        onclick="deleteDevice(this)"
                                        aria-label="Cihazı sil">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
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

.table code {
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
}
</style>

<script>
function performSearch() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const params = new URLSearchParams();
    
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    
    window.location.href = '<?php echo BASE_URL; ?>/pages/device-settings/device-list.php?' + params.toString();
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});

function deleteDevice(button) {
    const id = button.getAttribute('data-device-id');
    const mac = button.getAttribute('data-device-mac');
    
    if (!confirm(`${mac} adresli cihazı silmek istediğinize emin misiniz?`)) return;
    
    fetch('<?php echo BASE_URL; ?>/api/detected-device-delete.php', {
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
            showToast(data.message || 'Silme başarısız', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Hata oluştu', 'error');
    });
}

function clearAllDevices() {
    if (!confirm('Tüm tespit edilen cihazları silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/detected-devices-clear.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Temizleme başarısız', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Hata oluştu', 'error');
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
