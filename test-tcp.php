<?php
// Simple TCP test
$host = '10.40.40.1';
$port = 8728;
$timeout = 5;

echo "Testing TCP connection to $host:$port...\n";

$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

if ($socket) {
    echo "✓ TCP connection successful!\n";
    
    // Send MikroTik API login
    $login = "/login\n";
    fwrite($socket, $login);
    
    $response = fread($socket, 1024);
    echo "Response: " . bin2hex($response) . "\n";
    echo "Response (raw): " . $response . "\n";
    
    fclose($socket);
} else {
    echo "✗ TCP connection failed: $errstr ($errno)\n";
}
