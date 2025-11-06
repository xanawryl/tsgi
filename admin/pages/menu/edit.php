<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
require_role(ROLE_EDITOR);

// Variabel pesan status
$message = '';
$message_type = '';

// 2. Ambil ID Menu dari URL
$menu_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$menu_id) {
    $_SESSION['flash_message'] = 'ID Menu tidak valid.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/menu/');
    exit;
}

// Ambil koneksi $conn
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
    require_once __DIR__ . '/../../../config.php';
}

// ==========================================================
// PROSES FORM UPDATE (POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else {
        $label = trim($_POST['label']);
        $url = trim($_POST['url']);
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT) ?? 0;
        $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?? 0; // Ambil parent_id

        if (empty($label) || empty($url)) {
            $message = "Label Teks dan URL tidak boleh kosong.";
            $message_type = 'danger';
        } elseif ($parent_id == $menu_id) { // Cek agar tidak menjadi parent dirinya sendiri
             $message = "Menu tidak bisa menjadi parent bagi dirinya sendiri.";
             $message_type = 'danger';
        } else {
            $sql_update = "UPDATE menu_items SET label = ?, url = ?, sort_order = ?, parent_id = ? WHERE id = ?"; // Tambah parent_id
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update === false) {
                 $message = "Database error (prepare failed): " . $conn->error;
                 $message_type = 'danger';
            } else {
                // 'ssiii' = 2 string, 3 integers
                $stmt_update->bind_param("ssiii", $label, $url, $sort_order, $parent_id, $menu_id); // Tambah $parent_id
                if ($stmt_update->execute()) {
                    $_SESSION['flash_message'] = 'Link menu berhasil diperbarui!';
                    $_SESSION['flash_message_type'] = 'success';
                    header('Location: ' . $admin_base . '/pages/menu/');
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
// AMBIL DATA MENU UNTUK FORM
// ==========================================================
$sql_select = "SELECT label, url, sort_order, parent_id FROM menu_items WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
if ($stmt_select === false) { die('Prepare failed: ' . $conn->error); }
$stmt_select->bind_param("i", $menu_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
if ($result->num_rows == 1) {
    $menu_data = $result->fetch_assoc();
} else {
    $_SESSION['flash_message'] = 'Link menu tidak ditemukan.';
    $_SESSION['flash_message_type'] = 'danger';
    header('Location: ' . $admin_base . '/pages/menu/');
    exit;
}
$stmt_select->close();

// Ambil item menu utama (untuk dropdown parent) - jangan sertakan diri sendiri
$sql_parents = "SELECT id, label FROM menu_items WHERE parent_id = 0 AND id != ? ORDER BY sort_order ASC";
$stmt_parents = $conn->prepare($sql_parents);
$stmt_parents->bind_param("i", $menu_id);
$stmt_parents->execute();
$result_parents = $stmt_parents->get_result();
$parent_options = ($result_parents) ? $result_parents->fetch_all(MYSQLI_ASSOC) : [];
$stmt_parents->close();
// $conn->close(); // Jangan tutup

// Set Judul Halaman
$page_title = 'Edit Link Menu: ' . htmlspecialchars($menu_data['label']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Link Menu</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/menu/">Manage Menu</a></li>
        <li class="breadcrumb-item active">Edit Link</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-pencil-square me-1"></i> Formulir Edit Link Menu</div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $menu_id; ?>" method="POST">
                <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Menu</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="0" <?php echo ($menu_data['parent_id'] == 0) ? 'selected' : ''; ?>>
                            -- Tidak Ada (Menu Utama) --
                        </option>
                        <?php foreach ($parent_options as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" <?php echo ($menu_data['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="label" class="form-label">Label Teks</label>
                    <input type="text" class="form-control" id="label" name="label"
                           value="<?php echo htmlspecialchars($menu_data['label']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="url" class="form-label">URL (Link Tujuan)</label>
                    <input type="text" class="form-control" id="url" name="url"
                           value="<?php echo htmlspecialchars($menu_data['url']); ?>" required>
                    <div class="form-text">
                        Gunakan <code>/</code> untuk Home. 
                        Gunakan <code>/business/pks</code> untuk link ke PKS. 
                        Gunakan <code>#</code> untuk Parent Dropdown.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           value="<?php echo htmlspecialchars($menu_data['sort_order']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Update Link Menu</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) { $conn->close(); }
require_once __DIR__ . '/../../includes/footer.php';
?>