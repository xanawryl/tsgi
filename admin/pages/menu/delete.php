<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
require_role(ROLE_EDITOR);

// 2. Ambil ID Menu dari URL
$menu_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$menu_id) {
    $_SESSION['flash_message'] = 'ID Menu tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/menu/');
    exit;
}

// 3. Ambil Koneksi DB
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../config.php';
}

// 4. (PENTING) Cek apakah ini item khusus (BUSINESS)
$sql_select = "SELECT url FROM menu_items WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $menu_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    if ($row['url'] == '#business_dropdown') {
        $_SESSION['flash_message'] = 'Item menu "BUSINESS" tidak dapat dihapus.';
        $_SESSION['flash_message_type'] = 'danger';
        header('Location: ' . $admin_base . '/pages/menu/');
        exit;
    }
} else {
    $_SESSION['flash_message'] = 'Menu item tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/menu/');
    exit;
}
$stmt_select->close();


// 5. Hapus data dari Database
$sql_delete = "DELETE FROM menu_items WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $menu_id);

if ($stmt_delete->execute()) {
    $_SESSION['flash_message'] = 'Link menu berhasil dihapus!';
    $_SESSION['flash_message_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Gagal menghapus link menu: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
}

$stmt_delete->close();
$conn->close();

// 6. Alihkan kembali
header('Location: ' . $admin_base . '/pages/menu/');
exit;
?>