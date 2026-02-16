<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Dashboard';
$db = Database::getInstance();

$totalDevices = $db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE is_active = 1")['count'];
$mainDevice = $db->fetchOne("SELECT * FROM devices WHERE is_main = 1 AND is_active = 1");
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
$recentLogs = $db->fetchAll("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon bg-primary">
                <i class="bi bi-hdd-network"></i>
            </div>
            <div class="stats-content">
                <h3><?php echo $totalDevices; ?></h3>
                <p>Toplam Cihaz</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon bg-success">
                <i class="bi bi-people"></i>
            </div>
            <div class="stats-content">
                <h3>0</h3>
                <p>Aktif Kullanıcı</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon bg-warning">
                <i class="bi bi-person-gear"></i>
            </div>
            <div class="stats-content">
                <h3><?php echo $totalUsers; ?></h3>
                <p>Panel Kullanıcısı</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon bg-info">
                <i class="bi bi-activity"></i>
            </div>
            <div class="stats-content">
                <h3>--</h3>
                <p>Sistem Uptime</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-router me-2"></i>Ana Cihaz Durumu</h5>
                <?php if ($mainDevice): ?>
                    <span class="badge bg-success"><i class="bi bi-circle-fill me-1"></i>Bağlı</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="bi bi-circle-fill me-1"></i>Bağlantı Yok</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($mainDevice): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label><i class="bi bi-hdd-network text-primary"></i> Cihaz Adı</label>
                                <p><?php echo clean($mainDevice['name']); ?></p>
                            </div>
                            <div class="info-group">
                                <label><i class="bi bi-ethernet text-primary"></i> IP Adresi</label>
                                <p><?php echo clean($mainDevice['ip_address']); ?></p>
                            </div>
                            <div class="info-group">
                                <label><i class="bi bi-fingerprint text-primary"></i> Identity</label>
                                <p><?php echo clean($mainDevice['identity'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label><i class="bi bi-tag text-primary"></i> Model</label>
                                <p><?php echo clean($mainDevice['model'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-group">
                                <label><i class="bi bi-cpu text-primary"></i> RouterOS Versiyon</label>
                                <p><?php echo clean($mainDevice['routeros_version'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-group">
                                <label><i class="bi bi-clock-history text-primary"></i> Son Bağlantı</label>
                                <p><?php echo $mainDevice['last_connection'] ? timeAgo($mainDevice['last_connection']) : 'Hiç'; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="mb-3">Sistem Kaynakları</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small text-muted">CPU Kullanımı</label>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-primary" style="width: 0%">0%</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small text-muted">RAM Kullanımı</label>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: 0%">0%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 64px;"></i>
                        <h5 class="mt-3">Ana Cihaz Tanımlanmamış</h5>
                        <p class="text-muted">Lütfen ayarlar bölümünden ana cihazı tanımlayın.</p>
                        <a href="<?php echo BASE_URL; ?>/pages/panel-settings/settings.php" class="btn btn-primary">
                            <i class="bi bi-gear me-2"></i>Ayarlara Git
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Hızlı Erişim</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="<?php echo BASE_URL; ?>/pages/active-users.php" class="quick-action-item">
                        <i class="bi bi-people"></i>
                        <span>Aktif Kullanıcılar</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/router-settings/ppp-settings.php" class="quick-action-item">
                        <i class="bi bi-person-badge"></i>
                        <span>PPP Ayarları</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/device-settings/fetch-device.php" class="quick-action-item">
                        <i class="bi bi-download"></i>
                        <span>Cihaz Çek</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/router-settings/logs.php" class="quick-action-item">
                        <i class="bi bi-journal-text"></i>
                        <span>Loglar</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/device-settings/devices.php" class="quick-action-item">
                        <i class="bi bi-hdd-stack"></i>
                        <span>Cihazlar</span>
                    </a>
                    <?php if ($auth->hasPermission('settings_manage')): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/panel-settings/settings.php" class="quick-action-item">
                        <i class="bi bi-sliders"></i>
                        <span>Ayarlar</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Son Aktiviteler</h5>
                <a href="#" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>İşlem</th>
                                <th>Detay</th>
                                <th>IP Adresi</th>
                                <th>Zaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Henüz aktivite bulunmuyor</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-person-circle me-2"></i>
                                        <?php echo clean($log['username'] ?? 'Sistem'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'secondary';
                                        if (strpos($log['action'], 'login') !== false) $badgeClass = 'success';
                                        if (strpos($log['action'], 'failed') !== false) $badgeClass = 'danger';
                                        if (strpos($log['action'], 'logout') !== false) $badgeClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo clean($log['action']); ?></span>
                                    </td>
                                    <td class="text-muted small"><?php echo clean($log['details']); ?></td>
                                    <td class="text-muted small"><?php echo clean($log['ip_address']); ?></td>
                                    <td class="text-muted small"><?php echo timeAgo($log['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #ffffff;
}

.stats-icon.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stats-icon.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stats-icon.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stats-icon.bg-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }

.stats-content h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    color: var(--text-dark);
}

.stats-content p {
    margin: 0;
    color: var(--text-light);
    font-size: 14px;
}

.info-group {
    margin-bottom: 20px;
}

.info-group label {
    display: block;
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 5px;
    font-weight: 500;
}

.info-group p {
    margin: 0;
    font-size: 15px;
    color: var(--text-dark);
    font-weight: 600;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.quick-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: var(--bg-light);
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    color: var(--text-dark);
}

.quick-action-item:hover {
    background: var(--primary-color);
    color: #ffffff;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
}

.quick-action-item i {
    font-size: 32px;
    margin-bottom: 10px;
}

.quick-action-item span {
    font-size: 13px;
    font-weight: 500;
    text-align: center;
}

.table th {
    font-weight: 600;
    color: var(--text-light);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-color);
    padding: 15px;
}

.table td {
    padding: 15px;
    vertical-align: middle;
}

.progress {
    background-color: #e5e7eb;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
}

@media (max-width: 768px) {
    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
