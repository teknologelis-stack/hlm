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
    
    // Disable button and show loading
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
                
                // Update last checked time
                document.getElementById('lastChecked').textContent = new Date().toLocaleString();
                
                if (updateData.available) {
                    // Store the update info
                    availableUpdate = updateData;
                    
                    // Show update available message
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
                    
                    // Show action buttons
                    document.getElementById('createBackupBtn').style.display = 'inline-block';
                    document.getElementById('applyUpdateBtn').style.display = 'inline-block';
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            Sisteminiz güncel! (Versiyon ${updateData.current})
                        </div>
                    `;
                    
                    // Hide action buttons
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
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search"></i> Check for Updates';
        });
}

/**
 * Create a backup
 */
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
            // Show loading
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

/**
 * Apply update with progress tracking
 */
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
            // Show progress dialog
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
            
            // Start update
            const version = availableUpdate.version || availableUpdate.latest;
            fetch(BASE_URL + '/api/system-update-apply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    version: version
                })
            })
            .then(response => response.json())
            .then(data => {
                // Stop progress tracking
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                
                if (data.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        html: `
                            <p>${data.message || 'Güncelleme başarıyla uygulandı'}</p>
                            <p class="text-success">Sistem ${data.data.version} versiyonuna güncellendi</p>
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

/**
 * Start progress tracking
 */
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
                
                // Stop tracking if done or error
                if (data.status === 'done' || data.status === 'error') {
                    clearInterval(progressInterval);
                }
            })
            .catch(error => {
                console.error('Progress tracking error:', error);
            });
    }, 1000);
}

/**
 * Restore backup
 */
function restoreBackup(backupId) {
    Swal.fire({
        title: 'Yedeği Geri Yükle',
        text: 'Sistem bu yedekten geri yüklenecek. Mevcut tüm veriler değiştirilecek. Devam edilsin mi?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, geri yükle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Geri Yükleniyor...',
                text: 'Yedeğiniz geri yükleniyor, lütfen bekleyin',
                allowOutsideClick: false,
                allowEscapeKey: false,
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

/**
 * Save update settings
 */
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
    
    // In a real implementation, this would save to the backend
    // For now, we'll just store in localStorage
    localStorage.setItem('autoBackup', autoBackup);
    localStorage.setItem('updateChannel', updateChannel);
    
    setTimeout(() => {
        Swal.fire({
            title: 'Kaydedildi!',
            text: 'Güncelleme ayarları kaydedildi',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }, 500);
}

/**
 * Upload manual update
 */
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
    
    Swal.fire({
        title: 'Manuel Güncelleme Yükle',
        html: `
            <p><strong>${file.name}</strong> dosyasından güncelleme yüklenecek</p>
            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> Bunun geçerli bir HLM sürümü olduğundan emin olun!</p>
            <p>Devam edilsin mi?</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, yükle ve kur',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#ffc107',
    }).then((result) => {
        if (result.isConfirmed) {
            const progressDiv = document.getElementById('uploadProgress');
            progressDiv.style.display = 'block';
            
            // Show progress
            Swal.fire({
                title: 'Yükleniyor...',
                html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            // Note: This feature requires backend implementation
            setTimeout(() => {
                Swal.fire({
                    title: 'Uyarı',
                    text: 'Manuel yükleme özelliği ek backend geliştirmesi gerektirmektedir. Şimdilik GitHub üzerinden otomatik güncelleme kullanabilirsiniz.',
                    icon: 'info'
                });
                progressDiv.style.display = 'none';
            }, 1500);
            
            /* Production implementation would be:
            const formData = new FormData();
            formData.append('update_file', file);
            
            fetch(BASE_URL + '/api/system-update-upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progressDiv.style.display = 'none';
                if (data.success) {
                    Swal.fire('Başarılı!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Hata!', data.error, 'error');
                }
            })
            .catch(error => {
                progressDiv.style.display = 'none';
                Swal.fire('Hata!', 'Yükleme başarısız', 'error');
            });
            */
        }
    });
}

// Load saved settings on page load
document.addEventListener('DOMContentLoaded', function() {
    const autoBackup = localStorage.getItem('autoBackup');
    const updateChannel = localStorage.getItem('updateChannel');
    
    if (autoBackup !== null) {
        document.getElementById('autoBackup').checked = autoBackup === 'true';
    }
    
    if (updateChannel) {
        document.getElementById('updateChannel').value = updateChannel;
    }
});
