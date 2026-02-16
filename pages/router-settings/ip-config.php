<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('router_config');

$pageTitle = 'IP Yapılandırma';
$db = Database::getInstance();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-ethernet me-2"></i>IP Adresleri</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addIpModal">
                    <i class="bi bi-plus-circle me-2"></i>Ekle
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Adres</th>
                                <th>Interface</th>
                                <th>Network</th>
                                <th>Durum</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="ipAddressesBody">
                            <tr>
                                <td colspan="5" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <small class="text-muted ms-2">Yükleniyor...</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-signpost me-2"></i>IP Routes</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                    <i class="bi bi-plus-circle me-2"></i>Ekle
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Hedef</th>
                                <th>Gateway</th>
                                <th>Distance</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="ipRoutesBody">
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <small class="text-muted ms-2">Yükleniyor...</small>
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
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield me-2"></i>Firewall Filter Rules</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Chain</th>
                                <th>Action</th>
                                <th>Protocol</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody id="firewallRulesBody">
                            <tr>
                                <td colspan="5" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <small class="text-muted ms-2">Yükleniyor...</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>DHCP Server</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>İsim</th>
                                <th>Interface</th>
                                <th>Address Pool</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody id="dhcpServersBody">
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <small class="text-muted ms-2">Yükleniyor...</small>
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
    <div class="col-md-3 mb-3">
        <div class="ip-stat-card bg-primary">
            <i class="bi bi-ethernet"></i>
            <div>
                <h3 id="totalIps">0</h3>
                <p>IP Adresi</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="ip-stat-card bg-success">
            <i class="bi bi-signpost"></i>
            <div>
                <h3 id="totalRoutes">0</h3>
                <p>Route</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="ip-stat-card bg-warning">
            <i class="bi bi-shield"></i>
            <div>
                <h3 id="totalFirewall">0</h3>
                <p>Firewall Kuralı</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="ip-stat-card bg-info">
            <i class="bi bi-hdd-network"></i>
            <div>
                <h3 id="totalDhcp">0</h3>
                <p>DHCP Server</p>
            </div>
        </div>
    </div>
</div>

<!-- Add IP Modal -->
<div class="modal fade" id="addIpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>IP Adresi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addIpForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">IP Adresi *</label>
                        <input type="text" class="form-control" name="address" placeholder="192.168.1.1/24" required>
                        <small class="text-muted">CIDR formatında</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Interface *</label>
                        <select class="form-select" name="interface" required>
                            <option value="">Seçiniz...</option>
                            <option value="ether1">ether1</option>
                            <option value="ether2">ether2</option>
                            <option value="ether3">ether3</option>
                            <option value="bridge">bridge</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="comment" placeholder="Opsiyonel">
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

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Route Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRouteForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Hedef Ağ *</label>
                        <input type="text" class="form-control" name="dst_address" placeholder="0.0.0.0/0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gateway *</label>
                        <input type="text" class="form-control" name="gateway" placeholder="192.168.1.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Distance</label>
                        <input type="number" class="form-control" name="distance" value="1" min="1" max="255">
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
.ip-stat-card {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.ip-stat-card.bg-success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.ip-stat-card.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.ip-stat-card.bg-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.ip-stat-card i {
    font-size: 40px;
    opacity: 0.8;
}

.ip-stat-card h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.ip-stat-card p {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadIpAddresses();
    loadIpRoutes();
    loadFirewallRules();
    loadDhcpServers();
    
    document.getElementById('addIpForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>/api/ip-address-add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('IP adresi eklendi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addIpModal')).hide();
                this.reset();
                loadIpAddresses();
            } else {
                showToast(data.message, 'error');
            }
        });
    });
    
    document.getElementById('addRouteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('<?php echo BASE_URL; ?>/api/ip-route-add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Route eklendi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addRouteModal')).hide();
                this.reset();
                loadIpRoutes();
            } else {
                showToast(data.message, 'error');
            }
        });
    });
});

function loadIpAddresses() {
    fetch('<?php echo BASE_URL; ?>/api/ip-addresses.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('ipAddressesBody');
            if (data.success && data.addresses.length > 0) {
                tbody.innerHTML = data.addresses.map(ip => `
                    <tr>
                        <td><code>${ip.address}</code></td>
                        <td><small>${ip.interface}</small></td>
                        <td><small class="text-muted">${ip.network || 'N/A'}</small></td>
                        <td>${ip.disabled ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>'}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteIp('${ip.id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
                document.getElementById('totalIps').textContent = data.addresses.length;
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted"><small>${data.message || 'IP adresi bulunmuyor'}</small></td></tr>`;
            }
        });
}

function loadIpRoutes() {
    fetch('<?php echo BASE_URL; ?>/api/ip-routes.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('ipRoutesBody');
            if (data.success && data.routes.length > 0) {
                tbody.innerHTML = data.routes.map(route => `
                    <tr>
                        <td><code>${route.dst_address}</code></td>
                        <td><code>${route.gateway}</code></td>
                        <td><span class="badge bg-info">${route.distance}</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRoute('${route.id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
                document.getElementById('totalRoutes').textContent = data.routes.length;
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted"><small>${data.message || 'Route bulunmuyor'}</small></td></tr>`;
            }
        });
}

function loadFirewallRules() {
    fetch('<?php echo BASE_URL; ?>/api/firewall-rules.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('firewallRulesBody');
            if (data.success && data.rules.length > 0) {
                tbody.innerHTML = data.rules.map((rule, idx) => `
                    <tr>
                        <td>${idx + 1}</td>
                        <td><span class="badge bg-secondary">${rule.chain}</span></td>
                        <td><span class="badge bg-primary">${rule.action}</span></td>
                        <td><small>${rule.protocol || 'any'}</small></td>
                        <td>${rule.disabled ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>'}</td>
                    </tr>
                `).join('');
                document.getElementById('totalFirewall').textContent = data.rules.length;
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted"><small>${data.message || 'Firewall kuralı bulunmuyor'}</small></td></tr>`;
            }
        });
}

function loadDhcpServers() {
    fetch('<?php echo BASE_URL; ?>/api/dhcp-servers.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('dhcpServersBody');
            if (data.success && data.servers.length > 0) {
                tbody.innerHTML = data.servers.map(server => `
                    <tr>
                        <td><strong>${server.name}</strong></td>
                        <td><code>${server.interface}</code></td>
                        <td><small>${server.address_pool || 'N/A'}</small></td>
                        <td>${server.disabled ? '<span class="badge bg-danger">Pasif</span>' : '<span class="badge bg-success">Aktif</span>'}</td>
                    </tr>
                `).join('');
                document.getElementById('totalDhcp').textContent = data.servers.length;
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted"><small>${data.message || 'DHCP server bulunmuyor'}</small></td></tr>`;
            }
        });
}

function deleteIp(id) {
    if (!confirm('IP adresini silmek istediğinize emin misiniz?')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/ip-address-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('IP adresi silindi', 'success');
            loadIpAddresses();
        } else {
            showToast(data.message, 'error');
        }
    });
}

function deleteRoute(id) {
    if (!confirm('Route silmek istediğinize emin misiniz?')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/ip-route-delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Route silindi', 'success');
            loadIpRoutes();
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>