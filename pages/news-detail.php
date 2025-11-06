<?php
// ========== Halaman Detail Berita (pages/news-detail.php) ==========

// 1. Cek apakah variabel $news_slug ada (dari index.php)
if (!isset($news_slug)) {
    echo "<p class='text-center text-light'>Error: Slug berita tidak ditemukan.</p>";
    // Jika tidak ada slug, hentikan
    return;
}

// 2. Ambil koneksi database
if (!$conn) {
    die("Koneksi database tidak tersedia.");
}

// 3. Siapkan Query SQL (Menggunakan Prepared Statements)
// Ini SANGAT PENTING untuk keamanan (mencegah SQL Injection)
$sql = "SELECT title, content, image_url, created_at FROM news WHERE slug = ? LIMIT 1";

// Siapkan statement
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// 's' berarti parameter adalah string
$stmt->bind_param("s", $news_slug);

// Eksekusi statement
$stmt->execute();

// Ambil hasilnya
$result = $stmt->get_result();

// 4. Cek apakah berita ditemukan
if ($result && $result->num_rows > 0):
    // Jika berita ada, ambil datanya
    $row = $result->fetch_assoc();

    // Set judul halaman (ini akan mengubah <title> di header.php)
    // Kita perlu melakukannya di sini karena $page_title di index.php terlalu umum
    $page_title = $row['title']; 

    // Format tanggal
    $date = new DateTime($row['created_at']);
    $formatted_date = strtoupper($date->format('d F Y')); // misal: 23 OCTOBER 2025

    // Path gambar
    $image_path = BASE_URL . '/assets/img/news/' . htmlspecialchars($row['image_url']);

?>

    <section id="news-detail-content" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-light">

                    <a href="<?php echo BASE_URL; ?>/news" class="btn btn-outline-warning mb-4">
                        <i class="bi bi-chevron-left"></i> Kembali ke Semua Berita
                    </a>

                    <h1 class="section-title text-warning" style="text-align: left; font-size: 2.5rem;">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </h1>

                    <div class="d-flex align-items-center mb-3" style="color: #ccc;">
                        <i class="bi bi-calendar-event me-2"></i> <?php echo $formatted_date; ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-person-circle me-2"></i> ADMIN
                    </div>
                    <div class="news-content-body">
                        <?php
                        // Kita gunakan nl2br() untuk mengubah baris baru (Enter) menjadi tag <br>
                        // Ini membuat format teks dari database tetap rapi
                        echo nl2br($row['content']);
                        ?>
                    </div>

                </div>
            </div>
        </div>
    </section>

<?php
else:
    // 5. Jika Berita Tidak Ditemukan (slug salah)
    // Kita tampilkan halaman 404
    include 'pages/404.php';
endif;

// 6. Tutup statement
$stmt->close();
?>