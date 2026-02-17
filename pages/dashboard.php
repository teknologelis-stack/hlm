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

<!-- 🎉 v1.0.2 UPDATE SUCCESS MESSAGE -->
<div class="alert alert-success">
    <i class="fas fa-check-circle" style="font-size: 24px;"></i>
    <div>
        <strong>Güncelleme Başarılı! 🎉</strong>
        <p style="margin: 5px 0 0 0;">Sistem <strong>v<?php echo APP_VERSION; ?></strong> versiyonuna başarıyla güncellendi. Bu mesajı görüyorsanız, güncelleme sistemi mükemmel çalışıyor demektir!</p>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle" style="font-size: 24px;"></i>
    <div>
        <strong>Yenilikler v<?php echo APP_VERSION; ?></strong>
        <ul style="margin: 5px 0 0 20px;">
            <li>✅ Modern header eklendi</li>
            <li>✅ Navigasyon sidebar eklendi</li>
            <li>✅ Kullanıcı menüsü eklendi</li>
            <li>✅ Responsive tasarım</li>
        </ul>
    </div>
</div>

<h1>Dashboard</h1>
<p>Hoş geldiniz, <?php echo htmlspecialchars($auth->getCurrentUser()['username']); ?>!</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
