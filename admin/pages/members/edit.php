<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Variabel untuk pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Member dari URL
$member_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$member_id) {
    $_SESSION['flash_message'] = 'ID Board Member tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/members/');
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
        $full_name = trim($_POST['full_name']);
        $position = trim($_POST['position']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $sort_order = ($sort_order === false) ? 0 : $sort_order;
        $image_file = $_FILES['image_file'];
        $existing_image = $_POST['existing_image']; // Nama gambar lama

        // 4. Validasi Dasar
        if (empty($full_name) || empty($position)) {
            $message = "Nama Lengkap dan Jabatan tidak boleh kosong.";
            $message_type = 'danger';
        } else { // <-- Buka else validasi dasar

            $image_to_save = $existing_image; // Defaultnya, simpan gambar lama
            $final_filename = $existing_image;

            // 5. Cek jika ada GAMBAR BARU di-upload
            if (isset($image_file) && $image_file['error'] == UPLOAD_ERR_OK && $image_file['size'] > 0) {

                $target_dir = __DIR__ . "/../../../assets/img/members/";
                $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
                $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($image_file['name']));
                $unique_filename_base = "member_" . time() . "_" . pathinfo($safe_filename, PATHINFO_FILENAME);
                $target_file = $target_dir . $unique_filename_base . '.' . $file_extension;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $message = "Maaf, hanya file JPG, JPEG, PNG, GIF, & WEBP yang diizinkan.";
                    $message_type = 'danger';
                } elseif ($image_file['size'] > 5000000) {
                    $message = "Maaf, ukuran file Anda terlalu besar (Maks 5MB).";
                    $message_type = 'danger';
                }
                // Jika validasi awal OK, coba optimalkan (600px width, 90% quality)
                elseif (optimize_image($image_file['tmp_name'], $target_file, 600, 90)) {
                     // Ambil nama file AKHIR setelah dioptimasi
                     $optimized_files = glob($target_dir . $unique_filename_base . '.*');
                     if (!empty($optimized_files)) {
                         $final_filename = basename($optimized_files[0]);
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
                // Pastikan koneksi $conn ada
                if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                    require_once __DIR__ . '/../../../config.php';
                }

                $sql = "UPDATE board_members SET full_name = ?, position = ?, image_url = ?, sort_order = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Database error (prepare failed): " . $conn->error;
                    $message_type = 'danger';
                } else {
                    // 'sssii' = 3 string, 2 integers
                    $stmt->bind_param("sssii", $full_name, $position, $image_to_save, $sort_order, $member_id);

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = 'Board member berhasil diperbarui!';
                        $_SESSION['flash_message_type'] = 'success';
                        header('Location: ' . $admin_base . '/pages/members/'); // Kembali ke index
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
// AMBIL DATA MEMBER UNTUK FORM (JIKA BUKAN POST atau JIKA POST GAGAL)
// ==========================================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
$sql_select = "SELECT full_name, position, image_url, sort_order FROM board_members WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select member: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/members/');
     exit;
}
$stmt_select->bind_param("i", $member_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $member_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'Board Member tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/members/');
    exit;
}
$stmt_select->close();
// $conn->close(); // Jangan tutup di sini

// Set Judul Halaman
$page_title = 'Edit Board Member: ' . htmlspecialchars($member_data['full_name']);

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Board Member</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/members/">Manage Members</a></li>
        <li class="breadcrumb-item active">Edit Member</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Member
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $member_id; ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($member_data['image_url']); ?>">

                <div class="mb-3">
                    <label for="full_name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($member_data['full_name']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="position" class="form-label">Jabatan (misal: CEO / Direktur)</label>
                    <input type="text" class="form-control" id="position" name="position"
                           value="<?php echo htmlspecialchars($member_data['position']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto Profil Saat Ini:</label><br>
                    <?php if(!empty($member_data['image_url']) && file_exists(__DIR__ . "/../../../assets/img/members/" . $member_data['image_url'])): ?>
                         <img src="<?php echo BASE_URL . '/assets/img/members/' . htmlspecialchars($member_data['image_url']); ?>"
                              alt="Foto Lama" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;">
                    <?php else: ?>
                         <span class="text-muted">Tidak ada foto.</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="image_file" class="form-label">Upload Foto Baru (Opsional)</label>
                    <input class="form-control" type="file" id="image_file" name="image_file">
                    <div class="form-text">Biarkan kosong jika tidak ingin mengganti foto. Rasio 1:1 (persegi).</div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           value="<?php echo htmlspecialchars($member_data['sort_order']); ?>" required>
                    <div class="form-text">Angka yang lebih kecil akan tampil lebih dulu.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Member
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