<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM mikrotik_devices WHERE id = 2");
$device = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Device: " . $device['name'] . "\n";
echo "IP: " . $device['ip_address'] . "\n";
echo "Username: " . $device['username'] . "\n";
echo "Password: " . $device['password'] . "\n";
echo "Port: " . $device['port'] . "\n\n";

try {
    echo "Connecting to MikroTik...\n";
    
    $client = new Client([
        'host' => $device['ip_address'],
        'user' => $device['username'],
        'pass' => $device['password'],
        'port' => (int)$device['port'] ?: 8728,
        'timeout' => 10,
    ]);
    
    echo "Connected! Fetching data...\n\n";
    
    // System Resource
    $query = new Query('/system/resource/print');
    $resource = $client->query($query)->read();
    
    if ($resource && count($resource) > 0) {
        $r = $resource[0];
        echo "CPU: " . ($r['cpu-load'] ?? 'N/A') . "\n";
        
        $totalMem = (int)($r['total-memory'] ?? 1);
        $usedMem = (int)($r['used-memory'] ?? 0);
        $ram = $totalMem > 0 ? round(($usedMem / $totalMem) * 100) : 0;
        echo "RAM: {$ram}%\n";
        echo "Uptime: " . ($r['uptime'] ?? 'N/A') . "\n";
        echo "ROS Version: " . ($r['version'] ?? 'N/A') . "\n";
    }
    
    // PPPoE Active
    $query = new Query('/ppp/active/print');
    $pppoe = $client->query($query)->read();
    echo "PPPoE Count: " . ($pppoe ? count($pppoe) : 0) . "\n";
    
    // Interfaces
    $query = new Query('/interface/print');
    $interfaces = $client->query($query)->read();
    echo "Interface Count: " . ($interfaces ? count($interfaces) : 0) . "\n";
    
    echo "\n✓ BAŞARILI!\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
