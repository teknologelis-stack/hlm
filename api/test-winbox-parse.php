<?php
require_once __DIR__ . '/../config/database.php';
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/WinBoxParser.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Oturum geçersiz'], 401);
}

// Test data
$testBlock = 'group0Router host176.88.168.50:8299 keep-pwd loginadmin noteTepe_2116 pwdadmin123 secure-mode typeaddr';

$result = WinBoxParser::testParse($testBlock);

jsonResponse([
    'success' => true,
    'input' => $testBlock,
    'output' => $result
]);
?>
