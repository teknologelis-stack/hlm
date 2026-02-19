<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

$auth = new Auth();
$auth->requireLogin();

$updateManager = new UpdateManager();
$updateHistory = $updateManager->getUpdateHistory(10);
$backups = $updateManager->getBackupHistory(10);
$currentVersion = APP_VERSION;

$pageTitle = 'Update Manager';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/update-manager.css">

<div class="container-fluid" style="max-width: none !important; width: 100% !important; padding: 0 3rem !important;">
    <!-- Header -->
    <div class="update-manager-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1>
                    <i class="fas fa-sync-alt me-2"></i>
                    Update Manager
                </h1>
                <p class="mb-0 opacity-75">Sistem güncellemelerini yönetin ve takip edin</p>
            </div>
            <div class="version-badge">v<?php echo htmlspecialchars($currentVersion); ?></div>
        </div>
    </div>

    <!-- Info Stats -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="info-stat">
                <div class="stat-icon"><i class="fas fa-code-branch"></i></div>
                <div class="stat-label">Mevcut Versiyon</div>
                <div class="stat-value text-success">v<?php echo htmlspecialchars($currentVersion); ?></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="info-stat">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-label">Son Kontrol</div>
                <div id="lastChecked" class="stat-value text-secondary" style="font-size: 1rem; font-weight: 500;">Henüz kontrol edilmedi</div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="info-stat">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Durum</div>
                <div class="stat-value text-info">Hazır</div>
            </div>
        </div>
    </div>

    <!-- Check Updates -->
    <div class="update-card card">
        <div class="card-header">
            <h5><i class="fas fa-search me-2" style="color: #3b82f6;"></i>Güncellemeleri Kontrol Et</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3">
                <button id="checkUpdateBtn" class="btn btn-primary btn-lg btn-update" onclick="checkForUpdates()">
                    <i class="fas fa-search me-2"></i>Check for Updates
                </button>
                <button id="createBackupBtn" class="btn btn-success btn-lg btn-update" onclick="createBackup()" style="display:none;">
                    <i class="fas fa-database me-2"></i>Create Backup
                </button>
                <button id="applyUpdateBtn" class="btn btn-warning btn-lg btn-update text-white" onclick="applyUpdate()" style="display:none;">
                    <i class="fas fa-download me-2"></i>Apply Update
                </button>
            </div>
            <div id="updateCheckResult" class="mt-4"></div>
        </div>
    </div>

    <!-- Settings & Manual Update -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="update-card card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-cog me-2" style="color: #64748b;"></i>Güncelleme Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="autoBackup" checked>
                        <label class="form-check-label" for="autoBackup">Otomatik yedek al</label>
                    </div>
                    <div class="mb-3">
                        <label for="updateChannel" class="form-label fw-bold">Güncelleme Kanalı</label>
                        <select class="form-select" id="updateChannel">
                            <option value="stable" selected>Stable (Önerilen)</option>
                            <option value="beta">Beta</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-update w-100" onclick="saveUpdateSettings()">
                        <i class="fas fa-save me-2"></i>Ayarları Kaydet
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="update-card card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-upload me-2" style="color: #f59e0b;"></i>Manuel Güncelleme</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">ZIP dosyası yükleyin</p>
                    <input class="form-control mb-3" type="file" id="manualUpdateFile" accept=".zip">
                    <button class="btn btn-warning btn-update w-100 text-white" onclick="uploadManualUpdate()">
                        <i class="fas fa-upload me-2"></i>Yükle & Kur
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update History -->
    <?php if (!empty($updateHistory)): ?>
    <div class="update-card card">
        <div class="card-header">
            <h5><i class="fas fa-history me-2" style="color: #06b6d4;"></i>Güncelleme Geçmişi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Versiyon</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>Uygulayan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($updateHistory as $update): ?>
                        <tr>
                            <td><span class="badge badge-modern bg-primary">v<?php echo htmlspecialchars($update['version']); ?></span></td>
                            <td>
                                <?php if ($update['status'] === 'applied'): ?>
                                    <span class="badge badge-modern bg-success"><i class="fas fa-check me-1"></i>Uygulandı</span>
                                <?php else: ?>
                                    <span class="badge badge-modern bg-danger"><?php echo htmlspecialchars($update['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?php echo date('d.m.Y H:i', strtotime($update['applied_at'])); ?></td>
                            <td><span class="badge badge-modern bg-secondary"><?php echo htmlspecialchars($update['applied_by'] ?? 'admin'); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Backup History -->
    <?php if (!empty($backups)): ?>
    <div class="update-card card">
        <div class="card-header">
            <h5><i class="fas fa-database me-2" style="color: #10b981;"></i>Yedek Geçmişi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Dosya</th>
                            <th>Tür</th>
                            <th>Boyut</th>
                            <th>Tarih</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td class="font-monospace small"><?php echo htmlspecialchars($backup['filename']); ?></td>
                            <td><span class="badge badge-modern bg-info"><?php echo htmlspecialchars($backup['backup_type']); ?></span></td>
                            <td class="text-muted"><?php 
                                $size = isset($backup['size_bytes']) ? $backup['size_bytes'] : 0;
                                if ($size > 1024 * 1024) {
                                    echo round($size / (1024 * 1024), 2) . ' MB';
                                } elseif ($size > 1024) {
                                    echo round($size / 1024, 2) . ' KB';
                                } else {
                                    echo $size . ' B';
                                }
                            ?></td>
                            <td class="text-muted"><?php echo date('d.m.Y H:i', strtotime($backup['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="restoreBackup(<?php echo $backup['id']; ?>)">
                                    <i class="fas fa-undo me-1"></i>Geri Yükle
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/update-manager.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>