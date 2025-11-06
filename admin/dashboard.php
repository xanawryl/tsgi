<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once 'includes/auth-check.php';

// 2. Set Judul Halaman
$page_title = 'Dashboard';

// 3. Muat Header Admin
require_once 'includes/header.php';

// 4. Muat Sidebar Admin
require_once 'includes/sidebar.php';

// ===============================================
// 5. AMBIL DATA STATISTIK (BARU)
// ===============================================
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../config.php'; // Panggil config lagi
}

// Hitung jumlah berita
$sql_news_count = "SELECT COUNT(id) as total FROM news";
$result_news_count = $conn->query($sql_news_count);
$news_count = ($result_news_count) ? $result_news_count->fetch_assoc()['total'] : 0;

// Hitung jumlah item bisnis
$sql_business_count = "SELECT COUNT(id) as total FROM business_items";
$result_business_count = $conn->query($sql_business_count);
$business_count = ($result_business_count) ? $result_business_count->fetch_assoc()['total'] : 0;

// Hitung jumlah gambar galeri
$sql_gallery_count = "SELECT COUNT(id) as total FROM gallery_images";
$result_gallery_count = $conn->query($sql_gallery_count);
$gallery_count = ($result_gallery_count) ? $result_gallery_count->fetch_assoc()['total'] : 0;

// Hitung jumlah admin user
$sql_user_count = "SELECT COUNT(id) as total FROM admin_users";
$result_user_count = $conn->query($sql_user_count);
$user_count = ($result_user_count) ? $result_user_count->fetch_assoc()['total'] : 0;

// ===============================================
// AKHIR AMBIL DATA STATISTIK
// ===============================================

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Selamat datang, <?php echo htmlspecialchars($admin_full_name); ?>!</li>
    </ol>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-newspaper me-2"></i>Total Berita</span>
                    <span class="fs-4 fw-bold"><?php echo $news_count; ?></span>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?php echo $admin_base; ?>/pages/news/">Lihat Detail</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-briefcase-fill me-2"></i>Total Item Bisnis</span>
                     <span class="fs-4 fw-bold"><?php echo $business_count; ?></span>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-dark stretched-link" href="<?php echo $admin_base; ?>/pages/business/">Lihat Detail</a>
                    <div class="small text-dark"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                 <div class="card-body d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-images me-2"></i>Total Gambar Galeri</span>
                     <span class="fs-4 fw-bold"><?php echo $gallery_count; ?></span>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?php echo $admin_base; ?>/pages/gallery-groups/">Lihat Detail Grup</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                 <div class="card-body d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people-fill me-2"></i>Total Admin</span>
                     <span class="fs-4 fw-bold"><?php echo $user_count; ?></span>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="<?php echo $admin_base; ?>/pages/users/">Lihat Detail</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    <h3 class="mt-4 mb-3">Akses Cepat</h3>
    <div class="row">
         <div class="col-lg-4 col-md-6">
             <div class="card mb-4 shadow-sm">
                 <div class="card-body text-center">
                     <i class="bi bi-plus-circle fs-1 text-success mb-2"></i>
                     <h5 class="card-title">Tambah Berita Baru</h5>
                     <a href="<?php echo $admin_base; ?>/pages/news/create.php" class="btn btn-success stretched-link mt-2">Tambah</a>
                 </div>
             </div>
         </div>
         <div class="col-lg-4 col-md-6">
              <div class="card mb-4 shadow-sm">
                 <div class="card-body text-center">
                     <i class="bi bi-gear-fill fs-1 text-info mb-2"></i>
                     <h5 class="card-title">Pengaturan Website</h5>
                     <a href="<?php echo $admin_base; ?>/pages/site-settings.php" class="btn btn-info stretched-link mt-2 text-white">Edit Pengaturan</a>
                 </div>
             </div>
         </div>
          <div class="col-lg-4 col-md-6">
              <div class="card mb-4 shadow-sm">
                 <div class="card-body text-center">
                     <i class="bi bi-collection-fill fs-1 text-secondary mb-2"></i>
                     <h5 class="card-title">Manajemen Grup Galeri</h5>
                     <a href="<?php echo $admin_base; ?>/pages/gallery-groups/" class="btn btn-secondary stretched-link mt-2">Kelola Grup</a>
                 </div>
             </div>
         </div>
    </div>
     </div>

<?php
// Tutup koneksi jika belum ditutup
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
// 5. Muat Footer Admin
require_once 'includes/footer.php';
?>