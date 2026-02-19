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
    <!-- 🎉 v1.0.2 HEADER -->
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

    <div class="layout-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-wrapper">
