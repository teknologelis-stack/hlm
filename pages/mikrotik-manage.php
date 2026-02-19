<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Mikrotik Yönetim';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, name, ip_address, port, status FROM mikrotik_devices ORDER BY name");
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.nav-tabs .nav-link.active { border-top: 3px solid #3b82f6; }
.device-select { min-width: 250px; }
.table-responsive { overflow-x: auto; }
.action-btn { width: 80px; }
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4><i class="fas fa-server me-2"></i>Mikrotik Yönetim</h4>
        </div>
        <div class="col-md-6">
            <select id="deviceSelect" class="form-select device-select float-end" onchange="loadDeviceData()">
                <option value="">Cihaz Seçin...</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['ip_address'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="deviceInfo" style="display:none;">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ppp" type="button">PPP Secret</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vlan" type="button">VLAN</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ntp" type="button">NTP</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dns" type="button">DNS</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dhcp" type="button">DHCP</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ip" type="button">IP Adres</button></li>
        </ul>

        <div class="tab-content mt-3">
            <!-- PPP Secret -->
            <div class="tab-pane fade show active" id="ppp">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">PPP Kullanıcıları</h5>
                        <button class="btn btn-primary btn-sm" onclick="showPppModal()"><i class="fas fa-plus me-1"></i>Yeni Kullanıcı</button>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover" id="pppTable">
                            <thead><tr><th>Kullanıcı</th><th>Service</th><th>Profile</th><th>IP Adresi</th><th>Durum</th><th>İşlem</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VLAN -->
            <div class="tab-pane fade" id="vlan">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">VLAN Yönetimi</h5>
                        <button class="btn btn-primary btn-sm" onclick="showVlanModal()"><i class="fas fa-plus me-1"></i>Yeni VLAN</button>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover" id="vlanTable">
                            <thead><tr><th>Ad</th><th>VLAN ID</th><th>Arayüz</th><th>Durum</th><th>İşlem</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- NTP -->
            <div class="tab-pane fade" id="ntp">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">NTP Client</h5></div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="ntpClientEnabled">
                                    <label class="form-check-label">Aktif</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Birincil NTP</label>
                                    <input type="text" class="form-control" id="ntpPrimary" placeholder="time.windows.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">İkincil NTP</label>
                                    <input type="text" class="form-control" id="ntpSecondary" placeholder="pool.ntp.org">
                                </div>
                                <button class="btn btn-primary" onclick="saveNtpClient()">Kaydet</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">NTP Server</h5></div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="ntpServerEnabled">
                                    <label class="form-check-label">Aktif</label>
                                </div>
                                <button class="btn btn-primary" onclick="saveNtpServer()">Kaydet</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DNS -->
            <div class="tab-pane fade" id="dns">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">DNS Ayarları</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">DNS Sunucuları</label>
                                    <input type="text" class="form-control" id="dnsServers" placeholder="8.8.8.8,8.8.4.4">
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="dnsRemoteRequests">
                                    <label class="form-check-label">Uzaktan İzin Ver</label>
                                </div>
                                <button class="btn btn-primary" onclick="saveDns()">Kaydet</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Statik DNS Kayıtları</h5>
                                <button class="btn btn-primary btn-sm" onclick="showDnsModal()">+Ekle</button>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm" id="dnsTable">
                                    <thead><tr><th>Adres</th><th>IP</th><th>İşlem</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DHCP -->
            <div class="tab-pane fade" id="dhcp">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">DHCP Sunucuları</h5>
                                <button class="btn btn-primary btn-sm" onclick="showDhcpServerModal()">+Ekle</button>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm" id="dhcpServerTable">
                                    <thead><tr><th>Ad</th><th>Arayüz</th><th>Durum</th><th>İşlem</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">DHCP Ağları</h5>
                                <button class="btn btn-primary btn-sm" onclick="showDhcpNetworkModal()">+Ekle</button>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm" id="dhcpNetworkTable">
                                    <thead><tr><th>Adres</th><th>Gateway</th><th>DNS</th><th>İşlem</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">DHCP Lease'ler</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm" id="dhcpLeaseTable">
                            <thead><tr><th>IP</th><th>MAC</th><th>Host</th><th>Son Aktivite</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- IP Address -->
            <div class="tab-pane fade" id="ip">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">IP Adresleri</h5>
                        <button class="btn btn-primary btn-sm" onclick="showIpModal()"><i class="fas fa-plus me-1"></i>Yeni IP</button>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover" id="ipTable">
                            <thead><tr><th>Adres</th><th>Network</th><th>Arayüz</th><th>Durum</th><th>İşlem</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="noDevice" class="text-center py-5">
        <i class="fas fa-server fa-4x text-muted mb-3"></i>
        <p class="text-muted">Yönetmek için bir cihaz seçin</p>
    </div>
</div>

<!-- PPP Modal -->
<div class="modal fade" id="pppModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">PPP Kullanıcı</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="pppId">
                <div class="mb-3"><label class="form-label">Kullanıcı Adı</label><input type="text" class="form-control" id="pppName"></div>
                <div class="mb-3"><label class="form-label">Şifre</label><input type="text" class="form-control" id="pppPassword"></div>
                <div class="mb-3"><label class="form-label">Service</label><select class="form-select" id="pppService"><option value="any">any</option><option value="pppoe">PPPoE</option><option value="pptp">PPTP</option><option value="l2tp">L2TP</option><option value="ovpn">OVPN</option></select></div>
                <div class="mb-3"><label class="form-label">Profile</label><input type="text" class="form-control" id="pppProfile" value="default"></div>
                <div class="mb-3"><label class="form-label">Remote IP (opsiyonel)</label><input type="text" class="form-control" id="pppRemote"></div>
                <div class="mb-3"><label class="form-label">Local IP (opsiyonel)</label><input type="text" class="form-control" id="pppLocal"></div>
                <div class="mb-3"><label class="form-label">Açıklama</label><input type="text" class="form-control" id="pppComment"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="savePpp()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- VLAN Modal -->
<div class="modal fade" id="vlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">VLAN</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="vlanId">
                <div class="mb-3"><label class="form-label">VLAN Adı</label><input type="text" class="form-control" id="vlanName"></div>
                <div class="mb-3"><label class="form-label">VLAN ID</label><input type="number" class="form-control" id="vlanIdNum"></div>
                <div class="mb-3"><label class="form-label">Arayüz</label><input type="text" class="form-control" id="vlanInterface"></div>
                <div class="mb-3"><label class="form-label">Açıklama</label><input type="text" class="form-control" id="vlanComment"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveVlan()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- DNS Static Modal -->
<div class="modal fade" id="dnsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">DNS Kaydı</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="dnsId">
                <div class="mb-3"><label class="form-label">Domain Adı</label><input type="text" class="form-control" id="dnsName"></div>
                <div class="mb-3"><label class="form-label">IP Adresi</label><input type="text" class="form-control" id="dnsAddress"></div>
                <div class="mb-3"><label class="form-label">Açıklama</label><input type="text" class="form-control" id="dnsComment"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveDnsStatic()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- DHCP Server Modal -->
<div class="modal fade" id="dhcpServerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">DHCP Sunucu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Ad</label><input type="text" class="form-control" id="dhcpName"></div>
                <div class="mb-3"><label class="form-label">Arayüz</label><input type="text" class="form-control" id="dhcpInterface"></div>
                <div class="mb-3"><label class="form-label">Address Pool</label><select class="form-select" id="dhcpPool"><option value="static-only">static-only</option><option value="dhcp">dhcp</option></select></div>
                <div class="mb-3"><label class="form-label">Lease Time</label><input type="text" class="form-control" id="dhcpLease" value="10m"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveDhcpServer()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- DHCP Network Modal -->
<div class="modal fade" id="dhcpNetworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">DHCP Ağ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Ağ Adresi</label><input type="text" class="form-control" id="dhcpNetAddress" placeholder="192.168.1.0/24"></div>
                <div class="mb-3"><label class="form-label">Gateway</label><input type="text" class="form-control" id="dhcpNetGateway" placeholder="192.168.1.1"></div>
                <div class="mb-3"><label class="form-label">DNS Sunucuları</label><input type="text" class="form-control" id="dhcpNetDns" placeholder="8.8.8.8,8.8.4.4"></div>
                <div class="mb-3"><label class="form-label">Domain</label><input type="text" class="form-control" id="dhcpNetDomain"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveDhcpNetwork()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- IP Modal -->
<div class="modal fade" id="ipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">IP Adres</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="ipId">
                <div class="mb-3"><label class="form-label">Adres</label><input type="text" class="form-control" id="ipAddress" placeholder="192.168.1.1/24"></div>
                <div class="mb-3"><label class="form-label">Arayüz</label><input type="text" class="form-control" id="ipInterface"></div>
                <div class="mb-3"><label class="form-label">Network</label><input type="text" class="form-control" id="ipNetwork"></div>
                <div class="mb-3"><label class="form-label">Açıklama</label><input type="text" class="form-control" id="ipComment"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveIp()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let currentDeviceId = null;

function loadDeviceData() {
    currentDeviceId = document.getElementById('deviceSelect').value;
    if (!currentDeviceId) {
        document.getElementById('deviceInfo').style.display = 'none';
        document.getElementById('noDevice').style.display = 'block';
        return;
    }
    document.getElementById('deviceInfo').style.display = 'block';
    document.getElementById('noDevice').style.display = 'none';
    
    loadPpp();
    loadVlan();
    loadNtp();
    loadDns();
    loadDhcp();
    loadIp();
}

// PPP Secret
function loadPpp() {
    fetch(BASE_URL + '/api/mikrotik-ppp-secret.php?device_id=' + currentDeviceId)
        .then(r => r.json())
        .then(res => {
            const tbody = document.querySelector('#pppTable tbody');
            if (!res.success || !res.data) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Veri yüklenemedi</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(p => `
                <tr>
                    <td><strong>${p.name || '-'}</strong></td>
                    <td>${p.service || '-'}</td>
                    <td>${p.profile || '-'}</td>
                    <td>${p['remote-address'] || '-'}</td>
                    <td>${p.disabled === 'true' ? '<span class="badge bg-danger">Kapalı</span>' : '<span class="badge bg-success">Açık</span>'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1" onclick="editPpp('${p['.id']}', '${p.name}', '${p.profile}')"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deletePpp('${p['.id']}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        });
}

function showPppModal(id = '', name = '', profile = '') {
    document.getElementById('pppId').value = id;
    document.getElementById('pppName').value = name;
    document.getElementById('pppPassword').value = '';
    document.getElementById('pppProfile').value = profile || 'default';
    new bootstrap.Modal(document.getElementById('pppModal')).show();
}

function editPpp(id, name, profile) {
    showPppModal(id, name, profile);
}

function savePpp() {
    const id = document.getElementById('pppId').value;
    const data = {
        name: document.getElementById('pppName').value,
        password: document.getElementById('pppPassword').value,
        service: document.getElementById('pppService').value,
        profile: document.getElementById('pppProfile').value,
        remote_address: document.getElementById('pppRemote').value,
        local_address: document.getElementById('pppLocal').value,
        comment: document.getElementById('pppComment').value
    };
    
    const method = id ? 'PUT' : 'POST';
    if (id) data.id = id;
    
    fetch(BASE_URL + '/api/mikrotik-ppp-secret.php?device_id=' + currentDeviceId, {
        method: method,
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('pppModal')).hide();
            loadPpp();
        } else alert(res.error);
    });
}

function deletePpp(id) {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/api/mikrotik-ppp-secret.php?device_id=' + currentDeviceId, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(res => {
        if (res.success) loadPpp();
        else alert(res.error);
    });
}

// VLAN
function loadVlan() {
    fetch(BASE_URL + '/api/mikrotik-vlan.php?device_id=' + currentDeviceId)
        .then(r => r.json())
        .then(res => {
            const tbody = document.querySelector('#vlanTable tbody');
            if (!res.success || !res.data) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Veri yüklenemedi</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(v => `
                <tr>
                    <td><strong>${v.name || '-'}</strong></td>
                    <td>${v['vlan-id'] || '-'}</td>
                    <td>${v.interface || '-'}</td>
                    <td>${v.disabled === 'true' ? '<span class="badge bg-danger">Kapalı</span>' : '<span class="badge bg-success">Açık</span>'}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteVlan('${v['.id']}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        });
}

function showVlanModal() {
    document.getElementById('vlanId').value = '';
    document.getElementById('vlanName').value = '';
    document.getElementById('vlanIdNum').value = '';
    document.getElementById('vlanInterface').value = '';
    document.getElementById('vlanComment').value = '';
    new bootstrap.Modal(document.getElementById('vlanModal')).show();
}

function saveVlan() {
    const data = {
        name: document.getElementById('vlanName').value,
        vlan_id: document.getElementById('vlanIdNum').value,
        interface: document.getElementById('vlanInterface').value,
        comment: document.getElementById('vlanComment').value
    };
    
    fetch(BASE_URL + '/api/mikrotik-vlan.php?device_id=' + currentDeviceId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('vlanModal')).hide();
            loadVlan();
        } else alert(res.error);
    });
}

function deleteVlan(id) {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/api/mikrotik-vlan.php?device_id=' + currentDeviceId, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(res => {
        if (res.success) loadVlan();
        else alert(res.error);
    });
}

// NTP
function loadNtp() {
    fetch(BASE_URL + '/api/mikrotik-ntp.php?device_id=' + currentDeviceId)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                if (res.data.client) {
                    document.getElementById('ntpClientEnabled').checked = res.data.client.enabled === 'true';
                    document.getElementById('ntpPrimary').value = res.data.client['primary-ntp'] || '';
                    document.getElementById('ntpSecondary').value = res.data.client['secondary-ntp'] || '';
                }
                if (res.data.server) {
                    document.getElementById('ntpServerEnabled').checked = res.data.server.enabled === 'true';
                }
            }
        });
}

