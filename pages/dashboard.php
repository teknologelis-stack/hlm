<?php
/**
 * Dashboard Page
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/UpdateManager.php';

$auth = new Auth();
$auth->requireLogin();

$updateManager = new UpdateManager();
$currentUser = $auth->getCurrentUser();

// Get statistics
try {
    $db = Database::getInstance()->getConnection();
    
    // Count users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $userCount = $stmt->fetch()['count'];
    
    // Count backups
    $stmt = $db->query("SELECT COUNT(*) as count FROM system_backups");
    $backupCount = $stmt->fetch()['count'];
    
    // Get last update
    $stmt = $db->query("SELECT * FROM system_updates WHERE status = 'applied' ORDER BY applied_at DESC LIMIT 1");
    $lastUpdate = $stmt->fetch();
    
    // Get recent backups
    $recentBackups = $updateManager->getBackupHistory(5);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $userCount = 0;
    $backupCount = 0;
    $lastUpdate = null;
    $recentBackups = [];
}

$pageTitle = 'Dashboard - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Current Version</h6>
                        <h3 class="mb-0"><?php echo APP_VERSION; ?></h3>
                    </div>
                    <div>
                        <i class="bi bi-box-seam" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Active Users</h6>
                        <h3 class="mb-0"><?php echo $userCount; ?></h3>
                    </div>
                    <div>
                        <i class="bi bi-people" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Total Backups</h6>
                        <h3 class="mb-0"><?php echo $backupCount; ?></h3>
                    </div>
                    <div>
                        <i class="bi bi-archive" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Last Update</h6>
                        <h3 class="mb-0">
                            <?php echo $lastUpdate ? date('M d', strtotime($lastUpdate['applied_at'])) : 'N/A'; ?>
                        </h3>
                    </div>
                    <div>
                        <i class="bi bi-clock-history" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="<?php echo BASE_URL; ?>/pages/update-manager.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-cloud-download"></i><br>
                            Update Manager
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button class="btn btn-outline-success w-100" onclick="createQuickBackup()">
                            <i class="bi bi-archive"></i><br>
                            Create Backup
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-gear"></i><br>
                            Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Backups -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-archive"></i> Recent Backups</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentBackups)): ?>
                    <p class="text-muted text-center py-3">No backups found</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBackups as $backup): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $backup['backup_type'] === 'manual' ? 'primary' : 
                                                     ($backup['backup_type'] === 'auto' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($backup['backup_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($backup['size_bytes'] / 1024, 2); ?> KB</td>
                                        <td><?php echo htmlspecialchars($backup['username'] ?? 'System'); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($backup['created_at'])); ?></td>
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

<script>
function createQuickBackup() {
    Swal.fire({
        title: 'Create Backup',
        text: 'Do you want to create a backup now?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, create backup',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Creating backup...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(BASE_URL + '/api/system-backup-create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', data.message || 'Backup created successfully', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error!', data.error || 'Failed to create backup', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Network error occurred', 'error');
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
