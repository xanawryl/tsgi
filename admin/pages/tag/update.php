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
if (!isset($_POST['id_tag']) || !is_numeric($_POST['id_tag'])) {
    $_SESSION['flash_message'] = "ID Tag tidak valid.";
    $_SESSION['flash_message_type'] = "danger";
    header("Location: index.php");
    exit();
}

$id_tag = $_POST['id_tag'];
$nama_tag = $_POST['nama_tag'];
$slug_tag = slugify($nama_tag);

// Gunakan prepared statements
try {
    // Cek apakah slug baru bentrok dengan slug lain (yang BUKAN miliknya sendiri)
    $stmt_check = $conn->prepare("SELECT id_tag FROM tag WHERE slug_tag = ? AND id_tag != ?");
    $stmt_check->bind_param("si", $slug_tag, $id_tag);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    // Jika slug sudah ada, tambahkan ID tag agar unik
    if ($result_check->num_rows > 0) {
        $slug_tag = $slug_tag . '-' . $id_tag;
    }
    $stmt_check->close();

    // Update data
    $stmt_update = $conn->prepare("UPDATE tag SET nama_tag = ?, slug_tag = ? WHERE id_tag = ?");
    $stmt_update->bind_param("ssi", $nama_tag, $slug_tag, $id_tag);

    if ($stmt_update->execute()) {
        $_SESSION['flash_message'] = "Tag '{$nama_tag}' berhasil diupdate!";
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