function saveNtpClient() {
    fetch(BASE_URL + '/api/mikrotik-ntp.php?device_id=' + currentDeviceId, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            type: 'client',
            enabled: document.getElementById('ntpClientEnabled').checked ? 'yes' : 'no',
            primary_ntp: document.getElementById('ntpPrimary').value,
            secondary_ntp: document.getElementById('ntpSecondary').value
        })
    }).then(r => r.json()).then(res => alert(res.success ? 'Kaydedildi' : res.error));
}

function saveNtpServer() {
    fetch(BASE_URL + '/api/mikrotik-ntp.php?device_id=' + currentDeviceId, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            type: 'server',
            enabled: document.getElementById('ntpServerEnabled').checked ? 'yes' : 'no'
        })
    }).then(r => r.json()).then(res => alert(res.success ? 'Kaydedildi' : res.error));
}

// DNS
function loadDns() {
    fetch(BASE_URL + '/api/mikrotik-dns.php?device_id=' + currentDeviceId)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                if (res.data.settings) {
                    document.getElementById('dnsServers').value = res.data.settings.servers || '';
                    document.getElementById('dnsRemoteRequests').checked = res.data.settings['allow-remote-requests'] === 'true';
                }
                if (res.data.static) {
                    document.querySelector('#dnsTable tbody').innerHTML = res.data.static.map(d => `
                        <tr>
                            <td>${d.name || '-'}</td>
                            <td>${d.address || '-'}</td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteDns('${d['.id']}')"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    `).join('');
                }
            }
        });
}

