<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Album dari URL
$album_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$album_id) {
    $_SESSION['flash_message'] = 'ID Album tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
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
        $description = $_POST['description']; // Dari Summernote
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $cover_image_file = $_FILES['cover_image'];
        $existing_image = $_POST['existing_image']; // Nama gambar lama

        // 4. Validasi Dasar
        if (empty($title)) {
            $message = "Judul Album tidak boleh kosong.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar

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

            $image_to_save = $existing_image; // Defaultnya, simpan gambar lama
            $final_filename = $existing_image;

            // 5. Cek jika ada GAMBAR BARU di-upload
            if (isset($cover_image_file) && $cover_image_file['error'] == UPLOAD_ERR_OK && $cover_image_file['size'] > 0) {

                $target_dir = __DIR__ . "/../../../../assets/img/gallery/covers/";
                $file_extension = pathinfo($cover_image_file['name'], PATHINFO_EXTENSION);
                $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($cover_image_file['name']));
                $unique_filename_base = "cover_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
                $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                    $message_type = 'danger';
                } elseif ($cover_image_file['size'] > 5000000) {
                    $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                    $message_type = 'danger';
                }
                // Jika validasi awal OK, coba optimalkan (800px width, 85% quality)
                elseif (optimize_image($cover_image_file['tmp_name'], $target_file, 800, 85)) {
                     $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                     if (!empty($optimized_files)) {
                         $final_filename = basename($optimized_files[0]);
                         $image_to_save = $final_filename; // Update nama file untuk DB

                         // Hapus gambar lama
                         if (!empty($existing_image) && file_exists($target_dir . $existing_image)) {
                             @unlink($target_dir . $existing_image);
                         }
                     } else {
                          $message = "Gagal menemukan file gambar yang dioptimalkan.";
                          $message_type = 'danger';
                     }
                } else { // <-- else dari optimize_image()
                    $message = "Maaf, terjadi kesalahan saat mengunggah/mengoptimalkan file baru Anda.";
                    $message_type = 'danger';
                } // <-- Tutup else optimize_image()

            } // Akhir cek gambar baru

            // 6. Jika tidak ada error dari upload, Lanjut UPDATE Database
            if ($message_type != 'danger') {
                // Pastikan koneksi $conn ada
                if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                    require_once __DIR__ . '/../../../../config.php';
                }

                // Buat slug unik
                $slug = generate_unique_slug($conn, 'gallery_albums', $title, $album_id);

                $sql = "UPDATE gallery_albums SET title = ?, slug = ?, description = ?, cover_image = ?, sort_order = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Database error (prepare failed): " . $conn->error;
                    $message_type = 'danger';
                } else {
                    // 'ssssii' = 4 string, 2 integers
                    $stmt->bind_param("ssssii", $title, $slug, $description, $image_to_save, $sort_order, $album_id);

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = 'Album galeri berhasil diperbarui!';
                        $_SESSION['flash_message_type'] = 'success';
                        header('Location: ' . $admin_base . '/pages/gallery/albums/'); // Kembali ke index
                        exit;
                    } else {
                        $message = "Database error (execute failed): " . $stmt->error;
                        $message_type = 'danger';
                    }
                    $stmt->close();
                }
            } // <-- Tutup if message_type != danger

        } // <-- TUTUP else validasi dasar

    } // <-- TUTUP else validasi CSRF

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================


// ==========================================================
// AMBIL DATA ALBUM UNTUK FORM (JIKA BUKAN POST atau JIKA POST GAGAL)
// ==========================================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}
$sql_select = "SELECT title, slug, description, cover_image, sort_order FROM gallery_albums WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select album: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/gallery/albums/');
     exit;
}
$stmt_select->bind_param("i", $album_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $album_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'Album Galeri tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
    exit;
}
$stmt_select->close();
// $conn->close(); // Jangan tutup di sini

// Set Judul Halaman
$page_title = 'Edit Album: ' . htmlspecialchars($album_data['title']);

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Album Galeri</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/gallery/albums/">Manage Albums</a></li>
        <li class="breadcrumb-item active">Edit Album</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Album
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $album_id; ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($album_data['cover_image']); ?>">
                <input type="hidden" name="original_slug" value="<?php echo htmlspecialchars($album_data['slug']); ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Judul Album</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?php echo htmlspecialchars($album_data['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gambar Sampul Saat Ini:</label><br>
                    <?php if(!empty($album_data['cover_image']) && file_exists(__DIR__ . "/../../../../assets/img/gallery/covers/" . $album_data['cover_image'])): ?>
                         <img src="<?php echo BASE_URL . '/assets/img/gallery/covers/' . htmlspecialchars($album_data['cover_image']); ?>"
                              alt="Cover Lama" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;">
                    <?php else: ?>
                         <span class="text-muted">Tidak ada gambar sampul.</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="cover_image" class="form-label">Upload Cover Baru (Opsional)</label>
                    <input class="form-control" type="file" id="cover_image" name="cover_image">
                    <div class="form-text">Biarkan kosong jika tidak ingin mengganti cover.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Singkat (Opsional)</label>
                    <textarea class="form-control summernote-editor" id="description" name="description" rows="5"><?php echo htmlspecialchars($album_data['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           value="<?php echo htmlspecialchars($album_data['sort_order']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Album
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