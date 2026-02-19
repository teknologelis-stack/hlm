<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'NAT Kuralı Oluştur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-exchange-alt me-2"></i>NAT Kuralı Oluştur</h1>
    <p class="text-muted">Port yönlendirme ve NAT kurallarını buradan oluşturabilirsiniz</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus-circle me-2"></i>Yeni NAT Kuralı</h5>
            </div>
            <div class="card-body">
                <form id="natForm">
                    <div class="mb-3">
                        <label class="form-label">Kural Adı</label>
                        <input type="text" class="form-control" id="natName" placeholder="Web Server Yönlendirme" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kural Tipi</label>
                        <select class="form-select" id="natType">
                            <option value="dst-nat">Dst-NAT (Port Yönlendirme)</option>
                            <option value="src-nat">Src-NAT (Masquerade)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dış Port</label>
                        <input type="text" class="form-control" id="natDstPort" placeholder="8080">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef IP</label>
                        <input type="text" class="form-control" id="natToAddress" placeholder="192.168.1.100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef Port</label>
                        <input type="text" class="form-control" id="natToPort" placeholder="80">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Protokol</label>
                        <select class="form-select" id="natProtocol">
                            <option value="tcp">TCP</option>
                            <option value="udp">UDP</option>
                            <option value="tcp,udp">TCP/UDP</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Kuralı Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Mevcut NAT Kuralları</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kural Adı</th>
                                <th>Tip</th>
                                <th>Dış Port</th>
                                <th>Hedef</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Henüz NAT kuralı yok</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
