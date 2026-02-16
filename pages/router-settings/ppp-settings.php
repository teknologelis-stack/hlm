<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('router_config');

$pageTitle = 'PPP Ayarları';
$db = Database::getInstance();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>PPP Secret Kullanıcıları</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-circle me-2"></i>Yeni Kullanıcı
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchUser" placeholder="Kullanıcı ara...">
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <button class="btn btn-success btn-sm" id="refreshPPP">
                            <i class="bi bi-arrow-clockwise me-2"></i>Yenile
                        </button>
                        <button class="btn btn-warning btn-sm">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Kullanıcı Adı</th>
                                <th width="12%">Şifre</th>
                                <th width="12%">Servis</th>
                                <th width="12%">Local Address</th>
                                <th width="12%">Remote Address</th>
                                <th width="12%">Profile</th>
                                <th width="10%">Durum</th>
                                <th width="10%" class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="pppTableBody">
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted">PPP kullanıcıları yükleniyor...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni PPP Kullanıcısı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre *</label>
                        <input type="text" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Servis</label>
                        <select class="form-select" name="service">
                            <option value="any">Any</option>
                            <option value="pppoe">PPPoE</option>
                            <option value="pptp">PPTP</option>
                            <option value="l2tp">L2TP</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Local Address</label>
                        <input type="text" class="form-control" name="local_address" placeholder="192.168.1.1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remote Address</label>
                        <input type="text" class="form-control" name="remote_address" placeholder="192.168.1.2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile</label>
                        <input type="text" class="form-control" name="profile" placeholder="default">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadPPPUsers();
    
    document.getElementById('refreshPPP').addEventListener('click', loadPPPUsers);
    
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>/api/ppp-add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Kullanıcı eklendi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                this.reset();
                loadPPPUsers();
            } else {
                showToast(data.message, 'error');
            }
        });
    });
});

function loadPPPUsers() {
    const tbody = document.getElementById('pppTableBody');
    
    fetch('<?php echo BASE_URL; ?>/api/ppp-list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPPPUsers(data.users);
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">Veri yüklenirken hata oluştu</td></tr>`;
        });
}

function displayPPPUsers(users) {
    const tbody = document.getElementById('pppTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Kullanıcı bulunmuyor</td></tr>`;
        return;
    }
    
    tbody.innerHTML = users.map((user, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${user.name}</strong></td>
            <td>
                <span class="password-mask">********</span>
                <button class="btn btn-sm btn-link" onclick="togglePassword(this, '${user.password}')">
                    <i class="bi bi-eye"></i>
                </button>
            </td>
            <td><span class="badge bg-info">${user.service}</span></td>
            <td><code>${user.local_address || 'N/A'}</code></td>
            <td><code>${user.remote_address || 'N/A'}</code></td>
            <td>${user.profile || 'default'}</td>
            <td>
                ${user.disabled ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>'}
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning" onclick="editUser('${user.id}')">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteUser('${user.id}', '${user.name}')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function togglePassword(btn, password) {
    const span = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    
    if (span.textContent === '********') {
        span.textContent = password;
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        span.textContent = '********';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function deleteUser(id, name) {
    if (!confirm(`${name} kullanıcısını silmek istediğinize emin misiniz?`)) return;
    
    fetch('<?php echo BASE_URL; ?>/api/ppp-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Kullanıcı silindi', 'success');
            loadPPPUsers();
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>