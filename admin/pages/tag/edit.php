<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

// 2. Validasi ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "ID Tag tidak valid.";
    $_SESSION['flash_message_type'] = "danger";
    header("Location: index.php");
    exit();
}
$id_tag = $_GET['id'];

// 3. Ambil data tag dari DB
$stmt = $conn->prepare("SELECT * FROM tag WHERE id_tag = ?");
$stmt->bind_param("i", $id_tag);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_message'] = "Tag tidak ditemukan.";
    $_SESSION['flash_message_type'] = "danger";
    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit();
}
$tag = $result->fetch_assoc();
$stmt->close();

// 4. Set Judul Halaman
$page_title = 'Edit Tag';

// 5. Muat Header Admin
require_once __DIR__ . '/../../includes/header.php';

// 6. Muat Sidebar Admin
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Tag</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/tag/">Manage Tag</a></li>
        <li class="breadcrumb-item active">Edit Tag</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Form Edit Tag
        </div>
        <div class="card-body">
            <form action="update.php" method="POST">
                <input type="hidden" name="id_tag" value="<?php echo $tag['id_tag']; ?>">
                
                <div class="mb-3">
                    <label for="nama_tag" class="form-label">Nama Tag</label>
                    <input type="text" class="form-control" id="nama_tag" name="nama_tag" required 
                           value="<?php echo htmlspecialchars($tag['nama_tag']); ?>">
                </div>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Tag
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// 7. Muat Footer Admin
require_once __DIR__ . '/../../includes/footer.php';
?>