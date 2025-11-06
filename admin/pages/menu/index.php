<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
// Superadmin disarankan, tapi Editor boleh jika Anda izinkan
require_role(ROLE_EDITOR);

// 2. Set Judul Halaman
$page_title = 'Manage Header Menu';

// 3. Muat Header & Sidebar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// 4. Ambil semua menu item
$sql = "SELECT id, label, url, sort_order FROM menu_items ORDER BY sort_order ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Header Menu</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Menu</li>
    </ol>
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill"></i>
        Atur urutan menu menggunakan kolom "Sort Order" (angka lebih kecil tampil lebih dulu).
        <br>
        Item "BUSINESS" (URL: #business_dropdown) adalah item khusus yang akan menampilkan dropdown dinamis.
    </div>

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
            <i class="bi bi-plus-circle me-2"></i>Tambah Link Menu Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Link Menu
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered"> <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Label Teks</th>
                        <th>URL (Link)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $edit_url = "edit.php?id=" . $row['id'];
                            $delete_url = "delete.php?id=" . $row['id'];
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['sort_order']); ?></td>
                                <td><?php echo htmlspecialchars($row['label']); ?></td>
                                <td><?php echo htmlspecialchars($row['url']); ?></td>
                                <td>
                                    <a href="<?php echo $edit_url; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php // Jangan biarkan item khusus dihapus sembarangan
                                    if ($row['url'] != '#business_dropdown'): ?>
                                    <a href="#"
                                       class="btn btn-danger btn-sm"
                                       title="Delete"
                                       data-bs-toggle="modal"
                                       data-bs-target="#confirmDeleteModal"
                                       data-bs-url="<?php echo $delete_url; ?>">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada link menu.</td>
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
require_once __DIR__ . '/../../includes/footer.php';
?>