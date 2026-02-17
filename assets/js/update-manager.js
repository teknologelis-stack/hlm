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
                                <i class="bi bi-exclamation-triangle"></i> Update Available!
                            </h5>
                            <p><strong>Version ${updateData.latest}</strong> is now available!</p>
                            <p><small class="text-muted">Released: ${new Date(updateData.release_date).toLocaleDateString()}</small></p>
                            ${changesHtml ? '<hr><p class="mb-2"><strong>Changes:</strong></p>' + changesHtml : ''}
                        </div>
                    `;
                    
                    // Show action buttons
                    document.getElementById('createBackupBtn').style.display = 'inline-block';
                    document.getElementById('applyUpdateBtn').style.display = 'inline-block';
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            Your system is up to date! (Version ${updateData.current})
                        </div>
                    `;
                    
                    // Hide action buttons
                    document.getElementById('createBackupBtn').style.display = 'none';
                    document.getElementById('applyUpdateBtn').style.display = 'none';
                }
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle"></i> ${data.error || 'Failed to check for updates'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i> Network error occurred
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
        title: 'Create Backup',
        text: 'This will create a backup of your database. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, create backup',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754',
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Creating Backup...',
                text: 'Please wait while we backup your data',
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
                        title: 'Success!',
                        text: data.message || 'Backup created successfully',
                        icon: 'success',
                        confirmButtonColor: '#0d6efd'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.error || 'Failed to create backup',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Network error occurred',
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
        Swal.fire('Error', 'No update available', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Apply Update',
        html: `
            <p>You are about to update the system to version <strong>${availableUpdate.version || availableUpdate.latest}</strong></p>
            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> This will:</p>
            <ul class="text-start">
                <li>Create a pre-update backup</li>
                <li>Download and apply system updates</li>
                <li>May require a page refresh</li>
            </ul>
            <p class="text-danger"><strong>Continue?</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, update now!',
        cancelButtonText: 'Cancel',
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
                            <p>${data.message || 'Update applied successfully'}</p>
                            <p class="text-success">System updated to version ${data.data.version}</p>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: data.error || 'Failed to apply update',
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
                    text: 'Network error occurred',
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
        title: 'Restore Backup',
        text: 'This will restore the system from this backup. All current data will be replaced. Continue?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, restore',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Restoring...',
                text: 'Please wait while we restore your backup',
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
                        title: 'Success!',
                        text: data.message || 'Backup restored successfully',
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.error || 'Failed to restore backup',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Network error occurred',
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
        title: 'Saving...',
        text: 'Updating settings',
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
            title: 'Saved!',
            text: 'Update settings have been saved',
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
        Swal.fire('Error', 'Please select a ZIP file to upload', 'error');
        return;
    }
    
    if (!file.name.endsWith('.zip')) {
        Swal.fire('Error', 'Please select a valid ZIP file', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Upload Manual Update',
        html: `
            <p>You are about to install an update from <strong>${file.name}</strong></p>
            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> Make sure this is a valid HLM release!</p>
            <p>Continue?</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, upload and install',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ffc107',
    }).then((result) => {
        if (result.isConfirmed) {
            const progressDiv = document.getElementById('uploadProgress');
            progressDiv.style.display = 'block';
            
            // Create FormData
            const formData = new FormData();
            formData.append('update_file', file);
            
            // Show progress
            Swal.fire({
                title: 'Uploading...',
                html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            // Note: This would require a new API endpoint for manual uploads
            // For now, show a placeholder message
            setTimeout(() => {
                Swal.fire({
                    title: 'Not Implemented',
                    text: 'Manual upload feature requires additional backend implementation',
                    icon: 'info'
                });
                progressDiv.style.display = 'none';
            }, 1500);
            
            /* Production implementation would be:
            fetch(BASE_URL + '/api/system-update-upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progressDiv.style.display = 'none';
                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error!', data.error, 'error');
                }
            })
            .catch(error => {
                progressDiv.style.display = 'none';
                Swal.fire('Error!', 'Upload failed', 'error');
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
