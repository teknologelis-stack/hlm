<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'PTMP Cihazlar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-broadcast-tower me-2"></i>PTMP Cihazlar</h1>
    <p class="text-muted">Point-to-MultiPoint kablosuz bağlantı cihazları</p>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <button class="btn btn-primary" onclick="showAddPtmpModal()">
            <i class="fas fa-plus me-2"></i>Yeni PTMP Cihaz Ekle
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-primary"><i class="fas fa-broadcast-tower"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Toplam AP</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-success"><i class="fas fa-wifi"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Bağlı Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-info"><i class="fas fa-signal"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Online</p>
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
        <h5><i class="fas fa-list me-2"></i>PTMP (AP) Cihaz Listesi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ptmpTable">
                <thead>
                    <tr>
                        <th>Cihaz Adı</th>
                        <th>IP Adresi</th>
                        <th>MAC Adresi</th>
                        <th>SSID</th>
                        <th>Kanal</th>
                        <th>Bağlı</th>
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
