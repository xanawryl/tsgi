<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';

// 2. Ambil ID Item dari URL dan Validasi
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$item_id) {
    $_SESSION['flash_message'] = 'ID Item Bisnis tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/business/');
    exit;
}

// 3. Ambil Koneksi DB
// $conn sudah tersedia

// 4. Ambil nama file thumbnail SEBELUM menghapus data
$sql_select = "SELECT thumbnail_url FROM business_items WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $item_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $image_to_delete = $row['thumbnail_url'];
} else {
    $_SESSION['flash_message'] = 'Item Bisnis tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/business/');
    exit;
}
$stmt_select->close();


// 5. Hapus data dari Database
$sql_delete = "DELETE FROM business_items WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $item_id);

if ($stmt_delete->execute()) {
    // Berhasil menghapus dari DB, sekarang hapus file thumbnail-nya
    
    $target_dir = __DIR__ . "/../../../assets/img/business/";
    $file_path = $target_dir . $image_to_delete;
    
    if (!empty($image_to_delete) && file_exists($file_path)) {
        unlink($file_path); // Hapus file gambar
    }
    
    $_SESSION['flash_message'] = 'Item Bisnis berhasil dihapus!';
    $_SESSION['flash_message_type'] = 'success';
    
} else {
    // Gagal menghapus dari DB
    $_SESSION['flash_message'] = 'Gagal menghapus item bisnis: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali ke halaman manage business
header('Location: ' . $admin_base . '/pages/business/');
exit;
?>