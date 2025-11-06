<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Album dari URL (WAJIB)
$album_id = filter_input(INPUT_GET, 'album_id', FILTER_VALIDATE_INT);
if (!$album_id) {
    $_SESSION['flash_message'] = 'ID Album tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
    exit;
}

// 3. Ambil Info Album (untuk Judul Halaman & Cek Keberadaan)
// Pastikan koneksi ada sebelum query
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}
$sql_album = "SELECT title, slug FROM gallery_albums WHERE id = ?";
$stmt_album = $conn->prepare($sql_album);
if ($stmt_album === false) {
    $_SESSION['flash_message'] = 'Gagal menyiapkan query album: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
    exit;
}
$stmt_album->bind_param("i", $album_id);
$stmt_album->execute();
$result_album = $stmt_album->get_result();
if ($result_album->num_rows == 0) {
    $_SESSION['flash_message'] = 'Album Galeri tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/');
    exit;
}
$album_data = $result_album->fetch_assoc();
$album_title = $album_data['title'];
$album_slug = $album_data['slug'];
$stmt_album->close();
// Jangan tutup koneksi $conn di sini


// ==========================================================
// PROSES FORM UPLOAD (SAAT DI-SUBMIT)
// ==========================================================
// Kita cek 'image_caption' untuk membedakan dari request AJAX 'sort'
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['image_caption'])) {

    // ===== VALIDASI CSRF TOKEN =====
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else { // <-- Buka else CSRF
    // ===== AKHIR VALIDASI CSRF =====

        // 4. Ambil data dari form (HANYA JIKA TOKEN VALID)
        $image_caption = trim($_POST['image_caption']);
        $image_file = $_FILES['image_file'];

        // 5. Validasi
        if (!isset($image_file) || $image_file['error'] != UPLOAD_ERR_OK || $image_file['size'] == 0) {
            $message = "Anda harus memilih file gambar yang valid untuk diunggah.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar & gambar

            // ========== PROSES UPLOAD & OPTIMASI GAMBAR ==========
            $target_dir = __DIR__ . "/../../../../assets/img/gallery/"; // Path folder galeri utama
            
            // Buat folder jika belum ada
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
            $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($image_file['name']));
            $unique_filename_base = "gallery_" . $album_slug . "_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
            $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                $message_type = 'danger';
            }
            elseif ($image_file['size'] > 5000000) { // Maks 5MB
                $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                $message_type = 'danger';
            }
             // Gunakan optimize_image() (dari config.php)
            elseif (optimize_image($image_file['tmp_name'], $target_file, 1200, 85)) { // 1200px width, 85% quality

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
                    if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                        require_once __DIR__ . '/../../../../config.php';
                    }
                    
                    // Dapatkan sort_order berikutnya
                    $sql_max_sort = "SELECT MAX(sort_order) as max_sort FROM gallery_images WHERE album_id = ?";
                    $stmt_max_sort = $conn->prepare($sql_max_sort);
                    $stmt_max_sort->bind_param("i", $album_id);
                    $stmt_max_sort->execute();
                    $result_max_sort = $stmt_max_sort->get_result();
                    $max_sort = $result_max_sort->fetch_assoc()['max_sort'];
                    $next_sort_order = ($max_sort === null) ? 0 : $max_sort + 1;
                    $stmt_max_sort->close();

                    $sql_insert = "INSERT INTO gallery_images (album_id, image_url, caption, sort_order) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);

                    if ($stmt_insert === false) {
                        $message = "Database error (prepare failed): " . $conn->error;
                        $message_type = 'danger';
                        @unlink($target_dir . $final_filename);
                    } else {
                        // 'issi' = integer, string, string, integer
                        $stmt_insert->bind_param("issi", $album_id, $final_filename, $image_caption, $next_sort_order);

                        if ($stmt_insert->execute()) {
                            $_SESSION['flash_message'] = 'Gambar baru berhasil diunggah!';
                            $_SESSION['flash_message_type'] = 'success';
                            header('Location: ' . $admin_base . '/pages/gallery/images/index.php?album_id=' . $album_id);
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
// AKHIR PROSES FORM UPLOAD
// ==========================================================


// ==========================================================
// AMBIL SEMUA GAMBAR YANG ADA DI ALBUM INI
// ==========================================================
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}
$sql_images = "SELECT id, image_url, caption, sort_order FROM gallery_images WHERE album_id = ? ORDER BY sort_order ASC, id ASC";
$stmt_images = $conn->prepare($sql_images);
if ($stmt_images === false) {
     error_log("Gagal menyiapkan query gambar: " . $conn->error);
     $images = [];
} else {
    $stmt_images->bind_param("i", $album_id);
    $stmt_images->execute();
    $result_images = $stmt_images->get_result();
    $images = ($result_images) ? $result_images->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_images->close();
}
// $conn->close(); // Jangan tutup di sini


// Set Judul Halaman
$page_title = 'Manage Images: ' . htmlspecialchars($album_title);

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Images: <?php echo htmlspecialchars($album_title); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/gallery/albums/">Manage Albums</a></li>
        <li class="breadcrumb-item active">Manage Images</li>
    </ol>

    <?php
    // Tampilkan pesan error/flash
    if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php elseif (isset($_SESSION['flash_message'])):
        $alert_type = isset($_SESSION['flash_message_type']) ? $_SESSION['flash_message_type'] : 'success';
        $alert_class = ($alert_type == 'danger') ? 'alert-danger' : 'alert-success'; ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); endif; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-upload me-1"></i> Upload Gambar Baru</div>
        <div class="card-body">
            <form action="index.php?album_id=<?php echo $album_id; ?>" method="POST" enctype="multipart/form-data">
                 <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label for="image_caption" class="form-label">Judul/Caption Gambar (Opsional)</label>
                    <input type="text" class="form-control" id="image_caption" name="image_caption"
                           value="<?php echo isset($_POST['image_caption']) ? htmlspecialchars($_POST['image_caption']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="image_file" class="form-label">File Gambar</label>
                    <input class="form-control" type="file" id="image_file" name="image_file" required>
                    <div class="form-text">Tipe file: JPG, PNG, GIF, WEBP. Maks 5MB.</div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-2"></i>Upload</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-images me-1"></i> Gambar di Album Ini (<?php echo count($images); ?>) - Drag untuk mengurutkan</div>
        <div class="card-body">
            <div class="row g-3 list-unstyled" id="sortable-gallery">
                <?php if (empty($images)): ?>
                    <div class="col-12"><p class="text-center">Belum ada gambar di album ini.</p></div>
                <?php else: ?>
                    <?php foreach ($images as $image):
                        $image_path = BASE_URL . '/assets/img/gallery/' . htmlspecialchars($image['image_url']);
                        $delete_url = "delete.php?id=" . $image['id'] . "&album_id=" . $album_id; // Kirim album_id untuk kembali
                        $edit_url = "edit.php?id=" . $image['id'] . "&album_id=" . $album_id; // Kirim album_id untuk kembali
                    ?>
                        <div class="col-md-4 col-lg-3 sortable-item" data-id="<?php echo $image['id']; ?>">
                            <div class="card h-100 shadow-sm gallery-admin-card">
                                <?php if(!empty($image['image_url']) && file_exists(__DIR__ . "/../../../../assets/img/gallery/" . $image['image_url'])): ?>
                                     <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($image['caption']); ?>">
                                <?php else: ?>
                                      <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="aspect-ratio: 4/3;"><span class="text-muted small">Gambar tidak ditemukan</span></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <p class="card-text small"><?php echo htmlspecialchars($image['caption']); ?></p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="<?php echo $edit_url; ?>"
                                       class="btn btn-primary btn-sm" title="Edit Caption">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="#"
                                       class="btn btn-danger btn-sm" title="Delete Image"
                                       data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                       data-bs-url="<?php echo $delete_url; ?>">
                                        <i class="bi bi-trash-fill"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div> </div>
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

<script>
$( function() {
    // Pastikan jQuery UI sudah dimuat
    if (typeof $.ui !== 'undefined' && typeof $.ui.sortable !== 'undefined') {
        $( "#sortable-gallery" ).sortable({
            placeholder: "ui-sortable-placeholder col-md-4 col-lg-3",
            items: ".sortable-item",
            cursor: "move",
            opacity: 0.8,
            helper: "clone",
            update: function( event, ui ) {
                var imageOrder = $(this).sortable('toArray', {attribute: 'data-id'});
                var csrfToken = $('form input[name="csrf_token"]').val(); // Ambil token CSRF dari form upload

                $.ajax({
                    url: 'update-order.php', // File handler di folder yang sama
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        order: imageOrder,
                        album_id: <?php echo $album_id; ?>, // Inject album_id
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        if(response.success) {
                            console.log('Urutan gambar berhasil disimpan.');
                        } else {
                            alert('Gagal menyimpan urutan: ' + (response.message || 'Error tidak diketahui'));
                            $( "#sortable-gallery" ).sortable('cancel');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                         console.error("AJAX Error (Sort):", textStatus, errorThrown, jqXHR.responseText);
                         alert('Terjadi kesalahan saat menyimpan urutan.');
                         $( "#sortable-gallery" ).sortable('cancel');
                    }
                });
            }
        });
    } else {
        console.error("jQuery UI Sortable belum dimuat!");
    }
});
</script>