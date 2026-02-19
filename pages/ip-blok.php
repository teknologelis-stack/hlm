<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'IP Blok Listesi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-ban me-2"></i>IP Blok Listesi</h1>
    <p class="text-muted">Mikrotik cihazındaki IP bloklarını görüntüleyin</p>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2"></i>Bloklanan IP'ler</h5>
        <button class="btn btn-primary btn-sm" onclick="refreshIpBlocks()">
            <i class="fas fa-sync-alt me-1"></i>Yenile
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ipBlockTable">
                <thead>
                    <tr>
                        <th>IP Adresi</th>
                        <th>Açıklama</th>
                        <th>Eklenme Tarihi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Henüz bloklanmış IP yok</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
