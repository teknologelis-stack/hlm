<?php
/**
 * API: Fetch MikroTik Device Data
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');
ini_set('display_errors', 0);

try {
    $db = Database::getInstance()->getConnection();
    
    $deviceId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$deviceId) {
        $stmt = $db->query("SELECT * FROM mikrotik_devices");
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($devices as $device) {
            $results[] = updateDevice($db, $device);
        }
        
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        $stmt = $db->prepare("SELECT * FROM mikrotik_devices WHERE id = ?");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Cihaz bulunamadı']);
            exit();
        }
        
        $result = updateDevice($db, $device);
        echo json_encode(['success' => true, 'data' => $result]);
    }
    
} catch (Exception $e) {
    error_log("[API:mikrotik-fetch] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function formatUptime($uptime) {
    if (empty($uptime)) return '-';
    
    preg_match('/(\d+)w(\d+)d(\d+)h(\d+)m(\d+)s/', $uptime, $matches);
    
    if (empty($matches)) {
        return $uptime;
    }
    
    $weeks = (int)$matches[1];
    $days = (int)$matches[2];
    $hours = (int)$matches[3];
    $minutes = (int)$matches[4];
    $seconds = (int)$matches[5];
    
    $years = floor($weeks / 52);
    $remainingWeeks = $weeks % 52;
    $months = floor($remainingWeeks / 4);
    $weeksOnly = $remainingWeeks % 4;
    
    $parts = [];
    
    if ($years > 0) {
        $parts[] = $years . ' Yıl';
    }
    if ($months > 0) {
        $parts[] = $months . ' Ay';
    }
    if ($weeksOnly > 0) {
        $parts[] = $weeksOnly . ' Hafta';
    }
    if ($days > 0) {
        $parts[] = $days . ' Gün';
    }
    
    $timePart = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    $parts[] = $timePart;
    
    return implode(' ', $parts);
}

function updateDevice($db, $device) {
    $result = [
        'id' => $device['id'],
        'name' => $device['name'],
        'ip_address' => $device['ip_address'],
        'status' => 'offline',
        'cpu' => 0,
        'ram' => 0,
        'uptime' => '',
        'ros_version' => '',
        'pppoe_count' => 0,
        'interface_count' => 0
    ];
    
    try {
        $client = new Client([
            'host' => $device['ip_address'],
            'user' => $device['username'],
            'pass' => $device['password'],
            'port' => (int)$device['port'] ?: 8728,
            'timeout' => 10,
        ]);
        
        $query = new Query('/system/resource/print');
        $resource = $client->query($query)->read();
        
        if ($resource && count($resource) > 0) {
            $r = $resource[0];
            $result['cpu'] = (int)($r['cpu-load'] ?? 0);
            $totalMem = (int)($r['total-memory'] ?? 1);
            $usedMem = (int)($r['used-memory'] ?? 0);
            $result['ram'] = $totalMem > 0 ? round(($usedMem / $totalMem) * 100) : 0;
            $result['uptime'] = formatUptime($r['uptime'] ?? '');
            $result['ros_version'] = $r['version'] ?? '';
            $result['status'] = 'online';
        }
        
        $query = new Query('/ppp/active/print');
        $pppoe = $client->query($query)->read();
        
        // Sadece @ içeren kullanıcıları say
        $filteredPppoe = array_filter($pppoe ?? [], function($p) {
            return isset($p['name']) && strpos($p['name'], '@') !== false;
        });
        $result['pppoe_count'] = count($filteredPppoe);
        
        $query = new Query('/interface/print');
        $interfaces = $client->query($query)->read();
        $result['interface_count'] = $interfaces ? count($interfaces) : 0;
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        $result['status'] = 'offline';
    }
    
    $stmt = $db->prepare("UPDATE mikrotik_devices SET 
        status = ?, 
        cpu_usage = ?, 
        ram_usage = ?, 
        uptime = ?,
        ros_version = ?, 
        pppoe_count = ?, 
        interface_count = ?,
        last_connect = NOW() 
        WHERE id = ?");
    
    $stmt->execute([
        $result['status'],
        $result['cpu'],
        $result['ram'],
        $result['uptime'],
        $result['ros_version'],
        $result['pppoe_count'],
        $result['interface_count'],
        $device['id']
    ]);
    
    return $result;
}
