<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';

// 2. Ambil ID Berita dari URL dan Validasi
$news_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$news_id) {
    // Jika ID tidak valid atau tidak ada
    $_SESSION['flash_message'] = 'ID Berita tidak valid.';
    $_SESSION['flash_message_type'] = 'danger'; // Tipe pesan (untuk warna)
    header('Location: ' . $admin_base . '/pages/news/');
    exit;
}

// 3. Ambil Koneksi DB
// $conn sudah tersedia dari auth-check.php -> config.php

// 4. (PENTING) Ambil nama file gambar SEBELUM menghapus data
$sql_select = "SELECT image_url FROM news WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $news_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $image_to_delete = $row['image_url'];
} else {
    // ID berita tidak ditemukan di database
    $_SESSION['flash_message'] = 'Berita tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/news/');
    exit;
}
$stmt_select->close();


// 5. Hapus data dari Database
$sql_delete = "DELETE FROM news WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $news_id);

if ($stmt_delete->execute()) {
    // Berhasil menghapus dari DB, sekarang hapus file gambarnya
    
    $target_dir = __DIR__ . "/../../../assets/img/news/";
    $file_path = $target_dir . $image_to_delete;
    
    if (!empty($image_to_delete) && file_exists($file_path)) {
        unlink($file_path); // Hapus file gambar
    }
    
    $_SESSION['flash_message'] = 'Berita berhasil dihapus!';
    $_SESSION['flash_message_type'] = 'success';
    
} else {
    // Gagal menghapus dari DB
    $_SESSION['flash_message'] = 'Gagal menghapus berita: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali ke halaman manage news
header('Location: ' . $admin_base . '/pages/news/');
exit;
?>