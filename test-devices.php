<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, name, ip_address, port, username, status FROM mikrotik_devices");
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $devices], JSON_PRETTY_PRINT);
