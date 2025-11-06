<?php
// ========== Halaman Arsip Berita (pages/news.php) dengan Load More ==========

// 1. Ambil koneksi database
if (!$conn) {
    die("Koneksi database tidak tersedia.");
}

// ========== LOGIKA AWAL ==========
$items_per_page = 8; // Berapa item yang dimuat awal & per klik

// Hitung total berita (untuk tahu kapan harus menyembunyikan tombol)
$sql_count = "SELECT COUNT(id) as total FROM news";
$result_count = $conn->query($sql_count);
$total_items = ($result_count && $result_count->num_rows > 0) ? $result_count->fetch_assoc()['total'] : 0;

// ========== AKHIR LOGIKA AWAL ==========

// 2. Siapkan Query SQL (Hanya untuk halaman PERTAMA)
$sql = "SELECT id, slug, title, image_url, created_at
        FROM news
        ORDER BY created_at DESC
        LIMIT ?"; // Hanya LIMIT, OFFSET 0

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
// 'i' = 1 integer
$stmt->bind_param("i", $items_per_page);
$stmt->execute();
$result = $stmt->get_result();

?>

<section id="news-archive" class="py-5">
    <div class="container">
        <h2 class="section-title bg-dark py-5">Latest News</h2>
        <p class="text-center text-dark" style="max-width: 600px; margin: 0 auto 40px auto;">
            Ikuti perkembangan terbaru dan berita seputar TSGI, industri, dan
            pencapaian kami.
        </p>

        <div id="news-container">
            <div class="row g-4" id="news-grid">

                <?php
                // 3. Looping Data Berita (Halaman Pertama)
                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        // Variabel (sama seperti sebelumnya)
                        $date = new DateTime($row['created_at']);
                        $formatted_date = strtoupper($date->format('d M, Y'));
                        $image_path = BASE_URL . '/assets/img/news/' . htmlspecialchars($row['image_url']);
                        $detail_url = BASE_URL . '/news/' . htmlspecialchars($row['slug']);
                ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card mb-4 shadow-sm h-100">
                                <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>">
                                <div class="card-body d-flex flex-column">
                                    <span class="badge bg-light text-dark mb-2 align-self-start">
                                        <?php echo $formatted_date; ?>
                                    </span>
                                    <h5 class="card-title" style="color:#222e23; font-size:18px; font-weight:700;">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </h5>
                                    <div class="d-flex align-items-center mb-2" style="color:#526b7b; font-size:13px;">
                                        <i class="bi bi-person-circle me-1"></i> ADMIN
                                    </div>
                                    <a href="<?php echo $detail_url; ?>" class="card-link text-decoration-none mt-auto" style="color:#EF1520; font-weight:500;">
                                        READ MORE <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                <?php
                    endwhile;
                else:
                ?>
                    <div class="col-12">
                        <p class="text-center lead text-dark">Belum ada berita yang dipublikasikan.</p>
                    </div>
                <?php
                endif;
                $stmt->close();
                // JANGAN tutup koneksi $conn di sini
                ?>

            </div>
            <?php
            // Hitung halaman berikutnya
            $next_page = 2;
            // Tampilkan tombol hanya jika total item > item per halaman
            if ($total_items > $items_per_page):
            ?>
            <div class="text-center mt-5">
                <button id="load-more-news-btn" class="btn btn-gold btn-custom" data-page="<?php echo $next_page; ?>" data-items="<?php echo $items_per_page; ?>" data-total="<?php echo $total_items; ?>">
                    Load More News
                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
            <?php endif; ?>
            </div>
        </div>
</section>