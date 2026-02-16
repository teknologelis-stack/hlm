<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('devices_manage');

$pageTitle = 'Cihaz Yönetimi';
$db = Database::getInstance();

$devices = $db->fetchAll("SELECT * FROM devices ORDER BY is_main DESC, created_at DESC");

// Dashboard istatistikleri
$totalDevices = $db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE is_active = 1")['count'] ?? 0;
$routerCount = $db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE is_active = 1 AND device_type = 'router'")['count'] ?? 0;
$switchCount = $db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE is_active = 1 AND device_type = 'switch'")['count'] ?? 0;
$apCount = $db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE is_active = 1 AND device_type = 'ap'")['count'] ?? 0;
$onlineDevices = $db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE is_active = 1 AND last_seen > DATE_SUB(NOW(), INTERVAL " . DEVICE_ONLINE_THRESHOLD_MINUTES . " MINUTE)")['count'] ?? 0;

include __DIR__ . '/../../includes/header.php';
?>

<!-- Device Management CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/device-management.css">

<!-- Dashboard Kartları -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-hdd-network" style="font-size: 2rem; color: #0d6efd;"></i>
                <h3 class="mt-2"><?php echo $totalDevices; ?></h3>
                <p class="mb-0 text-muted">Toplam Cihaz</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-router" style="font-size: 2rem; color: #198754;"></i>
                <h3 class="mt-2"><?php echo $routerCount; ?></h3>
                <p class="mb-0 text-muted">Router</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-ethernet" style="font-size: 2rem; color: #0dcaf0;"></i>
                <h3 class="mt-2"><?php echo $switchCount; ?></h3>
                <p class="mb-0 text-muted">Switch</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-wifi" style="font-size: 2rem; color: #ffc107;"></i>
                <h3 class="mt-2"><?php echo $apCount; ?></h3>
                <p class="mb-0 text-muted">Access Point</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-check-circle" style="font-size: 2rem; color: #198754;"></i>
                <h3 class="mt-2"><?php echo $onlineDevices; ?></h3>
                <p class="mb-0 text-muted">Çevrimiçi</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-x-circle" style="font-size: 2rem; color: #dc3545;"></i>
                <h3 class="mt-2"><?php echo $totalDevices - $onlineDevices; ?></h3>
                <p class="mb-0 text-muted">Çevrimdışı</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Cihaz Yönetimi</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload me-2"></i>WinBox Import
                    </button>
                    <button class="btn btn-info" onclick="fetchPPPoEDevices()">
                        <i class="bi bi-download me-2"></i>Ana Cihazdan Çek
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deviceModal" onclick="openDeviceModal()">
                        <i class="bi bi-plus-circle me-2"></i>Yeni Cihaz Ekle
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 64px; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">Henüz cihaz eklenmemiş</h5>
                        <p class="text-muted">Yeni cihaz eklemek için yukarıdaki butona tıklayın</p>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#deviceModal" onclick="openDeviceModal()">
                            <i class="bi bi-plus-circle me-2"></i>İlk Cihazı Ekle
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Arama ve Filtreleme -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" id="searchDevice" class="form-control" placeholder="🔍 Cihaz adı veya IP ara...">
                        </div>
                        
                        <div class="col-md-2">
                            <select id="filterDeviceType" class="form-control">
                                <option value="">Tüm Tipler</option>
                                <option value="router">Router</option>
                                <option value="switch">Switch</option>
                                <option value="ap">Access Point</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select id="filterStatus" class="form-control">
                                <option value="">Tüm Durumlar</option>
                                <option value="online">Çevrimiçi</option>
                                <option value="offline">Çevrimdışı</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button id="btnRefreshAll" class="btn btn-info flex-fill">
                                    <i class="bi bi-arrow-clockwise"></i> Tümünü Güncelle
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-warning dropdown-toggle" type="button" id="bulkActionsBtn" data-bs-toggle="dropdown" disabled>
                                        <i class="bi bi-gear"></i> Toplu İşlem
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateType('router')">Router Yap</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateType('switch')">Switch Yap</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateType('ap')">AP Yap</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="bulkUpdateType('other')">Diğer Yap</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SADECE TABLO GÖRÜNÜMÜ -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered" id="devicesTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Durum</th>
                                    <th>Ad</th>
                                    <th>IP Adresi</th>
                                    <th>Tür</th>
                                    <th>Board Name</th>
                                    <th>Model</th>
                                    <th>Seri No</th>
                                    <th>Yazılım Versiyonu</th>
                                    <th>Uptime</th>
                                    <th style="min-width: 200px;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devices as $device): 
                                    // Online/offline durumu
                                    $isOnline = false;
                                    if (!empty($device['last_seen'])) {
                                        $lastSeen = strtotime($device['last_seen']);
                                        $threshold = time() - (DEVICE_ONLINE_THRESHOLD_MINUTES * 60);
                                        $isOnline = $lastSeen > $threshold;
                                    }
                                ?>
                                <tr data-device-id="<?php echo $device['id']; ?>">
                                    <td>
                                        <?php if ($device['is_main'] == 0): ?>
                                        <input type="checkbox" class="form-check-input device-checkbox" value="<?php echo intval($device['id']); ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isOnline): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Online</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Offline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($device['name']); ?></strong>
                                        <?php if ($device['is_main'] == 1): ?>
                                            <span class="badge badge-warning ml-2"><i class="bi bi-star-fill"></i> Ana Cihaz</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($device['ip_address'] . ':' . $device['port']); ?></code></td>
                                    <td>
                                        <?php
                                        $typeIcons = [
                                            'router' => '<i class="fas fa-router text-success"></i> Router',
                                            'switch' => '<i class="fas fa-ethernet text-info"></i> Switch',
                                            'ap' => '<i class="fas fa-wifi text-warning"></i> AP',
                                            'other' => '<i class="fas fa-server text-secondary"></i> Diğer'
                                        ];
                                        echo $typeIcons[$device['device_type']] ?? $typeIcons['other'];
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($device['board_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($device['model'] ?? '-'); ?></td>
                                    <td><small><?php echo htmlspecialchars($device['serial_number'] ?? '-'); ?></small></td>
                                    <td>
                                        <?php if (!empty($device['routeros_version'])): ?>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($device['routeros_version']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($device['uptime'])) {
                                            echo formatUptimeDisplay($device['uptime']);
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success" onclick="testDeviceById(<?php echo intval($device['id']); ?>)" title="Bağlantı Testi">
                                                <i class="fas fa-plug"></i> Test
                                            </button>
                                            
                                            <?php if ($device['is_main'] == 0): ?>
                                                <button class="btn btn-warning" onclick="setMainDevice(<?php echo intval($device['id']); ?>, <?php echo json_encode($device['name']); ?>)" title="Ana Cihaz Yap">
                                                    <i class="fas fa-star"></i> Ana Cihaz
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled title="Zaten Ana Cihaz">
                                                    <i class="fas fa-star"></i> Ana Cihaz
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-primary" onclick="editDevice(<?php echo intval($device['id']); ?>)" title="Düzenle">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </button>
                                            
                                            <?php if ($device['is_main'] == 0): ?>
                                                <button class="btn btn-danger" onclick="deleteDevice(<?php echo intval($device['id']); ?>, <?php echo json_encode($device['name']); ?>)" title="Sil">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled title="Ana Cihaz Silinemez">
                                                    <i class="fas fa-lock"></i> Sil
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Device Statistics -->
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="info-box bg-primary">
            <i class="bi bi-hdd-stack"></i>
            <div>
                <h3><?php echo count($devices); ?></h3>
                <p>Toplam Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-success">
            <i class="bi bi-check-circle"></i>
            <div>
                <h3><?php echo count(array_filter($devices, fn($d) => $d['is_active'])); ?></h3>
                <p>Aktif Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-warning">
            <i class="bi bi-star-fill"></i>
            <div>
                <h3><?php echo count(array_filter($devices, fn($d) => $d['is_main'])); ?></h3>
                <p>Ana Cihaz</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="info-box bg-info">
            <i class="bi bi-clock-history"></i>
            <div>
                <h3><?php echo count(array_filter($devices, fn($d) => $d['last_connection'])); ?></h3>
                <p>Bağlantı Kurulmuş</p>
            </div>
        </div>
    </div>
</div>

<!-- Device Add/Edit Modal -->
<div class="modal fade" id="deviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deviceModalTitle">Yeni Cihaz Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="deviceForm">
                    <input type="hidden" id="deviceId" name="device_id">
                    
                    <div class="mb-3">
                        <label class="form-label">IP Adresi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceIP" name="ip_address" required placeholder="192.168.1.1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Port</label>
                        <input type="number" class="form-control" id="devicePort" name="port" value="8728" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceUsername" name="username" required placeholder="admin">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Şifre <span class="text-danger" id="passwordRequired">*</span></label>
                        <input type="password" class="form-control" id="devicePassword" name="password" aria-required="true">
                        <small class="text-muted" id="passwordHint" style="display:none;">Boş bırakırsanız şifre değişmez</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cihaz Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceName" name="name" required placeholder="Ana Router">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cihaz Türü</label>
                        <select class="form-control" id="deviceType" name="device_type">
                            <option value="router">Router</option>
                            <option value="switch">Switch</option>
                            <option value="ap">Access Point</option>
                            <option value="other" selected>Diğer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isMainDevice" name="is_main">
                        <label class="form-check-label" for="isMainDevice">Ana cihaz olarak ayarla</label>
                    </div>
                    
                    <div id="testResult" class="mb-3"></div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info flex-fill" onclick="testDeviceConnection()">
                            <i class="bi bi-wifi me-2"></i>Bağlantıyı Test Et
                        </button>
                        <button type="button" id="saveDeviceBtn" class="btn btn-primary flex-fill">
                            <i class="bi bi-save me-2"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- WinBox Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-2"></i>WinBox Address Book Import
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Dosya Seçimi -->
                <div id="importStep1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">WinBox Dosyası (.WBX)</label>
                        <input type="file" class="form-control" id="winboxFile" accept=".wbx">
                        <small class="text-muted">
                            WinBox Address Book dosyanızı seçin (addresses.WBX)
                        </small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="debugBtn">
                            <i class="bi bi-bug me-2"></i>Debug Parse
                        </button>
                        <button type="button" class="btn btn-primary" id="parseBtn">
                            <i class="bi bi-search me-2"></i>Parse Et ve Önizle
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Önizleme ve Seçim -->
                <div id="importStep2" style="display: none;">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong id="previewTotal">0</strong> cihaz bulundu
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Grupları Seç</label>
                        <div id="groupsCheckboxes" class="row">
                            <!-- JavaScript ile doldurulacak -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="selectAllGroups">
                            <i class="bi bi-check-all me-2"></i>Tümünü Seç
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="updateExisting" checked>
                            <label class="form-check-label" for="updateExisting">
                                Mevcut cihazları güncelle
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Önizleme</label>
                        <div id="previewList" style="max-height: 300px; overflow-y: auto;" class="border rounded p-2">
                            <!-- JavaScript ile doldurulacak -->
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" id="backToStep1">
                            <i class="bi bi-arrow-left me-2"></i>Geri
                        </button>
                        <button type="button" class="btn btn-success" id="importBtn">
                            <i class="bi bi-check-circle me-2"></i>Import Et
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Sonuç -->
                <div id="importStep3" style="display: none;">
                    <div id="importResult">
                        <!-- JavaScript ile doldurulacak -->
                    </div>
                    <button type="button" class="btn btn-primary mt-3" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Sayfayı Yenile
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PPPoE Devices Modal -->
<div class="modal fade" id="pppoeModal" tabindex="-1" aria-labelledby="pppoeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pppoeModalLabel">
                    <i class="bi bi-download me-2"></i>PPPoE Cihazları
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Loading -->
                <div id="pppoeStep1" style="display: none;">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Ana cihazdan PPPoE secrets çekiliyor...
                    </div>
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Device List -->
                <div id="pppoeStep2" style="display: none;">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong id="pppoeTotal">0</strong> cihaz bulundu
                    </div>
                    
                    <!-- Username/Password Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-key me-2"></i>Bağlantı Bilgileri (Tüm Cihazlar İçin)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="pppoeUsername" placeholder="admin" value="admin">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Şifre <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="pppoePassword" placeholder="Şifre">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Port</label>
                                    <input type="number" class="form-control" id="pppoePort" value="8728">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Devices Table -->
                    <div id="pppoeDevicesList" class="table-responsive">
                        <!-- JavaScript will populate this -->
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>İptal
                        </button>
                        <button type="button" class="btn btn-success" id="importPPPoEBtn">
                            <i class="bi bi-check-circle me-2"></i>Seçilenleri Ekle
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Result -->
                <div id="pppoeStep3" style="display: none;">
                    <div id="pppoeResult">
                        <!-- JavaScript will populate this -->
                    </div>
                    <button type="button" class="btn btn-primary mt-3" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Sayfayı Yenile
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Device Management JavaScript -->
<script>
// BASE_URL'yi JavaScript'e aktar
const BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<!-- Load device-management.js after jQuery is loaded in footer -->
<script src="<?php echo BASE_URL; ?>/assets/js/device-management.js"></script>
