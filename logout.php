<?php
// Load config BEFORE session_start to avoid ini_set warnings
require_once __DIR__ . '/config/app.php';
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$auth->logout();

redirect('index.php');
?>