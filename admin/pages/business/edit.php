<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Item dari URL
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$item_id) {
    $_SESSION['flash_message'] = 'ID Item Bisnis tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/business/');
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
        $description = $_POST['description']; // Jangan trim Summernote
        $meta_description = trim($_POST['meta_description']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $thumbnail_file = $_FILES['thumbnail_file'];
        $existing_thumb = $_POST['existing_thumb'];
        $original_slug = $_POST['original_slug'] ?? '';

        // 4. Validasi Dasar
        if (empty($title) || empty($description)) {
            $message = "Judul dan Deskripsi tidak boleh kosong.";
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

            $thumb_to_save = $existing_thumb; // Defaultnya, simpan gambar lama
            $final_filename = $existing_thumb;

            // 5. Cek jika ada GAMBAR BARU di-upload
            if (isset($thumbnail_file) && $thumbnail_file['error'] == UPLOAD_ERR_OK && $thumbnail_file['size'] > 0) {

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
                     // Ambil nama file AKHIR setelah dioptimasi
                     $final_filename = '';
                     $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                     if (!empty($optimized_files)) {
                         $final_filename = basename($optimized_files[0]);
                         $thumb_to_save = $final_filename; // Update nama file untuk DB

                         // Hapus gambar lama JIKA upload & optimasi berhasil
                         if (!empty($existing_thumb) && file_exists($target_dir . $existing_thumb)) {
                             @unlink($target_dir . $existing_thumb);
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
                $slug = generate_unique_slug($conn, 'business_items', $title, $item_id);

                $sql = "UPDATE business_items SET slug = ?, title = ?, description = ?, meta_description = ?, thumbnail_url = ?, sort_order = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Database error (prepare failed): " . $conn->error;
                    $message_type = 'danger';
                } else {
                    // 'sssssii' = 5 strings, 2 integers
                    $stmt->bind_param("sssssii", $slug, $title, $description, $meta_description, $thumb_to_save, $sort_order, $item_id);

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = 'Item Bisnis berhasil diperbarui!';
                        $_SESSION['flash_message_type'] = 'success';
                        header('Location: ' . $admin_base . '/pages/business/');
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

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================


// ==========================================================
// AMBIL DATA ITEM UNTUK FORM (JIKA BUKAN POST atau JIKA POST GAGAL)
// ==========================================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
$sql_select = "SELECT title, description, meta_description, thumbnail_url, sort_order, slug FROM business_items WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select item: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/business/');
     exit;
}
$stmt_select->bind_param("i", $item_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $item_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'Item Bisnis tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/business/');
    exit;
}
$stmt_select->close();
// Jangan tutup koneksi $conn di sini

// Set Judul Halaman
$page_title = 'Edit Item Bisnis: ' . htmlspecialchars($item_data['title']);

// Muat Header Admin
require_once __DIR__ . '/../../includes/header.php';

// Muat Sidebar Admin
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Item Bisnis</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/business/">Manage Business</a></li>
        <li class="breadcrumb-item active">Edit Item</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Item Bisnis
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $item_id; ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="existing_thumb" value="<?php echo htmlspecialchars($item_data['thumbnail_url']); ?>">
                <input type="hidden" name="original_slug" value="<?php echo htmlspecialchars($item_data['slug']); ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Judul Item</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?php echo htmlspecialchars($item_data['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Thumbnail Saat Ini:</label><br>
                     <?php if(!empty($item_data['thumbnail_url']) && file_exists(__DIR__ . "/../../../assets/img/business/" . $item_data['thumbnail_url'])): ?>
                         <img src="<?php echo BASE_URL . '/assets/img/business/' . htmlspecialchars($item_data['thumbnail_url']); ?>"
                              alt="Thumbnail Lama" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;">
                     <?php else: ?>
                          <span class="text-muted">Tidak ada thumbnail.</span>
                     <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="thumbnail_file" class="form-label">Upload Thumbnail Baru (Opsional)</label>
                    <input class="form-control" type="file" id="thumbnail_file" name="thumbnail_file">
                    <div class="form-text">Biarkan kosong jika tidak ingin mengganti thumbnail.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Lengkap</label>
                    <textarea class="form-control summernote-editor" id="description" name="description" rows="10" required><?php echo htmlspecialchars($item_data['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description (SEO)</label>
                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3" maxlength="160"><?php echo htmlspecialchars($item_data['meta_description'] ?? ''); ?></textarea>
                    <div class="form-text">Deskripsi singkat (maks 160 karakter) untuk Google.</div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           value="<?php echo htmlspecialchars($item_data['sort_order']); ?>" required>
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Item Bisnis
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