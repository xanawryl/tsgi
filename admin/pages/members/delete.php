<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';
// Editor boleh menghapus
require_role(ROLE_EDITOR);

// 2. Ambil ID Member dari URL dan Validasi
$member_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$member_id) {
    $_SESSION['flash_message'] = 'ID Board Member tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/members/');
    exit;
}

// 3. Ambil Koneksi DB
// $conn sudah tersedia dari auth-check.php

// 4. Ambil nama file gambar SEBELUM menghapus data
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
$sql_select = "SELECT image_url FROM board_members WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/members/');
     exit;
}
$stmt_select->bind_param("i", $member_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $image_to_delete = $row['image_url'];
} else {
    $_SESSION['flash_message'] = 'Board Member tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/members/');
    exit;
}
$stmt_select->close();


// 5. Hapus data dari Database
$sql_delete = "DELETE FROM board_members WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
if ($stmt_delete === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query hapus: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/members/');
     exit;
}
$stmt_delete->bind_param("i", $member_id);

if ($stmt_delete->execute()) {
    // Berhasil menghapus dari DB, sekarang hapus file gambarnya
    
    $target_dir = __DIR__ . "/../../../assets/img/members/";
    $file_path = $target_dir . $image_to_delete;
    
    if (!empty($image_to_delete) && file_exists($file_path)) {
        @unlink($file_path); // Hapus file gambar
    }
    
    $_SESSION['flash_message'] = 'Board member berhasil dihapus!';
    $_SESSION['flash_message_type'] = 'success';
    
} else {
    // Gagal menghapus dari DB
    $_SESSION['flash_message'] = 'Gagal menghapus board member: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali ke halaman manage members
header('Location: ' . $admin_base . '/pages/members/');
exit;
?>