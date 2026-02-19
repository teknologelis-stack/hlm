<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Kapalı İş Emirleri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-check-circle me-2"></i>Kapalı İş Emirleri</h1>
    <p class="text-muted">Tamamlanmış ve kapatılmış iş emirleri</p>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="btn-group">
            <button class="btn btn-outline-primary active">Tümü</button>
            <button class="btn btn-outline-primary">Bugün</button>
            <button class="btn btn-outline-primary">Bu Hafta</button>
            <button class="btn btn-outline-primary">Bu Ay</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="closedOrdersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Konu</th>
                        <th>Müşteri</th>
                        <th>Tip</th>
                        <th>Öncelik</th>
                        <th>Atanan</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Kapalı iş emri yok</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
