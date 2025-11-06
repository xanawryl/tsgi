<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';
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
        $partner_name = trim($_POST['partner_name']);
        $website_url = trim($_POST['website_url']);
        $description = trim($_POST['description']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $image_file = $_FILES['logo_file']; // Ganti nama jadi logo_file

        // 3. Validasi Sederhana
        if (empty($partner_name)) {
            $message = "Nama Partner tidak boleh kosong.";
            $message_type = 'danger';
        }
        // Validasi Gambar
        elseif (!isset($image_file) || $image_file['error'] != UPLOAD_ERR_OK || $image_file['size'] == 0) {
            $message = "Anda harus mengunggah file logo.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar & gambar

            // ========== PROSES UPLOAD & OPTIMASI GAMBAR ==========
            $target_dir = __DIR__ . "/../../../assets/img/partners/"; // Path folder baru
            
            // Buat folder jika belum ada
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
            $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($image_file['name']));
            // Nama file unik
            $unique_filename_base = "partner_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
            $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']; // Tambahkan SVG untuk logo
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, WEBP, & SVG yang diizinkan.";
                $message_type = 'danger';
            } elseif ($image_file['size'] > 2000000) { // Maks 2MB untuk logo
                $message = "Maaf, ukuran file Anda terlalu besar (Maks 2MB).";
                $message_type = 'danger';
            }
            
            // Cek apakah file adalah SVG (SVG tidak dioptimasi oleh GD)
            $is_svg = (strtolower($file_extension) == 'svg' || $image_file['type'] == 'image/svg+xml');

            // Jika BUKAN SVG, optimasi. Jika SVG, langsung pindahkan.
            $upload_success = false;
            if ($is_svg) {
                // Hanya pindahkan SVG
                if (move_uploaded_file($image_file['tmp_name'], $target_file)) {
                    $upload_success = true;
                }
            } else {
                // Optimasi gambar lain (max width 400px, 90% quality)
                if (optimize_image($image_file['tmp_name'], $target_file, 400, 90)) {
                    $upload_success = true;
                }
            }


            if ($upload_success) {
                // ========== PROSES INSERT DATABASE ==========
                 $final_filename = '';
                 // Cek file SVG atau file hasil optimasi
                 if ($is_svg) {
                     $final_filename = basename($target_file);
                 } else {
                     $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                     if (!empty($optimized_files)) {
                         $final_filename = basename($optimized_files[0]);
                     }
                 }
                 
                 if (empty($final_filename)) {
                      $message = "Gagal menemukan file gambar yang disimpan.";
                      $message_type = 'danger';
                 }

                 if ($message_type != 'danger') {
                    // Pastikan koneksi $conn ada
                    if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                        require_once __DIR__ . '/../../../config.php';
                    }

                    $sql_insert = "INSERT INTO partners (partner_name, website_url, logo_url, description, sort_order) VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);

                    if ($stmt_insert === false) {
                        $message = "Database error (prepare failed): " . $conn->error;
                        $message_type = 'danger';
                        @unlink($target_dir . $final_filename);
                    } else {
                        // 'ssssi' = 4 string, 1 integer
                        $stmt_insert->bind_param("ssssi", $partner_name, $website_url, $final_filename, $description, $sort_order);

                        if ($stmt_insert->execute()) {
                            $_SESSION['flash_message'] = 'Partner baru berhasil ditambahkan!';
                            $_SESSION['flash_message_type'] = 'success';
                            header('Location: ' . $admin_base . '/pages/partners/'); // Kembali ke index
                            exit;
                        } else {
                            $message = "Database error (execute failed): " . $stmt_insert->error;
                            $message_type = 'danger';
                            @unlink($target_dir . $final_filename);
                        }
                        $stmt_insert->close();
                    }
                 } // <-- Tutup if message_type != danger

            } else { // <-- else dari $upload_success
                if (!$is_svg) {
                    $message = "Maaf, terjadi kesalahan saat mengunggah atau mengoptimalkan file Anda.";
                } else {
                    $message = "Maaf, terjadi kesalahan saat mengunggah file SVG Anda.";
                }
                $message_type = 'danger';
            } // <-- Tutup else $upload_success

        } // <-- TUTUP else validasi dasar & gambar

    } // <-- TUTUP else validasi CSRF

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM
// ==========================================================

// Set Judul Halaman
$page_title = 'Tambah Partner Baru';

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Partner Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/partners/">Manage Partners</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-person-plus-fill me-1"></i>
            Formulir Partner Baru
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
                    <label for="partner_name" class="form-label">Nama Partner</label>
                    <input type="text" class="form-control" id="partner_name" name="partner_name" required
                           value="<?php echo isset($_POST['partner_name']) ? htmlspecialchars($_POST['partner_name']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="website_url" class="form-label">URL Website (Opsional)</label>
                    <input type="url" class="form-control" id="website_url" name="website_url" placeholder="https://example.com"
                           value="<?php echo isset($_POST['website_url']) ? htmlspecialchars($_POST['website_url']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi Singkat (Opsional)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="logo_file" class="form-label">Logo Partner</label>
                    <input class="form-control" type="file" id="logo_file" name="logo_file" required>
                    <div class="form-text">Tipe: JPG, PNG, WEBP, SVG. Maks 2MB. Akan di-resize ke 400px (kecuali SVG).</div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" required
                           value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : '0'; ?>">
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Simpan Partner
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