function showDnsModal() {
    document.getElementById('dnsId').value = '';
    document.getElementById('dnsName').value = '';
    document.getElementById('dnsAddress').value = '';
    document.getElementById('dnsComment').value = '';
    new bootstrap.Modal(document.getElementById('dnsModal')).show();
}

function saveDns() {
    fetch(BASE_URL + '/api/mikrotik-dns.php?device_id=' + currentDeviceId, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'settings',
            servers: document.getElementById('dnsServers').value,
            allow_remote_requests: document.getElementById('dnsRemoteRequests').checked ? 'yes' : 'no'
        })
    }).then(r => r.json()).then(res => alert(res.success ? 'Kaydedildi' : res.error));
}

function saveDnsStatic() {
    fetch(BASE_URL + '/api/mikrotik-dns.php?device_id=' + currentDeviceId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'static',
            name: document.getElementById('dnsName').value,
            address: document.getElementById('dnsAddress').value,
            comment: document.getElementById('dnsComment').value
        })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('dnsModal')).hide();
            loadDns();
        } else alert(res.error);
    });
}

function deleteDns(id) {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/api/mikrotik-dns.php?device_id=' + currentDeviceId, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'static', id: id})
    }).then(r => r.json()).then(res => {
        if (res.success) loadDns();
        else alert(res.error);
    });
}

