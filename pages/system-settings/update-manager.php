<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
session_start();
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/UpdateManager.php';
require_once __DIR__ . '/../../includes/ConfigurationManager.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('system_manage');

$pageTitle = 'Güncelleme Yöneticisi';
$db = Database::getInstance();

// Güncelleme ve yedek geçmişini al
$updateManager = new UpdateManager($_SESSION['user_id']);
$updateHistory = $updateManager->getUpdateHistory(5);
$backupList = $updateManager->getBackupList(10);

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/update-manager.css">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Sistem Güncelleme ve Yapılandırma Yönetimi</h4>
                            <p class="text-muted mb-0">
                                <i class="bi bi-info-circle"></i> Mevcut Versiyon: 
                                <span class="badge bg-primary"><?php echo APP_VERSION; ?></span>
                            </p>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary" onclick="checkSystemStatus()">
                                <i class="bi bi-arrow-clockwise"></i> Durumu Yenile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="updateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="online-tab" data-bs-toggle="tab" data-bs-target="#online" type="button">
                <i class="bi bi-cloud-arrow-down"></i> Online Güncelleme
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="offline-tab" data-bs-toggle="tab" data-bs-target="#offline" type="button">
                <i class="bi bi-upload"></i> Offline Güncelleme
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button">
                <i class="bi bi-archive"></i> Yedekleme
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button">
                <i class="bi bi-gear"></i> Yapılandırma
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="bi bi-clock-history"></i> Geçmiş
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="updateTabsContent">
        
        <!-- Online Güncelleme -->
        <div class="tab-pane fade show active" id="online" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card update-card">
                        <div class="card-header">
                            <h5><i class="bi bi-cloud-arrow-down"></i> Online Güncelleme Kontrolü</h5>
                        </div>
                        <div class="card-body">
                            <div id="updateInfo">
                                <div class="text-center py-4">
                                    <i class="bi bi-hourglass-split" style="font-size: 48px; color: #6c757d;"></i>
                                    <p class="mt-3 text-muted">Güncelleme kontrolü yapmak için butona tıklayın</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button id="checkUpdateBtn" class="btn btn-primary" onclick="checkForUpdates()">
                                    <i class="bi bi-cloud-download"></i> Güncelleme Kontrol Et
                                </button>
                                <button id="applyUpdateBtn" class="btn btn-success" style="display:none;" onclick="applyOnlineUpdate()">
                                    <i class="bi bi-download"></i> Güncellemeyi Uygula
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-info-circle"></i> Bilgilendirme</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-0">
                                <p class="small mb-2"><strong>Online Güncelleme:</strong></p>
                                <ul class="small mb-0">
                                    <li>GitHub'dan otomatik güncelleme kontrolü</li>
                                    <li>Güncelleme öncesi otomatik yedekleme</li>
                                    <li>Tek tıkla güncelleme uygulama</li>
                                    <li>Rollback desteği</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offline Güncelleme -->
        <div class="tab-pane fade" id="offline" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-upload"></i> Offline Güncelleme Paketi Yükle</h5>
                        </div>
                        <div class="card-body">
                            <form id="offlineUpdateForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Güncelleme Paketi (.zip)</label>
                                    <input type="file" class="form-control" name="update_package" id="updatePackage" accept=".zip" required>
                                    <small class="form-text text-muted">Maksimum dosya boyutu: 50 MB</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Versiyon Numarası</label>
                                    <input type="text" class="form-control" name="version" id="updateVersion" placeholder="Örn: 1.1.0" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Yükle ve Uygula
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-exclamation-triangle"></i> Uyarılar</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning mb-0">
                                <p class="small mb-2"><strong>Önemli:</strong></p>
                                <ul class="small mb-0">
                                    <li>Sadece güvenilir kaynaklardan paket yükleyin</li>
                                    <li>Güncelleme öncesi yedek alın</li>
                                    <li>Güncelleme sırasında sistemi kapatmayın</li>
                                    <li>Geçerli ZIP formatında olmalıdır</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yedekleme -->
        <div class="tab-pane fade" id="backup" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-archive"></i> Yedek Oluştur</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Sistem yedeği veritabanı, yapılandırma dosyaları ve kodları içerir.</p>
                            <div class="mb-3">
                                <label class="form-label">Açıklama (Opsiyonel)</label>
                                <input type="text" class="form-control" id="backupDescription" placeholder="Örn: Güncelleme öncesi yedek">
                            </div>
                            <button id="createBackupBtn" class="btn btn-primary" onclick="createBackup()">
                                <i class="bi bi-archive"></i> Yedek Oluştur
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-arrow-clockwise"></i> Yedek Geri Yükle</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Daha önce oluşturulmuş bir yedeği geri yükleyin.</p>
                            <form id="restoreBackupForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Yedek Dosyası (.zip)</label>
                                    <input type="file" class="form-control" name="backup_file" accept=".zip" required>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-arrow-clockwise"></i> Geri Yükle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-list"></i> Mevcut Yedekler</h5>
                        </div>
                        <div class="card-body">
                            <div class="backup-list">
                                <?php if (empty($backupList)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                                        <p class="mt-2">Henüz yedek oluşturulmamış</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($backupList as $backup): ?>
                                        <div class="backup-item">
                                            <div>
                                                <h6 class="mb-1"><?php echo clean($backup['backup_name']); ?></h6>
                                                <p class="text-muted small mb-0">
                                                    <i class="bi bi-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($backup['created_at'])); ?> 
                                                    | <i class="bi bi-hdd"></i> <?php echo number_format($backup['file_size'] / 1024 / 1024, 2); ?> MB
                                                    <?php if ($backup['created_by_name']): ?>
                                                        | <i class="bi bi-person"></i> <?php echo clean($backup['created_by_name']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($backup['description']): ?>
                                                    <p class="text-muted small mb-0"><?php echo clean($backup['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary" onclick="downloadBackup(<?php echo $backup['id']; ?>)">
                                                    <i class="bi bi-download"></i> İndir
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yapılandırma -->
        <div class="tab-pane fade" id="config" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-box-arrow-down"></i> Dışa Aktar</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Sistem yapılandırmasını farklı formatlarda dışa aktarın.</p>
                            <div class="d-flex gap-2 mb-3">
                                <button class="btn btn-outline-primary" onclick="exportConfig('json')">
                                    <i class="bi bi-filetype-json"></i> JSON İndir
                                </button>
                                <button class="btn btn-outline-primary" onclick="exportConfig('csv', 'settings')">
                                    <i class="bi bi-filetype-csv"></i> Settings CSV
                                </button>
                                <button class="btn btn-outline-primary" onclick="exportConfig('csv', 'users')">
                                    <i class="bi bi-filetype-csv"></i> Users CSV
                                </button>
                            </div>
                            <div class="alert alert-info small mb-0">
                                <strong>JSON:</strong> Tüm sistem yapılandırması (settings, roles, users)<br>
                                <strong>CSV:</strong> Belirli tablo verileri
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-box-arrow-in-up"></i> İçe Aktar</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Yapılandırma dosyasını içe aktarın.</p>
                            <form id="importConfigForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Dosya Seç (JSON/CSV)</label>
                                    <input type="file" class="form-control" name="config_file" accept=".json,.csv" required>
                                </div>
                                <div class="mb-3" id="csvTypeDiv" style="display:none;">
                                    <label class="form-label">CSV Tipi</label>
                                    <select class="form-select" name="type" id="csvType">
                                        <option value="settings">Settings</option>
                                        <option value="users">Users</option>
                                        <option value="roles">Roles</option>
                                        <option value="devices">Devices</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-upload"></i> İçe Aktar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Geçmiş -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Güncelleme Geçmişi</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($updateHistory)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 48px;"></i>
                            <p class="mt-2">Henüz güncelleme yapılmamış</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Versiyon</th>
                                        <th>Tip</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                        <th>Uygulayan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($updateHistory as $update): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo clean($update['version_from']); ?></span>
                                                <i class="bi bi-arrow-right"></i>
                                                <span class="badge bg-primary"><?php echo clean($update['version_to']); ?></span>
                                            </td>
                                            <td><?php echo clean($update['update_type']); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'in_progress' => 'warning',
                                                    'pending' => 'secondary'
                                                ];
                                                $class = $statusClass[$update['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $class; ?>">
                                                    <?php echo clean($update['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($update['started_at'])); ?></td>
                                            <td><?php echo clean($update['applied_by_name'] ?? 'System'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/update-manager.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
