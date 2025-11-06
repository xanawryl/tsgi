<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Berita dari URL dan Validasi
$news_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$news_id) {
    $_SESSION['flash_message'] = 'ID Berita tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/news/');
    exit;
}

// ==========================================================
// PROSES FORM UPDATE (POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ===== VALIDASI CSRF TOKEN =====
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else { // <-- Buka else CSRF
    // ===== AKHIR VALIDASI CSRF =====

        // 3. Ambil data dari form (HANYA JIKA TOKEN VALID)
        $title = trim($_POST['title']);
        $content = $_POST['content']; // Jangan trim Summernote
        $meta_description = trim($_POST['meta_description']);
        $image_file = $_FILES['image_file'];
        $existing_image = $_POST['existing_image']; // Nama gambar lama
        $original_slug = $_POST['original_slug'] ?? ''; // Ambil slug asli (jika ada)

        // 4. Validasi Dasar
        if (empty($title) || empty($content)) {
            $message = "Judul dan Konten tidak boleh kosong.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar

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

            $image_to_save = $existing_image; // Defaultnya, simpan gambar lama
            $final_filename = $existing_image; // Nama file akhir, defaultnya yg lama

            // 5. Cek jika ada GAMBAR BARU di-upload
            if (isset($image_file) && $image_file['error'] == UPLOAD_ERR_OK && $image_file['size'] > 0) {

                $target_dir = __DIR__ . "/../../../assets/img/news/";
                $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
                $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($image_file['name']));
                $unique_filename_base = "news_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
                $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                    $message_type = 'danger';
                } elseif ($image_file['size'] > 5000000) {
                    $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                    $message_type = 'danger';
                }
                // Jika validasi awal OK, coba optimalkan
                // Ganti move_uploaded_file dengan optimize_image
                elseif (optimize_image($image_file['tmp_name'], $target_file)) {
                    // Ambil nama file AKHIR setelah dioptimasi
                    $final_path_parts = pathinfo($target_file);
                    // Cari file aktual yang disimpan
                    $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                    if (!empty($optimized_files)) {
                        $final_filename = basename($optimized_files[0]); // Nama file baru
                        $image_to_save = $final_filename; // Update nama file untuk DB

                        // Hapus gambar lama JIKA upload & optimasi berhasil
                        if (!empty($existing_image) && file_exists($target_dir . $existing_image)) {
                            @unlink($target_dir . $existing_image);
                        }
                    } else {
                         $message = "Gagal menemukan file gambar yang dioptimalkan setelah upload.";
                         $message_type = 'danger';
                    }

                } else { // <-- else dari optimize_image()
                    $message = "Maaf, terjadi kesalahan saat mengunggah atau mengoptimalkan file baru Anda.";
                    $message_type = 'danger';
                } // <-- Tutup else optimize_image()

            } // Akhir cek gambar baru

            // 6. Jika tidak ada error dari upload, Lanjut UPDATE Database
            if ($message_type != 'danger') {

                // Buat slug unik menggunakan fungsi
                $slug = generate_unique_slug($conn, 'news', $title, $news_id);

                $sql = "UPDATE news SET slug = ?, title = ?, content = ?, meta_description = ?, image_url = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Database error (prepare failed): " . $conn->error;
                    $message_type = 'danger';
                } else {
                    // 'sssssi' = 5 strings, 1 integer
                    $stmt->bind_param("sssssi", $slug, $title, $content, $meta_description, $image_to_save, $news_id);

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = 'Berita berhasil diperbarui!';
                        $_SESSION['flash_message_type'] = 'success';
                        header('Location: ' . $admin_base . '/pages/news/');
                        exit;
                    } else {
                        $message = "Database error (execute failed): " . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                } // <-- Tutup else prepare update
            } // <-- Tutup if message_type != danger

        } // <-- TUTUP else validasi dasar

    } // <-- TUTUP else validasi CSRF

    // Jangan tutup koneksi $conn di sini jika POST gagal dan perlu reload form
    // $conn->close();

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================


// ==========================================================
// AMBIL DATA BERITA UNTUK FORM (JIKA BUKAN POST atau JIKA POST GAGAL)
// ==========================================================
// Pastikan koneksi masih ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
$sql_select = "SELECT title, content, meta_description, image_url, slug FROM news WHERE id = ?"; // Ambil slug juga
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/news/');
     exit;
}
$stmt_select->bind_param("i", $news_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $news_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'Berita tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/news/');
    exit;
}
$stmt_select->close();
// $conn->close(); // Jangan tutup di sini

// Set Judul Halaman
$page_title = 'Edit Berita: ' . htmlspecialchars($news_data['title']);

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Berita</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/news/">Manage News</a></li>
        <li class="breadcrumb-item active">Edit Berita</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Berita
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $news_id; ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($news_data['image_url']); ?>">
                <input type="hidden" name="original_slug" value="<?php echo htmlspecialchars($news_data['slug']); ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Judul Berita</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?php echo htmlspecialchars($news_data['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gambar Utama Saat Ini:</label><br>
                    <?php if(!empty($news_data['image_url']) && file_exists(__DIR__ . "/../../../assets/img/news/" . $news_data['image_url'])): ?>
                         <img src="<?php echo BASE_URL . '/assets/img/news/' . htmlspecialchars($news_data['image_url']); ?>"
                              alt="Gambar Lama" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;">
                    <?php else: ?>
                         <span class="text-muted">Tidak ada gambar.</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="image_file" class="form-label">Upload Gambar Baru (Opsional)</label>
                    <input class="form-control" type="file" id="image_file" name="image_file">
                    <div class="form-text">Biarkan kosong jika tidak ingin mengganti gambar. Tipe: JPG, PNG, GIF, WEBP. Maks 5MB.</div>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Isi Konten Berita</label>
                    <textarea class="form-control summernote-editor" id="content" name="content" rows="10" required><?php echo htmlspecialchars($news_data['content']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description (SEO)</label>
                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3" maxlength="160"><?php echo htmlspecialchars($news_data['meta_description'] ?? ''); ?></textarea>
                    <div class="form-text">Deskripsi singkat (maks 160 karakter) untuk Google.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Berita
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