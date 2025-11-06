<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../includes/auth-check.php';

// 2. Set Judul Halaman
$page_title = 'Manage News';

// 3. Muat Header Admin
require_once __DIR__ . '/../../includes/header.php';

// 4. Muat Sidebar Admin
require_once __DIR__ . '/../../includes/sidebar.php';

// 5. Ambil semua berita dari database
// $conn sudah tersedia dari auth-check.php -> config.php
$sql = "SELECT id, title, slug, created_at FROM news ORDER BY created_at DESC";
$result = $conn->query($sql);

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage News</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage News</li>
    </ol>
<?php
    // ========== TAMPILKAN FLASH MESSAGE (Pesan Sukses/Gagal) ==========
    if (isset($_SESSION['flash_message'])):
    
        // Tentukan warna alert
        $alert_type = isset($_SESSION['flash_message_type']) ? $_SESSION['flash_message_type'] : 'success';
        if ($alert_type == 'danger') {
            $alert_class = 'alert-danger';
        } else {
            $alert_class = 'alert-success';
        }
    ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
        // Hapus session setelah ditampilkan
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_message_type']);
    endif;
    // ========== AKHIR FLASH MESSAGE ==========
    ?>
    <div class="mb-3">
        <a href="<?php echo $admin_base; ?>/pages/news/create.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>Tambah Berita Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Berita
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Judul Berita</th>
                        <th>Slug (URL)</th>
                        <th>Tanggal Publikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Judul Berita</th>
                        <th>Slug (URL)</th>
                        <th>Tanggal Publikasi</th>
                        <th>Aksi</th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    // Looping data berita
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $date = new DateTime($row['created_at']);
                            $formatted_date = $date->format('d M Y, H:i');
                            
                            // Siapkan link untuk Edit dan Delete
                            $edit_url = "edit.php?id=" . $row['id'];
                            // Kita akan buat delete-news.php nanti
                            $delete_url = "delete.php?id=" . $row['id'];
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td>/news/<?php echo htmlspecialchars($row['slug']); ?></td>
                                <td><?php echo $formatted_date; ?></td>
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
                            <td colspan="4" class="text-center">Belum ada berita.</td>
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
require_once __DIR__ . '/../../includes/footer.php';
?>