<?php

class WinBoxParser {
    
    /**
     * WinBox .WBX dosyasını parse eder
     * @param string $filePath
     * @return array
     */
    public static function parse($filePath) {
        try {
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                throw new Exception('Dosya okunamadı');
            }
            
            error_log("WinBox original file size: " . strlen($content));
            
            // AGGRESSIVE BINARY CLEANING - HER YERDE TEMİZLE!
            $content = preg_replace('/[\x00-\x1F\x7F-\xFF]+/', ' ', $content);
            
            // Çoklu boşlukları tek boşluğa
            $content = preg_replace('/\s+/', ' ', $content);
            
            // Her "group" kelimesinden yeni satır oluştur
            $content = preg_replace('/ (group[A-Za-z0-9_.-]+) /', "\n$1 ", $content);
            
            error_log("WinBox cleaned content length: " . strlen($content));
            
            // Satırlara böl
            $lines = explode("\n", $content);
            
            $devices = [];
            
            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                
                if (empty($line)) {
                    continue;
                }
                
                if (!preg_match('/^group/', $line)) {
                    continue;
                }
                
                // İlk 10 satırı logla
                if (count($devices) < 10) {
                    error_log("Device line: " . substr($line, 0, 150));
                }
                
                $device = self::parseDeviceLine($line);
                
                if ($device) {
                    $devices[] = $device;
                }
            }
            
            error_log("WinBox parsed devices count: " . count($devices));
            
            return $devices;
            
        } catch (Exception $e) {
            error_log("WinBox parse exception: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Tek bir cihaz satırını parse eder
     * @param string $line
     * @return array|null
     */
    private static function parseDeviceLine($line) {
        $device = [
            'group' => null,
            'ip_address' => null,
            'port' => 8728,
            'username' => 'admin',
            'password' => null,
            'name' => null
        ];
        
        try {
            // Group - "group" ve ardından gelen kelime (--Router, Tepe, Bellona, ..Tekir, vb.)
            if (preg_match('/^group([A-Za-z0-9_\.\-]+)/', $line, $matches)) {
                $device['group'] = $matches[1];
            }
            
            // Host (IP:Port)
            if (preg_match('/host([\d\.]+)(?::(\d+))?/', $line, $matches)) {
                $device['ip_address'] = $matches[1];
                if (isset($matches[2]) && !empty($matches[2])) {
                    $device['port'] = intval($matches[2]);
                }
            }
            
            // Login - "login" ve ardından gelen kelime
            if (preg_match('/login([A-Za-z0-9_\-]+)/', $line, $matches)) {
                $device['username'] = $matches[1];
            }
            
            // Note (cihaz adı) - "note" ve ardından gelen kelime (boşluğa kadar)
            if (preg_match('/note([A-Za-z0-9_\-]+)/', $line, $matches)) {
                $device['name'] = $matches[1];
            }
            
            // Password - "pwd" ve ardından gelen kelime (boşluğa veya "secure" kelimesine kadar)
            if (preg_match('/pwd([A-Za-z0-9_@!#$%^&*()\-]+)/', $line, $matches)) {
                $device['password'] = $matches[1];
            }
            
            // Zorunlu alanlar kontrolü
            if (empty($device['ip_address']) || empty($device['password']) || empty($device['name'])) {
                error_log("Invalid device: IP=" . ($device['ip_address'] ?? 'null') . 
                         ", Name=" . ($device['name'] ?? 'null') . 
                         ", Pass=" . (empty($device['password']) ? 'null' : 'OK') .
                         ", Group=" . ($device['group'] ?? 'null'));
                return null;
            }
            
            return $device;
            
        } catch (Exception $e) {
            error_log("parseDeviceLine error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Grupları listele
     * @param array $devices
     * @return array
     */
    public static function getGroups($devices) {
        $groups = [];
        foreach ($devices as $device) {
            if (!empty($device['group']) && !in_array($device['group'], $groups)) {
                $groups[] = $device['group'];
            }
        }
        sort($groups);
        return $groups;
    }
    
    /**
     * Debug için dosya bilgilerini döndürür
     * @param string $filePath
     * @return array
     */
    public static function debugFile($filePath) {
        try {
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                return ['error' => 'Dosya okunamadı'];
            }
            
            // Binary temizle
            $cleaned = preg_replace('/^[\x00-\x1F\x7F-\xFF]+/', '', $content);
            
            // Tüm içeriği tek satır haline getir
            $oneLine = preg_replace('/\s+/', ' ', $cleaned);
            $oneLine = preg_replace('/ (group[A-Za-z0-9_\.\-]+)/', "\n$1", $oneLine);
            
            // Satırlara böl
            $lines = explode("\n", $oneLine);
            $lines = array_filter($lines, function($line) {
                return !empty(trim($line));
            });
            $lines = array_values($lines);
            
            // Group satırları
            $groupLines = [];
            foreach ($lines as $lineNum => $line) {
                if (preg_match('/^group/', trim($line))) {
                    $groupLines[] = "Line $lineNum: " . substr($line, 0, 100);
                }
            }
            
            return [
                'file_size' => strlen($content),
                'cleaned_size' => strlen($cleaned),
                'total_device_lines' => count($lines),
                'group_count' => count($groupLines),
                'first_10_device_lines' => array_slice($groupLines, 0, 10),
                'sample_lines' => array_slice($lines, 0, 5),
                'host_count' => substr_count($cleaned, 'host'),
                'login_count' => substr_count($cleaned, 'login'),
                'note_count' => substr_count($cleaned, 'note'),
                'pwd_count' => substr_count($cleaned, 'pwd')
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Test fonksiyonu
     * @param string $content
     * @return array
     */
    public static function testParse($content) {
        // Geçici dosya oluştur
        $tmpFile = tempnam(sys_get_temp_dir(), 'wbx_');
        file_put_contents($tmpFile, $content);
        
        $result = self::parse($tmpFile);
        
        unlink($tmpFile);
        
        return $result;
    }
}
