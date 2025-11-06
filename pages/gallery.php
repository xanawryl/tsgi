<?php
// Pastikan koneksi $conn ada (dari index.php -> config.php)
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../config.php';
}

// ==========================================================
// LOGIKA SORTIR (BARU)
// ==========================================================

// 1. Tentukan opsi sortir yang diizinkan (keamanan)
$allowed_sorts = [
    'default' => 'sort_order ASC, created_at DESC', // Urutan Pilihan (dari admin)
    'date_desc' => 'created_at DESC', // Terbaru
    'date_asc' => 'created_at ASC',   // Terlama
    'title_asc' => 'title ASC',       // Judul (A-Z)
    'title_desc' => 'title DESC'      // Judul (Z-A)
];

// 2. Ambil pilihan sortir dari URL (?sort=...)
// Jika tidak ada atau tidak valid, gunakan 'default'
$sort_key = $_GET['sort'] ?? 'default';
if (!array_key_exists($sort_key, $allowed_sorts)) {
    $sort_key = 'default';
}

// 3. Siapkan string ORDER BY untuk SQL
$order_by_sql = $allowed_sorts[$sort_key];

// ==========================================================
// AKHIR LOGIKA SORTIR
// ==========================================================


// 4. Ambil semua album dari database (dengan ORDER BY dinamis)
$sql_albums = "SELECT title, slug, description, cover_image, created_at 
               FROM gallery_albums 
               ORDER BY $order_by_sql"; // <-- Menggunakan variabel dinamis

$result_albums = $conn->query($sql_albums);
$albums = ($result_albums && $result_albums->num_rows > 0) ? $result_albums->fetch_all(MYSQLI_ASSOC) : [];

// Fungsi helper untuk excerpt
if (!function_exists('get_excerpt')) {
    function get_excerpt($text, $length = 100) {
        $text = strip_tags($text); // Hapus tag HTML dari summernote
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length);
            $text = substr($text, 0, strrpos($text, ' '));
            $text .= '...';
        }
        return $text;
    }
}
?>

<section id="gallery-archive" class="py-5">
    <div class="container">
        <h2 class="section-title text-warning">Gallery</h2>
        <p class="text-center text-light" style="max-width: 600px; margin: 0 auto 40px auto;">
            Dokumentasi foto dari berbagai kegiatan, proyek, dan pencapaian kami.
        </p>

        <div class="row mb-4 justify-content-end">
            <div class="col-md-5 col-lg-4">
                <form method="GET" action="<?php echo BASE_URL; ?>/gallery" class="d-flex align-items-center">
                    <label for="sort-albums" class="form-label text-light me-2 mb-0 flex-shrink-0">Urutkan:</label>
                    <select class="form-select" id="sort-albums" name="sort" onchange="this.form.submit()">
                        <option value="default" <?php echo ($sort_key == 'default') ? 'selected' : ''; ?>>Urutan Pilihan</option>
                        <option value="date_desc" <?php echo ($sort_key == 'date_desc') ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="date_asc" <?php echo ($sort_key == 'date_asc') ? 'selected' : ''; ?>>Terlama</option>
                        <option value="title_asc" <?php echo ($sort_key == 'title_asc') ? 'selected' : ''; ?>>Judul (A-Z)</option>
                        <option value="title_desc" <?php echo ($sort_key == 'title_desc') ? 'selected' : ''; ?>>Judul (Z-A)</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="row g-4">

            <?php if (!empty($albums)): ?>
                <?php foreach ($albums as $album):
                    $album_url = BASE_URL . '/gallery/' . htmlspecialchars($album['slug']);
                    $cover_path = BASE_URL . '/assets/img/gallery/covers/' . htmlspecialchars($album['cover_image']);
                    // Cek file
                    $cover_exists = (!empty($album['cover_image']) && file_exists(__DIR__ . "/../assets/img/gallery/covers/" . $album['cover_image']));
                    $description = htmlspecialchars(get_excerpt($album['description']));
                ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="<?php echo $album_url; ?>" class="text-decoration-none">
                            <div class="card bg-secondary text-light h-100 shadow card-hover-effect gallery-album-card">
                                <?php if($cover_exists): ?>
                                     <img src="<?php echo $cover_path; ?>" 
                                          class="card-img-top" 
                                          alt="<?php echo htmlspecialchars($album['title']); ?>">
                                <?php else: ?>
                                      <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="aspect-ratio: 16/9;">
                                          <i class="bi bi-images fs-1 text-muted"></i>
                                      </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-warning"><?php echo htmlspecialchars($album['title']); ?></h5>
                                    <p class="card-text small mb-4" style="color: #eee;">
                                        <?php echo $description; ?>
                                    </p>
                                    <span class="btn btn-gold btn-custom mt-auto align-self-start">Lihat Album</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center text-light lead">Belum ada album galeri yang dipublikasikan.</p>
                </div>
            <?php endif; ?>

        </div>
        </div>
</section>