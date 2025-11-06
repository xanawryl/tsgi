<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
// 2. Muat functions.php untuk slugify()
require_once __DIR__ . '/../../includes/functions.php';

// Pastikan ini adalah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Ambil data dari form
$nama_tag = $_POST['nama_tag'];
$slug_tag = slugify($nama_tag);

// Gunakan prepared statements untuk keamanan
try {
    // Cek dulu apakah slug sudah ada
    $stmt_check = $conn->prepare("SELECT id_tag FROM tag WHERE slug_tag = ?");
    $stmt_check->bind_param("s", $slug_tag);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    // Jika slug sudah ada, tambahkan suffix unik (timestamp)
    if ($result_check->num_rows > 0) {
        $slug_tag = $slug_tag . '-' . time();
    }
    $stmt_check->close();

    // Insert data baru
    $stmt_insert = $conn->prepare("INSERT INTO tag (nama_tag, slug_tag) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $nama_tag, $slug_tag);

    if ($stmt_insert->execute()) {
        $_SESSION['flash_message'] = "Tag '{$nama_tag}' berhasil ditambahkan!";
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