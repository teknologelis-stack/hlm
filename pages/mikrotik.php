<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Mikrotik';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.modal-header { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; }
.modal-header .btn-close { filter: invert(1); }
.device-card { cursor: pointer; transition: all 0.3s; }
.device-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.device-card.selected { border: 2px solid #3b82f6; }
.status-online { color: #10b981; }
.status-offline { color: #ef4444; }
.status-unknown { color: #f59e0b; }
.detail-stat { padding: 15px; background: #f8fafc; border-radius: 10px; }
.detail-stat i { margin-bottom: 10px; }
.progress-ring { position: relative; width: 80px; height: 80px; }
.progress-ring svg { transform: rotate(-90deg); }
.progress-ring circle { fill: transparent; stroke-width: 8; }
.progress-ring .bg { stroke: #e2e8f0; }
.progress-ring .progress { stroke-linecap: round; transition: stroke-dashoffset 0.5s; }
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-server me-2"></i>Mikrotik</h1>
            <p class="text-muted">Mikrotik cihazlarınızı yönetin ve takip edin</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i>Cihaz Ekle
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-primary"><i class="fas fa-server"></i></div>
            <div class="stats-info">
                <h3 id="totalDevices">0</h3>
                <p>Toplam Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-success"><i class="fas fa-wifi"></i></div>
            <div class="stats-info">
                <h3 id="onlineDevices">0</h3>
                <p>Online</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-danger"><i class="fas fa-power-off"></i></div>
            <div class="stats-info">
                <h3 id="offlineDevices">0</h3>
                <p>Offline</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="stats-icon bg-warning"><i class="fas fa-users"></i></div>
            <div class="stats-info">
                <h3 id="totalPppoe">0</h3>
                <p>PPPoE Oturum</p>
            </div>
        </div>
    </div>
</div>

<!-- Device Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2"></i>Kayıtlı Cihazlar</h5>
        <div>
            <span class="badge bg-secondary me-2" id="socketStatus">
                <i class="fas fa-circle text-warning me-1"></i>Bağlanıyor...
            </span>
            <button class="btn btn-sm btn-outline-primary" onclick="loadDevices()">
                <i class="fas fa-sync-alt me-1"></i>Yenile
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="deviceTable">
                <thead>
                    <tr>
                        <th>Cihaz Adı</th>
                        <th>IP Adresi</th>
                        <th>Port</th>
                        <th>Durum</th>
                        <th>CPU</th>
                        <th>RAM</th>
                        <th>ROS</th>
                        <th>PPPoE</th>
                        <th>Son Bağlantı</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody id="deviceList">
                    <tr><td colspan="10" class="text-center text-muted">Yükleniyor...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="deviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Cihaz Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="deviceForm">
                    <input type="hidden" id="deviceId">
                    <div class="mb-3">
                        <label class="form-label">Cihaz Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceName" required placeholder="Ana Router">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IP Adresi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceIp" placeholder="192.168.1.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceUsername" required placeholder="admin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="devicePassword" placeholder="••••••••">
                        <small class="text-muted" id="passwordHint">Yeni cihaz eklemek için şifre gereklidir</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Port</label>
                        <input type="number" class="form-control" id="devicePort" value="8728">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" id="deviceDesc" rows="2" placeholder="opsiyonel"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;" onclick="deleteDevice()">
                    <i class="fas fa-trash me-1"></i>Sil
                </button>
                <div class="ms-auto">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="saveDevice()">
                        <i class="fas fa-save me-1"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Device Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailTitle">
                    <i class="fas fa-server me-2"></i>Cihaz Detayları
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center mb-4">
                    <div class="col-3">
                        <div class="detail-stat">
                            <i class="fas fa-microchip fa-2x text-primary"></i>
                            <h4 class="mt-2 mb-0" id="detailCpu">0%</h4>
                            <small class="text-muted">CPU</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="detail-stat">
                            <i class="fas fa-memory fa-2x text-success"></i>
                            <h4 class="mt-2 mb-0" id="detailRam">0%</h4>
                            <small class="text-muted">RAM</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="detail-stat">
                            <i class="fas fa-code-branch fa-2x text-info"></i>
                            <h4 class="mt-2 mb-0" id="detailRos">-</h4>
                            <small class="text-muted">ROS Versiyon</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="detail-stat">
                            <i class="fas fa-network-wired fa-2x text-warning"></i>
                            <h4 class="mt-2 mb-0" id="detailPppoe">0</h4>
                            <small class="text-muted">PPPoE</small>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Cihaz Adı:</strong></td>
                                <td id="detailName">-</td>
                            </tr>
                            <tr>
                                <td><strong>IP Adresi:</strong></td>
                                <td id="detailIp">-</td>
                            </tr>
                            <tr>
                                <td><strong>Port:</strong></td>
                                <td id="detailPort">-</td>
                            </tr>
                            <tr>
                                <td><strong>Kullanıcı:</strong></td>
                                <td id="detailUser">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Uptime:</strong></td>
                                <td id="detailUptime">-</td>
                            </tr>
                            <tr>
                                <td><strong>Interface:</strong></td>
                                <td id="detailInterface">-</td>
                            </tr>
                            <tr>
                                <td><strong>Durum:</strong></td>
                                <td id="detailStatus">-</td>
                            </tr>
                            <tr>
                                <td><strong>Son Bağlantı:</strong></td>
                                <td id="detailLastConnect">-</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="refreshDeviceDetail()">
                    <i class="fas fa-sync-alt me-1"></i>Yenile
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let devices = [];
let selectedDeviceId = null;
let refreshInterval = null;
let isDetailOpen = false;

// Verileri PHP API'den çek (her 1 saniye)
function startAutoRefresh() {
    if (refreshInterval) clearInterval(refreshInterval);
    
    refreshInterval = setInterval(() => {
        if (!isDetailOpen && document.readyState === 'complete') {
            fetchMikrotikData();
        }
    }, 5000); // 5 saniye
}

// Sayfa tamamen yüklendikten sonra verileri çek
function initPage() {
    if (document.readyState === 'complete') {
        startAutoRefresh();
    } else {
        window.addEventListener('load', startAutoRefresh);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadDevices();
    initPage();
});

// MikroTik verilerini çek
function fetchMikrotikData() {
    fetch(BASE_URL + '/api/mikrotik-fetch.php', { method: 'GET' })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const dataArray = Array.isArray(res.data) ? res.data : [res.data];
                dataArray.forEach(d => {
                    updateDeviceInList(d);
                });
                updateStats();
                document.getElementById('socketStatus').innerHTML = '<i class="fas fa-circle text-success me-1"></i>Aktif';
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            document.getElementById('socketStatus').innerHTML = '<i class="fas fa-circle text-danger me-1"></i>Hata';
        });
}

// Cihazı listede güncelle
function updateDeviceInList(data) {
    const idx = devices.findIndex(d => d.id === data.id);
    if (idx >= 0) {
        devices[idx] = { ...devices[idx], ...data };
    } else {
        devices.push(data);
    }
    renderDeviceTable();
}

// API'den cihazları getir (ilk yükleme)
function loadDevices() {
    fetch(BASE_URL + '/api/mikrotik-devices.php', { method: 'GET' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                devices = data.data;
                renderDeviceTable();
                updateStats();
            }
        })
        .catch(err => console.error('Load devices error:', err));
}

// Tabloyu render et
function renderDeviceTable() {
    const tbody = document.getElementById('deviceList');
    if (devices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Henüz cihaz eklenmedi</td></tr>';
        return;
    }
    
    let html = '';
    devices.forEach(d => {
        const statusClass = d.status === 'online' ? 'status-online' : (d.status === 'offline' ? 'status-offline' : 'status-unknown');
        const statusIcon = d.status === 'online' ? 'fa-check-circle' : (d.status === 'offline' ? 'fa-times-circle' : 'fa-question-circle');
        
        // CPU ve RAM renklendirme
        const cpuColor = d.cpu_usage > 80 ? 'text-danger' : (d.cpu_usage > 50 ? 'text-warning' : 'text-success');
        const ramColor = d.ram_usage > 80 ? 'text-danger' : (d.ram_usage > 50 ? 'text-warning' : 'text-success');
        
        html += `<tr class="device-card" onclick="showDeviceDetail(${d.id})">
            <td><strong>${d.name}</strong></td>
            <td><code>${d.ip_address}</code></td>
            <td>${d.port}</td>
            <td><i class="fas ${statusIcon} ${statusClass}"></i> ${d.status || 'unknown'}</td>
            <td class="${cpuColor}"><strong>${d.cpu_usage || 0}%</strong></td>
            <td class="${ramColor}"><strong>${d.ram_usage || 0}%</strong></td>
            <td><span class="badge bg-secondary">${d.ros_version || '-'}</span></td>
            <td><span class="badge bg-info">${d.pppoe_count || 0}</span></td>
            <td class="text-muted small">${d.last_connect ? formatDate(d.last_connect) : '-'}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="event.stopPropagation(); editDevice(${d.id})" title="Düzenle">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); confirmDelete(${d.id})" title="Sil">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// Stats güncelle
function updateStats() {
    document.getElementById('totalDevices').textContent = devices.length;
    document.getElementById('onlineDevices').textContent = devices.filter(d => d.status === 'online').length;
    document.getElementById('offlineDevices').textContent = devices.filter(d => d.status === 'offline').length;
    document.getElementById('totalPppoe').textContent = devices.reduce((sum, d) => sum + (parseInt(d.pppoe_count) || 0), 0);
}

// Listeyi güncelle (socket'ten gelen)
function updateDeviceInList(data) {
    const idx = devices.findIndex(d => d.id === data.id);
    if (idx >= 0) {
        devices[idx] = { ...devices[idx], ...data };
        renderDeviceTable();
        updateStats();
    }
}

// Tarih formatla
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('tr-TR');
}

// Modal fonksiyonları
function openAddModal() {
    document.getElementById('deviceForm').reset();
    document.getElementById('deviceId').value = '';
    document.getElementById('modalTitle').textContent = 'Cihaz Ekle';
    document.getElementById('deleteBtn').style.display = 'none';
    document.getElementById('passwordHint').textContent = 'Yeni cihaz eklemek için şifre gereklidir';
    document.getElementById('devicePassword').required = true;
    new bootstrap.Modal(document.getElementById('deviceModal')).show();
}

function editDevice(id) {
    const device = devices.find(d => d.id === id);
    if (!device) return;
    
    document.getElementById('deviceId').value = device.id;
    document.getElementById('deviceName').value = device.name;
    document.getElementById('deviceIp').value = device.ip_address;
    document.getElementById('deviceUsername').value = device.username;
    document.getElementById('devicePassword').value = '';
    document.getElementById('devicePort').value = device.port;
    document.getElementById('deviceDesc').value = device.description || '';
    document.getElementById('modalTitle').textContent = 'Cihaz Düzenle';
    document.getElementById('deleteBtn').style.display = 'block';
    document.getElementById('passwordHint').textContent = 'Değiştirmek istemiyorsanız boş bırakın';
    document.getElementById('devicePassword').required = false;
    
    new bootstrap.Modal(document.getElementById('deviceModal')).show();
}

function saveDevice() {
    const id = document.getElementById('deviceId').value;
    const password = document.getElementById('devicePassword').value;
    const name = document.getElementById('deviceName').value;
    const ip_address = document.getElementById('deviceIp').value;
    const username = document.getElementById('deviceUsername').value;
    
    // Validation
    if (!name || !ip_address || !username) {
        alert('Lütfen gerekli alanları doldurun (Cihaz Adı, IP, Kullanıcı)');
        return;
    }
    
    if (!id && !password) {
        alert('Yeni cihaz için şifre gereklidir');
        return;
    }
    
    const data = {
        name: name,
        ip_address: ip_address,
        username: username,
        port: document.getElementById('devicePort').value || 8728,
        description: document.getElementById('deviceDesc').value || ''
    };
    
    if (password) {
        data.password = password;
    }
    
    const method = id ? 'PUT' : 'POST';
    if (id) data.id = id;
    
    const apiUrl = BASE_URL + '/api/mikrotik-devices.php';
    
    fetch(apiUrl, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => {
        console.log('Response status:', r.status);
        return r.json();
    })
    .then(res => {
        console.log('API Response:', JSON.stringify(res));
        if (res.success) {
            // Modalı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('deviceModal'));
            if (modal) modal.hide();
            
            // Cihazları yenile
            loadDevices();
            
            // Başarı mesajı
            alert(id ? 'Cihaz başarıyla güncellendi!' : 'Cihaz başarıyla eklendi!');
        } else {
            alert('Hata: ' + (res.error || 'Bilinmeyen hata'));
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Sunucu hatası: ' + err.message);
    });
}

function deleteDevice() {
    const id = document.getElementById('deviceId').value;
    if (!id) return;
    
    if (confirm('Cihazı silmek istediğinize emin misiniz?')) {
        fetch(BASE_URL + '/api/mikrotik-devices.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('deviceModal')).hide();
                loadDevices();
            } else {
                alert(res.error);
            }
        });
    }
}

function confirmDelete(id) {
    if (confirm('Cihazı silmek istediğinize emin misiniz?')) {
        fetch(BASE_URL + '/api/mikrotik-devices.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) loadDevices();
            else alert(res.error);
        });
    }
}

// Cihaz detay modalı
function showDeviceDetail(id) {
    selectedDeviceId = id;
    isDetailOpen = true; // Modal açıkken otomatik yenilemeyi durdur
    
    const device = devices.find(d => d.id === id);
    if (!device) return;
    
    document.getElementById('detailTitle').innerHTML = `<i class="fas fa-server me-2"></i>${device.name}`;
    document.getElementById('detailName').textContent = device.name;
    document.getElementById('detailIp').textContent = device.ip_address;
    document.getElementById('detailPort').textContent = device.port;
    document.getElementById('detailUser').textContent = device.username;
    document.getElementById('detailCpu').textContent = (device.cpu_usage || 0) + '%';
    document.getElementById('detailRam').textContent = (device.ram_usage || 0) + '%';
    document.getElementById('detailRos').textContent = device.ros_version || '-';
    document.getElementById('detailPppoe').textContent = device.pppoe_count || 0;
    document.getElementById('detailUptime').textContent = device.uptime || '-';
    document.getElementById('detailInterface').textContent = device.interface_count || 0;
    document.getElementById('detailStatus').innerHTML = device.status === 'online' 
        ? '<span class="badge bg-success">Online</span>' 
        : (device.status === 'offline' ? '<span class="badge bg-danger">Offline</span>' : '<span class="badge bg-warning">Bilinmiyor</span>');
    document.getElementById('detailLastConnect').textContent = device.last_connect ? formatDate(device.last_connect) : '-';
    
    new bootstrap.Modal(document.getElementById('detailModal')).show();
    
    // Modal kapandığında otomatik yenilemeyi tekrar başlat
    document.getElementById('detailModal').addEventListener('hidden.bs.modal', function() {
        isDetailOpen = false;
    }, { once: true });
}

// Modal verilerini güncelle
function updateDetailModal(data) {
    console.log('Device data updated:', data);
    const device = devices.find(d => d.id === data.id);
    if (device) {
        Object.assign(device, data);
        if (selectedDeviceId === data.id) {
            document.getElementById('detailCpu').textContent = (data.cpu || 0) + '%';
            document.getElementById('detailRam').textContent = (data.ram || 0) + '%';
            document.getElementById('detailRos').textContent = data.ros_version || '-';
            document.getElementById('detailPppoe').textContent = data.pppoe_count || 0;
            document.getElementById('detailUptime').textContent = data.uptime || '-';
            document.getElementById('detailInterface').textContent = data.interface_count || 0;
            document.getElementById('detailStatus').innerHTML = data.status === 'online' 
                ? '<span class="badge bg-success">Online</span>' 
                : '<span class="badge bg-danger">Offline</span>';
        }
    }
}

function refreshDeviceDetail() {
    if (selectedDeviceId) {
        fetch(BASE_URL + '/api/mikrotik-fetch.php?id=' + selectedDeviceId)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    updateDetailModal(res.data);
                    updateDeviceInList(res.data);
                }
            });
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadDevices();
    startAutoRefresh(); // Her 10 saniyede MikroTik verilerini çek
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
