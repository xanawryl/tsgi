<?php
// ========== Halaman Arsip Bisnis (pages/business.php) dengan Load More ==========

// 1. Ambil koneksi database
if (!$conn) {
    die("Koneksi database tidak tersedia.");
}

// ========== LOGIKA AWAL LOAD MORE ==========
$items_per_page = 8; // **MUAT 8 ITEM AWAL (2 baris x 4 kolom)**

// Hitung total item bisnis
$sql_count = "SELECT COUNT(id) as total FROM business_items";
$result_count = $conn->query($sql_count);
$total_items = ($result_count && $result_count->num_rows > 0) ? $result_count->fetch_assoc()['total'] : 0;
// ========== AKHIR LOGIKA AWAL ==========

// 2. Siapkan Query SQL (Hanya untuk halaman PERTAMA)
$sql = "SELECT slug, title, description, thumbnail_url
        FROM business_items
        ORDER BY sort_order ASC
        LIMIT ?"; // Hanya LIMIT

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
// 'i' = 1 integer
$stmt->bind_param("i", $items_per_page);
$stmt->execute();
$result = $stmt->get_result();

/** @ignore */ // Fungsi helper excerpt (jika belum ada di file ini)
function get_excerpt($text, $length = 100) { // Kurangi panjang excerpt
    $text = strip_tags($text); // Hapus tag HTML dari summernote
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}
?>

<section id="business-archive" class="py-5">
    <div class="container">
        <h2 class="section-title bg-dark py-5">Our Business</h2>
        <p class="text-center text-light" style="max-width: 600px; margin: 0 auto 40px auto;">
            Jelajahi lini bisnis utama kami. Kami menyediakan produk berkualitas
            tinggi untuk pasar domestik dan internasional.
        </p>

        <div id="business-container">
            <div class="row g-4 justify-content-center" id="business-grid">

                <?php
                // 3. Looping Data Bisnis (Halaman Pertama)
                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $title = htmlspecialchars($row['title']);
                        $slug = htmlspecialchars($row['slug']);
                        $excerpt = htmlspecialchars(get_excerpt($row['description']));
                        $thumb_path = BASE_URL . '/assets/img/business/' . htmlspecialchars($row['thumbnail_url']);
                        $detail_url = BASE_URL . '/business/' . $slug;
                ?>
                        <div class="col-md-6 col-lg-3">
                            <a href="<?php echo $detail_url; ?>" class="text-decoration-none">
                                <div class="card bg-secondary text-light p-3 h-100 shadow card-hover-effect">
                                    <img src="<?php echo $thumb_path; ?>"
                                         class="card-img-top rounded mb-3"
                                         alt="<?php echo $title; ?>"
                                         style="aspect-ratio: 16/9; object-fit: cover;">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title text-warning"><?php echo $title; ?></h5>
                                        <p class="card-text mb-4" style="font-size: 0.9rem;"><?php echo $excerpt; ?></p>
                                        <span class="btn btn-gold btn-custom mt-auto align-self-start" style="max-width: 150px;">
                                            Learn More <i class="bi bi-chevron-right"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                <?php
                    endwhile;
                else:
                ?>
                    <div class="col-12">
                        <p class="text-center text-light lead">Lini bisnis belum tersedia.</p>
                    </div>
                <?php
                endif;
                $stmt->close();
                // Jangan tutup koneksi $conn
                ?>

            </div>
            <?php
            $next_page = 2;
            if ($total_items > $items_per_page):
            ?>
            <div class="text-center mt-5">
                <button id="load-more-business-btn" class="btn btn-gold btn-custom" data-page="<?php echo $next_page; ?>" data-items="<?php echo $items_per_page; ?>" data-total="<?php echo $total_items; ?>">
                    Load More Business
                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
            <?php endif; ?>
            </div>
        </div>
</section>