<?php
require_once __DIR__ . '/../config/app.php';
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/WinBoxParser.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum geçersiz'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Test data - TEK SATIR FORMAT (gerçek dosya)
$testContentSingleLine = '�� group--Router host213.14.68.118 keep-pwd logintekir noteTekir-4011 pwdharun46 secure-mode typeaddr
groupTepe host10.150.10.10 keep-pwd loginadmin noteUlasNeT_Tepe_Switch_1 pwd3115595 secure-mode typeaddr
groupBellona host10.150.20.11 keep-pwd loginadmin noteUlasNeT_Bellona_Cl pwd3115595 secure-mode typeaddr
groupCt host10.150.30.11 keep-pwd loginadmin noteUlasNeT_CT_Switch pwd3115595 secure-mode typeaddr
groupArda host10.150.40.13 keep-pwd loginadmin noteUlasNet_Arda_2 pwd3115595 secure-mode typeaddr';

// Test data - ÇOK SATIR FORMAT
$testContentMultiLine = 'À¾ group--Router host213.14.68.118
 keep-pwd
 logintekir noteTekir-4011
 pwdadmin123
 
secure-mode	 typeaddr  
 groupTepe host10.150.10.18
 keep-pwd
 loginadmin noteUlasNeT_Tepe_QRT5_5
 pwd3115595
 
secure-mode	 typeaddr  
 groupBellona host10.150.20.20
 keep-pwd
 loginadmin noteUlasNeT_Bellona_8
 pwd3115595
 
secure-mode	 typeaddr';

try {
    // Tek satır format test
    $devicesSingleLine = WinBoxParser::testParse($testContentSingleLine);
    
    // Çok satır format test
    $devicesMultiLine = WinBoxParser::testParse($testContentMultiLine);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'single_line_format' => [
            'device_count' => count($devicesSingleLine),
            'devices' => $devicesSingleLine
        ],
        'multi_line_format' => [
            'device_count' => count($devicesMultiLine),
            'devices' => $devicesMultiLine
        ],
        'total_parsed' => count($devicesSingleLine) + count($devicesMultiLine),
        'message' => 'Both formats parsed successfully'
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
