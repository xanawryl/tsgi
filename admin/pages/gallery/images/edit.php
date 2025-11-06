<?php
// 1. Cek Auth
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Variabel pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// 2. Ambil ID Gambar & ID Album dari URL
$image_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$album_id = filter_input(INPUT_GET, 'album_id', FILTER_VALIDATE_INT);

if (!$image_id || !$album_id) {
    $_SESSION['flash_message'] = 'ID Gambar atau Album tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/gallery/albums/'); // Fallback ke daftar album
    exit;
}

// URL untuk kembali (ke halaman manage images)
$return_url = $admin_base . '/pages/gallery/images/index.php?album_id=' . $album_id;

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
        $new_caption = trim($_POST['image_caption']); // Caption baru
        // (Validasi 'caption tidak boleh kosong' bisa dihapus jika Anda mengizinkan caption kosong)
        if (empty($new_caption)) {
             $message = "Caption tidak boleh kosong.";
             $message_type = 'danger';
        } else {
            // Proses Update DB
            if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                 require_once __DIR__ . '/../../../../config.php';
            }
            $sql_update = "UPDATE gallery_images SET caption = ? WHERE id = ? AND album_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update === false) {
                 $message = "Database error (prepare failed): " . $conn->error;
                 $message_type = 'danger';
            } else {
                $stmt_update->bind_param("sii", $new_caption, $image_id, $album_id);
                if ($stmt_update->execute()) {
                    $_SESSION['flash_message'] = 'Caption gambar berhasil diperbarui!';
                    $_SESSION['flash_message_type'] = 'success';
                    header('Location: ' . $return_url); // Kembali ke manage images
                    exit;
                } else {
                    $message = "Database error (execute failed): " . $stmt_update->error;
                    $message_type = 'danger';
                }
                $stmt_update->close();
            }
        }
    }
}
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================

// ==========================================================
// AMBIL DATA GAMBAR UNTUK FORM
// ==========================================================
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}
$sql_select = "SELECT caption, image_url, album_id FROM gallery_images WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) {
    $_SESSION['flash_message'] = 'Gagal menyiapkan query select gambar: ' . $conn->error;
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $return_url);
    exit;
}
$stmt_select->bind_param("i", $image_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
if ($result->num_rows == 1) {
    $image_data = $result->fetch_assoc();
    // Verifikasi album_id
    if ($image_data['album_id'] != $album_id) {
         $_SESSION['flash_message'] = 'Kesalahan: ID Album tidak cocok.';
         $_SESSION['flash_message_type'] = 'danger';
         header('Location: ' . $admin_base . '/pages/gallery/albums/');
         exit;
    }
} else {
    $_SESSION['flash_message'] = 'Gambar tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $return_url);
    exit;
}
$stmt_select->close();
$conn->close();

// Set Judul Halaman
$page_title = 'Edit Caption Gambar';

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Caption Gambar</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/gallery/albums/">Manage Albums</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $return_url; ?>">Manage Images</a></li>
        <li class="breadcrumb-item active">Edit Caption</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Edit Caption
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $image_id; ?>&album_id=<?php echo $album_id; ?>" method="POST">
                <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label class="form-label">Preview Gambar:</label><br>
                    <?php if(!empty($image_data['image_url']) && file_exists(__DIR__ . "/../../../../assets/img/gallery/" . $image_data['image_url'])): ?>
                         <img src="<?php echo BASE_URL . '/assets/img/gallery/' . htmlspecialchars($image_data['image_url']); ?>"
                              alt="Preview Gambar" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;">
                    <?php else: ?>
                          <span class="text-muted">Gambar tidak ditemukan.</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="image_caption" class="form-label">Judul/Caption Gambar</label>
                    <input type="text" class="form-control" id="image_caption" name="image_caption" required
                           value="<?php echo htmlspecialchars($image_data['caption']); ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Caption
                </button>
                <a href="<?php echo $return_url; ?>" class="btn btn-secondary">
                    Batal
                </a>
            </form>

        </div>
    </div>
</div>

<?php
// Muat Footer Admin
require_once __DIR__ . '/../../../includes/footer.php';
?>