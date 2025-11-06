<?php
// =================================================================
// INDEX.PHP (FRONT CONTROLLER / ROUTER UTAMA)
// =================================================================

// 1. Inisialisasi
// Mulai session (untuk login, form, dll di masa depan)
session_start();

// Muat file konfigurasi (koneksi DB, BASE_URL)
require_once 'config.php';

// 2. Parsing URL
// Ambil URL yang diminta dari parameter .htaccess
$url = isset($_GET['url']) ? $_GET['url'] : '';
$url = rtrim($url, '/'); // Hapus garis miring di akhir
$url_parts = explode('/', $url); // Pecah URL menjadi array

// Tentukan halaman utama yang diminta
// Jika kosong (halaman home), set ke 'home'
$main_page = !empty($url_parts[0]) ? strtolower($url_parts[0]) : 'home';

if ($main_page == 'contact-submit') {
    // Muat file handler form
    include 'includes/handle-contact-form.php';
    // Hentikan eksekusi, karena ini bukan halaman untuk ditampilkan
    exit;
}
// 3. Routing (Logika Pemilihan Halaman)
// Siapkan variabel untuk header
$page_title = 'Company Profile'; // Judul default
$current_page = $main_page;   // Halaman aktif di navbar
$page_file = '';              // File yang akan di-include

switch ($main_page) {
    case 'home':
        $page_title = 'Home';
        $page_file = 'pages/home.php';
        break;

    case 'about':
        $page_title = 'About Us';
        $page_file = 'pages/about.php';
        break;

case 'business':
        // Cek apakah ada parameter kedua (misal: /business/pks)
        if (isset($url_parts[1])) {
            $business_slug = $url_parts[1]; // Ini adalah 'pks' atau 'palm-oil'
            
            // ===== AMBIL META DATA DARI DB (BARU) =====
            $sql_meta = "SELECT title, meta_description FROM business_items WHERE slug = ? LIMIT 1";
            $stmt_meta = $conn->prepare($sql_meta);
             if ($stmt_meta) {
                $stmt_meta->bind_param("s", $business_slug);
                $stmt_meta->execute();
                $result_meta = $stmt_meta->get_result();
                if ($result_meta->num_rows > 0) {
                    $meta_data = $result_meta->fetch_assoc();
                    $page_title = $meta_data['title'];
                    $page_meta_description = $meta_data['meta_description'];
                } else {
                     $page_title = 'Business Not Found';
                }
                $stmt_meta->close();
            } else {
                $page_title = 'Business Detail'; // Fallback
            }
             // ===== AKHIR AMBIL META DATA =====

            $current_page = 'business-detail'; // Tandai sebagai halaman detail
            $page_file = 'pages/business-detail.php';

        } else {
            // Ini adalah halaman arsip /business
            $page_title = 'Our Business';
            $current_page = 'business'; // Tandai sebagai halaman arsip
            $page_file = 'pages/business.php';
        }
        break;

case 'news':
        // Cek apakah ada parameter kedua (misal: /news/judul-berita)
        if (isset($url_parts[1])) {
            $news_slug = $url_parts[1]; // Ini adalah slug berita
            
            // ===== AMBIL META DATA DARI DB (BARU) =====
            $sql_meta = "SELECT title, meta_description FROM news WHERE slug = ? LIMIT 1";
            $stmt_meta = $conn->prepare($sql_meta);
            if ($stmt_meta) {
                $stmt_meta->bind_param("s", $news_slug);
                $stmt_meta->execute();
                $result_meta = $stmt_meta->get_result();
                if ($result_meta->num_rows > 0) {
                    $meta_data = $result_meta->fetch_assoc();
                    $page_title = $meta_data['title']; // Set judul halaman
                    $page_meta_description = $meta_data['meta_description']; // Simpan meta desc
                } else {
                    $page_title = 'News Not Found'; // Jika slug salah
                }
                $stmt_meta->close();
            } else {
                 $page_title = 'News Detail'; // Fallback
            }
            // ===== AKHIR AMBIL META DATA =====

            $current_page = 'news-detail'; // Tandai sebagai halaman detail
            $page_file = 'pages/news-detail.php';

        } else {
            // Ini adalah halaman arsip /news
            $page_title = 'Latest News';
            $current_page = 'news'; // Tandai sebagai halaman arsip
            $page_file = 'pages/news.php';
        }
        break;
        
	case 'gallery':
        // Cek apakah ada parameter kedua (misal: /gallery/nama-album-slug)
        if (isset($url_parts[1])) {
            $album_slug = $url_parts[1]; // Ini adalah slug album

            // Ambil Meta Data
            $sql_meta = "SELECT title, description FROM gallery_albums WHERE slug = ? LIMIT 1";
            $stmt_meta = $conn->prepare($sql_meta);
            if ($stmt_meta) {
                $stmt_meta->bind_param("s", $album_slug);
                $stmt_meta->execute();
                $result_meta = $stmt_meta->get_result();
                if ($result_meta->num_rows > 0) {
                    $meta_data = $result_meta->fetch_assoc();
                    $page_title = 'Gallery: ' . $meta_data['title'];
                    // Ambil deskripsi singkat dari Summernote (jika ada)
                    $page_meta_description = substr(strip_tags($meta_data['description']), 0, 155);
                } else {
                    $page_title = 'Album Not Found';
                }
                $stmt_meta->close();
            } else {
                 $page_title = 'Gallery Detail';
            }

            $current_page = 'gallery-detail';
            $page_file = 'pages/gallery-detail.php'; // Halaman detail
        } else {
            // Ini adalah halaman arsip /gallery (daftar album)
            $page_title = 'Gallery';
            $current_page = 'gallery';
            $page_file = 'pages/gallery.php'; // Halaman daftar album
        }
        break;

    case 'contact':
        $page_title = 'Contact Us';
        $page_file = 'pages/contact.php';
        break;

    default:
        // Jika halaman tidak ada di switch
        $current_page = '404';
        $page_title = 'Page Not Found';
        $page_file = 'pages/404.php';
        http_response_code(404); // Kirim status 404 ke browser
        break;
}

// =================================================================
// 4. Merakit Halaman
// =================================================================

// Muat Header (ini akan menggunakan variabel $page_title dan $current_page)
include 'includes/header.php';

// Muat Konten Halaman
if (file_exists($page_file)) {
    include $page_file;
} else {
    // Fallback jika file tidak ditemukan (seharusnya sudah ditangani switch)
    include 'pages/404.php';
}

// Muat Footer
include 'includes/footer.php';

?>