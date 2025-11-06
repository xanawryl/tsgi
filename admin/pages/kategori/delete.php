<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

// Validasi ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "ID Kategori tidak valid.";
    $_SESSION['flash_message_type'] = "danger";
    header("Location: index.php");
    exit();
}
$id_kategori = $_GET['id'];

// Gunakan prepared statements
try {
    // Ingat: 'ON DELETE SET NULL' di database akan bekerja
    // Jadi, semua 'news.id_kategori' yang terkait akan menjadi NULL
    
    $stmt = $conn->prepare("DELETE FROM kategori WHERE id_kategori = ?");
    $stmt->bind_param("i", $id_kategori);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['flash_message'] = "Kategori berhasil dihapus.";
            $_SESSION['flash_message_type'] = "success";
        } else {
            throw new Exception("Kategori tidak ditemukan atau gagal dihapus.");
        }
    } else {
        throw new Exception("Gagal menghapus data dari database.");
    }
    $stmt->close();

} catch (Exception $e) {
    // Tangani jika ada foreign key constraint error (meskipun seharusnya tidak)
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
         $_SESSION['flash_message'] = "Gagal menghapus: Kategori ini masih digunakan oleh data lain.";
    } else {
         $_SESSION['flash_message'] = "Terjadi error: " . $e->getMessage();
    }
    $_SESSION['flash_message_type'] = "danger";
}

$conn->close();
// Redirect kembali ke halaman index
header("Location: index.php");
exit();
?>