<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Partner dari URL
$partner_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$partner_id) {
    $_SESSION['flash_message'] = 'ID Partner tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/partners/');
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
        $partner_name = trim($_POST['partner_name']);
        $website_url = trim($_POST['website_url']);
        $description = trim($_POST['description']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $image_file = $_FILES['logo_file'];
        $existing_logo = $_POST['existing_logo']; // Nama logo lama

        // 4. Validasi Dasar
        if (empty($partner_name)) {
            $message = "Nama Partner tidak boleh kosong.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar

            $logo_to_save = $existing_logo; // Defaultnya, simpan logo lama
            $final_filename = $existing_logo;

            // 5. Cek jika ada LOGO BARU di-upload
            if (isset($image_file) && $image_file['error'] == UPLOAD_ERR_OK && $image_file['size'] > 0) {

                $target_dir = __DIR__ . "/../../../assets/img/partners/";
                $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
                $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($image_file['name']));
                $unique_filename_base = "partner_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
                $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, WEBP, & SVG yang diizinkan.";
                    $message_type = 'danger';
                } elseif ($image_file['size'] > 2000000) { // Maks 2MB
                    $message = "Maaf, ukuran file Anda terlalu besar (Maks 2MB).";
                    $message_type = 'danger';
                }

                if ($message_type != 'danger') {
                    $is_svg = (strtolower($file_extension) == 'svg' || $image_file['type'] == 'image/svg+xml');
                    $upload_success = false;
                    
                    if ($is_svg) {
                        if (move_uploaded_file($image_file['tmp_name'], $target_file)) {
                            $upload_success = true;
                            $final_filename = basename($target_file); // Nama file SVG baru
                        }
                    } else {
                        if (optimize_image($image_file['tmp_name'], $target_file, 400, 90)) {
                            $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                            if (!empty($optimized_files)) {
                                $final_filename = basename($optimized_files[0]); // Nama file teroptimasi baru
                                $upload_success = true;
                            }
                        }
                    }

                    if ($upload_success) {
                        $logo_to_save = $final_filename; // Update nama file untuk DB
                        // Hapus logo lama JIKA upload & optimasi berhasil
                        if (!empty($existing_logo) && file_exists($target_dir . $existing_logo)) {
                            @unlink($target_dir . $existing_logo);
                        }
                    } else {
                        if (empty($message)) { // Jika belum ada pesan error spesifik
                            $message = "Maaf, terjadi kesalahan saat mengunggah atau mengoptimalkan logo baru Anda.";
                        }
                        $message_type = 'danger';
                    }
                } // <-- Tutup if message_type != danger (validasi awal)

            } // Akhir cek gambar baru

            // 6. Jika tidak ada error dari upload, Lanjut UPDATE Database
            if ($message_type != 'danger') {
                // Pastikan koneksi $conn ada
                if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                    require_once __DIR__ . '/../../../config.php';
                }

                $sql = "UPDATE partners SET partner_name = ?, website_url = ?, logo_url = ?, description = ?, sort_order = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Database error (prepare failed): " . $conn->error;
                    $message_type = 'danger';
                } else {
                    // 'ssssii' = 4 string, 2 integers
                    $stmt->bind_param("ssssii", $partner_name, $website_url, $logo_to_save, $description, $sort_order, $partner_id);

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = 'Partner berhasil diperbarui!';
                        $_SESSION['flash_message_type'] = 'success';
                        header('Location: ' . $admin_base . '/pages/partners/'); // Kembali ke index
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
// AMBIL DATA PARTNER UNTUK FORM (JIKA BUKAN POST atau JIKA POST GAGAL)
// ==========================================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
$sql_select = "SELECT partner_name, website_url, logo_url, description, sort_order FROM partners WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select partner: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/partners/');
     exit;
}
$stmt_select->bind_param("i", $partner_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $partner_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'Partner tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/partners/');
    exit;
}
$stmt_select->close();
// $conn->close(); // Jangan tutup di sini

// Set Judul Halaman
$page_title = 'Edit Partner: ' . htmlspecialchars($partner_data['partner_name']);

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Partner</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/partners/">Manage Partners</a></li>
        <li class="breadcrumb-item active">Edit Partner</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Partner
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $partner_id; ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($partner_data['logo_url']); ?>">

                <div class="mb-3">
                    <label for="partner_name" class="form-label">Nama Partner</label>
                    <input type="text" class="form-control" id="partner_name" name="partner_name"
                           value="<?php echo htmlspecialchars($partner_data['partner_name']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="website_url" class="form-label">URL Website (Opsional)</label>
                    <input type="url" class="form-control" id="website_url" name="website_url" placeholder="https://example.com"
                           value="<?php echo htmlspecialchars($partner_data['website_url']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Singkat (Opsional)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($partner_data['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Logo Saat Ini:</label><br>
                    <?php if(!empty($partner_data['logo_url']) && file_exists(__DIR__ . "/../../../assets/img/partners/" . $partner_data['logo_url'])): ?>
                         <img src="<?php echo BASE_URL . '/assets/img/partners/' . htmlspecialchars($partner_data['logo_url']); ?>"
                              alt="Logo Lama" style="max-width: 200px; height: auto; background: #f0f0f0; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;">
                    <?php else: ?>
                         <span class="text-muted">Tidak ada logo.</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="logo_file" class="form-label">Upload Logo Baru (Opsional)</label>
                    <input class="form-control" type="file" id="logo_file" name="logo_file">
                    <div class="form-text">Biarkan kosong jika tidak ingin mengganti logo. Tipe: JPG, PNG, WEBP, SVG. Maks 2MB.</div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           value="<?php echo htmlspecialchars($partner_data['sort_order']); ?>" required>
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Partner
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