<?php
/**
 * API: Get Update Progress
 */

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get progress from session
    $progress = $_SESSION['update_progress'] ?? [
        'status' => 'idle',
        'progress' => 0,
        'message' => 'Hazır'
    ];
    
    echo json_encode($progress);
} catch (Exception $e) {
    error_log("[API:update-progress] Exception: " . $e->getMessage());
    error_log("[API:update-progress] Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'progress' => 0,
        'message' => 'İlerleme bilgisi alınamadı'
    ]);
}
