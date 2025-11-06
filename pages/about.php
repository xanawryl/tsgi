<?php
// Pastikan koneksi $conn ada
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../config.php'; // Panggil config.php (path ini benar untuk file di /pages/)
}

// ==========================================================
// AMBIL DATA DINAMIS UNTUK HALAMAN INI
// ==========================================================

// 1. Ambil Board Members
$sql_members = "SELECT full_name, position, image_url FROM board_members ORDER BY sort_order ASC";
$result_members = $conn->query($sql_members);
$members = ($result_members && $result_members->num_rows > 0) ? $result_members->fetch_all(MYSQLI_ASSOC) : [];

// 2. Ambil Business Partners
$sql_partners = "SELECT partner_name, website_url, logo_url, description FROM partners ORDER BY sort_order ASC";
$result_partners = $conn->query($sql_partners);
$partners = ($result_partners && $result_partners->num_rows > 0) ? $result_partners->fetch_all(MYSQLI_ASSOC) : [];

// $conn akan ditutup otomatis di akhir script jika tidak dipanggil dari index.php
// Tapi karena kita panggil dari index.php, $conn akan ditutup oleh index.php
?>

<section id="about-intro" class="bg-dark py-5" style="padding-top: 100px;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 text-light">
                <h2 class="section-title text-warning" style="text-align: left;">Tentang TSGI</h2>
                <div class="about-content">
                    <?php echo get_setting_raw('about_us_intro', $conn, '<p>Deskripsi perusahaan belum diatur.</p>'); ?>
                </div>
            </div>
            <div class="col-lg-6 text-center text-light">
                <div class="p-4 rounded" style="background-color: rgba(255, 255, 255, 0.05);">
                    <h3 class="text-warning">Company Value</h3>
                    <h2 class="display-5 fst-italic" style="color: #fff;"><?php echo get_setting('company_value_text', $conn, 'Slogan Belum Diatur'); ?></h2>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="vision-mission" class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-md-6">
                <h3 class="section-title text-warning" style="text-align: left;">Visi Kami</h3>
                <div class="about-content text-dark">
                    <?php echo get_setting_raw('vision_text', $conn, '<p>Visi belum diatur.</p>'); ?>
                </div>
            </div>
            <div class="col-md-6">
                 <h3 class="section-title text-warning" style="text-align: left;">Misi Kami</h3>
                <div class="about-content text-dark">
                    <?php echo get_setting_raw('mission_text', $conn, '<p>Misi belum diatur.</p>'); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="services-intro" class="bg-dark py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-md-6">
                <h3 class="section-title text-warning" style="text-align: left;">Produk dan Layanan</h3>
                <div class="about-content text-light">
                    <?php echo get_setting_raw('products_services_intro', $conn, '<p>Info produk & layanan belum diatur.</p>'); ?>
                    <a href="<?php echo BASE_URL; ?>/business" class="btn btn-gold btn-custom mt-3">Lihat Lini Bisnis</a>
                </div>
            </div>
            <div class="col-md-6">
                 <h3 class="section-title text-warning" style="text-align: left;">Strategic Management Consultant</h3>
                <div class="about-content text-light">
                    <?php echo get_setting_raw('consultant_intro', $conn, '<p>Info konsultan belum diatur.</p>'); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="why-choose-us" class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="section-title text-warning">Mengapa Memilih Kami?</h2>
                <div class="about-content text-dark">
                    <?php echo get_setting_raw('why_choose_us', $conn, '<p>Alasan memilih kami belum diatur.</p>'); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="board-members" class="bg-dark py-5">
    <div class="container">
        <h2 class="section-title">Board Members</h2>
        <div class="row g-4 text-center text-light justify-content-center">
            
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): 
                    $image_path = BASE_URL . '/assets/img/members/' . htmlspecialchars($member['image_url']);
                ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card bg-secondary p-3 h-100">
                            <img src="<?php echo $image_path; ?>"
                                 class="card-img-top rounded-circle w-50 mx-auto mb-3 shadow-sm" 
                                 alt="<?php echo htmlspecialchars($member['full_name']); ?>"
                                 style="aspect-ratio: 1/1; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><?php echo htmlspecialchars($member['full_name']); ?></h5>
                                <p class="card-text mb-0"><?php echo htmlspecialchars($member['position']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center text-light lead">Data board member belum tersedia.</p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<section id="mitra-area" class="py-5">
    <div class="container">
        <h2 class="section-title">Affiliation</h2>
        <div class="row g-4 justify-content-center">
            
            <?php if (!empty($partners)): ?>
                <?php foreach ($partners as $partner): 
                    $logo_path = BASE_URL . '/assets/img/partners/' . htmlspecialchars($partner['logo_url']);
                ?>
                    <div class="col-md-5">
                        <div class="card bg-secondary text-light p-4 h-100 shadow d-flex flex-column">
                            <div class="text-center mb-3" style="min-height: 80px;">
                                <img src="<?php echo $logo_path; ?>" 
                                     alt="<?php echo htmlspecialchars($partner['partner_name']); ?> Logo" 
                                     style="max-width: 200px; max-height: 70px; object-fit: contain;">
                            </div>
                            <h4 class="text-warning text-center"><?php echo htmlspecialchars($partner['partner_name']); ?></h4>
                            <p class="mb-3">
                                <?php echo !empty($partner['description']) ? htmlspecialchars($partner['description']) : '[Deskripsi mitra belum tersedia]'; ?>
                            </p>
                            
                            <?php if (!empty($partner['website_url'])): // Tampilkan tombol hanya jika URL ada ?>
                                <a href="<?php echo htmlspecialchars($partner['website_url']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="btn btn-gold btn-custom mt-auto align-self-center" 
                                   style="max-width: 200px;">
                                    Kunjungi Website <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="col-12">
                    <p class="text-center text-light lead">Data Affiliation belum tersedia.</p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>