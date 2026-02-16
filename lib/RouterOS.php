<?php
/**
 * MikroTik RouterOS API Library
 * PHP implementation of MikroTik API protocol
 */

class RouterOS {
    private $socket;
    private $connected = false;
    private $debug = false;
    private $timeout = 5;
    private $attempts = 5;
    private $delay = 3;
    
    public $error = '';
    public $error_no = 0;
    
    /**
     * Connect to MikroTik Router
     */
    public function connect($ip, $port = 8728) {
        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            $this->connected = false;
            $this->error = '';
            $this->error_no = 0;
            
            $this->socket = @fsockopen($ip, $port, $this->error_no, $this->error, $this->timeout);
            
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->connected = true;
                return true;
            }
            
            if ($attempt < $this->attempts) {
                sleep($this->delay);
            }
        }
        
        return false;
    }
    
    /**
     * Disconnect from router
     */
    public function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
        }
        $this->connected = false;
    }
    
    /**
     * Login to router
     */
    public function login($username, $password) {
        if (!$this->connected) {
            return false;
        }
        
        $response = $this->comm('/login', [
            '=name=' . $username,
            '=password=' . $password
        ]);
        
        if (isset($response[0]) && $response[0] == '!done') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute command
     */
    public function comm($command, $params = []) {
        if (!$this->connected) {
            return false;
        }
        
        $this->write($command, $params);
        return $this->read();
    }
    
    /**
     * Write data to socket
     */
    private function write($command, $params = []) {
        $data = $this->encodeLength(strlen($command)) . $command;
        
        foreach ($params as $param) {
            $data .= $this->encodeLength(strlen($param)) . $param;
        }
        
        $data .= chr(0);
        
        fwrite($this->socket, $data);
        
        if ($this->debug) {
            error_log("SEND: " . $command . " " . implode(" ", $params));
        }
    }
    
    /**
     * Read data from socket
     */
    private function read() {
        $response = [];
        $receivedDone = false;
        
        while (true) {
            $line = $this->readSentence();
            
            if ($line === false) {
                break;
            }
            
            if ($line == '!done') {
                $receivedDone = true;
                $response[] = $line;
                break;
            }
            
            if ($line == '!trap' || $line == '!fatal') {
                $response[] = $line;
                break;
            }
            
            $response[] = $line;
        }
        
        if ($this->debug) {
            error_log("RECV: " . implode(" | ", $response));
        }
        
        return $response;
    }
    
    /**
     * Read sentence from socket
     */
    private function readSentence() {
        $sentence = [];
        
        while (true) {
            $word = $this->readWord();
            
            if ($word === false) {
                return false;
            }
            
            if (strlen($word) == 0) {
                return $sentence;
            }
            
            $sentence[] = $word;
        }
    }
    
    /**
     * Read word from socket
     */
    private function readWord() {
        $length = $this->decodeLength();
        
        if ($length === false) {
            return false;
        }
        
        if ($length == 0) {
            return '';
        }
        
        $word = '';
        $remaining = $length;
        
        while ($remaining > 0) {
            $data = fread($this->socket, $remaining);
            
            if ($data === false || $data === '') {
                return false;
            }
            
            $word .= $data;
            $remaining -= strlen($data);
        }
        
        return $word;
    }
    
    /**
     * Encode length for API protocol
     */
    private function encodeLength($length) {
        if ($length < 0x80) {
            return chr($length);
        }
        
        if ($length < 0x4000) {
            $length |= 0x8000;
            return chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        
        if ($length < 0x200000) {
            $length |= 0xC00000;
            return chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        
        if ($length < 0x10000000) {
            $length |= 0xE0000000;
            return chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        
        return chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
    }
    
    /**
     * Decode length from API protocol
     */
    private function decodeLength() {
        $byte = fread($this->socket, 1);
        
        if ($byte === false || $byte === '') {
            return false;
        }
        
        $byte = ord($byte);
        
        if ($byte == 0) {
            return 0;
        }
        
        if ($byte < 0x80) {
            return $byte;
        }
        
        if ($byte < 0xC0) {
            $byte2 = ord(fread($this->socket, 1));
            return (($byte & 0x3F) << 8) + $byte2;
        }
        
        if ($byte < 0xE0) {
            $byte2 = ord(fread($this->socket, 1));
            $byte3 = ord(fread($this->socket, 1));
            return (($byte & 0x1F) << 16) + ($byte2 << 8) + $byte3;
        }
        
        if ($byte < 0xF0) {
            $byte2 = ord(fread($this->socket, 1));
            $byte3 = ord(fread($this->socket, 1));
            $byte4 = ord(fread($this->socket, 1));
            return (($byte & 0x0F) << 24) + ($byte2 << 16) + ($byte3 << 8) + $byte4;
        }
        
        $byte2 = ord(fread($this->socket, 1));
        $byte3 = ord(fread($this->socket, 1));
        $byte4 = ord(fread($this->socket, 1));
        $byte5 = ord(fread($this->socket, 1));
        return ($byte2 << 24) + ($byte3 << 16) + ($byte4 << 8) + $byte5;
    }
    
    /**
     * Parse response to array
     */
    public function parseResponse($response) {
        $result = [];
        $current = [];
        
        foreach ($response as $line) {
            if ($line == '!done' || $line == '!trap' || $line == '!fatal') {
                if (!empty($current)) {
                    $result[] = $current;
                    $current = [];
                }
                continue;
            }
            
            if (substr($line, 0, 1) == '=') {
                $parts = explode('=', substr($line, 1), 2);
                if (count($parts) == 2) {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }
        
        if (!empty($current)) {
            $result[] = $current;
        }
        
        return $result;
    }
    
    /**
     * Set debug mode
     */
    public function setDebug($debug = true) {
        $this->debug = $debug;
    }
    
    /**
     * Set timeout
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }
    
    /**
     * Check if connected
     */
    public function isConnected() {
        return $this->connected;
    }
}
?>