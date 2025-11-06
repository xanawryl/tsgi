<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';
// Editor boleh menghapus
require_role(ROLE_EDITOR);

// 2. Ambil ID Partner dari URL dan Validasi
$partner_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$partner_id) {
    $_SESSION['flash_message'] = 'ID Partner tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/partners/');
    exit;
}

// 3. Ambil Koneksi DB
// $conn sudah tersedia dari auth-check.php

// 4. Ambil nama file logo SEBELUM menghapus data
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
$sql_select = "SELECT logo_url FROM partners WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/partners/');
     exit;
}
$stmt_select->bind_param("i", $partner_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $image_to_delete = $row['logo_url'];
} else {
    $_SESSION['flash_message'] = 'Partner tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/partners/');
    exit;
}
$stmt_select->close();


// 5. Hapus data dari Database
$sql_delete = "DELETE FROM partners WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
if ($stmt_delete === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query hapus: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/partners/');
     exit;
}
$stmt_delete->bind_param("i", $partner_id);

if ($stmt_delete->execute()) {
    // Berhasil menghapus dari DB, sekarang hapus file logonya
    
    $target_dir = __DIR__ . "/../../../assets/img/partners/";
    $file_path = $target_dir . $image_to_delete;
    
    if (!empty($image_to_delete) && file_exists($file_path)) {
        @unlink($file_path); // Hapus file gambar
    }
    
    $_SESSION['flash_message'] = 'Partner berhasil dihapus!';
    $_SESSION['flash_message_type'] = 'success';
    
} else {
    // Gagal menghapus dari DB
    $_SESSION['flash_message'] = 'Gagal menghapus partner: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali ke halaman manage partners
header('Location: ' . $admin_base . '/pages/partners/');
exit;
?>