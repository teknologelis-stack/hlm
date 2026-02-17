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

// Get update history and backups
$updateManager = new UpdateManager();
$updateHistory = $updateManager->getUpdateHistory(10);
$backups = $updateManager->getBackupHistory(10);
$currentVersion = APP_VERSION;

$pageTitle = 'Update Manager';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-cloud-download-alt text-primary"></i>
                Update Manager
            </h1>
            <p class="text-muted">Sistem güncellemelerini yönetin</p>
        </div>
        <div class="badge bg-primary fs-6">v<?php echo htmlspecialchars($currentVersion); ?></div>
    </div>

    <!-- System Information Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle text-info"></i>
                        Sistem Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Mevcut Versiyon</label>
                            <div class="h5 mb-0">
                                <span class="badge bg-success">v<?php echo htmlspecialchars($currentVersion); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Son Kontrol</label>
                            <div class="h6 mb-0" id="lastChecked">
                                <span class="text-secondary">Henüz kontrol edilmedi</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Güncelleme Durumu</label>
                            <div class="h6 mb-0">
                                <span class="badge bg-info">Hazır</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Check for Updates Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-search text-warning"></i>
                        Güncellemeleri Kontrol Et
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 d-md-flex">
                        <button id="checkUpdateBtn" class="btn btn-primary btn-lg" onclick="checkForUpdates()">
                            <i class="fas fa-search"></i> Check for Updates
                        </button>
                        <button id="createBackupBtn" class="btn btn-success btn-lg" onclick="createBackup()" style="display:none;">
                            <i class="fas fa-database"></i> Create Backup
                        </button>
                        <button id="applyUpdateBtn" class="btn btn-warning btn-lg" onclick="applyUpdate()" style="display:none;">
                            <i class="fas fa-download"></i> Apply Update
                        </button>
                    </div>
                    
                    <div id="updateCheckResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Settings Card -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog text-secondary"></i>
                        Güncelleme Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoBackup" checked>
                            <label class="form-check-label" for="autoBackup">
                                <i class="fas fa-database text-success"></i>
                                Güncellemeden önce otomatik yedek al
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="updateChannel" class="form-label">
                            <i class="fas fa-satellite-dish"></i>
                            Güncelleme Kanalı
                        </label>
                        <select class="form-select" id="updateChannel">
                            <option value="stable" selected>Stable (Önerilen)</option>
                            <option value="beta">Beta</option>
                            <option value="dev">Development</option>
                        </select>
                        <small class="text-muted">Hangi sürümleri alacağınızı seçin</small>
                    </div>
                    
                    <button class="btn btn-primary" onclick="saveUpdateSettings()">
                        <i class="fas fa-save"></i> Ayarları Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- Manual Update Card -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload text-primary"></i>
                        Manuel Güncelleme
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        GitHub'dan indirdiğiniz ZIP dosyasını yükleyin
                    </p>
                    
                    <div class="mb-3">
                        <label for="manualUpdateFile" class="form-label">ZIP Dosyası Seç</label>
                        <input class="form-control" type="file" id="manualUpdateFile" accept=".zip">
                    </div>
                    
                    <button class="btn btn-warning" onclick="uploadManualUpdate()">
                        <i class="fas fa-upload"></i> Yükle & Kur
                    </button>
                    
                    <div id="uploadProgress" style="display:none;" class="mt-3">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update History Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-info"></i>
                        Güncelleme Geçmişi
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($updateHistory)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-tag"></i> Versiyon</th>
                                    <th><i class="fas fa-check-circle"></i> Durum</th>
                                    <th><i class="fas fa-calendar"></i> Tarih</th>
                                    <th><i class="fas fa-user"></i> Uygulayan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($updateHistory as $update): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">v<?php echo htmlspecialchars($update['version']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($update['status'] === 'applied'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Uygulandı
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> <?php echo htmlspecialchars($update['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($update['applied_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($update['username'] ?? 'admin'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        Henüz güncelleme geçmişi bulunmuyor
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup History Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-database text-success"></i>
                        Yedek Geçmişi
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($backups)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-file"></i> Dosya Adı</th>
                                    <th><i class="fas fa-tag"></i> Tür</th>
                                    <th><i class="fas fa-hdd"></i> Boyut</th>
                                    <th><i class="fas fa-calendar"></i> Tarih</th>
                                    <th><i class="fas fa-cog"></i> İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <small class="font-monospace">
                                            <i class="fas fa-file-archive text-warning"></i>
                                            <?php echo htmlspecialchars($backup['filename']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($backup['backup_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            $sizeKB = round($backup['size_bytes'] / 1024, 2);
                                            echo $sizeKB . ' KB';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($backup['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="restoreBackup(<?php echo $backup['id']; ?>)"
                                                title="Geri Yükle">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        Henüz yedek bulunmuyor
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/update-manager.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