// DHCP
function loadDhcp() {
    fetch(BASE_URL + '/api/mikrotik-dhcp.php?device_id=' + currentDeviceId)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                // Servers
                document.querySelector('#dhcpServerTable tbody').innerHTML = (res.data.servers || []).map(s => `
                    <tr>
                        <td>${s.name || '-'}</td>
                        <td>${s.interface || '-'}</td>
                        <td>${s.disabled === 'true' ? '<span class="badge bg-danger">Kapalı</span>' : '<span class="badge bg-success">Açık</span>'}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="deleteDhcpServer('${s['.id']}')"><i class="fas fa-trash"></i></button></td>
                    </tr>
                `).join('');
                
                // Networks
                document.querySelector('#dhcpNetworkTable tbody').innerHTML = (res.data.networks || []).map(n => `
                    <tr>
                        <td>${n.address || '-'}</td>
                        <td>${n.gateway || '-'}</td>
                        <td>${n['dns-server'] || '-'}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="deleteDhcpNetwork('${n['.id']}')"><i class="fas fa-trash"></i></button></td>
                    </tr>
                `).join('');
                
                // Leases
                document.querySelector('#dhcpLeaseTable tbody').innerHTML = (res.data.leases || []).map(l => `
                    <tr>
                        <td>${l.address || '-'}</td>
                        <td><code>${l['mac-address'] || '-'}</code></td>
                        <td>${l.hostname || '-'}</td>
                        <td>${l['last-seen'] || '-'}</td>
                    </tr>
                `).join('');
            }
        });
}

