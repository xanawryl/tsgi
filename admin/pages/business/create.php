<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';

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
        $description = $_POST['description']; // Jangan trim Summernote
        $meta_description = trim($_POST['meta_description']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $thumbnail_file = $_FILES['thumbnail_file'];

        // 3. Validasi Sederhana
        if (empty($title) || empty($description)) {
            $message = "Judul dan Deskripsi tidak boleh kosong.";
            $message_type = 'danger';
        }
        // 4. Validasi Gambar
        elseif (!isset($thumbnail_file) || $thumbnail_file['error'] != UPLOAD_ERR_OK || $thumbnail_file['size'] == 0) {
            $message = "Anda harus mengunggah gambar thumbnail yang valid.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar & gambar

            // ====> DEFINISI FUNGSI generate_unique_slug DI SINI <====
            /** @ignore */
             function generate_unique_slug(mysqli $db, string $table, string $title, ?int $exclude_id = null): string {
                 $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                 if (empty($base_slug)) { $base_slug = 'item-' . time(); }
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
            $target_dir = __DIR__ . "/../../../assets/img/business/";
            $file_extension = pathinfo($thumbnail_file['name'], PATHINFO_EXTENSION);
            $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($thumbnail_file['name']));
            $unique_filename_base = "business_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
            $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                $message_type = 'danger';
            } elseif ($thumbnail_file['size'] > 5000000) {
                $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                $message_type = 'danger';
            }
            // Jika validasi awal OK, coba optimalkan
            // Ganti move_uploaded_file dengan optimize_image
            elseif (optimize_image($thumbnail_file['tmp_name'], $target_file)) {

                 // ========== PROSES INSERT DATABASE ==========
                 // Ambil nama file AKHIR setelah dioptimasi
                 $final_filename = '';
                 $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                 if (!empty($optimized_files)) {
                     $final_filename = basename($optimized_files[0]);
                 } else {
                      $message = "Gagal menemukan file gambar yang dioptimalkan.";
                      $message_type = 'danger';
                 }

                 if($message_type != 'danger') {
                    // Buat slug unik menggunakan fungsi
                    $slug = generate_unique_slug($conn, 'business_items', $title);

                    // Siapkan query
                    $sql = "INSERT INTO business_items (slug, title, description, meta_description, thumbnail_url, sort_order)
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt === false) {
                         $message = "Database error (prepare failed): " . $conn->error;
                         $message_type = 'danger';
                         @unlink($target_dir . $final_filename);
                    } else {
                        // 'sssssi' = 5 string, 1 integer
                        $stmt->bind_param("sssssi", $slug, $title, $description, $meta_description, $final_filename, $sort_order);

                        if ($stmt->execute()) {
                            $_SESSION['flash_message'] = 'Item bisnis baru berhasil ditambahkan!';
                            $_SESSION['flash_message_type'] = 'success';
                            header('Location: ' . $admin_base . '/pages/business/');
                            exit;
                        } else {
                            $message = "Database error (execute failed): " . $stmt->error;
                            $message_type = 'danger';
                             @unlink($target_dir . $final_filename);
                        }
                        $stmt->close();
                    }
                 } // <-- Tutup if message_type != danger (setelah cek final filename)

            } else { // <-- else dari optimize_image()
                $message = "Maaf, terjadi kesalahan saat mengunggah atau mengoptimalkan file Anda.";
                 if (!file_exists($thumbnail_file['tmp_name'])) { $message .= " File temporary tidak ditemukan."; }
                 elseif (!is_readable($thumbnail_file['tmp_name'])) { $message .= " File temporary tidak dapat dibaca."; }
                $message_type = 'danger';
            } // <-- Tutup else optimize_image()

        } // <-- Tutup else validasi dasar & gambar

    } // <-- TUTUP else validasi CSRF
    // Jangan tutup koneksi $conn di sini jika POST gagal
} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM
// ==========================================================


// Set Judul Halaman
$page_title = 'Tambah Item Bisnis Baru';

// Muat Header Admin
require_once __DIR__ . '/../../includes/header.php';

// Muat Sidebar Admin
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Item Bisnis Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/business/">Manage Business</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Item Bisnis Baru
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
                    <label for="title" class="form-label">Judul Item (misal: PKS)</label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="thumbnail_file" class="form-label">Gambar Thumbnail</label>
                    <input class="form-control" type="file" id="thumbnail_file" name="thumbnail_file" required>
                    <div class="form-text">Tipe file: JPG, PNG, GIF, WEBP. Maks 5MB.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Lengkap</label>
                    <textarea class="form-control summernote-editor" id="description" name="description" rows="10" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description (SEO)</label>
                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3" maxlength="160"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
                    <div class="form-text">Deskripsi singkat (maks 160 karakter) untuk Google.</div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" required
                           value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : '0'; ?>">
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Simpan Item Bisnis
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
require_once __DIR__ . '/../../includes/footer.php';
?>