<?php
// 1. Cek Auth
require_once __DIR__ . '/../includes/auth-check.php';

// Variabel pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// Ambil ID admin yang sedang login dari session
$admin_id = $_SESSION['admin_id'];

// ==========================================================
// PROSES FORM UPDATE (POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else {
        // Ambil data form
        $full_name = trim($_POST['full_name']);
        $password = $_POST['password']; // Jangan trim
        $confirm_password = $_POST['confirm_password'];

        // Validasi Dasar
        if (empty($full_name)) {
            $message = "Nama Lengkap tidak boleh kosong.";
            $message_type = 'danger';
        }
        // Cek password HANYA jika diisi
        elseif (!empty($password) && $password !== $confirm_password) {
            $message = "Password baru dan Konfirmasi Password tidak cocok.";
            $message_type = 'danger';
        } elseif (!empty($password) && strlen($password) < 6) {
            $message = "Password baru minimal harus 6 karakter.";
            $message_type = 'danger';
        } else {
            // Lanjut UPDATE

            // Siapkan bagian query password (HANYA jika diisi)
            $password_sql_part = "";
            $bind_types = "si"; // s=full_name, i=id
            $bind_params = [$full_name];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $password_sql_part = ", password = ?";
                $bind_types .= "s"; // Tambah s untuk password
                $bind_params[] = $hashed_password;
            }

            // Tambahkan ID ke akhir parameter binding
            $bind_params[] = $admin_id;

            // Buat query UPDATE lengkap
            $sql_update = "UPDATE admin_users SET full_name = ? {$password_sql_part} WHERE id = ?";
            // Pastikan koneksi $conn ada
            if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                require_once __DIR__ . '/../../config.php';
            }
            $stmt_update = $conn->prepare($sql_update);

            if ($stmt_update === false) {
                 $message = "Database error (prepare update failed): " . $conn->error;
                 $message_type = 'danger';
            } else {
                $stmt_update->bind_param($bind_types, ...$bind_params);

                if ($stmt_update->execute()) {
                    // Update session jika nama berubah
                    $_SESSION['admin_full_name'] = $full_name;

                    $message = "Profil berhasil diperbarui!";
                    $message_type = 'success';
                    // Kita tidak redirect, tetap di halaman ini dengan pesan sukses
                } else {
                    $message = "Database error (execute failed): " . $stmt_update->error;
                    $message_type = 'danger';
                }
                $stmt_update->close();
            }
        } // akhir else validasi dasar
    } // akhir else csrf
    // Jangan tutup koneksi jika gagal & perlu reload form
}
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================

// ==========================================================
// AMBIL DATA USER UNTUK FORM (selalu ambil data terbaru)
// ==========================================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../config.php';
}
$sql_select = "SELECT username, full_name FROM admin_users WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
    // Handle error (meskipun kecil kemungkinannya terjadi di sini)
    die("Gagal menyiapkan query select profil: " . $conn->error);
}
$stmt_select->bind_param("i", $admin_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
$user_data = $result->fetch_assoc(); // Kita tahu user pasti ada
$stmt_select->close();
$conn->close();

// Set Judul Halaman
$page_title = 'Edit Profil Saya';

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Profil Saya</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Edit Profil</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-person-badge-fill me-1"></i>
            Formulir Edit Profil
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="profile.php" method="POST">
                <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled readonly>
                    <div class="form-text">Username tidak dapat diubah.</div>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                </div>

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
                    <i class="bi bi-save me-2"></i>Update Profil
                </button>
                 <a href="<?php echo $admin_base; ?>/dashboard.php" class="btn btn-secondary">
                    Kembali ke Dashboard
                </a>
            </form>

        </div>
    </div>
</div>

<?php
// Muat Footer Admin
require_once __DIR__ . '/../includes/footer.php';
?>