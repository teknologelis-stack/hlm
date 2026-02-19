<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Statik IP Blok';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-thumbtack me-2"></i>Statik IP Blok</h1>
    <p class="text-muted">Statik IP adreslerini buradan yönetebilirsiniz</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle me-2"></i>Statik IP Ekle</h5>
            </div>
            <div class="card-body">
                <form id="staticIpForm">
                    <div class="mb-3">
                        <label class="form-label">IP Adresi</label>
                        <input type="text" class="form-control" id="staticIp" placeholder="192.168.1.100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MAC Adresi</label>
                        <input type="text" class="form-control" id="staticMac" placeholder="AA:BB:CC:DD:EE:FF">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" id="staticDesc" placeholder="Müşteri adı veya not">
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
                <h5><i class="fas fa-list me-2"></i>Statik IP Listesi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>MAC</th>
                                <th>Açıklama</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Henüz statik IP eklenmedi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
