<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'PTP Cihazlar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-point-to-point me-2"></i>PTP Cihazlar</h1>
    <p class="text-muted">Point-to-Point kablosuz bağlantı cihazları</p>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <button class="btn btn-primary" onclick="showAddPtpModal()">
            <i class="fas fa-plus me-2"></i>Yeni PTP Cihaz Ekle
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-primary"><i class="fas fa-point-to-point"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Toplam Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-success"><i class="fas fa-signal"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Online</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-danger"><i class="fas fa-times-circle"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Offline</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Uyarı</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>PTP Cihaz Listesi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ptpTable">
                <thead>
                    <tr>
                        <th>Cihaz Adı</th>
                        <th>IP Adresi</th>
                        <th>MAC Adresi</th>
                        <th>SSID</th>
                        <th>Signal</th>
                        <th>Status</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Henüz cihaz eklenmedi</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
