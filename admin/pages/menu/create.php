<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
require_role(ROLE_EDITOR);

// Variabel pesan status
$message = '';
$message_type = '';

// Ambil koneksi $conn
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
    require_once __DIR__ . '/../../../config.php';
}

// ==========================================================
// PROSES FORM SAAT DI-SUBMIT (POST)
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
        } else {
            $sql_insert = "INSERT INTO menu_items (label, url, sort_order, parent_id) VALUES (?, ?, ?, ?)"; // Tambah parent_id
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) {
                 $message = "Database error (prepare failed): " . $conn->error;
                 $message_type = 'danger';
            } else {
                // 'ssii' = 2 string, 2 integers
                $stmt_insert->bind_param("ssii", $label, $url, $sort_order, $parent_id); // Tambah $parent_id

                if ($stmt_insert->execute()) {
                    $_SESSION['flash_message'] = 'Link menu baru berhasil ditambahkan!';
                    $_SESSION['flash_message_type'] = 'success';
                    header('Location: ' . $admin_base . '/pages/menu/');
                    exit;
                } else {
                    $message = "Database error (execute failed): " . $stmt_insert->error;
                    $message_type = 'danger';
                }
                $stmt_insert->close();
            }
        }
    }
}
// ==========================================================
// AKHIR PROSES FORM
// ==========================================================

// Ambil item menu utama (untuk dropdown parent)
$sql_parents = "SELECT id, label FROM menu_items WHERE parent_id = 0 ORDER BY sort_order ASC";
$result_parents = $conn->query($sql_parents);
$parent_options = ($result_parents) ? $result_parents->fetch_all(MYSQLI_ASSOC) : [];

// Set Judul Halaman
$page_title = 'Tambah Link Menu Baru';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Link Menu Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/menu/">Manage Menu</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Formulir Link Menu Baru</div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="create.php" method="POST">
                <?php echo csrf_input(); ?>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Menu</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="0">-- Tidak Ada (Menu Utama) --</option>
                        <?php foreach ($parent_options as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>">
                                <?php echo htmlspecialchars($parent['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pilih "Menu Utama" untuk link biasa, atau pilih menu lain (misal: "BUSINESS") untuk membuatnya jadi *dropdown item*.</div>
                </div>

                <div class="mb-3">
                    <label for="label" class="form-label">Label Teks (misal: "Home", "PKS")</label>
                    <input type="text" class="form-control" id="label" name="label" required>
                </div>
                
                <div class="mb-3">
                    <label for="url" class="form-label">URL (Link Tujuan)</label>
                    <input type="text" class="form-control" id="url" name="url" required placeholder="/about atau https://...">
                    <div class="form-text">
                        Gunakan <code>/</code> untuk Home. 
                        Gunakan <code>/business/pks</code> untuk link ke PKS. 
                        Gunakan <code>#</code> untuk Parent Dropdown.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Nomor Urut (Sort Order)</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" required value="0">
                </div>

                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Simpan Link Menu</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) { $conn->close(); }
require_once __DIR__ . '/../../includes/footer.php';
?>