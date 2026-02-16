<?php
function getSetting($key, $default = null) {
    $db = Database::getInstance();
    $setting = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $setting ? $setting['setting_value'] : $default;
}

function updateSetting($key, $value) {
    $db = Database::getInstance();
    return $db->update('settings', 
        ['setting_value' => $value], 
        'setting_key = :key', 
        ['key' => $key]
    );
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = $days . 'g';
    if ($hours > 0) $parts[] = $hours . 's';
    if ($minutes > 0) $parts[] = $minutes . 'd';
    
    return implode(' ', $parts);
}

function parseUptimeToSeconds($uptime) {
    $seconds = 0;
    
    // Parse MikroTik uptime format: "1w2d3h4m5s" or "2d3h4m5s" or "3h4m5s" etc.
    if (preg_match('/(\d+)w/', $uptime, $matches)) {
        $seconds += intval($matches[1]) * 604800; // weeks
    }
    if (preg_match('/(\d+)d/', $uptime, $matches)) {
        $seconds += intval($matches[1]) * 86400; // days
    }
    if (preg_match('/(\d+)h/', $uptime, $matches)) {
        $seconds += intval($matches[1]) * 3600; // hours
    }
    if (preg_match('/(\d+)m/', $uptime, $matches)) {
        $seconds += intval($matches[1]) * 60; // minutes
    }
    if (preg_match('/(\d+)s/', $uptime, $matches)) {
        $seconds += intval($matches[1]); // seconds
    }
    
    return $seconds;
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return $diff . ' saniye önce';
    if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 2592000) return floor($diff / 86400) . ' gün önce';
    
    return date('d.m.Y H:i', $timestamp);
}

function pagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) return '';
    
    $html = '<nav><ul class="pagination">';
    
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1">İlk</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage - 1) . '">Önceki</a></li>';
    }
    
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage + 1) . '">Sonraki</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">Son</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

function logAction($action, $details = null, $deviceId = null) {
    $db = Database::getInstance();
    
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $db->insert('logs', [
        'user_id' => $userId,
        'device_id' => $deviceId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ]);
}

/**
 * Log aktivite kaydet - wrapper for logAction
 */
function logActivity($userId, $action, $details = '', $deviceId = null) {
    try {
        $db = Database::getInstance();
        $data = [
            'user_id' => $userId,
            'device_id' => $deviceId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        $db->insert('logs', $data);
    } catch (Exception $e) {
        error_log("Log error: " . $e->getMessage());
    }
}

/**
 * IP adres doğrulama
 */
function isValidIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * MAC adres doğrulama
 */
function isValidMAC($mac) {
    return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac) === 1;
}

/**
 * Güvenli sayfa yönlendirme
 */
function safeRedirect($url) {
    // Validate URL is internal (relative path or same domain)
    if (!empty($url) && isset($url[0])) {
        // If it's a relative URL (starts with / or doesn't contain ://)
        if ($url[0] === '/' || strpos($url, '://') === false) {
            // Safe - it's a relative URL
        } else {
            // Check if it's the same domain
            $parsed = parse_url($url);
            $baseParsed = parse_url(BASE_URL);
            if (!$parsed || !isset($parsed['host']) || $parsed['host'] !== $baseParsed['host']) {
                error_log("Attempted redirect to external URL: " . $url);
                $url = BASE_URL; // Redirect to home instead
            }
        }
    } else {
        $url = BASE_URL;
    }
    
    if (headers_sent()) {
        // Use json_encode for safer JavaScript string embedding
        $safeUrl = json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo "<script>window.location.href=" . $safeUrl . ";</script>";
    } else {
        header("Location: " . $url);
    }
    exit;
}

/**
 * Uptime formatla (RouterOS formatından Türkçe'ye)
 * Örnek: 3d16h27m45s -> 3 gün 16:27:45
 * Örnek: 5w2d3h -> 5 hafta 2 gün 03:00:00
 * @param string $uptime
 * @return string
 */
function formatUptimeDisplay($uptime) {
    $uptime = trim($uptime);
    
    // Regex ile parçala (y: yıl, w: hafta, d: gün, h: saat, m: dakika, s: saniye)
    $pattern = '/(?:(\d+)y)?(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/';
    
    if (preg_match($pattern, $uptime, $matches)) {
        $years = intval($matches[1] ?? 0);
        $weeks = intval($matches[2] ?? 0);
        $days = intval($matches[3] ?? 0);
        $hours = intval($matches[4] ?? 0);
        $minutes = intval($matches[5] ?? 0);
        $seconds = intval($matches[6] ?? 0);
        
        $parts = [];
        
        if ($years > 0) {
            $parts[] = $years . ' yıl';
        }
        
        if ($weeks > 0) {
            $parts[] = $weeks . ' hafta';
        }
        
        if ($days > 0) {
            $parts[] = $days . ' gün';
        }
        
        // Saat:dakika:saniye formatı
        $timePart = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        
        // Build result and escape
        $result = !empty($parts) ? implode(' ', $parts) . ' ' . $timePart : $timePart;
        return htmlspecialchars($result);
    }
    
    return htmlspecialchars($uptime);
}
?>