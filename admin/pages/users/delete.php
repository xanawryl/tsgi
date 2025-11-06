<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

require_role(ROLE_SUPERADMIN);

// 2. Ambil ID User dari URL dan Validasi
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    $_SESSION['flash_message'] = 'ID User tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/users/');
    exit;
}

// 3. (PENTING) Cegah Self-Deletion
// User tidak boleh menghapus akunnya sendiri
if ($user_id == $_SESSION['admin_id']) {
    $_SESSION['flash_message'] = 'Anda tidak dapat menghapus akun Anda sendiri.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/users/');
    exit;
}

// 4. Ambil Koneksi DB
// $conn sudah tersedia

// 5. Hapus data dari Database
$sql_delete = "DELETE FROM admin_users WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);

// Cek jika prepare gagal
if ($stmt_delete === false) {
     $_SESSION['flash_message'] = 'Gagal menyiapkan query hapus: ' . $conn->error;
     $_SESSION['flash_message_type'] = 'danger';
     header('Location: ' . $admin_base . '/pages/users/');
     exit;
}

$stmt_delete->bind_param("i", $user_id);

if ($stmt_delete->execute()) {
    // Cek apakah ada baris yang benar-benar terhapus
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['flash_message'] = 'Admin user berhasil dihapus!';
        $_SESSION['flash_message_type'] = 'success';
    } else {
        // ID ada tapi tidak terhapus (kasus aneh, mungkin ID tidak ada)
        $_SESSION['flash_message'] = 'User Admin tidak ditemukan atau tidak dapat dihapus.';
        $_SESSION['flash_message_type'] = 'warning';
    }
} else {
    // Gagal menghapus dari DB
    $_SESSION['flash_message'] = 'Gagal menghapus admin user: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali ke halaman manage users
header('Location: ' . $admin_base . '/pages/users/');
exit;
?>