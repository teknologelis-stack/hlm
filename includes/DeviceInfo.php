<?php

class DeviceInfo {
    
    // Device type detection patterns
    const ROUTER_PATTERNS = ['ccr', 'rb'];
    const SWITCH_PATTERNS = ['crs', 'css', 'switch'];
    const AP_PATTERNS = ['cap', 'wap', 'ap'];
    
    /**
     * Cihaz bilgilerini RouterOS API'den çeker
     * @param array $device
     * @return array|null
     */
    public static function fetchDeviceInfo($device) {
        try {
            $client = new \RouterOS\Client([
                'host' => $device['ip_address'],
                'user' => $device['username'],
                'pass' => decrypt($device['password']),
                'port' => $device['port'],
            ]);
            
            // Identity
            $identityQuery = new \RouterOS\Query('/system/identity/print');
            $identityResponse = $client->query($identityQuery)->read();
            
            // Resource
            $resourceQuery = new \RouterOS\Query('/system/resource/print');
            $resourceResponse = $client->query($resourceQuery)->read();
            
            $info = [
                'identity' => $identityResponse[0]['name'] ?? $device['name'],
                'routeros_version' => $resourceResponse[0]['version'] ?? 'Unknown',
                'board_name' => $resourceResponse[0]['board-name'] ?? 'Unknown',
                'uptime' => $resourceResponse[0]['uptime'] ?? '0s',
                'cpu_load' => intval($resourceResponse[0]['cpu-load'] ?? 0),
                'memory_total' => intval($resourceResponse[0]['total-memory'] ?? 0),
                'memory_free' => intval($resourceResponse[0]['free-memory'] ?? 0),
                'last_seen' => date('Y-m-d H:i:s'),
                'device_type' => self::detectDeviceType($resourceResponse[0]['board-name'] ?? '')
            ];
            
            return $info;
            
        } catch (Exception $e) {
            error_log("DeviceInfo fetch error for {$device['ip_address']}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Board name'den cihaz tipini tespit eder
     * @param string $boardName
     * @return string
     */
    private static function detectDeviceType($boardName) {
        $boardName = strtolower($boardName);
        
        foreach (self::ROUTER_PATTERNS as $pattern) {
            if (strpos($boardName, $pattern) !== false) {
                return 'router';
            }
        }
        
        foreach (self::SWITCH_PATTERNS as $pattern) {
            if (strpos($boardName, $pattern) !== false) {
                return 'switch';
            }
        }
        
        foreach (self::AP_PATTERNS as $pattern) {
            if (strpos($boardName, $pattern) !== false) {
                return 'ap';
            }
        }
        
        return 'other';
    }
    
    /**
     * Tüm cihazların bilgilerini günceller
     * @param Database $db
     * @return array
     */
    public static function updateAllDevices($db) {
        $devices = $db->fetchAll("SELECT * FROM devices WHERE is_active = 1");
        
        $stats = [
            'total' => count($devices),
            'updated' => 0,
            'failed' => 0
        ];
        
        foreach ($devices as $device) {
            $info = self::fetchDeviceInfo($device);
            
            if ($info) {
                $db->update('devices', $info, 'id = :id', ['id' => $device['id']]);
                $stats['updated']++;
            } else {
                $stats['failed']++;
            }
        }
        
        return $stats;
    }
}
