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
if (!isset($_POST['id_kategori']) || !is_numeric($_POST['id_kategori'])) {
    $_SESSION['flash_message'] = "ID Kategori tidak valid.";
    $_SESSION['flash_message_type'] = "danger";
    header("Location: index.php");
    exit();
}

$id_kategori = $_POST['id_kategori'];
$nama_kategori = $_POST['nama_kategori'];
$slug_kategori = slugify($nama_kategori);

// Gunakan prepared statements
try {
    // Cek apakah slug baru bentrok dengan slug lain (yang BUKAN miliknya sendiri)
    $stmt_check = $conn->prepare("SELECT id_kategori FROM kategori WHERE slug_kategori = ? AND id_kategori != ?");
    $stmt_check->bind_param("si", $slug_kategori, $id_kategori);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    // Jika slug sudah ada, tambahkan ID kategori agar unik
    if ($result_check->num_rows > 0) {
        $slug_kategori = $slug_kategori . '-' . $id_kategori;
    }
    $stmt_check->close();

    // Update data
    $stmt_update = $conn->prepare("UPDATE kategori SET nama_kategori = ?, slug_kategori = ? WHERE id_kategori = ?");
    $stmt_update->bind_param("ssi", $nama_kategori, $slug_kategori, $id_kategori);

    if ($stmt_update->execute()) {
        $_SESSION['flash_message'] = "Kategori '{$nama_kategori}' berhasil diupdate!";
        $_SESSION['flash_message_type'] = "success";
    } else {
        throw new Exception("Gagal mengupdate data ke database.");
    }
    $stmt_update->close();

} catch (Exception $e) {
    $_SESSION['flash_message'] = "Terjadi error: " . $e->getMessage();
    $_SESSION['flash_message_type'] = "danger";
}

$conn->close();
// Redirect kembali ke halaman index
header("Location: index.php");
exit();
?>