<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

// 2. Set Judul Halaman
$page_title = 'Tambah Kategori';

// 3. Muat Header Admin
require_once __DIR__ . '/../../includes/header.php';

// 4. Muat Sidebar Admin
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Kategori Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/pages/kategori/">Manage Kategori</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-square me-1"></i>
            Form Kategori
        </div>
        <div class="card-body">
            <form action="store.php" method="POST">
                <div class="mb-3">
                    <label for="nama_kategori" class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required placeholder="Cth: Teknologi">
                </div>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Batal
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-2"></i>Simpan Kategori
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// 6. Muat Footer Admin
require_once __DIR__ . '/../../includes/footer.php';
?>