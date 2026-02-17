<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: ' . BASE_URL . '/index.php');
exit();
