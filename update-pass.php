<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// Update password
$stmt = $db->prepare("UPDATE mikrotik_devices SET password = ? WHERE id = 2");
$stmt->execute(['1234']);

echo "Password updated to: 1234\n";

// Check
$stmt = $db->query("SELECT username, password FROM mikrotik_devices WHERE id = 2");
$device = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($device);
