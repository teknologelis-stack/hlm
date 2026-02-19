<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Lokal IP Blok';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-building me-2"></i>Lokal IP Blok</h1>
    <p class="text-muted">Lokal ağdaki IP adreslerini buradan yönetebilirsiniz</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle me-2"></i>Lokal IP Ekle</h5>
            </div>
            <div class="card-body">
                <form id="localIpForm">
                    <div class="mb-3">
                        <label class="form-label">IP Aralığı</label>
                        <input type="text" class="form-control" id="localIpRange" placeholder="192.168.1.0/24" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DHCP Pool Adı</label>
                        <input type="text" class="form-control" id="localPoolName" placeholder="dhcp-pool">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gateway</label>
                        <input type="text" class="form-control" id="localGateway" placeholder="192.168.1.1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNS Sunucuları</label>
                        <input type="text" class="form-control" id="localDns" placeholder="8.8.8.8, 8.8.4.4">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-network-wide me-2"></i>Ağ Yapılandırmaları</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ağ</th>
                                <th>Pool</th>
                                <th>Gateway</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Henüz yapılandırma yok</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
