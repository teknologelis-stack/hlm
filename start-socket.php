<?php
$cmd = 'cd /d ' . __DIR__ . '/socket-server && node server.js';
$descriptorspec = [
    0 => ['pipe', 'r'],
    1 => ['file', __DIR__ . '/logs/socket.log', 'a'],
    2 => ['file', __DIR__ . '/logs/socket.log', 'a']
];

$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    echo "Socket server started. PID: " . proc_get_status($process)['pid'];
    proc_close($process);
}
