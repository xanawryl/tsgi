<?php
// 1. Cek Auth
require_once __DIR__ . '/../../../includes/auth-check.php';
require_role(ROLE_EDITOR);

// 2. Ambil ID Gambar & ID Album dari URL
$image_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$album_id = filter_input(INPUT_GET, 'album_id', FILTER_VALIDATE_INT);

// URL untuk kembali (jika terjadi error)
$return_url = ($album_id) ? ($admin_base . '/pages/gallery/images/index.php?album_id=' . $album_id) : ($admin_base . '/pages/gallery/albums/');

if (!$image_id || !$album_id) {
    $_SESSION['flash_message'] = 'ID Gambar atau Album tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $return_url);
    exit;
}

// 3. Ambil Koneksi DB
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}

// 4. Ambil nama file gambar SEBELUM menghapus data
$sql_select = "SELECT image_url FROM gallery_images WHERE id = ? AND album_id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $return_url);
     exit;
}
$stmt_select->bind_param("ii", $image_id, $album_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $image_to_delete = $row['image_url'];
} else {
    $_SESSION['flash_message'] = 'Gambar tidak ditemukan atau tidak cocok dengan album.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $return_url);
    exit;
}
$stmt_select->close();


// 5. Hapus data dari Database
$sql_delete = "DELETE FROM gallery_images WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
if ($stmt_delete === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query hapus: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $return_url);
     exit;
}
$stmt_delete->bind_param("i", $image_id);

if ($stmt_delete->execute()) {
    // Berhasil menghapus dari DB, sekarang hapus file gambarnya
    $target_dir = __DIR__ . "/../../../../assets/img/gallery/";
    $file_path = $target_dir . $image_to_delete;
    
    if (!empty($image_to_delete) && file_exists($file_path)) {
        @unlink($file_path); // Hapus file gambar
    }
    
    $_SESSION['flash_message'] = 'Gambar berhasil dihapus!';
    $_SESSION['flash_message_type'] = 'success';
    
} else {
    $_SESSION['flash_message'] = 'Gagal menghapus gambar: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali ke halaman manage images
header('Location: ' . $return_url);
exit;
?>