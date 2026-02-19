<?php
// Session and Auth should be initialized by the calling page before including this header
// This ensures proper control over timing and avoids conflicts
if (!isset($auth) || !isset($currentUser)) {
    // Fallback if not initialized by parent page
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../includes/auth.php';
    if (!isset($auth)) {
        $auth = new Auth();
    }
    $currentUser = $auth->getCurrentUser();
}

// Default display values
$displayUsername = htmlspecialchars($currentUser['username'] ?? 'User');
$displayRole = htmlspecialchars($currentUser['role_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'MikroTik Panel'; ?> - HLM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php if (!empty($extraCss)) echo $extraCss; ?>
</head>
<body>
    <!-- HEADER WITH SEARCH -->
    <header class="main-header">
        <div class="header-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <i class="fas fa-network-wired"></i>
                <span>HLM Panel</span>
                <span class="version-badge">v<?php echo APP_VERSION; ?></span>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="header-search">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="globalSearch" class="search-input" placeholder="Sayfa ara..." autocomplete="off">
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <button class="user-button" onclick="toggleUserMenu()">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $displayUsername; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-info">
                        <strong><?php echo $displayUsername; ?></strong>
                        <small><?php echo $displayRole; ?></small>
                    </div>
                    <hr>
                    <a href="<?php echo BASE_URL; ?>/pages/profile.php">
                        <i class="fas fa-user"></i> Profil
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/settings.php">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a>
                    <hr>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </header>

    <script>
    // Search functionality
    const searchPages = [
        { name: 'Dashboard', url: '<?php echo BASE_URL; ?>/pages/dashboard.php', icon: 'fa-home' },
        { name: 'Mikrotik', url: '<?php echo BASE_URL; ?>/pages/mikrotik.php', icon: 'fa-server' },
        { name: 'IP Blok Listesi', url: '<?php echo BASE_URL; ?>/pages/ip-blok.php', icon: 'fa-ban' },
        { name: 'Statik IP Blok', url: '<?php echo BASE_URL; ?>/pages/static-ip.php', icon: 'fa-thumbtack' },
        { name: 'Lokal IP Blok', url: '<?php echo BASE_URL; ?>/pages/local-ip.php', icon: 'fa-building' },
        { name: 'NAT Kuralı Oluştur', url: '<?php echo BASE_URL; ?>/pages/nat-olustur.php', icon: 'fa-exchange-alt' },
        { name: 'Log Görüntüle', url: '<?php echo BASE_URL; ?>/pages/logs.php', icon: 'fa-clipboard-list' },
        { name: 'PTP Cihazlar', url: '<?php echo BASE_URL; ?>/pages/ptp-cihazlar.php', icon: 'fa-point-to-point' },
        { name: 'PTMP Cihazlar', url: '<?php echo BASE_URL; ?>/pages/ptmp-cihazlar.php', icon: 'fa-broadcast-tower' },
        { name: 'Müşteri Cihazları', url: '<?php echo BASE_URL; ?>/pages/musteri-cihazlar.php', icon: 'fa-users' },
        { name: 'İş Emri Oluştur', url: '<?php echo BASE_URL; ?>/pages/is-emri-olustur.php', icon: 'fa-plus-circle' },
        { name: 'İşlemde Olanlar', url: '<?php echo BASE_URL; ?>/pages/islemde-olanlar.php', icon: 'fa-spinner' },
        { name: 'Kapalı İş Emirleri', url: '<?php echo BASE_URL; ?>/pages/kapali-is-emirleri.php', icon: 'fa-check-circle' },
        { name: 'Ayarlar', url: '<?php echo BASE_URL; ?>/pages/settings.php', icon: 'fa-sliders-h' },
        { name: 'Kullanıcılar', url: '<?php echo BASE_URL; ?>/pages/users.php', icon: 'fa-user-cog' },
        { name: 'Roller', url: '<?php echo BASE_URL; ?>/pages/roles.php', icon: 'fa-user-shield' },
        { name: 'Yedekleme', url: '<?php echo BASE_URL; ?>/pages/backups.php', icon: 'fa-database' },
        { name: 'Güncelleme', url: '<?php echo BASE_URL; ?>/pages/update-manager.php', icon: 'fa-cloud-download-alt' },
        { name: 'Sistem Logları', url: '<?php echo BASE_URL; ?>/pages/system-logs.php', icon: 'fa-file-alt' }
    ];

    document.getElementById('globalSearch').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const resultsContainer = document.getElementById('searchResults');
        
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return;
        }
        
        const matches = searchPages.filter(page => 
            page.name.toLowerCase().includes(query)
        );
        
        if (matches.length > 0) {
            let html = '';
            matches.forEach(page => {
                html += `<a href="${page.url}" class="search-result-item">
                    <i class="fas ${page.icon}"></i>
                    <span>${page.name}</span>
                </a>`;
            });
            resultsContainer.innerHTML = html;
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.innerHTML = '<div class="search-no-results">Sonuç bulunamadı</div>';
            resultsContainer.style.display = 'block';
        }
    });

    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-search')) {
            document.getElementById('searchResults').style.display = 'none';
        }
    });
    </script>

    <div class="layout-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-wrapper">
