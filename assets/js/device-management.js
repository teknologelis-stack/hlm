/**
 * Device Management JavaScript
 * Tüm buton, modal ve AJAX işlemleri
 */

// Global değişkenler
let devicesDataTable = null;
let pppoeDevicesList = [];
let selectedDeviceIds = [];

// Sayfa yüklendiğinde
$(document).ready(function() {
    console.log('Device Management JS loaded');
    
    // Check if required modals exist
    if ($('#pppoeModal').length === 0) {
        console.warn('⚠️ Warning: PPPoE modal (#pppoeModal) not found in DOM');
    }
    
    initDataTable();
    initEventListeners();
    initCheckboxEvents();
});

// ============= DataTable =============
function initDataTable() {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#devicesTable')) {
        $('#devicesTable').DataTable().destroy();
    }
    
    if ($('#devicesTable').length) {
        devicesDataTable = $('#devicesTable').DataTable({
            "language": {
                "sEmptyTable": "Tabloda herhangi bir veri yok",
                "sInfo": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
                "sInfoEmpty": "Kayıt yok",
                "sInfoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
                "sLengthMenu": "_MENU_ kayıt göster",
                "sLoadingRecords": "Yükleniyor...",
                "sProcessing": "İşleniyor...",
                "sSearch": "Ara:",
                "sZeroRecords": "Eşleşen kayıt bulunamadı",
                "oPaginate": {
                    "sFirst": "İlk",
                    "sLast": "Son",
                    "sNext": "Sonraki",
                    "sPrevious": "Önceki"
                }
            },
            "order": [[2, 'asc']],
            "pageLength": 25,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": [0, -1] },
                { 
                    "responsivePriority": 1, 
                    "targets": -1  // Keep action column always visible
                },
                { 
                    "responsivePriority": 2, 
                    "targets": 2  // Device name second priority
                }
            ]
        });
    }
}

// ============= Event Listeners =============
function initEventListeners() {
    // Tümünü Güncelle butonu
    $('#btnRefreshAll').off('click').on('click', refreshAllDevices);
    
    // PPPoE Import butonu
    $('#importPPPoEBtn').off('click').on('click', importPPPoEDevices);
    
    // Ana Cihazdan Çek butonu (HTML'de onclick ile çağrılıyor, ama yine de tanımlayalım)
    // $('#btnFetchPPPoE').off('click').on('click', fetchPPPoEDevices);
    
    // Save Device butonu
    $('#saveDeviceBtn').off('click').on('click', saveDevice);
    
    // WinBox Import butonları
    $('#parseBtn').off('click').on('click', parseWinBoxFile);
    $('#debugBtn').off('click').on('click', debugWinBoxParse);
    $('#importBtn').off('click').on('click', importWinBoxDevices);
    $('#backToStep1').off('click').on('click', backToWinBoxStep1);
    $('#selectAllGroups').off('click').on('click', selectAllWinBoxGroups);
    
    // Search and filter handlers
    $('#searchDevice').off('input').on('input', filterDeviceTable);
    $('#filterDeviceType').off('change').on('change', filterDeviceTable);
    $('#filterStatus').off('change').on('change', filterDeviceTable);
}

