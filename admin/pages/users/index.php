<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';

require_role(ROLE_SUPERADMIN);

// 2. Set Judul Halaman
$page_title = 'Manage Admin Users';

// 3. Muat Header & Sidebar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// 4. Ambil semua admin user (kecuali password)
$sql = "SELECT id, username, full_name, created_at FROM admin_users ORDER BY username ASC";
$result = $conn->query($sql);

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Admin Users</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Users</li>
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
        <a href="<?php echo $admin_base; ?>/pages/users/create.php" class="btn btn-success">
            <i class="bi bi-person-plus-fill me-2"></i>Tambah Admin Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Admin
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $date = new DateTime($row['created_at']);
                            $formatted_date = $date->format('d M Y, H:i');
                            $edit_url = "edit.php?id=" . $row['id'];
                            $delete_url = "delete.php?id=" . $row['id'];
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo $formatted_date; ?></td>
                                <td>
                                    <a href="<?php echo $edit_url; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <?php // Jangan biarkan admin menghapus dirinya sendiri
                                    if ($row['id'] != $_SESSION['admin_id']): ?>
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
                            <td colspan="4" class="text-center">Belum ada admin lain.</td>
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