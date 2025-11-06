<?php
// ========== PENGECEK OTENTIKASI ADMIN ==========
// File ini HARUS dipanggil di baris paling atas
// di setiap halaman admin yang aman.

// Mulai session (harus paling atas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Muat konfigurasi utama (untuk BASE_URL)
require_once __DIR__ . '/../../config.php';
$admin_base = BASE_URL . '/admin';

// Tentukan durasi timeout (dalam detik)
$timeout_duration = 1800; // 1800 detik = 30 menit

// Cek! Apakah user sudah login?
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika BELUM, tendang kembali ke halaman login dengan pesan error
    header('Location: ' . $admin_base . '/index.php?error=2'); // error=2 = "Anda harus login"
    exit;
}

// Cek apakah timestamp aktivitas terakhir ada
if (isset($_SESSION['last_activity'])) {
    // Hitung selisih waktu (sekarang - aktivitas terakhir)
    $inactive_time = time() - $_SESSION['last_activity'];

    // Jika waktu tidak aktif MELEBIHI durasi timeout
    if ($inactive_time > $timeout_duration) {
        // Hancurkan session
        session_unset();    // Hapus semua variabel session
        session_destroy();  // Hancurkan session

        // Alihkan ke login dengan pesan timeout
        header('Location: ' . $admin_base . '/index.php?error=3'); // error=3 = "Session timeout"
        exit;
    }
}

// Jika lolos cek login DAN timeout, update timestamp aktivitas terakhir
$_SESSION['last_activity'] = time();

// Set variabel global untuk digunakan di halaman (seperti sebelumnya)
$admin_full_name = $_SESSION['admin_full_name'] ?? 'Admin'; // Gunakan null coalescing
$admin_username = $_SESSION['admin_username'] ?? 'admin';
$admin_role = $_SESSION['admin_role'] ?? ROLE_EDITOR; // Default ke editor jika tidak ada

?>