<?php
// 1. Cek Auth
require_once __DIR__ . '/../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// 2. Set Judul Halaman
$page_title = 'Manage Board Members';

// 3. Muat Header & Sidebar
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// 4. Ambil semua member
$sql = "SELECT id, full_name, position, image_url, sort_order FROM board_members ORDER BY sort_order ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Board Members</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Members</li>
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
            <i class="bi bi-plus-circle me-2"></i>Tambah Member Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Board Members
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nama Lengkap</th>
                        <th>Jabatan</th>
                        <th>Urutan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $image_path = BASE_URL . '/assets/img/members/' . htmlspecialchars($row['image_url']);
                            $edit_url = "edit.php?id=" . $row['id'];
                            $delete_url = "delete.php?id=" . $row['id'];
                    ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($row['full_name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;">
                                </td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['position']); ?></td>
                                <td><?php echo htmlspecialchars($row['sort_order']); ?></td>
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
                            <td colspan="5" class="text-center">Belum ada data board member.</td>
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