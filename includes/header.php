<?php
if (!isset($auth)) {
    die('Auth required');
}
$currentUser = $auth->getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="bi bi-router" style="font-size: 36px; color: var(--primary-color);"></i>
                    <h4>MikroTik Panel</h4>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li class="<?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/pages/dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage == 'active-users' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/pages/active-users.php">
                            <i class="bi bi-people"></i>
                            <span>Aktif Kullanıcılar</span>
                        </a>
                    </li>
                    
                    <li class="menu-header">Router Ayarları</li>
                    
                    <li class="has-submenu <?php echo in_array($currentPage, ['ppp-settings', 'vlan-settings', 'ip-config', 'device-management', 'logs']) ? 'active' : ''; ?>">
                        <a href="#routerSubmenu" data-bs-toggle="collapse">
                            <i class="bi bi-hdd-network"></i>
                            <span>Router Ayarları</span>
                            <i class="bi bi-chevron-down arrow"></i>
                        </a>
                        <ul class="collapse submenu <?php echo in_array($currentPage, ['ppp-settings', 'vlan-settings', 'ip-config', 'device-management', 'logs']) ? 'show' : ''; ?>" id="routerSubmenu">
                            <li class="<?php echo $currentPage == 'ppp-settings' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/router-settings/ppp-settings.php">
                                    <i class="bi bi-person-badge"></i>
                                    <span>PPP Ayarları</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'vlan-settings' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/router-settings/vlan-settings.php">
                                    <i class="bi bi-diagram-3"></i>
                                    <span>VLAN Ayarları</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'ip-config' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/router-settings/ip-config.php">
                                    <i class="bi bi-ethernet"></i>
                                    <span>IP Yapılandırma</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'device-management' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/router-settings/device-management.php">
                                    <i class="bi bi-hdd-network"></i>
                                    <span>Cihaz Yönetimi</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'logs' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/router-settings/logs.php">
                                    <i class="bi bi-journal-text"></i>
                                    <span>Loglar</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="has-submenu <?php echo in_array($currentPage, ['fetch-device', 'device-list']) ? 'active' : ''; ?>">
                        <a href="#deviceSubmenu" data-bs-toggle="collapse">
                            <i class="bi bi-hdd-stack"></i>
                            <span>Device Settings</span>
                            <i class="bi bi-chevron-down arrow"></i>
                        </a>
                        <ul class="collapse submenu <?php echo in_array($currentPage, ['fetch-device', 'device-list']) ? 'show' : ''; ?>" id="deviceSubmenu">
                            <li class="<?php echo $currentPage == 'fetch-device' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/device-settings/fetch-device.php">
                                    <i class="bi bi-download"></i>
                                    <span>Cihaz Çek</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'device-list' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/device-settings/device-list.php">
                                    <i class="bi bi-list-ul"></i>
                                    <span>Cihaz Listele</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <?php if ($auth->hasPermission('settings_manage')): ?>
                    <li class="menu-header">Panel Yönetimi</li>
                    
                    <li class="has-submenu <?php echo in_array($currentPage, ['user-management', 'roles', 'settings']) ? 'active' : ''; ?>">
                        <a href="#panelSubmenu" data-bs-toggle="collapse">
                            <i class="bi bi-gear"></i>
                            <span>Panel Ayarları</span>
                            <i class="bi bi-chevron-down arrow"></i>
                        </a>
                        <ul class="collapse submenu <?php echo in_array($currentPage, ['user-management', 'roles', 'settings']) ? 'show' : ''; ?>" id="panelSubmenu">
                            <li class="<?php echo $currentPage == 'user-management' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/panel-settings/user-management.php">
                                    <i class="bi bi-person-gear"></i>
                                    <span>Kullanıcı Yönetimi</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'roles' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/panel-settings/roles.php">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Roller</span>
                                </a>
                            </li>
                            <li class="<?php echo $currentPage == 'settings' ? 'active' : ''; ?>">
                                <a href="<?php echo BASE_URL; ?>/pages/panel-settings/settings.php">
                                    <i class="bi bi-sliders"></i>
                                    <span>Ayarlar</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <div class="topbar">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="topbar-left">
                    <h5 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
                </div>
                
                <div class="topbar-right">
                    <div class="topbar-item">
                        <button class="btn btn-icon" id="fullscreenBtn">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                    </div>
                    
                    <div class="topbar-item">
                        <div class="dropdown">
                            <button class="btn btn-icon" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <span class="badge">3</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <h6 class="dropdown-header">Bildirimler</h6>
                                <a href="#" class="dropdown-item">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    <div>
                                        <strong>Sistem Güncellemesi</strong>
                                        <small>5 dakika önce</small>
                                    </div>
                                </a>
                                <a href="#" class="dropdown-item">
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                    <div>
                                        <strong>Bağlantı Uyarısı</strong>
                                        <small>1 saat önce</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="topbar-item">
                        <div class="dropdown">
                            <button class="user-dropdown" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                <span><?php echo clean($currentUser['username']); ?></span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="#" class="dropdown-item">
                                    <i class="bi bi-person"></i> Profil
                                </a>
                                <a href="#" class="dropdown-item">
                                    <i class="bi bi-gear"></i> Ayarlar
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Çıkış Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-wrapper">