<?php
// 1. Cek Auth
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// 2. Set Judul Halaman
$page_title = 'Manage Gallery Albums';

// 3. Muat Header & Sidebar
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';

// 4. Ambil semua album dari database
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../../../config.php';
}
$sql = "SELECT id, title, slug, cover_image, created_at, sort_order 
        FROM gallery_albums 
        ORDER BY sort_order ASC, created_at DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Gallery Albums</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Albums</li>
    </ol>

    <?php
    // Tampilkan Flash Message
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
    ?>

    <div class="mb-3">
        <a href="create.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>Tambah Album Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Album
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Judul Album</th>
                        <th>Slug (URL)</th>
                        <th>Urutan</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            // Buat folder baru /assets/img/gallery/covers/
                            $cover_path = BASE_URL . '/assets/img/gallery/covers/' . htmlspecialchars($row['cover_image']);
                            $edit_url = "edit.php?id=" . $row['id'];
                            $delete_url = "delete.php?id=" . $row['id'];
                            // Link ke halaman manajemen gambar baru
                            $images_url = "../images/index.php?album_id=" . $row['id'];
                    ?>
                            <tr>
                                <td>
                                    <?php if(!empty($row['cover_image']) && file_exists(__DIR__ . "/../../../../assets/img/gallery/covers/" . $row['cover_image'])): ?>
                                        <img src="<?php echo $cover_path; ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" style="width: 100px; height: 70px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100px; height: 70px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <span class="text-muted small">No Cover</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td>/gallery/<?php echo htmlspecialchars($row['slug']); ?></td>
                                <td><?php echo htmlspecialchars($row['sort_order']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td style="min-width: 180px;">
                                    <a href="<?php echo $images_url; ?>" class="btn btn-success btn-sm" title="Manage Images">
                                        <i class="bi bi-images"></i> Foto
                                    </a>
                                    <a href="<?php echo $edit_url; ?>" class="btn btn-primary btn-sm" title="Edit Album">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="#"
                                       class="btn btn-danger btn-sm"
                                       title="Delete Album"
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
                            <td colspan="6" class="text-center">Belum ada album galeri.</td>
                        </tr>
                    <?php
                    endif;
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 5. Muat Footer Admin
require_once __DIR__ . '/../../../includes/footer.php';
?>