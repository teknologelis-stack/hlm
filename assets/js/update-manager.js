/**
 * Update Manager JavaScript
 */

let availableUpdate = null;

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
                
                if (updateData.updates_available && updateData.updates.length > 0) {
                    // Store the update info
                    availableUpdate = updateData.updates[0];
                    
                    // Show update available message
                    let changesHtml = '';
                    if (availableUpdate.changes && availableUpdate.changes.length > 0) {
                        changesHtml = '<ul class="mb-0">';
                        availableUpdate.changes.forEach(change => {
                            changesHtml += `<li>${change}</li>`;
                        });
                        changesHtml += '</ul>';
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">
                                <i class="bi bi-exclamation-triangle"></i> Update Available!
                            </h5>
                            <p><strong>Version ${availableUpdate.version}</strong> is now available!</p>
                            <p><small class="text-muted">Released: ${availableUpdate.released_at}</small></p>
                            <hr>
                            <p class="mb-2"><strong>Changes:</strong></p>
                            ${changesHtml}
                        </div>
                    `;
                    
                    // Show action buttons
                    document.getElementById('createBackupBtn').style.display = 'inline-block';
                    document.getElementById('applyUpdateBtn').style.display = 'inline-block';
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            Your system is up to date! (Version ${updateData.current_version})
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
 * Apply update
 */
function applyUpdate() {
    if (!availableUpdate) {
        Swal.fire('Error', 'No update available', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Apply Update',
        html: `
            <p>You are about to update the system to version <strong>${availableUpdate.version}</strong></p>
            <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> This will:</p>
            <ul class="text-start">
                <li>Create a pre-update backup</li>
                <li>Apply system updates</li>
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
            // Show loading
            Swal.fire({
                title: 'Applying Update...',
                html: `
                    <p>Please wait while we update your system</p>
                    <p class="text-muted"><small>This may take a few moments...</small></p>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
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
                    version: availableUpdate.version
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        html: `
                            <p>${data.message || 'Update applied successfully'}</p>
                            <p class="text-success">System updated to version ${data.data.new_version}</p>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.error || 'Failed to apply update',
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
