<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('router_config');

$pageTitle = 'VLAN Ayarları';
$db = Database::getInstance();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>VLAN Yapılandırması</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVlanModal">
                    <i class="bi bi-plus-circle me-2"></i>Yeni VLAN
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchVlan" placeholder="VLAN ara...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success btn-sm" id="refreshVlan">
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
                                <th width="15%">VLAN ID</th>
                                <th width="20%">İsim</th>
                                <th width="15%">Interface</th>
                                <th width="15%">IP Address</th>
                                <th width="12%">Network</th>
                                <th width="10%">Durum</th>
                                <th width="8%" class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="vlanTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted">VLAN'lar yükleniyor...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-ethernet me-2"></i>Bridge Portları</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Port</th>
                                <th>Bridge</th>
                                <th>PVID</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody id="bridgePortsBody">
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    <small>Bridge portları yükleniyor...</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>VLAN Bilgileri</h6>
            </div>
            <div class="card-body">
                <div class="vlan-info">
                    <div class="info-box">
                        <i class="bi bi-diagram-3 text-primary"></i>
                        <div>
                            <h4 id="totalVlans">0</h4>
                            <p>Toplam VLAN</p>
                        </div>
                    </div>
                    <div class="info-box">
                        <i class="bi bi-check-circle text-success"></i>
                        <div>
                            <h4 id="activeVlans">0</h4>
                            <p>Aktif VLAN</p>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3 mb-0">
                    <h6 class="alert-heading"><i class="bi bi-lightbulb me-2"></i>VLAN Nedir?</h6>
                    <small>VLAN (Virtual LAN), fiziksel ağı mantıksal olarak bölümlere ayırmanızı sağlar. Her VLAN kendi broadcast domain'ine sahiptir ve izole edilmiştir.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add VLAN Modal -->
<div class="modal fade" id="addVlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni VLAN Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addVlanForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">VLAN ID *</label>
                            <input type="number" class="form-control" name="vlan_id" min="1" max="4094" required placeholder="10">
                            <small class="text-muted">1-4094 arası değer</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">VLAN İsmi *</label>
                            <input type="text" class="form-control" name="name" required placeholder="Örn: GUEST">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Interface *</label>
                        <select class="form-select" name="interface" required>
                            <option value="">Seçiniz...</option>
                            <option value="bridge">bridge</option>
                            <option value="ether1">ether1</option>
                            <option value="ether2">ether2</option>
                            <option value="ether3">ether3</option>
                            <option value="ether4">ether4</option>
                            <option value="ether5">ether5</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IP Address</label>
                            <input type="text" class="form-control" name="ip_address" placeholder="192.168.10.1/24">
                            <small class="text-muted">CIDR formatında</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Network</label>
                            <input type="text" class="form-control" name="network" placeholder="192.168.10.0/24">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="comment" rows="2" placeholder="VLAN açıklaması..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="disabled" id="vlanDisabled">
                            <label class="form-check-label" for="vlanDisabled">Pasif (Disabled)</label>
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

<style>
.vlan-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.info-box {
    background: var(--bg-light);
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.info-box i {
    font-size: 36px;
}

.info-box h4 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
}

.info-box p {
    margin: 0;
    font-size: 13px;
    color: var(--text-light);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadVlans();
    loadBridgePorts();
    
    document.getElementById('refreshVlan').addEventListener('click', loadVlans);
    
    document.getElementById('addVlanForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>/api/vlan-add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('VLAN eklendi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addVlanModal')).hide();
                this.reset();
                loadVlans();
            } else {
                showToast(data.message, 'error');
            }
        });
    });
});

function loadVlans() {
    const tbody = document.getElementById('vlanTableBody');
    
    fetch('<?php echo BASE_URL; ?>/api/vlan-list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayVlans(data.vlans);
                updateVlanStats(data.vlans);
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Veri yüklenirken hata oluştu</td></tr>`;
        });
}

function displayVlans(vlans) {
    const tbody = document.getElementById('vlanTableBody');
    
    if (vlans.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">VLAN bulunmuyor</td></tr>`;
        return;
    }
    
    tbody.innerHTML = vlans.map((vlan, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><span class="badge bg-primary">VLAN ${vlan.vlan_id}</span></td>
            <td><strong>${vlan.name}</strong></td>
            <td><code>${vlan.interface}</code></td>
            <td><code>${vlan.ip_address || 'N/A'}</code></td>
            <td><small class="text-muted">${vlan.network || 'N/A'}</small></td>
            <td>
                ${vlan.disabled ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>'}
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-warning" onclick="editVlan('${vlan.id}')">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteVlan('${vlan.id}', '${vlan.name}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updateVlanStats(vlans) {
    document.getElementById('totalVlans').textContent = vlans.length;
    document.getElementById('activeVlans').textContent = vlans.filter(v => !v.disabled).length;
}

function loadBridgePorts() {
    const tbody = document.getElementById('bridgePortsBody');
    
    fetch('<?php echo BASE_URL; ?>/api/bridge-ports.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ports.length > 0) {
                tbody.innerHTML = data.ports.map(port => `
                    <tr>
                        <td><code>${port.interface}</code></td>
                        <td><small>${port.bridge}</small></td>
                        <td><span class="badge bg-info">${port.pvid}</span></td>
                        <td>${port.disabled ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>'}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted"><small>${data.message || 'Port bulunmuyor'}</small></td></tr>`;
            }
        });
}

function deleteVlan(id, name) {
    if (!confirm(`${name} VLAN'ını silmek istediğinize emin misiniz?`)) return;
    
    fetch('<?php echo BASE_URL; ?>/api/vlan-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('VLAN silindi', 'success');
            loadVlans();
        } else {
            showToast(data.message, 'error');
        }
    });
}

function editVlan(id) {
    showToast('Düzenleme özelliği yakında eklenecek', 'info');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>