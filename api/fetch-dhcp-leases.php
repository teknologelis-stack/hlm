<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mikrotik.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

if (!$auth->hasPermission('devices_manage')) {
    jsonResponse(['success' => false, 'message' => 'Yetkiniz yok'], 403);
}

$db = Database::getInstance();

try {
    // Connect to main device
    $mikrotik = new MikroTikHelper();
    
    if (!$mikrotik->device) {
        jsonResponse(['success' => false, 'message' => 'Ana cihaz bulunamadı. Lütfen önce bir ana cihaz tanımlayın.']);
    }
    
    if (!$mikrotik->connect()) {
        jsonResponse(['success' => false, 'message' => 'Ana cihaza bağlanılamadı: ' . $mikrotik->error]);
    }
    
    // Fetch DHCP leases
    $leases = $mikrotik->client->query('/ip/dhcp-server/lease/print')->read();
    
    if (empty($leases)) {
        jsonResponse(['success' => true, 'message' => 'DHCP lease bulunamadı', 'stats' => [
            'total' => 0,
            'new' => 0,
            'updated' => 0
        ], 'devices' => []]);
    }
    
    $stats = [
        'total' => count($leases),
        'new' => 0,
        'updated' => 0
    ];
    
    $devices = [];
    
    foreach ($leases as $lease) {
        $macAddress = $lease['mac-address'] ?? null;
        $ipAddress = $lease['address'] ?? null;
        
        if (!$macAddress || !$ipAddress) {
            continue;
        }
        
        $hostname = $lease['host-name'] ?? null;
        $server = $lease['server'] ?? null;
        $status = isset($lease['status']) ? $lease['status'] : 'waiting';
        
        // Check if device already exists
        $existing = $db->fetchOne(
            "SELECT * FROM detected_devices WHERE mac_address = ?",
            [$macAddress]
        );
        
        $deviceData = [
            'mac_address' => $macAddress,
            'ip_address' => $ipAddress,
            'hostname' => $hostname,
            'interface' => null,
            'server' => $server,
            'status' => $status,
            'last_seen' => date('Y-m-d H:i:s'),
            'detected_from_device_id' => $mikrotik->device['id']
        ];
        
        if ($existing) {
            // Update existing device
            $db->update(
                'detected_devices',
                $deviceData,
                'mac_address = :mac',
                ['mac' => $macAddress]
            );
            $stats['updated']++;
        } else {
            // Insert new device
            $deviceData['first_detected'] = date('Y-m-d H:i:s');
            $db->insert('detected_devices', $deviceData);
            $stats['new']++;
        }
        
        $devices[] = $deviceData;
    }
    
    logAction('dhcp_leases_fetched', "Fetched " . $stats['total'] . " DHCP leases from main device", $mikrotik->device['id']);
    
    jsonResponse([
        'success' => true,
        'message' => 'DHCP lease\'ler başarıyla çekildi',
        'stats' => $stats,
        'devices' => $devices
    ]);
    
} catch (Exception $e) {
    logAction('dhcp_leases_fetch_failed', 'Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
