/**
 * Update Manager JavaScript
 */

let availableUpdate = null;
let progressInterval = null;

/**
 * Check for available updates
 */
function checkForUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const resultDiv = document.getElementById('updateCheckResult');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Checking...';
    
    resultDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="bi bi-hourglass-split"></i> Checking for updates...
        </div>
    `;
    
    fetch(BASE_URL + '/api/system-update-check.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const updateData = data.data;
                
                document.getElementById('lastChecked').textContent = new Date().toLocaleString();
                
                if (updateData.available) {
                    availableUpdate = updateData;
                    
                    let changesHtml = '';
                    if (updateData.changelog && updateData.changelog.length > 0) {
                        changesHtml = '<ul class="mb-0">';
                        updateData.changelog.forEach(change => {
                            changesHtml += `<li>${change}</li>`;
                        });
                        changesHtml += '</ul>';
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">
                                <i class="bi bi-exclamation-triangle"></i> Güncelleme Mevcut!
                            </h5>
                            <p><strong>Versiyon ${updateData.latest}</strong> şimdi kullanılabilir!</p>
                            <p><small class="text-muted">Yayınlanma: ${new Date(updateData.release_date).toLocaleDateString()}</small></p>
                            ${changesHtml ? '<hr><p class="mb-2"><strong>Değişiklikler:</strong></p>' + changesHtml : ''}
                        </div>
                    `;
                    
                    document.getElementById('createBackupBtn').style.display = 'inline-block';
                    document.getElementById('applyUpdateBtn').style.display = 'inline-block';
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            Sisteminiz güncel! (Versiyon ${updateData.current})
                        </div>
                    `;
                    
                    document.getElementById('createBackupBtn').style.display = 'none';
                    document.getElementById('applyUpdateBtn').style.display = 'none';
                }
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle"></i> ${data.error || 'Güncellemeler kontrol edilemedi'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> Ağ hatası oluştu
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search"></i> Check for Updates';
        });
}

function createBackup() {
    Swal.fire({
        title: 'Yedek Oluştur',
        text: 'Veritabanınızın yedeği oluşturulacak. Devam edilsin mi?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, yedek oluştur',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#198754',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Yedek Oluşturuluyor...',
                text: 'Verileriniz yedekleniyor, lütfen bekleyin',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(BASE_URL + '/api/system-backup-create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        text: data.message || 'Yedek başarıyla oluşturuldu',
                        icon: 'success',
                        confirmButtonColor: '#0d6efd'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: data.error || 'Yedek oluşturulamadı',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Hata!',
                    text: 'Ağ hatası oluştu',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function applyUpdate() {
    if (!availableUpdate) {
        Swal.fire('Hata', 'Güncelleme bulunamadı', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Güncelleme Uygula',
        html: `
            <p>Sistem <strong>${availableUpdate.version || availableUpdate.latest}</strong> versiyonuna güncellenecek</p>
            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> Bu işlem:</p>
            <ul class="text-start">
                <li>Otomatik yedek oluşturacak</li>
                <li>Sistem güncellemelerini indirecek ve uygulayacak</li>
                <li>Sayfa yenilenmesi gerektirebilir</li>
            </ul>
            <p class="text-danger"><strong>Devam edilsin mi?</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, şimdi güncelle!',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Güncelleniyor...',
                html: `
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p id="progressMessage" class="text-muted">Başlıyor...</p>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    startProgressTracking();
                }
            });
            
            const version = availableUpdate.version || availableUpdate.latest;
            
            fetch(BASE_URL + '/api/system-update-apply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'version=' + encodeURIComponent(version)
            })
            .then(response => response.json())
            .then(data => {
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                
                if (data.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        html: `
                            <p>${data.message || 'Güncelleme başarıyla uygulandı'}</p>
                            <p class="text-success">Sistem ${data.data?.version || version} versiyonuna güncellendi</p>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: data.error || 'Güncelleme uygulanamadı',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                Swal.fire({
                    title: 'Hata!',
                    text: 'Ağ hatası oluştu',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function startProgressTracking() {
    progressInterval = setInterval(() => {
        fetch(BASE_URL + '/api/system-update-progress.php')
            .then(r => r.json())
            .then(data => {
                const progressBar = document.getElementById('progressBar');
                const progressMessage = document.getElementById('progressMessage');
                
                if (progressBar) {
                    progressBar.style.width = data.progress + '%';
                    progressBar.textContent = data.progress + '%';
                }
                
                if (progressMessage) {
                    progressMessage.textContent = data.message || 'İşleniyor...';
                }
                
                if (data.status === 'done' || data.status === 'error') {
                    clearInterval(progressInterval);
                }
            })
            .catch(error => {
                console.error('Progress tracking error:', error);
            });
    }, 1000);
}

function restoreBackup(backupId) {
    Swal.fire({
        title: 'Yedeği Geri Yükle',
        text: 'Sistem bu yedekten geri yüklenecek. Devam edilsin mi?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, geri yükle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Geri Yükleniyor...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(BASE_URL + '/api/system-backup-restore.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    backup_id: backupId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        text: data.message || 'Yedek başarıyla geri yüklendi',
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: data.error || 'Yedek geri yüklenemedi',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Hata!',
                    text: 'Ağ hatası oluştu',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function saveUpdateSettings() {
    const autoBackup = document.getElementById('autoBackup').checked;
    const updateChannel = document.getElementById('updateChannel').value;
    
    Swal.fire({
        title: 'Kaydediliyor...',
        text: 'Ayarlar güncelleniyor',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Save to database
    fetch(BASE_URL + '/api/system-update-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            autoBackup: autoBackup,
            updateChannel: updateChannel
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Also save to localStorage for offline access
            localStorage.setItem('autoBackup', autoBackup);
            localStorage.setItem('updateChannel', updateChannel);
            
            Swal.fire({
                title: 'Kaydedildi!',
                text: 'Güncelleme ayarları kaydedildi',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: data.error || 'Ayarlar kaydedilemedi',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Fallback to localStorage
        localStorage.setItem('autoBackup', autoBackup);
        localStorage.setItem('updateChannel', updateChannel);
        
        Swal.fire({
            title: 'Kaydedildi!',
            text: 'Ayarlar yerel olarak kaydedildi (çevrimdışı mod)',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    });
}

function uploadManualUpdate() {
    const fileInput = document.getElementById('manualUpdateFile');
    const file = fileInput.files[0];
    
    if (!file) {
        Swal.fire('Hata', 'Lütfen yüklenecek bir ZIP dosyası seçin', 'error');
        return;
    }
    
    if (!file.name.endsWith('.zip')) {
        Swal.fire('Hata', 'Lütfen geçerli bir ZIP dosyası seçin', 'error');
        return;
    }
    
    // Check file size (max 100MB)
    if (file.size > 100 * 1024 * 1024) {
        Swal.fire('Hata', 'Dosya boyutu çok büyük (max 100MB)', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Manuel Güncelleme',
        html: `
            <p>ZIP dosyası yüklenecek ve sistem güncellenecek</p>
            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> Bu işlem:</p>
            <ul class="text-start">
                <li>Otomatik yedek oluşturacak</li>
                <li>Dosyaları güncelleyecek</li>
                <li>Sayfa yenilenmesi gerektirebilir</li>
            </ul>
            <p class="text-danger"><strong>Devam edilsin mi?</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, yükle ve güncelle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Yükleniyor...',
                html: `
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p id="progressMessage" class="text-muted">Dosya yükleniyor...</p>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    startProgressTracking();
                }
            });
            
            const formData = new FormData();
            formData.append('update_file', file);
            
            fetch(BASE_URL + '/api/system-update-manual.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                
                if (data.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        html: `
                            <p>${data.message || 'Güncelleme başarıyla uygulandı'}</p>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: data.error || 'Güncelleme uygulanamadı',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                Swal.fire({
                    title: 'Hata!',
                    text: 'Ağ hatası oluştu',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Load settings from database
    loadSettings();
});

function loadSettings() {
    fetch(BASE_URL + '/api/system-settings-get.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const settings = data.data;
                
                if (settings.auto_backup !== undefined) {
                    document.getElementById('autoBackup').checked = settings.auto_backup === '1' || settings.auto_backup === true;
                }
                
                if (settings.update_channel) {
                    document.getElementById('updateChannel').value = settings.update_channel;
                }
                
                // Also save to localStorage
                localStorage.setItem('autoBackup', document.getElementById('autoBackup').checked);
                localStorage.setItem('updateChannel', document.getElementById('updateChannel').value);
            }
        })
        .catch(error => {
            console.log('Using localStorage settings');
            // Fallback to localStorage
            const autoBackup = localStorage.getItem('autoBackup');
            const updateChannel = localStorage.getItem('updateChannel');
            
            if (autoBackup !== null) {
                document.getElementById('autoBackup').checked = autoBackup === 'true';
            }
            
            if (updateChannel) {
                document.getElementById('updateChannel').value = updateChannel;
            }
        });
}