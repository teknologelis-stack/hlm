<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Müşteri Cihazları';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users me-2"></i>Müşteri Cihazları</h1>
    <p class="text-muted">Müşterilere ait cihazları buradan yönetebilirsiniz</p>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <button class="btn btn-primary" onclick="showAddCustomerModal()">
            <i class="fas fa-plus me-2"></i>Yeni Müşteri Ekle
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-primary"><i class="fas fa-users"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Toplam Müşteri</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-success"><i class="fas fa-wifi"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Aktif Bağlantı</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Pasif</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-danger"><i class="fas fa-user-slash"></i></div>
            <div class="stats-info">
                <h3>0</h3>
                <p>Askıda</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Müşteri Listesi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="customerTable">
                <thead>
                    <tr>
                        <th>Müşteri Adı</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>Paket</th>
                        <th>Cihaz IP</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Henüz müşteri eklenmedi</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
