/**
 * Güncelleme Yöneticisi JavaScript
 * HLM - Hospital Laboratory Management
 */

let updateData = null;

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Offline form dinleyicisi
    const offlineForm = document.getElementById('offlineUpdateForm');
    if (offlineForm) {
        offlineForm.addEventListener('submit', handleOfflineUpdate);
    }
    
    // Restore form dinleyicisi
    const restoreForm = document.getElementById('restoreBackupForm');
    if (restoreForm) {
        restoreForm.addEventListener('submit', handleRestoreBackup);
    }
    
    // Config import form
    const importForm = document.getElementById('importConfigForm');
    if (importForm) {
        importForm.addEventListener('submit', handleConfigImport);
        
        // Dosya değiştiğinde CSV tipini göster/gizle
        const fileInput = importForm.querySelector('input[type="file"]');
        fileInput.addEventListener('change', function() {
            const fileName = this.value;
            const csvTypeDiv = document.getElementById('csvTypeDiv');
            if (fileName.toLowerCase().endsWith('.csv')) {
                csvTypeDiv.style.display = 'block';
            } else {
                csvTypeDiv.style.display = 'none';
            }
        });
    }
});

/**
 * Online güncelleme kontrolü
 */
function checkForUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const updateInfo = document.getElementById('updateInfo');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Kontrol ediliyor...';
    
    fetch(BASE_URL + '/api/system-update-check.php')
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                showToast(result.message, 'error');
                return;
            }
            
            const data = result.data;
            updateData = data;
            
            if (data.available) {
                updateInfo.innerHTML = `
                    <div class="alert alert-success update-available">
                        <h5><i class="bi bi-cloud-download"></i> Yeni Güncelleme Mevcut!</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Mevcut Versiyon:</strong> <span class="version-badge">${data.current}</span></p>
                                <p class="mb-2"><strong>Yeni Versiyon:</strong> <span class="version-badge update-available">${data.latest}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Yayın Tarihi:</strong> ${formatDate(data.published_at)}</p>
                                <p class="mb-2"><strong>Yayın Adı:</strong> ${data.release_name}</p>
                            </div>
                        </div>
                        ${data.changelog ? `
                            <div class="changelog mt-3">
                                <h6>Değişiklik Notları:</h6>
                                <div class="changelog-content">${formatChangelog(data.changelog)}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                document.getElementById('applyUpdateBtn').style.display = 'inline-block';
            } else {
                updateInfo.innerHTML = `
                    <div class="alert alert-info">
                        <h5><i class="bi bi-check-circle"></i> Sistem Güncel</h5>
                        <p class="mb-0">Mevcut versiyon: <strong>${data.current}</strong></p>
                        <p class="mb-0 mt-2 text-muted small">${data.message}</p>
                    </div>
                `;
                
                document.getElementById('applyUpdateBtn').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Update check error:', error);
            updateInfo.innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle"></i> Hata</h5>
                    <p class="mb-0">Güncelleme kontrolü başarısız: ${error.message}</p>
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-download"></i> Güncelleme Kontrol Et';
        });
}

/**
 * Online güncelleme uygula
 */
function applyOnlineUpdate() {
    if (!updateData || !updateData.available) {
        showToast('Önce güncelleme kontrolü yapın', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Güncelleme Uygulanacak',
        html: `
            <p>Sistem <strong>v${updateData.current}</strong> versiyonundan <strong>v${updateData.latest}</strong> versiyonuna güncellenecek.</p>
            <div class="alert alert-warning mt-3 text-start">
                <p class="mb-1"><strong>⚠️ Önemli Uyarılar:</strong></p>
                <ul class="small mb-0">
                    <li>Otomatik yedek oluşturulacaktır</li>
                    <li>Güncelleme 2-5 dakika sürebilir</li>
                    <li>Bu süre zarfında sistemi kapatmayın</li>
                </ul>
            </div>
            <p class="mt-3">Devam etmek istiyor musunuz?</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Güncelle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            performUpdate();
        }
    });
}

function performUpdate() {
    Swal.fire({
        title: 'Güncelleme Uygulanıyor',
        html: `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                <p>Lütfen bekleyin...</p>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         style="width: 100%"></div>
                </div>
                <p class="text-muted small mt-2">Bu işlem birkaç dakika sürebilir</p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(BASE_URL + '/api/system-update-apply.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            source: 'online',
            package_url: updateData.download_url,
            version: updateData.latest
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                title: 'Başarılı!',
                html: `
                    <p>Sistem başarıyla güncellendi!</p>
                    <p class="mt-2"><strong>Yeni Versiyon:</strong> ${result.new_version}</p>
                    <p class="text-muted small">Sayfa yenileniyor...</p>
                `,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: result.message,
                icon: 'error',
                confirmButtonText: 'Tamam'
            });
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        Swal.fire({
            title: 'Hata!',
            text: 'Güncelleme sırasında hata oluştu: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Tamam'
        });
    });
}

