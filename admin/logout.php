<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Muat config hanya untuk BASE_URL
require_once __DIR__ . '/../config.php';
$admin_base = BASE_URL . '/admin';

// Alihkan ke halaman login
header('Location: ' . $admin_base . '/index.php');
exit;
?>