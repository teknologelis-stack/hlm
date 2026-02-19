<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Sistem Logları';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-file-alt me-2"></i>Sistem Logları</h1>
    <p class="text-muted">PHP ve uygulama log kayıtları</p>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5><i class="fas fa-list me-2"></i>Uygulama Logları</h5>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-danger btn-sm" onclick="clearLogs()">
                    <i class="fas fa-trash me-1"></i>Logları Temizle
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php
        $logFile = ROOT_PATH . '/logs/error.log';
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $logs = array_slice($logs, -100); // Son 100 satır
            echo '<div class="log-viewer">';
            foreach ($logs as $line) {
                echo '<div class="log-line">' . htmlspecialchars($line) . '</div>';
            }
            echo '</div>';
        } else {
            echo '<p class="text-muted">Henüz log dosyası oluşturulmamış</p>';
        }
        ?>
    </div>
</div>

<style>
.log-viewer {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 500px;
    overflow-y: auto;
}
.log-line {
    padding: 2px 0;
    border-bottom: 1px solid #333;
}
.log-line:last-child {
    border-bottom: none;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
