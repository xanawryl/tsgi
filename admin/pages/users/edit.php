<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
// Pastikan hanya Superadmin yang bisa akses halaman edit user
require_role(ROLE_SUPERADMIN);

// Variabel pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID User dari URL
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    $_SESSION['flash_message'] = 'ID User tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/users/'); // Redirect ke index user
    exit;
}

// Tidak boleh edit diri sendiri di halaman ini
if ($user_id == $_SESSION['admin_id']) {
     $_SESSION['flash_message'] = 'Anda tidak dapat mengedit akun Anda sendiri di halaman ini. Gunakan halaman Profil.';
     $_SESSION['flash_message_type'] = 'warning';
     header('Location: ' . $admin_base . '/pages/users/'); // Redirect ke index user
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

        // 3. Ambil data form (HANYA JIKA TOKEN VALID)
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $password = $_POST['password']; // Jangan trim password
        $confirm_password = $_POST['confirm_password'];
        $original_username = $_POST['original_username'];
        // *** PERUBAHAN: Ambil role (hanya jika Superadmin, tapi aman diambil saja) ***
        $role = trim($_POST['role']);

        // 4. Validasi Dasar
        // *** PERUBAHAN: Tambah cek role ***
        if (empty($username) || empty($full_name) || empty($role)) {
            $message = "Username, Nama Lengkap, dan Role tidak boleh kosong.";
            $message_type = 'danger';
        }
        // Cek password HANYA jika diisi
        elseif (!empty($password) && $password !== $confirm_password) {
            $message = "Password baru dan Konfirmasi Password tidak cocok.";
            $message_type = 'danger';
        } elseif (!empty($password) && strlen($password) < 6) {
            $message = "Password baru minimal harus 6 karakter.";
            $message_type = 'danger';
        // *** PERUBAHAN: Validasi nilai role ***
        } elseif ($role !== ROLE_SUPERADMIN && $role !== ROLE_EDITOR) {
             $message = "Peran (Role) yang dipilih tidak valid.";
             $message_type = 'danger';
        } else { // <-- Buka else validasi dasar

            // Cek username duplikat HANYA jika username diubah
            $username_changed = ($username !== $original_username);
            $username_exists = false;
            if ($username_changed) {
                if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) { require_once __DIR__ . '/../../../config.php'; }
                $sql_check = "SELECT id FROM admin_users WHERE username = ?";
                $stmt_check = $conn->prepare($sql_check);
                if ($stmt_check === false) { $message = "Database error (prepare check failed): " . $conn->error; $message_type = 'danger'; }
                else { $stmt_check->bind_param("s", $username); $stmt_check->execute(); $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows > 0) { $username_exists = true; }
                    $stmt_check->close(); }
            } // <-- Tutup if username_changed

            if ($username_exists) {
                $message = "Username '$username' sudah digunakan. Pilih username lain.";
                $message_type = 'danger';
            } elseif ($message_type != 'danger') { // Lanjut UPDATE hanya jika tidak ada error
                // Lanjut UPDATE

                // Siapkan bagian query password (HANYA jika diisi)
                $password_sql_part = "";
                // *** PERUBAHAN: Tambah tipe 's' untuk role ***
                $bind_types = "sssi"; // s=username, s=full_name, s=role, i=id
                $bind_params = [$username, $full_name, $role]; // Mulai array parameter

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $password_sql_part = ", password = ?";
                    $bind_types .= "s"; // Tambah s untuk password
                    $bind_params[] = $hashed_password; // Tambahkan hash ke array parameter
                }

                // Tambahkan ID ke akhir parameter binding
                $bind_params[] = $user_id;

                // Buat query UPDATE lengkap (tambah 'role = ?')
                $sql_update = "UPDATE admin_users SET username = ?, full_name = ?, role = ? {$password_sql_part} WHERE id = ?";
                if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) { require_once __DIR__ . '/../../../config.php'; }
                $stmt_update = $conn->prepare($sql_update);

                if ($stmt_update === false) {
                    $message = "Database error (prepare update failed): " . $conn->error;
                    $message_type = 'danger';
                } else {
                    $stmt_update->bind_param($bind_types, ...$bind_params);

                    if ($stmt_update->execute()) {
                        $_SESSION['flash_message'] = "Admin user '$username' berhasil diperbarui!";
                         $_SESSION['flash_message_type'] = 'success';
                        header('Location: ' . $admin_base . '/pages/users/'); // Redirect ke index user
                        exit;
                    } else {
                        $message = "Database error (execute failed): " . $stmt_update->error;
                        $message_type = 'danger';
                    }
                    $stmt_update->close();
                } // <-- Tutup else prepare update
            } // <-- Tutup elseif message_type != danger

        } // <-- TUTUP else validasi dasar

    } // <-- TUTUP else validasi CSRF

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================


// ==========================================================
// AMBIL DATA USER UNTUK FORM (JIKA BUKAN POST atau JIKA POST GAGAL)
// ==========================================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}
// *** PERUBAHAN: Ambil kolom 'role' ***
$sql_select = "SELECT username, full_name, role FROM admin_users WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query select user: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/users/'); // Redirect ke index user
     exit;
}
$stmt_select->bind_param("i", $user_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'User Admin tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/users/'); // Redirect ke index user
    exit;
}
$stmt_select->close();
// Jangan tutup koneksi $conn di sini

// Set Judul Halaman
$page_title = 'Edit Admin User: ' . htmlspecialchars($user_data['username']);

// Muat Header & Sidebar Admin
// *** PERUBAHAN: Sesuaikan path require ***
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Admin User</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/users/">Manage Users</a></li>
        <li class="breadcrumb-item active">Edit User (<?php echo htmlspecialchars($user_data['username']); ?>)</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Admin User
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $user_id; ?>" method="POST">
                 <?php echo csrf_input(); ?>
                <input type="hidden" name="original_username" value="<?php echo htmlspecialchars($user_data['username']); ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    <div class="form-text">Gunakan huruf kecil, angka, atau underscore. Unik.</div>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                </div>

                <?php if (has_role(ROLE_SUPERADMIN)): ?>
                <div class="mb-3">
                     <label for="role" class="form-label">Peran (Role)</label>
                     <select class="form-select" id="role" name="role" required>
                         <option value="<?php echo ROLE_EDITOR; ?>" <?php echo ($user_data['role'] == ROLE_EDITOR) ? 'selected' : ''; ?>>Editor</option>
                         <option value="<?php echo ROLE_SUPERADMIN; ?>" <?php echo ($user_data['role'] == ROLE_SUPERADMIN) ? 'selected' : ''; ?>>Superadmin</option>
                     </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($user_data['role']); ?>">
                    <?php endif; ?>

                <hr class="my-4">

                <h5 class="mb-3">Ubah Password (Opsional)</h5>
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <div class="form-text">Biarkan kosong jika tidak ingin mengubah password. Minimal 6 karakter.</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Admin
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