<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Loglar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-clipboard-list me-2"></i>Loglar</h1>
    <p class="text-muted">Mikrotik cihazından alınan log kayıtları</p>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5><i class="fas fa-list me-2"></i>Sistem Logları</h5>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="logFilter" style="width: auto;">
                        <option value="all">Tümü</option>
                        <option value="info">Bilgi</option>
                        <option value="warning">Uyarı</option>
                        <option value="error">Hata</option>
                    </select>
                    <button class="btn btn-primary btn-sm" onclick="refreshLogs()">
                        <i class="fas fa-sync-alt me-1"></i>Yenile
                    </button>
                    <button class="btn btn-success btn-sm" onclick="exportLogs()">
                        <i class="fas fa-download me-1"></i>Dışa Aktar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-hover" id="logTable">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Saat</th>
                        <th>Topic</th>
                        <th>Mesaj</th>
                        <th>Seviye</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    <tr>
                        <td colspan="5" class="text-center text-muted">Log verileri yükleniyor...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
