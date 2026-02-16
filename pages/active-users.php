<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Aktif Kullanıcılar';
$db = Database::getInstance();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Aktif PPP Bağlantıları</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" id="refreshBtn">
                        <i class="bi bi-arrow-clockwise me-2"></i>Yenile
                    </button>
                    <button class="btn btn-success btn-sm" id="exportBtn">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Kullanıcı adı, IP veya MAC ara...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" id="autoRefresh">
                            <label class="form-check-label" for="autoRefresh">Otomatik Yenile (30s)</label>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="activeUsersTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Kullanıcı Adı</th>
                                <th width="15%">IP Adresi</th>
                                <th width="15%">MAC Adresi</th>
                                <th width="12%">Uptime</th>
                                <th width="12%">RX / TX</th>
                                <th width="10%">Servis</th>
                                <th width="11%" class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Aktif kullanıcılar yükleniyor...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Toplam <strong id="totalUsers">0</strong> aktif kullanıcı
                    </div>
                    <div class="text-muted small">
                        Son güncelleme: <strong id="lastUpdate">--</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="stat-box bg-primary">
            <i class="bi bi-people-fill"></i>
            <div>
                <h3 id="statTotal">0</h3>
                <p>Toplam Bağlantı</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-box bg-success">
            <i class="bi bi-arrow-down-circle"></i>
            <div>
                <h3 id="statRx">0 MB</h3>
                <p>Toplam İndirme</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-box bg-warning">
            <i class="bi bi-arrow-up-circle"></i>
            <div>
                <h3 id="statTx">0 MB</h3>
                <p>Toplam Yükleme</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-box bg-info">
            <i class="bi bi-clock-history"></i>
            <div>
                <h3 id="statAvgUptime">0d</h3>
                <p>Ort. Uptime</p>
            </div>
        </div>
    </div>
</div>

<style>
.stat-box {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: #ffffff;
    padding: 25px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-box.bg-success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-box.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-box.bg-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.stat-box i {
    font-size: 48px;
    opacity: 0.8;
}

.stat-box h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.stat-box p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.badge-online {
    background: #10b981;
    color: #ffffff;
}

.btn-disconnect {
    background: #ef4444;
    color: #ffffff;
    border: none;
    padding: 5px 15px;
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.3s ease;
}

.btn-disconnect:hover {
    background: #dc2626;
    transform: translateY(-2px);
}
</style>

<script>
let autoRefreshInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    loadActiveUsers();
    
    document.getElementById('refreshBtn').addEventListener('click', loadActiveUsers);
    
    document.getElementById('autoRefresh').addEventListener('change', function() {
        if (this.checked) {
            autoRefreshInterval = setInterval(loadActiveUsers, 30000);
            showToast('Otomatik yenileme aktif edildi', 'info');
        } else {
            clearInterval(autoRefreshInterval);
            showToast('Otomatik yenileme durduruldu', 'info');
        }
    });
    
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    document.getElementById('exportBtn').addEventListener('click', exportToCSV);
});

function loadActiveUsers() {
    const tbody = document.getElementById('usersTableBody');
    
    fetch('<?php echo BASE_URL; ?>/api/active-users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUsers(data.users);
                updateStats(data.stats);
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('tr-TR');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>${data.message}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="bi bi-x-circle me-2"></i>Veri yüklenirken hata oluştu
                    </td>
                </tr>
            `;
        });
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle me-2"></i>Aktif kullanıcı bulunmuyor
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = users.map((user, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>
                <strong>${user.name}</strong>
                <br><small class="text-muted">${user.caller_id || 'N/A'}</small>
            </td>
            <td><code>${user.address}</code></td>
            <td><small class="text-muted">${user.caller_id || 'N/A'}</small></td>
            <td><span class="badge badge-online">${user.uptime}</span></td>
            <td>
                <small class="text-success"><i class="bi bi-arrow-down"></i> ${formatBytes(user.bytes_in || 0)}</small><br>
                <small class="text-warning"><i class="bi bi-arrow-up"></i> ${formatBytes(user.bytes_out || 0)}</small>
            </td>
            <td><span class="badge bg-info">${user.service}</span></td>
            <td class="text-center">
                <button class="btn-disconnect" onclick="disconnectUser('${user.id}', '${user.name}')">
                    <i class="bi bi-x-circle me-1"></i>Kes
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('totalUsers').textContent = users.length;
}

function updateStats(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statRx').textContent = formatBytes(stats.total_rx || 0);
    document.getElementById('statTx').textContent = formatBytes(stats.total_tx || 0);
    document.getElementById('statAvgUptime').textContent = stats.avg_uptime || '0d';
}

function disconnectUser(userId, username) {
    if (!confirm(`${username} kullanıcısının bağlantısını kesmek istediğinize emin misiniz?`)) {
        return;
    }
    
    fetch('<?php echo BASE_URL; ?>/api/disconnect-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`${username} bağlantısı kesildi`, 'success');
            loadActiveUsers();
        } else {
            showToast(data.message || 'Bağlantı kesilemedi', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('İşlem sırasında hata oluştu', 'error');
    });
}

function exportToCSV() {
    const table = document.getElementById('activeUsersTable');
    let csv = [];
    
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
    csv.push(headers.join(','));
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cols = Array.from(row.querySelectorAll('td')).map(td => td.textContent.trim());
        csv.push(cols.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `aktif_kullanicilar_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Export başarılı', 'success');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
