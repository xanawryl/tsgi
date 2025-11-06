<?php
// 1. Cek Auth
require_once __DIR__ . '/../includes/auth-check.php';

require_role(ROLE_SUPERADMIN);

// Variabel pesan status
$message = '';
$message_type = ''; // 'success' or 'danger'

// ==========================================================
// PROSES FORM UPDATE (POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ===== VALIDASI CSRF TOKEN =====
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = "Invalid request or session expired. Please try again.";
        $message_type = 'danger';
    } else { // <-- Buka else CSRF
    // ===== AKHIR VALIDASI CSRF =====

        // Pastikan koneksi $conn ada sebelum memulai transaksi
        if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
             require_once __DIR__ . '/../../config.php'; // Panggil config lagi jika koneksi hilang
        }

        // Mulai transaksi
        $conn->begin_transaction();
        $all_ok = true;

        try {
            // Loop melalui semua data yang dikirim dari form (HANYA JIKA TOKEN VALID)
            foreach ($_POST as $key => $value) {
                // Kita hanya proses input yang namanya dimulai dengan 'setting_'
                if (strpos($key, 'setting_') === 0) {
                    $setting_key = substr($key, 8); // Hapus 'setting_' dari nama input
                    // Gunakan trim() tapi perbolehkan string kosong
                    $setting_value = trim($value);

                    // Siapkan query UPDATE atau INSERT (UPSERT)
                    $sql = "INSERT INTO site_settings (setting_key, setting_value)
                            VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = ?";
                    $stmt = $conn->prepare($sql);

                    // Cek jika prepare gagal
                    if ($stmt === false) {
                        $all_ok = false;
                        $message = "Gagal menyiapkan query untuk '$setting_key': " . $conn->error;
                        $message_type = 'danger';
                        break; // Keluar dari loop foreach
                    }

                    // sss = 3 string (key, value, value lagi untuk update)
                    $stmt->bind_param("sss", $setting_key, $setting_value, $setting_value);

                    if (!$stmt->execute()) {
                        // Jika satu query gagal, batalkan semua
                        $all_ok = false;
                        $message = "Gagal menyimpan pengaturan '$setting_key': " . $stmt->error;
                        $message_type = 'danger';
                        $stmt->close();
                        break; // Keluar dari loop foreach
                    }
                    $stmt->close();
                } // <-- Tutup if strpos
            } // <-- Tutup foreach

            // Jika semua query berhasil
            if ($all_ok) {
                $conn->commit(); // Simpan permanen perubahan
                $message = "Pengaturan website berhasil diperbarui!";
                $message_type = 'success';

                // ===== RELOAD PENGATURAN SETELAH UPDATE =====
                global $site_settings;
                $site_settings = null;
                // Pastikan koneksi $conn masih ada sebelum memanggil load
                if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
                     load_site_settings($conn); // Panggil fungsi dari config.php
                     $settings = $site_settings; // Update array $settings untuk form
                } else {
                     // Handle jika koneksi hilang (seharusnya tidak terjadi di sini)
                     $message .= " (Gagal reload pengaturan terbaru)";
                     $message_type = ($message_type == 'success') ? 'warning' : $message_type;
                }
                // ===== AKHIR RELOAD PENGATURAN =====

            } else {
                $conn->rollback(); // Batalkan semua perubahan jika ada error
                // Pesan error sudah diatur di dalam loop
            }

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "Terjadi kesalahan database: " . $exception->getMessage();
            $message_type = 'danger';
        }

    } // <-- TUTUP else validasi CSRF (INI YANG MUNGKIN HILANG SEBELUMNYA)

    // Jangan tutup koneksi $conn di sini jika POST gagal dan perlu reload form
    // $conn->close();

} // <-- TUTUP if POST
// ==========================================================
// AKHIR PROSES FORM UPDATE
// ==========================================================


// ==========================================================
// AMBIL SEMUA PENGATURAN DARI DATABASE UNTUK FORM
// Pastikan koneksi $conn ada
// ==========================================================
if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
     require_once __DIR__ . '/../../config.php'; // Panggil config lagi
}
// Gunakan variabel global $site_settings jika sudah dimuat
global $site_settings;
if ($site_settings === null) {
    // Jika belum dimuat (misalnya saat load halaman pertama kali, bukan setelah POST)
    load_site_settings($conn); // Panggil fungsi dari config.php
}
$settings = $site_settings ?? []; // Gunakan array kosong jika $site_settings masih null

