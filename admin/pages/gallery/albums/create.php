<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// ==========================================================
// PROSES FORM SAAT DI-SUBMIT (METODE POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ===== VALIDASI CSRF TOKEN =====
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else { // <-- Buka else CSRF
    // ===== AKHIR VALIDASI CSRF =====

        // 2. Ambil data dari form (HANYA JIKA TOKEN VALID)
        $title = trim($_POST['title']);
        $description = $_POST['description']; // Dari Summernote
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $cover_image_file = $_FILES['cover_image'];

        // 3. Validasi Sederhana
        if (empty($title)) {
            $message = "Judul Album tidak boleh kosong.";
            $message_type = 'danger';
        }
        // 4. Validasi Gambar (Opsional, tapi disarankan)
        elseif (!isset($cover_image_file) || $cover_image_file['error'] != UPLOAD_ERR_OK || $cover_image_file['size'] == 0) {
            $message = "Anda harus mengunggah Gambar Sampul (Cover).";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar & gambar

            // ====> DEFINISI FUNGSI generate_unique_slug DI SINI <====
            /** @ignore */
            function generate_unique_slug(mysqli $db, string $table, string $title, ?int $exclude_id = null): string {
                $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                if (empty($base_slug)) { $base_slug = 'album-' . time(); }
                $slug = $base_slug;
                $counter = 2;
                while (true) {
                    $sql_check = "SELECT id FROM `{$table}` WHERE slug = ?";
                    $params = [$slug];
                    $types = "s";
                    if ($exclude_id !== null) { $sql_check .= " AND id != ?"; $params[] = $exclude_id; $types .= "i"; }
                    $stmt_check = $db->prepare($sql_check);
                    if ($stmt_check === false) { error_log("Prepare failed: (" . $db->errno . ") " . $db->error); return $base_slug . '-' . time(); }
                    $stmt_check->bind_param($types, ...$params);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    $stmt_check->close();
                    if ($result_check->num_rows == 0) { break; }
                    else { $slug = $base_slug . '-' . $counter; $counter++; }
                }
                return $slug;
            }
            // ----- Akhir Fungsi -----

            // ========== PROSES UPLOAD & OPTIMASI GAMBAR ==========
            $target_dir = __DIR__ . "/../../../../assets/img/gallery/covers/"; // Path folder baru
            
            // Buat folder jika belum ada
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_extension = pathinfo($cover_image_file['name'], PATHINFO_EXTENSION);
            $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($cover_image_file['name']));
            // Nama file unik
            $unique_filename_base = "cover_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
            $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                $message_type = 'danger';
            } elseif ($cover_image_file['size'] > 5000000) { // Maks 5MB
                $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                $message_type = 'danger';
            }
            // Gunakan optimize_image() (dari config.php)
            // Kita set max_width 800px untuk cover
            elseif (optimize_image($cover_image_file['tmp_name'], $target_file, 800, 85)) { // 800px width, 85% quality

                // ========== PROSES INSERT DATABASE ==========
                 $final_filename = '';
                 $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                 if (!empty($optimized_files)) {
                     $final_filename = basename($optimized_files[0]);
                 } else {
                      $message = "Gagal menemukan file gambar yang dioptimalkan.";
                      $message_type = 'danger';
                 }

                 if ($message_type != 'danger') {
                    // Pastikan koneksi $conn ada
                    if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                        require_once __DIR__ . '/../../../../config.php';
                    }

                    // Buat slug unik
                    $slug = generate_unique_slug($conn, 'gallery_albums', $title);

                    $sql_insert = "INSERT INTO gallery_albums (title, slug, description, cover_image, sort_order) VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);

                    if ($stmt_insert === false) {
                        $message = "Database error (prepare failed): " . $conn->error;
                        $message_type = 'danger';
                        @unlink($target_dir . $final_filename);
                    } else {
                        // 'ssssi' = 4 string, 1 integer
                        $stmt_insert->bind_param("ssssi", $title, $slug, $description, $final_filename, $sort_order);

                        if ($stmt_insert->execute()) {
                            $_SESSION['flash_message'] = 'Album galeri baru berhasil ditambahkan!';
                            $_SESSION['flash_message_type'] = 'success';
                            header('Location: ' . $admin_base . '/pages/gallery/albums/'); // Kembali ke index
                            exit;
                        } else {
                            $message = "Database error (execute failed): " . $stmt_insert->error;
                            $message_type = 'danger';
                            @unlink($target_dir . $final_filename);
                        }
                        $stmt_insert->close();
                    }
                 } // <-- Tutup if message_type != danger

            } else { // <-- else dari optimize_image()
                $message = "Maaf, terjadi kesalahan saat mengunggah atau mengoptimalkan file Anda.";
                $message_type = 'danger';
            } // <-- Tutup else optimize_image()

        } // <-- TUTUP else validasi dasar & gambar

    } // <-- TUTUP else validasi CSRF

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM
// ==========================================================

// Set Judul Halaman
$page_title = 'Tambah Album Galeri Baru';

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Album Galeri Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/gallery/albums/">Manage Albums</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle me-1"></i>
            Formulir Album Baru
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="create.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label for="title" class="form-label">Judul Album</label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    <div class="form-text">Slug (URL) akan dibuat otomatis dari judul ini.</div>
                </div>
                
                <div class="mb-3">
                    <label for="cover_image" class="form-label">Gambar Sampul (Cover)</label>
                    <input class="form-control" type="file" id="cover_image" name="cover_image" required>
                    <div class="form-text">Rekomendasi rasio 16:9 atau 4:3. Tipe: JPG, PNG, WEBP. Maks 5MB.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Singkat (Opsional)</label>
                    <textarea class="form-control summernote-editor" id="description" name="description" rows="5"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" required
                           value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : '0'; ?>">
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Simpan Album
                </button>
                <a href="index.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>

        </div>
    </div>
</div>
<?php
// Tutup koneksi jika belum ditutup
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
// Muat Footer Admin
require_once __DIR__ . '/../../../includes/footer.php';
?>