/**
 * Offline güncelleme
 */
function handleOfflineUpdate(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const version = document.getElementById('updateVersion').value;
    formData.append('version', version);
    
    Swal.fire({
        title: 'Güncelleme Yükleniyor',
        html: '<div class="spinner-border text-primary"></div><p class="mt-3">Dosya yükleniyor ve uygulanıyor...</p>',
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    fetch(BASE_URL + '/api/system-update-apply.php', {
        method: 'POST',
        body: formData
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
                window.location.reload();
            });
        } else {
            Swal.fire('Hata!', result.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', 'Güncelleme başarısız: ' + error.message, 'error');
    });
}

/**
 * Yedek oluştur
 */
function createBackup() {
    const description = document.getElementById('backupDescription').value;
    const btn = document.getElementById('createBackupBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Oluşturuluyor...';
    
    fetch(BASE_URL + '/api/system-backup-create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ description: description })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                title: 'Başarılı!',
                html: `
                    <p>Yedek başarıyla oluşturuldu!</p>
                    <p class="mt-2"><strong>Dosya:</strong> ${result.backup_name}</p>
                    <p><strong>Boyut:</strong> ${(result.file_size / 1024 / 1024).toFixed(2)} MB</p>
                `,
                icon: 'success',
                confirmButtonText: 'Tamam'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Hata!', result.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', 'Yedek oluşturulamadı: ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-archive"></i> Yedek Oluştur';
    });
}

/**
 * Yedek geri yükle
 */
function handleRestoreBackup(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Emin misiniz?',
        html: `
            <p>Yedek geri yükleme mevcut verilerin <strong>üzerine yazacaktır</strong>.</p>
            <div class="alert alert-danger mt-3 text-start">
                <p class="mb-0"><strong>⚠️ Dikkat:</strong> Bu işlem geri alınamaz!</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Geri Yükle',
        cancelButtonText: 'İptal',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(this);
            
            Swal.fire({
                title: 'Geri Yükleniyor',
                html: '<div class="spinner-border text-warning"></div><p class="mt-3">Lütfen bekleyin...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            fetch(BASE_URL + '/api/system-backup-restore.php', {
                method: 'POST',
                body: formData
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
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Hata!', result.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', 'Geri yükleme başarısız: ' + error.message, 'error');
            });
        }
    });
}

/**
 * Yapılandırma export
 */
function exportConfig(format, type = 'settings') {
    let url = BASE_URL + '/api/config-export.php?format=' + format;
    if (format === 'csv') {
        url += '&type=' + type;
    }
    
    showToast('Export başlatılıyor...', 'info');
    window.location.href = url;
}

/**
 * Yapılandırma import
 */
function handleConfigImport(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    Swal.fire({
        title: 'İçe Aktarılıyor',
        html: '<div class="spinner-border text-success"></div><p class="mt-3">Yapılandırma yükleniyor...</p>',
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    fetch(BASE_URL + '/api/config-import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                title: 'Başarılı!',
                html: `
                    <p>Yapılandırma içe aktarıldı!</p>
                    <p class="mt-2"><strong>İçe Aktarılan:</strong> ${result.imported}</p>
                    <p><strong>Atlanan:</strong> ${result.skipped}</p>
                    ${result.errors && result.errors.length > 0 ? 
                        `<div class="alert alert-warning mt-3 text-start">
                            <strong>Hatalar:</strong>
                            <ul class="small mb-0">
                                ${result.errors.slice(0, 5).map(e => '<li>' + e + '</li>').join('')}
                            </ul>
                        </div>` : ''
                    }
                `,
                icon: 'success',
                confirmButtonText: 'Tamam'
            });
            
            this.reset();
        } else {
            Swal.fire('Hata!', result.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', 'İçe aktarma başarısız: ' + error.message, 'error');
    });
}

/**
 * Yedek indir
 */
function downloadBackup(backupId) {
    showToast('İndirme başlatılıyor...', 'info');
    // Bu fonksiyon için ayrı bir API endpoint gerekebilir
    // Şimdilik basit bir çözüm:
    window.location.href = BASE_URL + '/api/download-backup.php?id=' + backupId;
}

/**
 * Sistem durumunu yenile
 */
function checkSystemStatus() {
    showToast('Durum kontrol ediliyor...', 'info');
    window.location.reload();
}

/**
 * Yardımcı fonksiyonlar
 */
function formatDate(dateString) {
    if (!dateString) return 'Bilinmiyor';
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatChangelog(changelog) {
    // Markdown'ı basit HTML'e çevir
    return changelog
        .replace(/### (.*)/g, '<h6>$1</h6>')
        .replace(/## (.*)/g, '<h5>$1</h5>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\n- /g, '<li>')
        .replace(/\n/g, '<br>');
}

function showToast(message, type = 'info') {
    const iconMap = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: iconMap[type] || 'info',
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}