// Cek apakah ada error saat load setting awal (meskipun jarang terjadi)
if (empty($settings) && $message_type != 'danger') { // Hanya tampilkan jika belum ada error lain
    // Cek error koneksi terakhir
     if ($conn->connect_error) {
        $message = "Gagal koneksi database saat mengambil pengaturan: " . $conn->connect_error;
        $message_type = 'danger';
     } elseif ($conn->error) {
         $message = "Gagal query pengaturan: " . $conn->error;
         $message_type = 'danger';
     }
}


// Set Judul Halaman
$page_title = 'Pengaturan Website';

// Muat Header & Sidebar Admin
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Helper function untuk mendapatkan nilai setting dengan aman (sudah ada di config.php)
// function get_setting($key, $settings_array, $default = '') { ... }
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Pengaturan Website</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $admin_base; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Pengaturan Website</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-gear-fill me-1"></i>
            Edit Pengaturan Umum Website
        </div>
        <div class="card-body">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="site-settings.php" method="POST">
                <?php echo csrf_input(); ?>

                <h5 class="mb-3 text-primary">Informasi Kontak</h5>
                <div class="mb-3">
                    <label for="setting_contact_email" class="form-label">Email Kontak Utama</label>
                    <input type="email" class="form-control" id="setting_contact_email" name="setting_contact_email"
                           value="<?php echo get_setting('contact_email', $conn); // Gunakan $conn ?>">
                </div>
                <div class="mb-3">
                    <label for="setting_contact_phone" class="form-label">Nomor Telepon Kontak</label>
                    <input type="text" class="form-control" id="setting_contact_phone" name="setting_contact_phone"
                           value="<?php echo get_setting('contact_phone', $conn); // Gunakan $conn ?>">
                </div>
                <div class="mb-3">
                    <label for="setting_contact_address" class="form-label">Alamat Lengkap Kantor</label>
                    <textarea class="form-control" id="setting_contact_address" name="setting_contact_address" rows="3"><?php echo get_setting('contact_address', $conn); // Gunakan $conn ?></textarea>
                </div>
                 <div class="mb-3">
                    <label for="setting_company_short_address" class="form-label">Alamat Singkat (untuk Footer)</label>
                    <input type="text" class="form-control" id="setting_company_short_address" name="setting_company_short_address"
                           value="<?php echo get_setting('company_short_address', $conn); // Gunakan $conn ?>">
                </div>
                 <div class="mb-3">
                    <label for="setting_office_hours" class="form-label">Jam Kerja</label>
                    <input type="text" class="form-control" id="setting_office_hours" name="setting_office_hours"
                           value="<?php echo get_setting('office_hours', $conn); // Gunakan $conn ?>">
                </div>

                <hr class="my-4">

                <h5 class="mb-3 text-primary">Integrasi</h5>
                 <div class="mb-3">
                    <label for="setting_google_maps_embed_url" class="form-label">URL Embed Google Maps</label>
                    <input type="url" class="form-control" id="setting_google_maps_embed_url" name="setting_google_maps_embed_url"
                           value="<?php echo get_setting('google_maps_embed_url', $conn); // Gunakan $conn ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                     <div class="form-text">Salin URL dari atribut `src` kode embed Google Maps.</div>
                </div>

				<h5 class="mb-3 text-primary">Sosial Media</h5>
                 <div class="mb-3">
                    <label for="setting_social_facebook_url" class="form-label">URL Facebook</label>
                    <input type="url" class="form-control" id="setting_social_facebook_url" name="setting_social_facebook_url"
                           value="<?php echo get_setting('social_facebook_url', $conn); ?>" placeholder="https://www.facebook.com/nama_halaman">
                </div>
                 <div class="mb-3">
                    <label for="setting_social_instagram_url" class="form-label">URL Instagram</label>
                    <input type="url" class="form-control" id="setting_social_instagram_url" name="setting_social_instagram_url"
                           value="<?php echo get_setting('social_instagram_url', $conn); ?>" placeholder="https://www.instagram.com/nama_akun">
                </div>
                <div class="mb-3">
                    <label for="setting_social_linkedin_url" class="form-label">URL LinkedIn</label>
                    <input type="url" class="form-control" id="setting_social_linkedin_url" name="setting_social_linkedin_url"
                           value="<?php echo get_setting('social_linkedin_url', $conn); ?>" placeholder="https://www.linkedin.com/company/nama_perusahaan">
                </div>
                 <div class="mb-3">
                    <label for="setting_social_twitter_url" class="form-label">URL Twitter/X</label>
                    <input type="url" class="form-control" id="setting_social_twitter_url" name="setting_social_twitter_url"
                           value="<?php echo get_setting('social_twitter_url', $conn); ?>" placeholder="https://twitter.com/nama_akun">
                </div>
                 <div class="form-text">Biarkan kosong jika tidak ingin menampilkan ikon sosial media tersebut.</div>
				 
			<hr class="my-4">
            <h5 class="mb-3 text-primary">Konten Halaman "About Us"</h5>

            <div class="mb-3">
                <label for="setting_company_value_text" class="form-label">Company Value (Slogan)</label>
                <input type="text" class="form-control" id="setting_company_value_text" name="setting_company_value_text"
                       value="<?php echo get_setting('company_value_text', $conn); ?>" placeholder="Sinergize Your Business">
            </div>
			
				<div class="mb-3">
                    <label for="setting_home_about_summary" class="form-label">Ringkasan "About" (untuk Homepage)</label>
                    <textarea class="form-control" id="setting_home_about_summary" name="setting_home_about_summary" rows="4"><?php echo get_setting('home_about_summary', $conn); // Ini BUKAN Summernote, pakai get_setting() ?></textarea>
                    <div class="form-text">Ini adalah teks singkat yang muncul di halaman utama (Home).</div>
                </div>

				<div class="mb-3">
                    <label for="setting_about_us_intro" class="form-label">About Us (Teks Lengkap untuk Halaman About)</label>
                    <textarea class="form-control summernote-editor" id="setting_about_us_intro" name="setting_about_us_intro" rows="5"><?php echo get_setting_raw('about_us_intro', $conn); // <-- BENAR: get_setting_raw() dengan $conn ?></textarea>
                </div>

				<div class="mb-3">
                    <label for="setting_vision_text" class="form-label">Visi</label>
                    <textarea class="form-control summernote-editor" id="setting_vision_text" name="setting_vision_text" rows="5"><?php echo get_setting_raw('vision_text', $conn); // <-- BENAR: get_setting_raw() dengan $conn ?></textarea>
                </div>

				<div class="mb-3">
                    <label for="setting_mission_text" class="form-label">Misi (Gunakan bullets di editor)</label>
                    <textarea class="form-control summernote-editor" id="setting_mission_text" name="setting_mission_text" rows="5"><?php echo get_setting_raw('mission_text', $conn); // <-- BENAR: get_setting_raw() dengan $conn ?></textarea>
                </div>

				<div class="mb-3">
                    <label for="setting_products_services_intro" class="form-label">Produk dan Layanan</label>
                    <textarea class="form-control summernote-editor" id="setting_products_services_intro" name="setting_products_services_intro" rows="5"><?php echo get_setting_raw('products_services_intro', $conn); // <-- BENAR: get_setting_raw() dengan $conn ?></textarea>
                </div>

				<div class="mb-3">
                    <label for="setting_consultant_intro" class="form-label">Strategic Management Consultant</label>
                    <textarea class="form-control summernote-editor" id="setting_consultant_intro" name="setting_consultant_intro" rows="5"><?php echo get_setting_raw('consultant_intro', $conn); // <-- BENAR: get_setting_raw() dengan $conn ?></textarea>
                </div>

				<div class="mb-3">
                    <label for="setting_why_choose_us" class="form-label">Mengapa Memilih Kami?</label>
                    <textarea class="form-control summernote-editor" id="setting_why_choose_us" name="setting_why_choose_us" rows="5"><?php echo get_setting_raw('why_choose_us', $conn); // <-- BENAR: get_setting_raw() dengan $conn ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-save me-2"></i>Simpan Pengaturan
                </button>
            </form>

        </div>
    </div>
</div>

<?php
// Tutup koneksi jika belum ditutup
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
// Muat Footer Admin
require_once __DIR__ . '/../includes/footer.php';
?>