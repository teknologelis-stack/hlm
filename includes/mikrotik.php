<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\Config;

class MikroTikHelper {
    // Public per requirement - allows external access to RouterOS client for advanced operations
    public $client;
    private $db;
    public $device;
    public $error = '';
    
    public function __construct($deviceId = null) {
        $this->db = Database::getInstance();
        
        if ($deviceId) {
            $this->device = $this->db->fetchOne("SELECT * FROM devices WHERE id = ? AND is_active = 1", [$deviceId]);
        } else {
            $this->device = $this->db->fetchOne("SELECT * FROM devices WHERE is_main = 1 AND is_active = 1");
        }
    }
    
    public function connect() {
        if (!$this->device) {
            $this->error = 'Cihaz bulunamadı';
            return false;
        }
        
        try {
            $config = (new Config())
                ->set('host', $this->device['ip_address'])
                ->set('port', $this->device['port'])
                ->set('user', $this->device['username'])
                ->set('pass', decrypt($this->device['password']))
                ->set('timeout', 5);
            
            $this->client = new Client($config);
            $this->updateLastConnection();
            return true;
        } catch (Exception $e) {
            $this->error = 'Bağlantı hatası: ' . $e->getMessage();
            return false;
        }
    }
    
    public function testConnection() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $identity = $this->client->query('/system/identity/print')->read();
            $resource = $this->client->query('/system/resource/print')->read();
            
            return [
                'identity' => $identity[0]['name'] ?? 'Unknown',
                'version' => $resource[0]['version'] ?? 'Unknown',
                'model' => $resource[0]['board-name'] ?? 'Unknown',
                'serial' => $resource[0]['serial-number'] ?? 'Unknown',
                'uptime' => $resource[0]['uptime'] ?? 'Unknown',
                'cpu_load' => $resource[0]['cpu-load'] ?? '0',
                'free_memory' => $resource[0]['free-memory'] ?? '0',
                'total_memory' => $resource[0]['total-memory'] ?? '0'
            ];
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getActivePPP() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/ppp/active/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getPPPSecrets() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/ppp/secret/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function addPPPSecret($name, $password, $service = 'any', $localAddress = '', $remoteAddress = '', $profile = 'default') {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $name)
                ->equal('password', $password)
                ->equal('service', $service)
                ->equal('profile', $profile);
            
            if (!empty($localAddress)) {
                $query->equal('local-address', $localAddress);
            }
            
            if (!empty($remoteAddress)) {
                $query->equal('remote-address', $remoteAddress);
            }
            
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function removePPPSecret($id) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ppp/secret/remove'))->equal('.id', $id);
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function disconnectPPPUser($id) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ppp/active/remove'))->equal('.id', $id);
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getIPAddresses() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/ip/address/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getIPRoutes() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/ip/route/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getVLANs() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/interface/vlan/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getBridgePorts() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/interface/bridge/port/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getFirewallRules() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/ip/firewall/filter/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function getDHCPServers() {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            return $this->client->query('/ip/dhcp-server/print')->read();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function addIPAddress($address, $interface, $comment = '') {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ip/address/add'))
                ->equal('address', $address)
                ->equal('interface', $interface);
            
            if (!empty($comment)) {
                $query->equal('comment', $comment);
            }
            
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function removeIPAddress($id) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ip/address/remove'))->equal('.id', $id);
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function addIPRoute($dstAddress, $gateway, $distance = 1) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ip/route/add'))
                ->equal('dst-address', $dstAddress)
                ->equal('gateway', $gateway)
                ->equal('distance', (string)$distance);
            
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function removeIPRoute($id) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/ip/route/remove'))->equal('.id', $id);
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function addVLAN($name, $vlanId, $interface) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/interface/vlan/add'))
                ->equal('name', $name)
                ->equal('vlan-id', (string)$vlanId)
                ->equal('interface', $interface);
            
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function removeVLAN($id) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            $query = (new Query('/interface/vlan/remove'))->equal('.id', $id);
            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    private function updateLastConnection() {
        if ($this->device) {
            $this->db->update('devices', 
                ['last_connection' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $this->device['id']]
            );
        }
    }
}
?>
