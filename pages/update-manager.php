<?php
/**
 * Update Manager Page
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

$auth = new Auth();
$auth->requireLogin();

$updateManager = new UpdateManager();
$currentUser = $auth->getCurrentUser();

// Get update history
$updateHistory = $updateManager->getUpdateHistory(10);
$backupHistory = $updateManager->getBackupHistory(10);

$pageTitle = 'Update Manager - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-cloud-download"></i> Update Manager
        </h1>
    </div>
</div>

<!-- Current Version -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Version</h6>
                        <p class="h4"><?php echo APP_VERSION; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Last Checked</h6>
                        <p id="lastChecked" class="text-muted">Never</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Check Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-search"></i> Check for Updates</h5>
            </div>
            <div class="card-body">
                <div id="updateCheckResult" class="mb-3"></div>
                
                <button id="checkUpdateBtn" class="btn btn-primary" onclick="checkForUpdates()">
                    <i class="bi bi-search"></i> Check for Updates
                </button>
                
                <button id="createBackupBtn" class="btn btn-success ms-2" onclick="createBackup()" style="display: none;">
                    <i class="bi bi-archive"></i> Create Backup
                </button>
                
                <button id="applyUpdateBtn" class="btn btn-warning ms-2" onclick="applyUpdate()" style="display: none;">
                    <i class="bi bi-download"></i> Apply Update
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update History -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Update History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($updateHistory)): ?>
                    <p class="text-muted text-center py-3">No updates applied yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Applied At</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($updateHistory as $update): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($update['version']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $update['status'] === 'applied' ? 'success' : 
                                                     ($update['status'] === 'failed' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($update['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $update['applied_at'] ? date('Y-m-d H:i', strtotime($update['applied_at'])) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($update['username'] ?? 'System'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Backup History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-archive"></i> Backup History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($backupHistory)): ?>
                    <p class="text-muted text-center py-3">No backups created yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backupHistory as $backup): ?>
                                    <tr>
                                        <td><small><?php echo htmlspecialchars($backup['filename']); ?></small></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $backup['backup_type'] === 'manual' ? 'primary' : 
                                                     ($backup['backup_type'] === 'auto' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($backup['backup_type']); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo number_format($backup['size_bytes'] / 1024, 1); ?> KB</small></td>
                                        <td><small><?php echo date('Y-m-d H:i', strtotime($backup['created_at'])); ?></small></td>
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

<!-- Custom JavaScript for Update Manager -->
<script src="<?php echo BASE_URL; ?>/assets/js/update-manager.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
