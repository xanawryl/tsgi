<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

// 2. Set Judul Halaman
$page_title = 'Manage Kategori';

// 3. Muat Header Admin
require_once __DIR__ . '/../../includes/header.php';

// 4. Muat Sidebar Admin
require_once __DIR__ . '/../../includes/sidebar.php';

// 5. Ambil semua kategori dari database
// $conn sudah tersedia dari auth-check.php
$sql = "SELECT id_kategori, nama_kategori, slug_kategori FROM kategori ORDER BY nama_kategori ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Kategori</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Kategori</li>
    </ol>
    
    <?php
    // ========== TAMPILKAN FLASH MESSAGE (Pesan Sukses/Gagal) ==========
    if (isset($_SESSION['flash_message'])):
        $alert_type = isset($_SESSION['flash_message_type']) ? $_SESSION['flash_message_type'] : 'success';
        $alert_class = ($alert_type == 'danger') ? 'alert-danger' : 'alert-success';
    ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_message_type']);
    endif;
    // ========== AKHIR FLASH MESSAGE ==========
    ?>

    <div class="mb-3">
        <a href="<?php echo $admin_base; ?>/pages/kategori/create.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>Tambah Kategori Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Kategori
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Slug (URL)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Slug (URL)</th>
                        <th>Aksi</th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    // Looping data kategori
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $edit_url = "edit.php?id=" . $row['id_kategori'];
                            $delete_url = "delete.php?id=" . $row['id_kategori'];
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                <td>/kategori/<?php echo htmlspecialchars($row['slug_kategori']); ?></td>
                                <td>
                                    <a href="<?php echo $edit_url; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <a href="#" 
                                       class="btn btn-danger btn-sm" 
                                       title="Delete"
                                       data-bs-toggle="modal" 
                                       data-bs-target="#confirmDeleteModal"
                                       data-bs-url="<?php echo $delete_url; ?>"> 
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="3" class="text-center">Belum ada kategori.</td>
                        </tr>
                    <?php
                    endif;
                    $conn->close(); // Tutup koneksi
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 6. Muat Footer Admin
// Pastikan modal delete ada di footer.php Anda, sama seperti di fitur News
require_once __DIR__ . '/../../includes/footer.php';
?>