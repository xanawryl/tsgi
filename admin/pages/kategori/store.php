<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
// 2. Muat functions.php untuk slugify()
require_once __DIR__ . '/../../includes/functions.php';

// Pastikan ini adalah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Jika bukan POST, redirect ke halaman index
    header("Location: index.php");
    exit();
}

// Ambil data dari form
$nama_kategori = $_POST['nama_kategori'];
$slug_kategori = slugify($nama_kategori);

// Gunakan prepared statements untuk keamanan
try {
    // Cek dulu apakah slug sudah ada
    $stmt_check = $conn->prepare("SELECT id_kategori FROM kategori WHERE slug_kategori = ?");
    $stmt_check->bind_param("s", $slug_kategori);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    // Jika slug sudah ada, tambahkan suffix unik (timestamp)
    if ($result_check->num_rows > 0) {
        $slug_kategori = $slug_kategori . '-' . time();
    }
    $stmt_check->close();

    // Insert data baru
    $stmt_insert = $conn->prepare("INSERT INTO kategori (nama_kategori, slug_kategori) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $nama_kategori, $slug_kategori);

    if ($stmt_insert->execute()) {
        $_SESSION['flash_message'] = "Kategori '{$nama_kategori}' berhasil ditambahkan!";
        $_SESSION['flash_message_type'] = "success";
    } else {
        throw new Exception("Gagal menyimpan data ke database.");
    }
    $stmt_insert->close();

} catch (Exception $e) {
    $_SESSION['flash_message'] = "Terjadi error: " . $e->getMessage();
    $_SESSION['flash_message_type'] = "danger";
}

$conn->close();
// Redirect kembali ke halaman index
header("Location: index.php");
exit();
?>