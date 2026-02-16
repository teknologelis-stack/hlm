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
$auth->requirePermission('roles_manage');

$pageTitle = 'Rol Yönetimi';
$db = Database::getInstance();

$roles = $db->fetchAll("SELECT r.*, COUNT(u.id) as user_count FROM roles r LEFT JOIN users u ON r.id = u.role_id GROUP BY r.id ORDER BY r.name");

include $rootPath . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Roller ve Yetkiler</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                    <i class="bi bi-plus-circle me-2"></i>Yeni Rol
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($roles as $role): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="role-card">
                            <div class="role-header">
                                <div class="role-icon">
                                    <i class="bi bi-shield-fill-check"></i>
                                </div>
                                <div class="role-info">
                                    <h5><?php echo clean($role['name']); ?></h5>
                                    <span class="text-muted"><?php echo $role['user_count']; ?> kullanıcı</span>
                                </div>
                            </div>
                            
                            <p class="role-description"><?php echo clean($role['description']); ?></p>
                            
                            <div class="permissions-list">
                                <?php
                                $permissions = json_decode($role['permissions'], true);
                                $permissionLabels = [
                                    'dashboard_view' => 'Dashboard Görüntüleme',
                                    'users_manage' => 'Kullanıcı Yönetimi',
                                    'devices_manage' => 'Cihaz Yönetimi',
                                    'router_config' => 'Router Yapılandırma',
                                    'logs_view' => 'Log Görüntüleme',
                                    'settings_manage' => 'Ayarlar Yönetimi',
                                    'roles_manage' => 'Rol Yönetimi'
                                ];
                                
                                foreach ($permissionLabels as $key => $label):
                                    $hasPermission = isset($permissions[$key]) && $permissions[$key] === true;
                                ?>
                                <div class="permission-item">
                                    <?php if ($hasPermission): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    <?php endif; ?>
                                    <span><?php echo $label; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="role-actions">
                                <button class="btn btn-sm btn-outline-warning" onclick="editRole(<?php echo $role['id']; ?>)">
                                    <i class="bi bi-pencil me-1"></i>Düzenle
                                </button>
                                <?php if ($role['user_count'] == 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRole(<?php echo $role['id']; ?>, '<?php echo clean($role['name']); ?>')">
                                    <i class="bi bi-trash me-1"></i>Sil
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni Rol Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rol Adı *</label>
                        <input type="text" class="form-control" name="name" placeholder="Örn: Moderator" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Rolün açıklaması"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label mb-3">Yetkiler *</label>
                        <div class="permissions-grid">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[dashboard_view]" id="perm1" value="1">
                                <label class="form-check-label" for="perm1">
                                    <i class="bi bi-speedometer2 text-primary"></i> Dashboard Görüntüleme
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[users_manage]" id="perm2" value="1">
                                <label class="form-check-label" for="perm2">
                                    <i class="bi bi-people text-primary"></i> Kullanıcı Yönetimi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[devices_manage]" id="perm3" value="1">
                                <label class="form-check-label" for="perm3">
                                    <i class="bi bi-hdd-network text-primary"></i> Cihaz Yönetimi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[router_config]" id="perm4" value="1">
                                <label class="form-check-label" for="perm4">
                                    <i class="bi bi-gear text-primary"></i> Router Yapılandırma
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[logs_view]" id="perm5" value="1">
                                <label class="form-check-label" for="perm5">
                                    <i class="bi bi-journal-text text-primary"></i> Log Görüntüleme
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[settings_manage]" id="perm6" value="1">
                                <label class="form-check-label" for="perm6">
                                    <i class="bi bi-sliders text-primary"></i> Ayarlar Yönetimi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[roles_manage]" id="perm7" value="1">
                                <label class="form-check-label" for="perm7">
                                    <i class="bi bi-shield-check text-primary"></i> Rol Yönetimi
                                </label>
                            </div>
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

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Rol Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoleForm">
                <input type="hidden" name="role_id" id="editRoleId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rol Adı *</label>
                        <input type="text" class="form-control" name="name" id="editRoleName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" id="editRoleDescription" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label mb-3">Yetkiler *</label>
                        <div class="permissions-grid">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[dashboard_view]" id="editPerm1" value="1">
                                <label class="form-check-label" for="editPerm1">
                                    <i class="bi bi-speedometer2 text-primary"></i> Dashboard Görüntüleme
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[users_manage]" id="editPerm2" value="1">
                                <label class="form-check-label" for="editPerm2">
                                    <i class="bi bi-people text-primary"></i> Kullanıcı Yönetimi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[devices_manage]" id="editPerm3" value="1">
                                <label class="form-check-label" for="editPerm3">
                                    <i class="bi bi-hdd-network text-primary"></i> Cihaz Yönetimi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[router_config]" id="editPerm4" value="1">
                                <label class="form-check-label" for="editPerm4">
                                    <i class="bi bi-gear text-primary"></i> Router Yapılandırma
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[logs_view]" id="editPerm5" value="1">
                                <label class="form-check-label" for="editPerm5">
                                    <i class="bi bi-journal-text text-primary"></i> Log Görüntüleme
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[settings_manage]" id="editPerm6" value="1">
                                <label class="form-check-label" for="editPerm6">
                                    <i class="bi bi-sliders text-primary"></i> Ayarlar Yönetimi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[roles_manage]" id="editPerm7" value="1">
                                <label class="form-check-label" for="editPerm7">
                                    <i class="bi bi-shield-check text-primary"></i> Rol Yönetimi
                                </label>
                            </div>
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
.role-card {
    background: #ffffff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.role-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.role-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.role-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #ffffff;
}

.role-info h5 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
}

.role-description {
    color: var(--text-light);
    font-size: 14px;
    margin-bottom: 20px;
    flex-grow: 0;
}

.permissions-list {
    flex-grow: 1;
    margin-bottom: 20px;
}

.permission-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 13px;
    border-bottom: 1px solid var(--border-color);
}

.permission-item:last-child {
    border-bottom: none;
}

.permission-item i {
    font-size: 16px;
}

.role-actions {
    display: flex;
    gap: 8px;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}

.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.form-check-label {
    cursor: pointer;
    font-size: 14px;
}

.form-check-label i {
    margin-right: 5px;
}
</style>

<script>
document.getElementById('addRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>/api/roles-add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Rol eklendi', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addRoleModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
});

document.getElementById('editRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>/api/roles-update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Rol güncellendi', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editRoleModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
});

function editRole(id) {
    fetch('<?php echo BASE_URL; ?>/api/roles-get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editRoleId').value = data.role.id;
                document.getElementById('editRoleName').value = data.role.name;
                document.getElementById('editRoleDescription').value = data.role.description;
                
                const permissions = JSON.parse(data.role.permissions);
                document.getElementById('editPerm1').checked = permissions.dashboard_view || false;
                document.getElementById('editPerm2').checked = permissions.users_manage || false;
                document.getElementById('editPerm3').checked = permissions.devices_manage || false;
                document.getElementById('editPerm4').checked = permissions.router_config || false;
                document.getElementById('editPerm5').checked = permissions.logs_view || false;
                document.getElementById('editPerm6').checked = permissions.settings_manage || false;
                document.getElementById('editPerm7').checked = permissions.roles_manage || false;
                
                new bootstrap.Modal(document.getElementById('editRoleModal')).show();
            }
        });
}

function deleteRole(id, name) {
    if (!confirm(`${name} rolünü silmek istediğinize emin misiniz?`)) return;
    
    fetch('<?php echo BASE_URL; ?>/api/roles-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ role_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Rol silindi', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>

<?php include $rootPath . '/includes/footer.php'; ?>