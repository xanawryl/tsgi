<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh menghapus
require_role(ROLE_EDITOR);

// 2. Ambil ID Album dari URL dan Validasi
$album_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$album_id) {
    $_SESSION['flash_message'] = 'ID Album tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
    exit;
}

// 3. Ambil Koneksi DB
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}

// 4. Inisialisasi array untuk menyimpan nama file yang akan dihapus
$files_to_delete = [];

// 5. Ambil nama file COVER ALBUM
$sql_select_cover = "SELECT cover_image FROM gallery_albums WHERE id = ?";
$stmt_select_cover = $conn->prepare($sql_select_cover);
if ($stmt_select_cover === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select cover: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/gallery/albums/');
     exit;
}
$stmt_select_cover->bind_param("i", $album_id);
$stmt_select_cover->execute();
$result_cover = $stmt_select_cover->get_result();

if ($result_cover->num_rows == 1) {
    $row = $result_cover->fetch_assoc();
    if (!empty($row['cover_image'])) {
        // Tambahkan path lengkap cover ke array
        $files_to_delete[] = __DIR__ . "/../../../../assets/img/gallery/covers/" . $row['cover_image'];
    }
} else {
    $_SESSION['flash_message'] = 'Album tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
    exit;
}
$stmt_select_cover->close();


// 6. Ambil SEMUA nama file GAMBAR di dalam album
$sql_select_images = "SELECT image_url FROM gallery_images WHERE album_id = ?";
$stmt_select_images = $conn->prepare($sql_select_images);
if ($stmt_select_images === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select gambar: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/gallery/albums/');
     exit;
}
$stmt_select_images->bind_param("i", $album_id);
$stmt_select_images->execute();
$result_images = $stmt_select_images->get_result();

if ($result_images->num_rows > 0) {
    while($row_image = $result_images->fetch_assoc()) {
        if (!empty($row_image['image_url'])) {
            // Tambahkan path lengkap gambar ke array
            $files_to_delete[] = __DIR__ . "/../../../../assets/img/gallery/" . $row_image['image_url'];
        }
    }
}
$stmt_select_images->close();


// 7. Hapus data dari Database
// ON DELETE CASCADE akan otomatis menghapus record di gallery_images
$sql_delete = "DELETE FROM gallery_albums WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
if ($stmt_delete === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query hapus: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/gallery/albums/');
     exit;
}
$stmt_delete->bind_param("i", $album_id);

if ($stmt_delete->execute()) {
    // Berhasil menghapus dari DB, sekarang hapus semua file fisiknya
    
    $files_deleted_count = 0;
    foreach ($files_to_delete as $file_path) {
        if (file_exists($file_path)) {
            if (@unlink($file_path)) { // Hapus file
                $files_deleted_count++;
            }
        }
    }
    
    $_SESSION['flash_message'] = "Album galeri berhasil dihapus! $files_deleted_count file terkait juga telah dihapus dari server.";
    $_SESSION['flash_message_type'] = 'success';
    
} else {
    // Gagal menghapus dari DB
    $_SESSION['flash_message'] = 'Gagal menghapus album: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 8. Alihkan kembali ke halaman manage albums
header('Location: ' . $admin_base . '/pages/gallery/albums/');
exit;
?>