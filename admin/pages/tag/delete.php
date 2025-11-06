<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

// Validasi ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "ID Tag tidak valid.";
    $_SESSION['flash_message_type'] = "danger";
    header("Location: index.php");
    exit();
}
$id_tag = $_GET['id'];

// Gunakan prepared statements
try {
    // Ingat: 'ON DELETE CASCADE' di tabel 'news_tag' akan bekerja
    // Jadi, semua relasi di 'news_tag' akan otomatis terhapus
    
    $stmt = $conn->prepare("DELETE FROM tag WHERE id_tag = ?");
    $stmt->bind_param("i", $id_tag);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['flash_message'] = "Tag berhasil dihapus.";
            $_SESSION['flash_message_type'] = "success";
        } else {
            throw new Exception("Tag tidak ditemukan atau gagal dihapus.");
        }
    } else {
        throw new Exception("Gagal menghapus data dari database.");
    }
    $stmt->close();

} catch (Exception $e) {
    // Tangani jika ada foreign key constraint error
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
         $_SESSION['flash_message'] = "Gagal menghapus: Tag ini masih digunakan oleh data lain (seharusnya tidak terjadi jika CASCADE aktif).";
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