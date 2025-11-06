<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
// Pastikan hanya Superadmin yang bisa akses halaman ini
require_role(ROLE_SUPERADMIN);

// Variabel pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// ==========================================================
// PROSES FORM SAAT DI-SUBMIT (POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ===== VALIDASI CSRF TOKEN =====
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else { // <-- Buka else CSRF
    // ===== AKHIR VALIDASI CSRF =====

        // 2. Ambil data form (HANYA JIKA TOKEN VALID)
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $password = $_POST['password']; // Jangan trim password
        $confirm_password = $_POST['confirm_password'];
        // *** PERUBAHAN: Ambil data role ***
        $role = trim($_POST['role']);

        // 3. Validasi
        // *** PERUBAHAN: Tambah cek $role ***
        if (empty($username) || empty($full_name) || empty($password) || empty($confirm_password) || empty($role)) {
            $message = "Semua kolom wajib diisi.";
            $message_type = 'danger';
        } elseif ($password !== $confirm_password) {
            $message = "Password dan Konfirmasi Password tidak cocok.";
            $message_type = 'danger';
        } elseif (strlen($password) < 6) {
            $message = "Password minimal harus 6 karakter.";
            $message_type = 'danger';
        // *** PERUBAHAN: Validasi nilai role ***
        } elseif ($role !== ROLE_SUPERADMIN && $role !== ROLE_EDITOR) {
             $message = "Peran (Role) yang dipilih tidak valid.";
             $message_type = 'danger';
        } else { // <-- Buka else validasi dasar

            // Cek apakah username sudah ada
            // Pastikan koneksi $conn ada
             if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                 require_once __DIR__ . '/../../../config.php'; // Panggil config lagi
             }
            $sql_check = "SELECT id FROM admin_users WHERE username = ?";
            $stmt_check = $conn->prepare($sql_check);
             if ($stmt_check === false) {
                 $message = "Database error (prepare check failed): " . $conn->error;
                 $message_type = 'danger';
             } else {
                $stmt_check->bind_param("s", $username);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $message = "Username '$username' sudah digunakan. Pilih username lain.";
                    $message_type = 'danger';
                } else {
                    // Username unik, lanjut hash password & insert
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // *** PERUBAHAN: Tambah kolom 'role' ke INSERT ***
                    $sql_insert = "INSERT INTO admin_users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                     if ($stmt_insert === false) {
                         $message = "Database error (prepare insert failed): " . $conn->error;
                         $message_type = 'danger';
                     } else {
                        // *** PERUBAHAN: Tambah tipe 's' dan variabel $role ke bind_param ***
                        // 'ssss' = 4 string
                        $stmt_insert->bind_param("ssss", $username, $hashed_password, $full_name, $role);

                        if ($stmt_insert->execute()) {
                            $_SESSION['flash_message'] = "Admin user '$username' berhasil ditambahkan!";
                            $_SESSION['flash_message_type'] = 'success';
                            // Redirect ke halaman index di folder users
                            header('Location: ' . $admin_base . '/pages/users/');
                            exit;
                        } else {
                            $message = "Database error (execute failed): " . $stmt_insert->error;
                            $message_type = 'danger';
                        }
                        $stmt_insert->close();
                    } // <-- Tutup else prepare insert
                } // <-- Tutup else username unik
                $stmt_check->close();
            } // <-- Tutup else prepare check
        } // <-- TUTUP else validasi dasar

    } // <-- TUTUP else validasi CSRF

    // Jangan tutup koneksi jika proses gagal & perlu reload form
    // $conn->close();

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM
// ==========================================================

// Set Judul Halaman
$page_title = 'Tambah Admin Baru';

// Muat Header & Sidebar Admin
// *** PERUBAHAN: Sesuaikan path require ***
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Admin Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/users/">Manage Users</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-person-plus-fill me-1"></i>
            Formulir Admin Baru
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="create.php" method="POST">
                 <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; // Keep value ?>">
                    <div class="form-text">Gunakan huruf kecil, angka, atau underscore. Unik.</div>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; // Keep value ?>">
                </div>

                <?php // Hanya Superadmin yang bisa set role
                if (has_role(ROLE_SUPERADMIN)): ?>
                <div class="mb-3">
                     <label for="role" class="form-label">Peran (Role)</label>
                     <select class="form-select" id="role" name="role" required>
                         <option value="<?php echo ROLE_EDITOR; ?>" <?php echo (isset($_POST['role']) && $_POST['role'] == ROLE_EDITOR) ? 'selected' : ''; ?>>Editor</option>
                         <option value="<?php echo ROLE_SUPERADMIN; ?>" <?php echo (isset($_POST['role']) && $_POST['role'] == ROLE_SUPERADMIN) ? 'selected' : ''; ?>>Superadmin</option>
                     </select>
                </div>
                <?php else: ?>
                     <input type="hidden" name="role" value="<?php echo ROLE_EDITOR; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Minimal 6 karakter.</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Simpan Admin
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
// *** PERUBAHAN: Sesuaikan path require ***
require_once __DIR__ . '/../../includes/footer.php';
?>