<?php
session_start();

// Root path tanımla (PHP 7.0+ syntax)
$rootPath = dirname(__DIR__, 2);

require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/app.php';
require_once $rootPath . '/includes/auth.php';
require_once $rootPath . '/includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('users_manage');

$pageTitle = 'Kullanıcı Yönetimi';
$db = Database::getInstance();

$users = $db->fetchAll("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC");
$roles = $db->fetchAll("SELECT * FROM roles ORDER BY name");

include $rootPath . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Panel Kullanıcıları</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-circle me-2"></i>Yeni Kullanıcı
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchUser" placeholder="Kullanıcı ara...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <select class="form-select d-inline-block w-auto" id="filterRole">
                            <option value="">Tüm Roller</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo clean($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select d-inline-block w-auto ms-2" id="filterStatus">
                            <option value="">Tüm Durumlar</option>
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Kullanıcı Adı</th>
                                <th width="20%">E-posta</th>
                                <th width="15%">Rol</th>
                                <th width="12%">Son Giriş</th>
                                <th width="10%">Durum</th>
                                <th width="10%">Kayıt Tarihi</th>
                                <th width="8%" class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach ($users as $index => $user): ?>
                            <tr data-role="<?php echo $user['role_id']; ?>" data-status="<?php echo $user['is_active']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-sm bg-primary me-2">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo clean($user['username']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo clean($user['email']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'secondary';
                                    if ($user['role_name'] === 'Admin') $badgeClass = 'danger';
                                    if ($user['role_name'] === 'Operator') $badgeClass = 'primary';
                                    if ($user['role_name'] === 'Viewer') $badgeClass = 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo clean($user['role_name']); ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Hiç'; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-warning" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo clean($user['username']); ?>')">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo clean($user['username']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="stats-box">
            <i class="bi bi-people-fill text-primary"></i>
            <div>
                <h3><?php echo count($users); ?></h3>
                <p>Toplam Kullanıcı</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-box">
            <i class="bi bi-check-circle text-success"></i>
            <div>
                <h3><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></h3>
                <p>Aktif Kullanıcı</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-box">
            <i class="bi bi-shield-fill-check text-danger"></i>
            <div>
                <h3><?php echo count(array_filter($users, fn($u) => $u['role_name'] === 'Admin')); ?></h3>
                <p>Admin</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-box">
            <i class="bi bi-clock-history text-info"></i>
            <div>
                <h3><?php echo count(array_filter($users, fn($u) => $u['last_login'])); ?></h3>
                <p>Giriş Yapmış</p>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="newPassword" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()">
                                <i class="bi bi-arrow-repeat"></i> Oluştur
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" name="role_id" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo clean($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label" for="isActive">Aktif</label>
                        </div>
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

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Kullanıcı Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı *</label>
                        <input type="text" class="form-control" name="username" id="editUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta *</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" name="role_id" id="editRole" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo clean($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                            <label class="form-check-label" for="editIsActive">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.user-avatar-sm {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 600;
    font-size: 14px;
}

.stats-box {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stats-box i {
    font-size: 40px;
}

.stats-box h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
}

.stats-box p {
    margin: 0;
    color: var(--text-light);
    font-size: 14px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>/api/users-add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Kullanıcı eklendi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        });
    });
    
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>/api/users-update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Kullanıcı güncellendi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        });
    });
    
    document.getElementById('searchUser').addEventListener('keyup', filterUsers);
    document.getElementById('filterRole').addEventListener('change', filterUsers);
    document.getElementById('filterStatus').addEventListener('change', filterUsers);
});

function filterUsers() {
    const search = document.getElementById('searchUser').value.toLowerCase();
    const roleFilter = document.getElementById('filterRole').value;
    const statusFilter = document.getElementById('filterStatus').value;
    
    const rows = document.querySelectorAll('#usersTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const role = row.dataset.role;
        const status = row.dataset.status;
        
        let show = true;
        
        if (search && !text.includes(search)) show = false;
        if (roleFilter && role !== roleFilter) show = false;
        if (statusFilter !== '' && status !== statusFilter) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function generatePassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('newPassword').value = password;
    document.getElementById('newPassword').type = 'text';
}

function editUser(id) {
    fetch('<?php echo BASE_URL; ?>/api/users-get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editUserId').value = data.user.id;
                document.getElementById('editUsername').value = data.user.username;
                document.getElementById('editEmail').value = data.user.email;
                document.getElementById('editRole').value = data.user.role_id;
                document.getElementById('editIsActive').checked = data.user.is_active == 1;
                
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            }
        });
}

function resetPassword(id, username) {
    const newPassword = prompt(`${username} için yeni şifre:`);
    if (!newPassword) return;
    
    fetch('<?php echo BASE_URL; ?>/api/users-reset-password.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ user_id: id, password: newPassword })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Şifre sıfırlandı', 'success');
        } else {
            showToast(data.message, 'error');
        }
    });
}

function deleteUser(id, username) {
    if (!confirm(`${username} kullanıcısını silmek istediğinize emin misiniz?`)) return;
    
    fetch('<?php echo BASE_URL; ?>/api/users-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ user_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Kullanıcı silindi', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>

<?php include $rootPath . '/includes/footer.php'; ?>