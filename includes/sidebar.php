<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        
        <!-- Dashboard - Direct Link -->
        <div class="nav-section">
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <?php 
        $currentPage = basename($_SERVER['PHP_SELF']);
        $mikrotikPages = ['mikrotik.php', 'ip-blok.php', 'static-ip.php', 'local-ip.php', 'nat-olustur.php', 'logs.php'];
        $istasyonPages = ['ptp-cihazlar.php', 'ptmp-cihazlar.php', 'musteri-cihazlar.php'];
        $isEmriPages = ['is-emri-olustur.php', 'islemde-olanlar.php', 'kapali-is-emirleri.php'];
        $sistemPages = ['settings.php', 'users.php', 'roles.php', 'backups.php', 'update-manager.php', 'system-logs.php'];
        
        $mikrotikOpen = in_array($currentPage, $mikrotikPages) ? 'active' : '';
        $istasyonOpen = in_array($currentPage, $istasyonPages) ? 'active' : '';
        $isEmriOpen = in_array($currentPage, $isEmriPages) ? 'active' : '';
        $sistemOpen = in_array($currentPage, $sistemPages) ? 'active' : '';
        ?>

        <!-- Mikrotik İşlemleri - Accordion -->
        <div class="nav-section">
            <div class="nav-accordion <?php echo $mikrotikOpen; ?>" onclick="toggleAccordion(this)">
                <div class="nav-accordion-header">
                    <i class="fas fa-server"></i>
                    <span>Mikrotik İşlemleri</span>
                    <i class="fas fa-chevron-down nav-arrow"></i>
                </div>
            </div>
            <div class="nav-accordion-content" style="<?php echo $mikrotikOpen ? 'max-height: 500px;' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/mikrotik.php" class="nav-item <?php echo $currentPage == 'mikrotik.php' ? 'active' : ''; ?>">
                    <i class="fas fa-network-wired"></i>
                    <span>Mikrotik</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/ip-blok.php" class="nav-item <?php echo $currentPage == 'ip-blok.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ban"></i>
                    <span>IP Blok Listesi</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/static-ip.php" class="nav-item <?php echo $currentPage == 'static-ip.php' ? 'active' : ''; ?>">
                    <i class="fas fa-thumbtack"></i>
                    <span>Statik IP Blok</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/local-ip.php" class="nav-item <?php echo $currentPage == 'local-ip.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Lokal IP Blok</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/nat-olustur.php" class="nav-item <?php echo $currentPage == 'nat-olustur.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>NAT Kuralı Oluştur</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/logs.php" class="nav-item <?php echo $currentPage == 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Log Görüntüle</span>
                </a>
            </div>
        </div>

        <!-- İstasyon İşlemleri -->
        <div class="nav-section">
            <div class="nav-accordion <?php echo $istasyonOpen; ?>" onclick="toggleAccordion(this)">
                <div class="nav-accordion-header">
                    <i class="fas fa-wifi"></i>
                    <span>İstasyon İşlemleri</span>
                    <i class="fas fa-chevron-down nav-arrow"></i>
                </div>
            </div>
            <div class="nav-accordion-content" style="<?php echo $istasyonOpen ? 'max-height: 500px;' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/ptp-cihazlar.php" class="nav-item <?php echo $currentPage == 'ptp-cihazlar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram"></i>
                    <span>PTP Cihazlar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/ptmp-cihazlar.php" class="nav-item <?php echo $currentPage == 'ptmp-cihazlar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-broadcast-tower"></i>
                    <span>PTMP Cihazlar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/musteri-cihazlar.php" class="nav-item <?php echo $currentPage == 'musteri-cihazlar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Müşteri Cihazları</span>
                </a>
            </div>
        </div>

        <!-- İş Emirleri -->
        <div class="nav-section">
            <div class="nav-accordion <?php echo $isEmriOpen; ?>" onclick="toggleAccordion(this)">
                <div class="nav-accordion-header">
                    <i class="fas fa-tasks"></i>
                    <span>İş Emirleri</span>
                    <i class="fas fa-chevron-down nav-arrow"></i>
                </div>
            </div>
            <div class="nav-accordion-content" style="<?php echo $isEmriOpen ? 'max-height: 500px;' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/is-emri-olustur.php" class="nav-item <?php echo $currentPage == 'is-emri-olustur.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>İş Emri Oluştur</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/islemde-olanlar.php" class="nav-item <?php echo $currentPage == 'islemde-olanlar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-spinner"></i>
                    <span>İşlemde Olanlar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/kapali-is-emirleri.php" class="nav-item <?php echo $currentPage == 'kapali-is-emirleri.php' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span>Kapalı İş Emirleri</span>
                </a>
            </div>
        </div>

        <!-- Sistem -->
        <div class="nav-section">
            <div class="nav-accordion <?php echo $sistemOpen; ?>" onclick="toggleAccordion(this)">
                <div class="nav-accordion-header">
                    <i class="fas fa-cog"></i>
                    <span>Sistem</span>
                    <i class="fas fa-chevron-down nav-arrow"></i>
                </div>
            </div>
            <div class="nav-accordion-content" style="<?php echo $sistemOpen ? 'max-height: 500px;' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/settings.php" class="nav-item <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>Ayarlar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/users.php" class="nav-item <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Kullanıcılar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/roles.php" class="nav-item <?php echo $currentPage == 'roles.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Roller</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/backups.php" class="nav-item <?php echo $currentPage == 'backups.php' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i>
                    <span>Yedekleme</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/update-manager.php" class="nav-item <?php echo $currentPage == 'update-manager.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cloud-download-alt"></i>
                    <span>Güncelleme</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/system-logs.php" class="nav-item <?php echo $currentPage == 'system-logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Sistem Logları</span>
                </a>
            </div>
        </div>

    </nav>
    
    <div class="sidebar-footer">
        <div class="version-info">
            <i class="fas fa-code-branch"></i>
            <span>Versiyon <?php echo APP_VERSION; ?></span>
        </div>
    </div>
</aside>

<script>
function toggleAccordion(element) {
    // Toggle current accordion
    element.classList.toggle('active');
    const content = element.nextElementSibling;
    
    if (content.style.maxHeight) {
        content.style.maxHeight = null;
    } else {
        content.style.maxHeight = content.scrollHeight + "px";
    }
}
</script>
