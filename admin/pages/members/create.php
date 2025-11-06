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
        $full_name = trim($_POST['full_name']);
        $position = trim($_POST['position']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $image_file = $_FILES['image_file'];

        // 3. Validasi Sederhana
        if (empty($full_name) || empty($position)) {
            $message = "Nama Lengkap dan Jabatan tidak boleh kosong.";
            $message_type = 'danger';
        }
        // 4. Validasi Gambar
        elseif (!isset($image_file) || $image_file['error'] != UPLOAD_ERR_OK || $image_file['size'] == 0) {
            $message = "Anda harus mengunggah foto profil yang valid.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar & gambar

            // ========== PROSES UPLOAD & OPTIMASI GAMBAR ==========
            $target_dir = __DIR__ . "/../../../assets/img/members/"; // Path folder baru
            
            // Buat folder jika belum ada
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
            $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($image_file['name']));
            // Nama file unik
            $unique_filename_base = "member_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
            $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                $message_type = 'danger';
            } elseif ($image_file['size'] > 5000000) { // Maks 5MB
                $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                $message_type = 'danger';
            }
            // Gunakan optimize_image() (dari config.php)
            // Kita set max_width lebih kecil untuk foto profil, misal 600px
            elseif (optimize_image($image_file['tmp_name'], $target_file, 600, 90)) { // 600px width, 90% quality

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
                        require_once __DIR__ . '/../../../config.php';
                    }

                    $sql_insert = "INSERT INTO board_members (full_name, position, image_url, sort_order) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);

                    if ($stmt_insert === false) {
                        $message = "Database error (prepare failed): " . $conn->error;
                        $message_type = 'danger';
                        @unlink($target_dir . $final_filename);
                    } else {
                        // 'sssi' = 3 string, 1 integer
                        $stmt_insert->bind_param("sssi", $full_name, $position, $final_filename, $sort_order);

                        if ($stmt_insert->execute()) {
                            $_SESSION['flash_message'] = 'Board member baru berhasil ditambahkan!';
                            $_SESSION['flash_message_type'] = 'success';
                            header('Location: ' . $admin_base . '/pages/members/'); // Kembali ke index
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
$page_title = 'Tambah Board Member Baru';

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Board Member Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/members/">Manage Members</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-person-plus-fill me-1"></i>
            Formulir Member Baru
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
                    <label for="full_name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="position" class="form-label">Jabatan (misal: CEO / Direktur)</label>
                    <input type="text" class="form-control" id="position" name="position" required
                           value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="image_file" class="form-label">Foto Profil</label>
                    <input class="form-control" type="file" id="image_file" name="image_file" required>
                    <div class="form-text">Rekomendasi rasio 1:1 (persegi). Tipe: JPG, PNG, WEBP. Maks 5MB.</div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" required
                           value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : '0'; ?>">
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Simpan Member
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