<!-- 🎉 v1.0.2 SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">ANA MENÜ</div>
            
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/devices.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'devices.php' ? 'active' : ''; ?>">
                <i class="fas fa-server"></i>
                <span>Cihazlar</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/hotspots.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hotspots.php' ? 'active' : ''; ?>">
                <i class="fas fa-wifi"></i>
                <span>Hotspot</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">YÖNETİM</div>
            
            <a href="<?php echo BASE_URL; ?>/pages/users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Kullanıcılar</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/roles.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>Roller</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/logs.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Loglar</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">SİSTEM</div>
            
            <a href="<?php echo BASE_URL; ?>/pages/update-manager.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'update-manager.php' ? 'active' : ''; ?>">
                <i class="fas fa-cloud-download-alt"></i>
                <span>Güncelleme</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/backups.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'backups.php' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i>
                <span>Yedekler</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Ayarlar</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="version-info">
            <i class="fas fa-code-branch"></i>
            <span>Versiyon 1.0.2</span>
        </div>
    </div>
</aside>