function showDhcpServerModal() {
    document.getElementById('dhcpName').value = '';
    document.getElementById('dhcpInterface').value = '';
    new bootstrap.Modal(document.getElementById('dhcpServerModal')).show();
}

function showDhcpNetworkModal() {
    document.getElementById('dhcpNetAddress').value = '';
    document.getElementById('dhcpNetGateway').value = '';
    document.getElementById('dhcpNetDns').value = '';
    document.getElementById('dhcpNetDomain').value = '';
    new bootstrap.Modal(document.getElementById('dhcpNetworkModal')).show();
}

function saveDhcpServer() {
    fetch(BASE_URL + '/api/mikrotik-dhcp.php?device_id=' + currentDeviceId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'server',
            name: document.getElementById('dhcpName').value,
            interface: document.getElementById('dhcpInterface').value,
            address_pool: document.getElementById('dhcpPool').value,
            lease_time: document.getElementById('dhcpLease').value
        })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('dhcpServerModal')).hide();
            loadDhcp();
        } else alert(res.error);
    });
}

function deleteDhcpServer(id) {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/api/mikrotik-dhcp.php?device_id=' + currentDeviceId, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'server', id: id})
    }).then(r => r.json()).then(res => {
        if (res.success) loadDhcp();
        else alert(res.error);
    });
}

function saveDhcpNetwork() {
    fetch(BASE_URL + '/api/mikrotik-dhcp.php?device_id=' + currentDeviceId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'network',
            address: document.getElementById('dhcpNetAddress').value,
            gateway: document.getElementById('dhcpNetGateway').value,
            dns_servers: document.getElementById('dhcpNetDns').value,
            domain: document.getElementById('dhcpNetDomain').value
        })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('dhcpNetworkModal')).hide();
            loadDhcp();
        } else alert(res.error);
    });
}

function deleteDhcpNetwork(id) {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/api/mikrotik-dhcp.php?device_id=' + currentDeviceId, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'network', id: id})
    }).then(r => r.json()).then(res => {
        if (res.success) loadDhcp();
        else alert(res.error);
    });
}

// IP Address
function loadIp() {
    fetch(BASE_URL + '/api/mikrotik-ip.php?device_id=' + currentDeviceId)
        .then(r => r.json())
        .then(res => {
            const tbody = document.querySelector('#ipTable tbody');
            if (!res.success || !res.data) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Veri yüklenemedi</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(ip => `
                <tr>
                    <td><code>${ip.address || '-'}</code></td>
                    <td>${ip.network || '-'}</td>
                    <td>${ip.interface || '-'}</td>
                    <td>${ip.disabled === 'true' ? '<span class="badge bg-danger">Kapalı</span>' : '<span class="badge bg-success">Açık</span>'}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteIp('${ip['.id']}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        });
}

function showIpModal() {
    document.getElementById('ipId').value = '';
    document.getElementById('ipAddress').value = '';
    document.getElementById('ipInterface').value = '';
    document.getElementById('ipNetwork').value = '';
    document.getElementById('ipComment').value = '';
    new bootstrap.Modal(document.getElementById('ipModal')).show();
}

function saveIp() {
    const data = {
        address: document.getElementById('ipAddress').value,
        interface: document.getElementById('ipInterface').value,
        network: document.getElementById('ipNetwork').value,
        comment: document.getElementById('ipComment').value
    };
    
    fetch(BASE_URL + '/api/mikrotik-ip.php?device_id=' + currentDeviceId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('ipModal')).hide();
            loadIp();
        } else alert(res.error);
    });
}

function deleteIp(id) {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/api/mikrotik-ip.php?device_id=' + currentDeviceId, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(res => {
        if (res.success) loadIp();
        else alert(res.error);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
