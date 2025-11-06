<?php
// ========== Halaman Detail Bisnis (pages/business-detail.php) ==========

// 1. Cek apakah variabel $business_slug ada (dari index.php)
if (!isset($business_slug)) {
    echo "<p class='text-center text-light'>Error: Slug bisnis tidak ditemukan.</p>";
    return;
}

// 2. Ambil koneksi database
if (!$conn) {
    die("Koneksi database tidak tersedia.");
}

// 3. Query 1: Ambil Detail Item Bisnis
// Gunakan prepared statement untuk keamanan
$sql_item = "SELECT title, description, thumbnail_url 
             FROM business_items 
             WHERE slug = ? LIMIT 1";

$stmt_item = $conn->prepare($sql_item);
if ($stmt_item === false) {
    die("Error preparing statement (item): " . $conn->error);
}
$stmt_item->bind_param("s", $business_slug);
$stmt_item->execute();
$result_item = $stmt_item->get_result();

// 4. Cek apakah item bisnis ditemukan
if ($result_item && $result_item->num_rows > 0):
    $item = $result_item->fetch_assoc();
    
    // Set judul halaman
    $page_title = $item['title'];
    $thumb_path = BASE_URL . '/assets/img/business/' . htmlspecialchars($item['thumbnail_url']);

    // 5. Query 2: Ambil "Folder" Galeri yang Terkait
    // (Hanya jika item bisnis ditemukan)
    
    $sql_galleries = "SELECT gallery_slug, title, description, icon_class 
                      FROM gallery_groups 
                      WHERE business_slug = ? 
                      ORDER BY id ASC";
                      
    $stmt_galleries = $conn->prepare($sql_galleries);
    if ($stmt_galleries === false) {
        die("Error preparing statement (galleries): " . $conn->error);
    }
    $stmt_galleries->bind_param("s", $business_slug);
    $stmt_galleries->execute();
    $result_galleries = $stmt_galleries->get_result();
    
    // Ambil semua galeri ke dalam array
    $galleries = $result_galleries->fetch_all(MYSQLI_ASSOC);
    $stmt_galleries->close();

?>

    <section id="business-description" class="bg-dark py-5">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($item['title']); ?></h2>
            <div class="row justify-content-center">
                <div class="col-md-10 text-light">
                    <a href="<?php echo BASE_URL; ?>/business" class="btn btn-outline-warning mb-4">
                        <i class="bi bi-chevron-left"></i> Kembali ke Lini Bisnis
                    </a>
                    
                    <img 
                      src="<?php echo $thumb_path; ?>" 
                      alt="<?php echo htmlspecialchars($item['title']); ?>" 
                      class="img-fluid rounded mb-4 shadow"
                      style="aspect-ratio: 16/9; object-fit: cover; width: 100%;"
                    >
                    
                    <div class="business-content-body">
                        <?php 
                        // nl2br() untuk menghargai 'Enter' dari database
                        echo nl2br($item['description']);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php 
    // 6. Tampilkan Bagian Galeri (HANYA JIKA ADA GALERI)
    if (!empty($galleries)): 
    ?>
        <section id="business-gallery-links" class="py-5">
            <div class="container">
                <h3 class="text-center text-warning mb-4">Dokumentasi & Galeri</h3>
                <div class="row g-4 justify-content-center">
                    
                    <?php foreach ($galleries as $gallery): 
                        $gallery_url = BASE_URL . '/gallery/' . htmlspecialchars($gallery['gallery_slug']);
                    ?>
                        <div class="col-md-4 col-lg-3">
                            <a href="<?php echo $gallery_url; ?>" class="text-decoration-none">
                                <div class="card bg-secondary text-light p-3 h-100 text-center shadow card-hover-effect">
                                    <i class="<?php echo htmlspecialchars($gallery['icon_class']); ?>" style="font-size: 4rem; color: #ffd700"></i>
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">
                                            <?php echo htmlspecialchars($gallery['title']); ?>
                                        </h5>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars($gallery['description']); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php
else:
    // 7. Jika Item Bisnis Tidak Ditemukan (slug salah)
    include 'pages/404.php';
endif;

// 8. Tutup statement
$stmt_item->close();
?>