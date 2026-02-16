<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('logs_view');

$pageTitle = 'Sistem Logları';
$db = Database::getInstance();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$totalLogs = $db->fetchOne("SELECT COUNT(*) as count FROM logs")['count'];
$totalPages = ceil($totalLogs / $perPage);

$logs = $db->fetchAll(
    "SELECT l.*, u.username FROM logs l 
     LEFT JOIN users u ON l.user_id = u.id 
     ORDER BY l.created_at DESC 
     LIMIT ? OFFSET ?",
    [$perPage, $offset]
);

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Aktivite Logları</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-warning btn-sm" onclick="exportLogs()">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                    <?php if ($auth->hasPermission('settings_manage')): ?>
                    <button class="btn btn-danger btn-sm" onclick="clearAllLogs()">
                        <i class="bi bi-trash me-2"></i>Tümünü Temizle
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchLog" placeholder="Log ara...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterAction">
                            <option value="">Tüm İşlemler</option>
                            <option value="login">Giriş</option>
                            <option value="logout">Çıkış</option>
                            <option value="user_added">Kullanıcı Eklendi</option>
                            <option value="user_updated">Kullanıcı Güncellendi</option>
                            <option value="user_deleted">Kullanıcı Silindi</option>
                            <option value="device_added">Cihaz Eklendi</option>
                            <option value="device_deleted">Cihaz Silindi</option>
                            <option value="settings_updated">Ayarlar Güncellendi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" id="filterDate">
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Filtreleri Temizle
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="logsTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Kullanıcı</th>
                                <th width="15%">İşlem</th>
                                <th width="30%">Detay</th>
                                <th width="12%">IP Adresi</th>
                                <th width="15%">Tarih/Saat</th>
                                <th width="8%" class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                    <p class="mt-2">Henüz log kaydı bulunmuyor</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $index => $log): ?>
                                <tr data-action="<?php echo clean($log['action']); ?>" data-date="<?php echo date('Y-m-d', strtotime($log['created_at'])); ?>">
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar-xs bg-primary me-2">
                                                <?php echo strtoupper(substr($log['username'] ?? 'S', 0, 1)); ?>
                                            </div>
                                            <strong><?php echo clean($log['username'] ?? 'Sistem'); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'secondary';
                                        $icon = 'info-circle';
                                        
                                        if (strpos($log['action'], 'login') !== false) {
                                            $badgeClass = 'success';
                                            $icon = 'box-arrow-in-right';
                                        } elseif (strpos($log['action'], 'logout') !== false) {
                                            $badgeClass = 'warning';
                                            $icon = 'box-arrow-right';
                                        } elseif (strpos($log['action'], 'failed') !== false) {
                                            $badgeClass = 'danger';
                                            $icon = 'exclamation-triangle';
                                        } elseif (strpos($log['action'], 'added') !== false) {
                                            $badgeClass = 'success';
                                            $icon = 'plus-circle';
                                        } elseif (strpos($log['action'], 'deleted') !== false) {
                                            $badgeClass = 'danger';
                                            $icon = 'trash';
                                        } elseif (strpos($log['action'], 'updated') !== false) {
                                            $badgeClass = 'info';
                                            $icon = 'pencil';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <i class="bi bi-<?php echo $icon; ?> me-1"></i>
                                            <?php echo clean($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo clean($log['details']); ?></td>
                                    <td><code class="small"><?php echo clean($log['ip_address']); ?></code></td>
                                    <td class="small">
                                        <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info" onclick="viewLogDetail(<?php echo htmlspecialchars(json_encode($log), ENT_QUOTES); ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Önceki</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Sonraki</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <div class="text-center text-muted small mt-3">
                    Toplam <strong><?php echo $totalLogs; ?></strong> log kaydı | 
                    Sayfa <strong><?php echo $page; ?></strong> / <strong><?php echo $totalPages; ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="log-stat-card">
            <i class="bi bi-journal-text text-primary"></i>
            <div>
                <h3><?php echo $totalLogs; ?></h3>
                <p>Toplam Log</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="log-stat-card">
            <i class="bi bi-calendar-day text-success"></i>
            <div>
                <h3><?php 
                    $today = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE DATE(created_at) = CURDATE()")['count'];
                    echo $today;
                ?></h3>
                <p>Bugünkü Log</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="log-stat-card">
            <i class="bi bi-box-arrow-in-right text-info"></i>
            <div>
                <h3><?php 
                    $logins = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE action LIKE '%login%'")['count'];
                    echo $logins;
                ?></h3>
                <p>Giriş İşlemi</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="log-stat-card">
            <i class="bi bi-exclamation-triangle text-danger"></i>
            <div>
                <h3><?php 
                    $failed = $db->fetchOne("SELECT COUNT(*) as count FROM logs WHERE action LIKE '%failed%'")['count'];
                    echo $failed;
                ?></h3>
                <p>Başarısız İşlem</p>
            </div>
        </div>
    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Log Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="log-detail-grid">
                    <div class="detail-item">
                        <label><i class="bi bi-person text-primary"></i> Kullanıcı</label>
                        <p id="detailUser">--</p>
                    </div>
                    <div class="detail-item">
                        <label><i class="bi bi-tag text-primary"></i> İşlem</label>
                        <p id="detailAction">--</p>
                    </div>
                    <div class="detail-item">
                        <label><i class="bi bi-info-circle text-primary"></i> Detay</label>
                        <p id="detailDetails">--</p>
                    </div>
                    <div class="detail-item">
                        <label><i class="bi bi-geo-alt text-primary"></i> IP Adresi</label>
                        <p id="detailIp">--</p>
                    </div>
                    <div class="detail-item">
                        <label><i class="bi bi-browser-chrome text-primary"></i> User Agent</label>
                        <p id="detailUserAgent" class="small">--</p>
                    </div>
                    <div class="detail-item">
                        <label><i class="bi bi-clock text-primary"></i> Tarih/Saat</label>
                        <p id="detailDate">--</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
.user-avatar-xs {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 600;
    font-size: 12px;
}

.log-stat-card {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.log-stat-card i {
    font-size: 40px;
}

.log-stat-card h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
}

.log-stat-card p {
    margin: 0;
    font-size: 13px;
    color: var(--text-light);
}

.log-detail-grid {
    display: grid;
    gap: 15px;
}

.detail-item {
    background: var(--bg-light);
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid var(--primary-color);
}

.detail-item label {
    display: block;
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 8px;
    font-weight: 500;
}

.detail-item p {
    margin: 0;
    font-size: 15px;
    color: var(--text-dark);
    font-weight: 600;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchLog').addEventListener('keyup', filterLogs);
    document.getElementById('filterAction').addEventListener('change', filterLogs);
    document.getElementById('filterDate').addEventListener('change', filterLogs);
});

function filterLogs() {
    const search = document.getElementById('searchLog').value.toLowerCase();
    const actionFilter = document.getElementById('filterAction').value;
    const dateFilter = document.getElementById('filterDate').value;
    
    const rows = document.querySelectorAll('#logsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const action = row.dataset.action;
        const date = row.dataset.date;
        
        let show = true;
        
        if (search && !text.includes(search)) show = false;
        if (actionFilter && !action.includes(actionFilter)) show = false;
        if (dateFilter && date !== dateFilter) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('searchLog').value = '';
    document.getElementById('filterAction').value = '';
    document.getElementById('filterDate').value = '';
    filterLogs();
}

function viewLogDetail(log) {
    document.getElementById('detailUser').textContent = log.username || 'Sistem';
    document.getElementById('detailAction').textContent = log.action;
    document.getElementById('detailDetails').textContent = log.details || 'Detay yok';
    document.getElementById('detailIp').textContent = log.ip_address;
    document.getElementById('detailUserAgent').textContent = log.user_agent || 'Bilinmiyor';
    document.getElementById('detailDate').textContent = new Date(log.created_at).toLocaleString('tr-TR');
    
    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}

function exportLogs() {
    const table = document.getElementById('logsTable');
    let csv = [];
    
    const headers = ['#', 'Kullanıcı', 'İşlem', 'Detay', 'IP Adresi', 'Tarih'];
    csv.push(headers.join(','));
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = Array.from(row.querySelectorAll('td')).slice(0, 6).map(td => {
                return '"' + td.textContent.trim().replace(/"/g, '""') + '"';
            });
            csv.push(cols.join(','));
        }
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `logs_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Loglar export edildi', 'success');
}

function clearAllLogs() {
    const confirmation = prompt('Tüm logları silmek için "TEMIZLE" yazın:');
    if (confirmation !== 'TEMIZLE') {
        showToast('İşlem iptal edildi', 'info');
        return;
    }
    
    if (!confirm('SON UYARI! Tüm log kayıtları silinecek. Devam etmek istediğinize emin misiniz?')) return;
    
    fetch('<?php echo BASE_URL; ?>/api/clear-logs.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Loglar temizlendi, yönlendiriliyorsunuz...', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>