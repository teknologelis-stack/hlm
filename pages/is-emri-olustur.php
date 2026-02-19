<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'İş Emri Oluştur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-plus-circle me-2"></i>İş Emri Oluştur</h1>
    <p class="text-muted">Yeni bir iş emri oluşturun</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus me-2"></i>Yeni İş Emri</h5>
            </div>
            <div class="card-body">
                <form id="workOrderForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Müşteri Seç</label>
                                <select class="form-select" id="woCustomer" required>
                                    <option value="">Müşteri seçin...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">İşlem Tipi</label>
                                <select class="form-select" id="woType" required>
                                    <option value="">Tip seçin...</option>
                                    <option value="kurulum">Kurulum</option>
                                    <option value="bakim">Bakım</option>
                                    <option value="ariza">Arıza</option>
                                    <option value="iptal">İptal</option>
                                    <option value="nakliye">Nakliye</option>
                                    <option value="diger">Diğer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Öncelik</label>
                                <select class="form-select" id="woPriority" required>
                                    <option value="normal">Normal</option>
                                    <option value="yuksek">Yüksek</option>
                                    <option value="acil">Acil</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tahmini Süre</label>
                                <select class="form-select" id="woDuration">
                                    <option value="1">1 Saat</option>
                                    <option value="2">2 Saat</option>
                                    <option value="4">4 Saat</option>
                                    <option value="8">8 Saat</option>
                                    <option value="1gun">1 Gün</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konu</label>
                        <input type="text" class="form-control" id="woSubject" placeholder="İş emri konusu" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" id="woDescription" rows="4" placeholder="Detaylı açıklama..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea class="form-control" id="woAddress" rows="2" placeholder="Çalışma adresi"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>İş Emri Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Bilgiler</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>İş emri oluşturulduktan sonra listeye eklenir</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>İşlemde olanlar listesinde görünür</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Tamamlandığında kapatılabilir</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
