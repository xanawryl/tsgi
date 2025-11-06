<?php
// Pastikan koneksi $conn ada (dari index.php) dan $album_slug ada (dari router)
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) || !isset($album_slug)) {
    // Jika file diakses langsung atau slug tidak ada, redirect
    header('Location: ' . BASE_URL . '/gallery');
    exit;
}

// 1. Ambil info album (kita sudah ambil title & desc di router untuk SEO)
// Kita ambil lagi di sini untuk jaga-jaga jika $page_title belum di-set
if (!isset($page_title) || $page_title == 'Album Not Found') {
    $sql_album_check = "SELECT title, description FROM gallery_albums WHERE slug = ? LIMIT 1";
    $stmt_album_check = $conn->prepare($sql_album_check);
    if ($stmt_album_check) {
        $stmt_album_check->bind_param("s", $album_slug);
        $stmt_album_check->execute();
        $result_album_check = $stmt_album_check->get_result();
        if ($result_album_check->num_rows > 0) {
            $album_data = $result_album_check->fetch_assoc();
            $page_title = $album_data['title'];
            $album_description = $album_data['description'];
        } else {
             // Jika slug tetap tidak ditemukan, 404
             include 'pages/404.php';
             return;
        }
        $stmt_album_check->close();
    }
} else {
    // Ambil deskripsi yang sudah diambil di router (untuk efisiensi)
    $album_description = $page_meta_description ?? ''; // Gunakan meta desc sebagai deskripsi
}


// 2. Ambil SEMUA gambar untuk album ini
$sql_images = "SELECT g.image_url, g.caption, a.id 
               FROM gallery_images g
               JOIN gallery_albums a ON g.album_id = a.id
               WHERE a.slug = ? 
               ORDER BY g.sort_order ASC";
               
$stmt_images = $conn->prepare($sql_images);
if ($stmt_images === false) {
     error_log("Gagal menyiapkan query gambar: " . $conn->error);
     $images = [];
} else {
    $stmt_images->bind_param("s", $album_slug);
    $stmt_images->execute();
    $result_images = $stmt_images->get_result();
    $images = ($result_images) ? $result_images->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_images->close();
}
// $conn->close(); // Biarkan router yang menutup
?>

<section id="gallery-detail" class="py-5">
    <div class="container">
        
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10 text-center">
                <h2 class="section-title text-warning"><?php echo htmlspecialchars($page_title); ?></h2>
                <div class="about-content text-light">
                    <?php 
                    // Tampilkan deskripsi (sudah di-strip_tags di router, tapi kita panggil get_setting_raw untuk Summernote)
                    // Kita perlu ambil ulang deskripsi mentah jika ingin format HTML
                    if (function_exists('get_setting_raw')) {
                         $sql_desc = "SELECT description FROM gallery_albums WHERE slug = ? LIMIT 1";
                         $stmt_desc = $conn->prepare($sql_desc);
                         $stmt_desc->bind_param("s", $album_slug);
                         $stmt_desc->execute();
                         $result_desc = $stmt_desc->get_result();
                         $desc_raw = $result_desc->fetch_assoc()['description'] ?? '';
                         $stmt_desc->close();
                         echo !empty($desc_raw) ? $desc_raw : '<p class="text-light">Deskripsi untuk album ini belum tersedia.</p>';
                    } else {
                         echo '<p class="text-light">' . htmlspecialchars($album_description) . '</p>';
                    }
                    ?>
                </div>
                 <a href="<?php echo BASE_URL; ?>/gallery" class="btn btn-outline-warning mt-3">
                    <i class="bi bi-chevron-left"></i> Kembali ke Semua Album
                </a>
            </div>
        </div>

        <hr style="color: #556b57;">

        <div class="row g-3 gallery-grid mt-4">

            <?php if (!empty($images)): ?>
                <?php foreach ($images as $image):
                    $image_path = BASE_URL . '/assets/img/gallery/' . htmlspecialchars($image['image_url']);
                    $caption = htmlspecialchars($image['caption']);
                ?>
                    <div class="col-md-4 col-lg-3">
                        <a href="<?php echo $image_path; ?>" 
                           class="gallery-item shadow" 
                           data-lightbox="<?php echo $album_slug; // Grup lightbox ?>" 
                           data-title="<?php echo $caption; // Caption untuk lightbox ?>">
                            
                            <img src="<?php echo $image_path; ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo $caption; ?>">
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center text-light lead">Belum ada gambar di album ini.</p>
                </div>
            <?php endif; ?>

        </div>
        </div>
</section>