// ============= Checkbox İşlemleri =============
function initCheckboxEvents() {
    // Tümünü Seç
    $('#selectAll').off('change').on('change', function() {
        $('.device-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });
    
    // Tek checkbox
    $(document).off('change', '.device-checkbox').on('change', '.device-checkbox', function() {
        updateBulkActions();
        
        const totalCheckboxes = $('.device-checkbox').length;
        const checkedCheckboxes = $('.device-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
}

// Toplu işlem butonlarını güncelle
function updateBulkActions() {
    selectedDeviceIds = [];
    $('.device-checkbox:checked').each(function() {
        selectedDeviceIds.push(parseInt($(this).val()));
    });
    
    const count = selectedDeviceIds.length;
    const btn = $('#bulkActionsBtn');
    
    if (btn.length) {
        btn.prop('disabled', count === 0);
    }
}

// Seçimi temizle
function clearSelection() {
    $('.device-checkbox, #selectAll').prop('checked', false);
    updateBulkActions();
}

// ============= Test Butonu =============
function testDeviceById(deviceId) {
    Swal.fire({
        title: 'Bağlantı Testi',
        text: 'Cihaz bilgileri alınıyor...',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // First: Get device details
    fetch(BASE_URL + '/api/get-device.php?id=' + deviceId)
        .then(response => response.json())
        .then(deviceData => {
            if (!deviceData.success) {
                throw new Error(deviceData.message || 'Cihaz bilgileri alınamadı');
            }
            
            const device = deviceData.device;
            Swal.update({ text: 'Cihaza bağlanılıyor...' });
            
            // Then: Test with full credentials
            return fetch(BASE_URL + '/api/test-device.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ip_address: device.ip_address,
                    port: device.port,
                    username: device.username,
                    password: '', // Empty when device_id is provided - API will fetch encrypted password from database
                    device_id: deviceId // API uses this to fetch stored encrypted password
                })
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Bağlantı Başarılı!',
                    html: `
                        <div class="text-left">
                            <p><strong>Identity:</strong> ${data.device.identity || '-'}</p>
                            <p><strong>RouterOS:</strong> ${data.device.version || '-'}</p>
                            <p><strong>Model:</strong> ${data.device.model || '-'}</p>
                            <p><strong>Board Name:</strong> ${data.device.board_name || '-'}</p>
                            <p><strong>Seri No:</strong> ${data.device.serial_number || '-'}</p>
                            <p><strong>Uptime:</strong> ${data.device.uptime || '-'}</p>
                            <p><strong>CPU Load:</strong> ${data.device.cpu_load || 0}%</p>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'Tamam'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Bağlantı Hatası', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Hata', error.message || 'Test işlemi başarısız', 'error');
            console.error('Test error:', error);
        });
}

// ============= Tek Cihaz Yenile =============
function refreshDevice(deviceId) {
    Swal.fire({
        title: 'Bilgiler Güncelleniyor',
        text: 'Lütfen bekleyin...',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(BASE_URL + '/api/test-device.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Başarılı!', 'Cihaz bilgileri güncellendi', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Hata!', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', 'Güncelleme başarısız', 'error');
        console.error('Refresh error:', error);
    });
}

// ============= Tümünü Güncelle =============
function refreshAllDevices() {
    Swal.fire({
        title: 'Tüm Cihazlar Güncellensin mi?',
        text: 'Bu işlem birkaç dakika sürebilir.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Güncelle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#0dcaf0'
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = $('#btnRefreshAll');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Güncelleniyor...');
            
            fetch(BASE_URL + '/api/update-all-devices.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Tümünü Güncelle');
                
                if (data.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        html: `
                            <p>${data.message}</p>
                            <ul class="text-left">
                                <li>Toplam: ${data.stats.total}</li>
                                <li>Güncellenen: ${data.stats.success}</li>
                                <li>Çevrimdışı: ${data.stats.offline}</li>
                                <li>Başarısız: ${data.stats.failed}</li>
                            </ul>
                        `,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(error => {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Tümünü Güncelle');
                Swal.fire('Hata!', 'Güncelleme başarısız', 'error');
                console.error('Update all error:', error);
            });
        }
    });
}

// ============= Ana Cihaz Yap =============
function setMainDevice(deviceId, deviceName) {
    Swal.fire({
        title: 'Ana Cihaz Olarak Ayarla?',
        html: `<strong>${escapeHtml(deviceName)}</strong> ana cihaz olarak ayarlanacak.<br><br>Mevcut ana cihaz değiştirilecek.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Ana Cihaz Yap',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + '/api/set-main-device.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ device_id: deviceId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Başarılı!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', 'İşlem başarısız', 'error');
                console.error('Set main error:', error);
            });
        }
    });
}

// ============= Cihaz Düzenle =============
function editDevice(deviceId) {
    fetch(BASE_URL + '/api/get-device.php?id=' + deviceId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const device = data.device;
            
            Swal.fire({
                title: 'Cihaz Düzenle',
                html: `
                    <form id="editDeviceForm" class="text-start">
                        <input type="hidden" name="device_id" id="edit_device_id" value="${device.id}">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Cihaz Adı *</label>
                            <input type="text" class="form-control" id="edit_name" value="${escapeHtml(device.name)}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_ip" class="form-label">IP Adresi *</label>
                            <input type="text" class="form-control" id="edit_ip" value="${device.ip_address}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_port" class="form-label">Port</label>
                            <input type="number" class="form-control" id="edit_port" value="${device.port || 8728}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Kullanıcı Adı *</label>
                            <input type="text" class="form-control" id="edit_username" value="${escapeHtml(device.username)}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="edit_password" placeholder="Değiştirmek için girin">
                            <small class="text-muted">Boş bırakırsanız şifre değişmez</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_type" class="form-label">Cihaz Türü *</label>
                            <select class="form-control" id="edit_type" required>
                                <option value="other" ${device.device_type === 'other' ? 'selected' : ''}>Diğer</option>
                                <option value="router" ${device.device_type === 'router' ? 'selected' : ''}>Router</option>
                                <option value="switch" ${device.device_type === 'switch' ? 'selected' : ''}>Switch</option>
                                <option value="ap" ${device.device_type === 'ap' ? 'selected' : ''}>Access Point</option>
                            </select>
                        </div>
                    </form>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Güncelle',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#0d6efd',
                preConfirm: () => {
                    const deviceId = document.getElementById('edit_device_id').value;
                    const name = document.getElementById('edit_name').value;
                    const ip = document.getElementById('edit_ip').value;
                    const port = document.getElementById('edit_port').value;
                    const username = document.getElementById('edit_username').value;
                    const password = document.getElementById('edit_password').value;
                    const type = document.getElementById('edit_type').value;
                    
                    if (!deviceId || !name || !ip || !username) {
                        Swal.showValidationMessage('Lütfen tüm gerekli alanları doldurun');
                        return false;
                    }
                    
                    const updateData = {
                        device_id: deviceId,
                        name: name,
                        ip_address: ip,
                        port: parseInt(port),
                        username: username,
                        device_type: type
                    };
                    
                    if (password) {
                        updateData.password = password;
                    }
                    
                    return fetch(BASE_URL + '/api/update-device.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(updateData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Güncelleme başarısız');
                        }
                        return data;
                    })
                    .catch(error => {
                        console.error('Update error:', error);
                        Swal.showValidationMessage(`İşlem başarısız: ${error.message}`);
                        return false;
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Başarılı!', result.value.message, 'success').then(() => {
                        location.reload();
                    });
                }
            });
        } else {
            Swal.fire('Hata!', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Get device error:', error);
        Swal.fire('Hata!', 'Cihaz bilgileri alınamadı', 'error');
    });
}

// ============= Cihaz Sil =============
function deleteDevice(deviceId, deviceName) {
    Swal.fire({
        title: 'Emin misiniz?',
        html: `<strong>${escapeHtml(deviceName)}</strong> adlı cihazı silmek istediğinizden emin misiniz?<br><br><span class="text-danger">Bu işlem geri alınamaz!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + '/api/delete-device.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ device_id: deviceId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Silindi!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', 'Silme işlemi başarısız', 'error');
                console.error('Delete error:', error);
            });
        }
    });
}

// ============= PPPoE Çek =============
function fetchPPPoEDevices() {
    console.log('=== fetchPPPoEDevices STARTED ===');
    
    // Check if modal exists
    if ($('#pppoeModal').length === 0) {
        console.error('❌ Modal #pppoeModal NOT FOUND in DOM!');
        Swal.fire({
            icon: 'error',
            title: 'Modal Bulunamadı',
            text: 'PPPoE modal elementi HTML\'de yok. Sayfa yenilenerek düzeltilecek.',
            confirmButtonText: 'Sayfayı Yenile'
        }).then(() => {
            location.reload();
        });
        return;
    }
    
    console.log('✅ Modal found, opening...');
    
    // Show modal with loading state
    $('#pppoeStep1').show();
    $('#pppoeStep2').hide();
    $('#pppoeStep3').hide();
    $('#pppoeModal').modal('show');
    
    console.log('🔄 Fetching from:', BASE_URL + '/api/fetch-pppoe-devices.php');
    
    fetch(BASE_URL + '/api/fetch-pppoe-devices.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        console.log('📡 Response status:', response.status);
        
        if (!response.ok) {
            return response.json().then(errData => {
                throw new Error(errData.message || `HTTP ${response.status}`);
            }).catch(() => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('✅ Data received:', data);
        
        // Hide loading, show device list
        $('#pppoeStep1').hide();
        $('#pppoeStep2').show();
        
        if (data.success && data.devices && data.devices.length > 0) {
            pppoeDevicesList = data.devices;
            
            // Calculate stats
            const newDevices = data.devices.filter(d => d.exists !== true);
            const stats = {
                total_pppoe: data.total || data.count || data.devices.length,
                filtered: data.filtered || 0,
                found: newDevices.length
            };
            
            console.log('📊 Stats:', stats);
            $('#pppoeTotal').text(stats.found);
            
            renderPPPoEDevices(data.devices, stats);
        } else if (data.success && (!data.devices || data.devices.length === 0)) {
            $('#pppoeDevicesList').html(`
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    PPPoE cihazı bulunamadı veya tüm cihazlar zaten ekli.
                </div>
            `);
        } else {
            $('#pppoeModal').modal('hide');
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: data.message || 'Bilinmeyen hata'
            });
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        $('#pppoeModal').modal('hide');
        Swal.fire({
            icon: 'error',
            title: 'Bağlantı Hatası',
            html: `<p>Ana cihaza bağlanılamadı</p><small>${error.message}</small>`
        });
    });
}

// PPPoE cihazlarını tablo olarak göster
function renderPPPoEDevices(devices, stats) {
    console.log('🎨 renderPPPoEDevices called with', devices.length, 'devices');
    
    if (!Array.isArray(devices)) {
        console.error('❌ devices is not an array:', devices);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Geçersiz veri formatı'
        });
        return;
    }
    
    const safeStats = stats || {};
    const totalPppoe = safeStats.total_pppoe || devices.length;
    const filtered = safeStats.filtered || devices.length;
    const found = safeStats.found || devices.length;
    
    let html = `
        <div class="alert alert-info mb-3">
            <strong>Toplam PPPoE:</strong> ${totalPppoe} | 
            <strong>Filtrelenmiş:</strong> ${filtered} | 
            <strong>Yeni Bulunan:</strong> ${found}
        </div>
        
        <table class="table table-bordered table-sm table-hover">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="selectAllPPPoE"></th>
                    <th>Cihaz Adı</th>
                    <th>IP Adresi</th>
                    <th>Tür</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    devices.forEach((device, index) => {
        const typeIcon = {
            'router': '🔀 Router',
            'switch': '📡 Switch',
            'ap': '📶 AP',
            'other': '⚙️ Diğer'
        }[device.device_type] || '⚙️ Diğer';
        
        const statusBadge = device.exists 
            ? '<span class="badge bg-secondary">Zaten Kayıtlı</span>'
            : '<span class="badge bg-success">Yeni</span>';
        
        const disabled = device.exists ? 'disabled' : '';
        const disabledAttrs = device.exists ? 'aria-label="Bu cihaz zaten kayıtlı, seçilemez"' : '';
        
        html += `
            <tr>
                <td><input type="checkbox" class="pppoe-checkbox" value="${index}" ${disabled} ${disabledAttrs}></td>
                <td><strong>${escapeHtml(device.name)}</strong></td>
                <td><code>${device.ip_address}</code></td>
                <td>${typeIcon}</td>
                <td>${statusBadge}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    $('#pppoeDevicesList').html(html);
    
    // Select all checkbox
    $('#selectAllPPPoE').off('change').on('change', function() {
        $('.pppoe-checkbox:not(:disabled)').prop('checked', this.checked);
    });
    
    console.log('✅ renderPPPoEDevices completed');
}

// Seçili PPPoE cihazlarını ekle
function importPPPoEDevices() {
    const selectedDevices = [];
    const username = $('#pppoeUsername').val().trim();
    const password = $('#pppoePassword').val().trim();
    const port = parseInt($('#pppoePort').val()) || 8728;
    
    if (!username || !password) {
        Swal.fire('Uyarı', 'Kullanıcı adı ve şifre gereklidir', 'warning');
        return;
    }
    
    $('.pppoe-checkbox:checked').each(function() {
        const index = parseInt($(this).val());
        const device = pppoeDevicesList[index];
        
        selectedDevices.push({
            name: device.name,
            ip_address: device.ip_address,
            device_type: device.device_type
        });
    });
    
    if (selectedDevices.length === 0) {
        Swal.fire('Uyarı', 'Lütfen en az bir cihaz seçin', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Cihazlar Ekleniyor',
        text: `${selectedDevices.length} cihaz ekleniyor...`,
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(BASE_URL + '/api/import-pppoe-devices.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            devices: selectedDevices,
            username: username,
            password: password,
            port: port
        })
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        $('#pppoeModal').modal('hide');
        
        if (data.success) {
            let messageHtml = `<p>${data.message}</p>`;
            
            // Show errors if any
            if (data.stats && data.stats.errors && data.stats.errors.length > 0) {
                messageHtml += '<hr><strong>Hatalar:</strong><ul class="text-start">';
                data.stats.errors.forEach(error => {
                    messageHtml += `<li>${escapeHtml(error)}</li>`;
                });
                messageHtml += '</ul>';
            }
            
            Swal.fire({
                title: 'Başarılı!',
                html: messageHtml,
                icon: 'success'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Hata', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.close();
        const errorMsg = error && error.message ? error.message : 'Bilinmeyen hata';
        Swal.fire('Hata', 'İçe aktarma başarısız: ' + errorMsg, 'error');
    });
}

// ============= Toplu Tür Değiştir =============
function bulkUpdateType(deviceType) {
    if (selectedDeviceIds.length === 0) {
        Swal.fire('Uyarı', 'Lütfen en az bir cihaz seçin', 'warning');
        return;
    }
    
    const typeNames = {
        'router': 'Router',
        'switch': 'Switch',
        'ap': 'Access Point',
        'other': 'Diğer'
    };
    
    Swal.fire({
        title: 'Toplu Tür Güncelleme',
        text: `${selectedDeviceIds.length} cihazın türü "${typeNames[deviceType]}" olarak değiştirilecek. Onaylıyor musunuz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Güncelle',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + '/api/bulk-update-type.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    device_ids: selectedDeviceIds,
                    device_type: deviceType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Başarılı!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', 'Güncelleme başarısız', 'error');
                console.error('Bulk update type error:', error);
            });
        }
    });
}

// ============= Yardımcı Fonksiyonlar =============
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============= Save Device =============
function saveDevice() {
    const form = document.getElementById('deviceForm');
    if (!form) return;
    
    const deviceId = document.getElementById('deviceId');
    const deviceIP = document.getElementById('deviceIP');
    const devicePort = document.getElementById('devicePort');
    const deviceUsername = document.getElementById('deviceUsername');
    const devicePassword = document.getElementById('devicePassword');
    const deviceName = document.getElementById('deviceName');
    const deviceType = document.getElementById('deviceType');
    const isMainDevice = document.getElementById('isMainDevice');
    
    // Validation
    if (!deviceIP || !deviceIP.value.trim()) {
        Swal.fire('Uyarı', 'IP adresi gereklidir', 'warning');
        return;
    }
    
    if (!deviceUsername || !deviceUsername.value.trim()) {
        Swal.fire('Uyarı', 'Kullanıcı adı gereklidir', 'warning');
        return;
    }
    
    if (!deviceName || !deviceName.value.trim()) {
        Swal.fire('Uyarı', 'Cihaz adı gereklidir', 'warning');
        return;
    }
    
    const isEdit = deviceId && deviceId.value;
    
    // For new devices, password is required
    if (!isEdit && (!devicePassword || !devicePassword.value.trim())) {
        Swal.fire('Uyarı', 'Şifre gereklidir', 'warning');
        return;
    }
    
    // Prepare data
    const data = {
        ip_address: deviceIP.value.trim(),
        port: devicePort ? parseInt(devicePort.value) : 8728,
        username: deviceUsername.value.trim(),
        name: deviceName.value.trim(),
        device_type: deviceType ? deviceType.value : 'other',
        is_main: isMainDevice ? isMainDevice.checked : false
    };
    
    // Add password if provided
    if (devicePassword && devicePassword.value.trim()) {
        data.password = devicePassword.value.trim();
    }
    
    // Add device_id for edit mode
    if (isEdit) {
        data.device_id = deviceId.value;
    }
    
    // Show loading
    Swal.fire({
        title: isEdit ? 'Güncelleniyor...' : 'Kaydediliyor...',
        text: 'Lütfen bekleyin...',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Determine API endpoint
    const apiUrl = isEdit ? BASE_URL + '/api/update-device.php' : BASE_URL + '/api/add-device.php';
    
    // Send request
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                title: 'Başarılı!',
                text: result.message,
                icon: 'success',
                confirmButtonText: 'Tamam'
            }).then(() => {
                $('#deviceModal').modal('hide');
                location.reload();
            });
        } else {
            Swal.fire('Hata!', result.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', 'İşlem başarısız oldu', 'error');
        console.error('Save device error:', error);
    });
}

// ============= Device Modal Functions =============
function openDeviceModal(id = null) {
    const modal = document.getElementById('deviceModal');
    const form = document.getElementById('deviceForm');
    const title = document.getElementById('deviceModalTitle');
    const passwordHint = document.getElementById('passwordHint');
    const passwordRequired = document.getElementById('passwordRequired');
    const passwordField = document.getElementById('devicePassword');
    
    if (!form) return;
    
    form.reset();
    const testResult = document.getElementById('testResult');
    if (testResult) testResult.innerHTML = '';
    
    if (id) {
        title.textContent = 'Cihazı Düzenle';
        // Show password hint for edit mode
        if (passwordHint) passwordHint.style.display = 'block';
        if (passwordRequired) passwordRequired.style.display = 'none';
        if (passwordField) passwordField.setAttribute('aria-required', 'false');
        
        // Load device data
        fetch(BASE_URL + '/api/get-device.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const deviceId = document.getElementById('deviceId');
                    const deviceIP = document.getElementById('deviceIP');
                    const devicePort = document.getElementById('devicePort');
                    const deviceUsername = document.getElementById('deviceUsername');
                    const deviceName = document.getElementById('deviceName');
                    const deviceType = document.getElementById('deviceType');
                    const isMainDevice = document.getElementById('isMainDevice');
                    
                    if (deviceId) deviceId.value = data.device.id;
                    if (deviceIP) deviceIP.value = data.device.ip_address;
                    if (devicePort) devicePort.value = data.device.port;
                    if (deviceUsername) deviceUsername.value = data.device.username;
                    if (deviceName) deviceName.value = data.device.name;
                    if (deviceType) deviceType.value = data.device.device_type || 'other';
                    if (isMainDevice) isMainDevice.checked = data.device.is_main == 1;
                }
            });
    } else {
        title.textContent = 'Yeni Cihaz Ekle';
        const deviceId = document.getElementById('deviceId');
        if (deviceId) deviceId.value = '';
        // Hide password hint for new device mode
        if (passwordHint) passwordHint.style.display = 'none';
        if (passwordRequired) passwordRequired.style.display = 'inline';
        if (passwordField) passwordField.setAttribute('aria-required', 'true');
    }
}

function testDeviceConnection() {
    const resultDiv = document.getElementById('testResult');
    if (!resultDiv) return;
    
    const deviceIP = document.getElementById('deviceIP');
    const devicePort = document.getElementById('devicePort');
    const deviceUsername = document.getElementById('deviceUsername');
    const devicePassword = document.getElementById('devicePassword');
    
    if (!deviceIP || !devicePort || !deviceUsername || !devicePassword) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Form alanları bulunamadı</div>';
        return;
    }
    
    const formData = {
        ip_address: deviceIP.value,
        port: devicePort.value,
        username: deviceUsername.value,
        password: devicePassword.value
    };
    
    resultDiv.innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Test ediliyor...</div>';
    
    fetch(BASE_URL + '/api/test-device.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Bağlantı Başarılı</h6>
                    <small>
                        <strong>Identity:</strong> ${escapeHtml(data.data.identity || 'N/A')}<br>
                        <strong>Version:</strong> ${escapeHtml(data.data.version || 'N/A')}<br>
                        <strong>Model:</strong> ${escapeHtml(data.data.board || 'N/A')}
                    </small>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i>${escapeHtml(data.message || 'Bağlantı başarısız')}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-x-circle me-2"></i>Test sırasında hata oluştu
            </div>
        `;
        console.error('Test connection error:', error);
    });
}

// ============= WinBox Import Functions =============
let parsedWinBoxDevices = [];
let winBoxGroupData = {};

function parseWinBoxFile() {
    const fileInput = document.getElementById('winboxFile');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        Swal.fire('Uyarı', 'Lütfen bir dosya seçin', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('winbox_file', file);
    
    Swal.fire({
        title: 'Dosya İşleniyor',
        text: 'WinBox dosyası parse ediliyor...',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(BASE_URL + '/api/parse-winbox.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            parsedWinBoxDevices = data.devices || [];
            winBoxGroupData = data.groups || {};
            
            if (parsedWinBoxDevices.length === 0) {
                Swal.fire('Bilgi', 'Dosyada cihaz bulunamadı', 'info');
                return;
            }
            
            displayWinBoxPreview(parsedWinBoxDevices, winBoxGroupData);
            
            // Show step 2
            $('#importStep1').hide();
            $('#importStep2').show();
        } else {
            Swal.fire('Hata', data.message || 'Dosya parse edilemedi', 'error');
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire('Hata', 'Dosya işlenirken hata oluştu', 'error');
        console.error('Parse WinBox error:', error);
    });
}

function debugWinBoxParse() {
    const fileInput = document.getElementById('winboxFile');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        Swal.fire('Uyarı', 'Lütfen bir dosya seçin', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('winbox_file', file);
    
    fetch(BASE_URL + '/api/debug-winbox.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Debug WinBox Parse:', data);
        Swal.fire({
            title: 'Debug Bilgisi',
            html: `<pre style="text-align: left; max-height: 400px; overflow: auto;">${JSON.stringify(data, null, 2)}</pre>`,
            width: '800px',
            confirmButtonText: 'Tamam'
        });
    })
    .catch(error => {
        Swal.fire('Hata', 'Debug işlemi başarısız', 'error');
        console.error('Debug WinBox error:', error);
    });
}

function displayWinBoxPreview(devices, groups) {
    // Update total count
    $('#previewTotal').text(devices.length);
    
    // Render groups checkboxes
    let groupsHtml = '';
    const groupNames = Object.keys(groups);
    
    if (groupNames.length > 0) {
        groupNames.forEach(groupName => {
            const count = groups[groupName];
            // Create unique hash-based ID using a safe encoding method
            let groupHash;
            try {
                groupHash = encodeURIComponent(groupName).replace(/[^a-zA-Z0-9]/g, '').substring(0, 16);
            } catch (e) {
                // Fallback to simple replacement if encoding fails
                groupHash = groupName.replace(/[^a-zA-Z0-9]/g, '').substring(0, 16);
            }
            const sanitizedId = `group_${groupHash}_${Date.now().toString(36).slice(-4)}`;
            groupsHtml += `
                <div class="col-md-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input winbox-group-checkbox" type="checkbox" value="${escapeHtml(groupName)}" id="${sanitizedId}" checked>
                        <label class="form-check-label" for="${sanitizedId}">
                            ${escapeHtml(groupName)} <span class="badge bg-secondary">${count}</span>
                        </label>
                    </div>
                </div>
            `;
        });
    } else {
        groupsHtml = '<p class="text-muted">Grup bulunamadı</p>';
    }
    
    $('#groupsCheckboxes').html(groupsHtml);
    
    // Render devices preview
    let previewHtml = '<ul class="list-group">';
    devices.forEach((device, index) => {
        previewHtml += `
            <li class="list-group-item d-flex justify-content-between align-items-center" data-group="${escapeHtml(device.group || '')}">
                <div class="form-check me-3">
                    <input class="form-check-input winbox-device-checkbox" 
                           type="checkbox" 
                           value="${index}" 
                           id="device_${index}" 
                           checked>
                </div>
                <div class="flex-grow-1">
                    <strong>${escapeHtml(device.name)}</strong>
                    <br>
                    <small class="text-muted">${device.ip_address}:${device.port || 8728}</small>
                    ${device.group ? `<span class="badge bg-info ms-2">${escapeHtml(device.group)}</span>` : ''}
                </div>
                <span class="badge bg-primary">${device.device_type || 'other'}</span>
            </li>
        `;
    });
    previewHtml += '</ul>';
    
    $('#previewList').html(previewHtml);
    
    // Add event listener for group filtering
    $('.winbox-group-checkbox').off('change').on('change', filterWinBoxPreview);
}

function filterWinBoxPreview() {
    const selectedGroups = [];
    $('.winbox-group-checkbox:checked').each(function() {
        selectedGroups.push($(this).val());
    });
    
    $('#previewList li').each(function() {
        const deviceGroup = $(this).data('group');
        // Match import logic: if groups selected, only show devices with matching groups
        if (selectedGroups.length === 0) {
            $(this).show();
        } else if (deviceGroup && selectedGroups.includes(deviceGroup)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function selectAllWinBoxGroups() {
    const currentlyAllChecked = $('.winbox-group-checkbox:checked').length === $('.winbox-group-checkbox').length;
    $('.winbox-group-checkbox').prop('checked', !currentlyAllChecked);
    filterWinBoxPreview();
}

function importWinBoxDevices() {
    // Get selected device indices
    const selectedIndices = [];
    $('.winbox-device-checkbox:checked').each(function() {
        selectedIndices.push(parseInt($(this).val()));
    });
    
    const devicesToImport = selectedIndices.map(i => parsedWinBoxDevices[i]);
    
    if (devicesToImport.length === 0) {
        Swal.fire('Uyarı', 'Lütfen en az bir cihaz seçin', 'warning');
        return;
    }
    
    const updateExisting = $('#updateExisting').is(':checked');
    
    Swal.fire({
        title: 'Cihazlar İçe Aktarılıyor',
        text: `${devicesToImport.length} cihaz içe aktarılıyor...`,
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(BASE_URL + '/api/import-winbox.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            devices: devicesToImport,
            update_existing: updateExisting
        })
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            let resultHtml = `
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle me-2"></i>${data.message}</h5>
                    <hr>
                    <p>
                        <strong>Toplam:</strong> ${data.stats.total}<br>
                        <strong>Eklenen:</strong> ${data.stats.added}<br>
                        <strong>Güncellenen:</strong> ${data.stats.updated}<br>
                        <strong>Atlanan:</strong> ${data.stats.skipped}
                    </p>
                </div>
            `;
            
            if (data.stats.errors && data.stats.errors.length > 0) {
                resultHtml += `
                    <div class="alert alert-warning">
                        <h6>Hatalar:</h6>
                        <ul>
                            ${data.stats.errors.map(err => `<li>${escapeHtml(err)}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            $('#importResult').html(resultHtml);
            $('#importStep2').hide();
            $('#importStep3').show();
        } else {
            Swal.fire('Hata', data.message || 'İçe aktarma başarısız', 'error');
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire('Hata', 'İçe aktarma sırasında hata oluştu', 'error');
        console.error('Import WinBox error:', error);
    });
}

function backToWinBoxStep1() {
    $('#importStep2').hide();
    $('#importStep1').show();
    parsedWinBoxDevices = [];
    winBoxGroupData = {};
}

// ============= Table Filter Functions =============
let deviceTableFilterInitialized = false;

function filterDeviceTable() {
    if (!devicesDataTable) return;
    
    // Initialize filter function once
    if (!deviceTableFilterInitialized) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            // Only apply to our table
            if (settings.nTable.id !== 'devicesTable') return true;
            
            // Get current filter values
            const searchTerm = $('#searchDevice').val().toLowerCase();
            const typeFilter = $('#filterDeviceType').val();
            const statusFilter = $('#filterStatus').val();
            
            // Get the row element to access data attributes
            const row = devicesDataTable.row(dataIndex).node();
            if (!row) return true;
            
            // data[2] = Name, data[3] = IP Address, data[4] = Type
            const name = data[2].toLowerCase();
            const ip = data[3].toLowerCase();
            const type = data[4];
            
            // Search filter
            const searchMatch = !searchTerm || name.includes(searchTerm) || ip.includes(searchTerm);
            
            // Type filter
            const typeMatch = !typeFilter || type.includes(typeFilter);
            
            // Status filter - check CSS classes on badge instead of text
            let statusMatch = true;
            if (statusFilter) {
                const $row = $(row);
                const statusBadge = $row.find('.badge');
                const hasOnlineClass = statusBadge.hasClass('bg-success');
                
                if (statusFilter === 'online') {
                    statusMatch = hasOnlineClass;
                } else if (statusFilter === 'offline') {
                    statusMatch = !hasOnlineClass;
                }
            }
            
            return searchMatch && typeMatch && statusMatch;
        });
        
        deviceTableFilterInitialized = true;
    }
    
    // Redraw table with current filter values
    devicesDataTable.draw();
}
