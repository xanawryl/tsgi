<?php
// 1. Tentukan halaman saat ini. 'home' adalah default.
$page = $_GET['page'] ?? 'home';

// 2. Daftar halaman yang valid untuk keamanan.
$allowedPages = [
    'home', 
    'news', 
    'branch-jakarta', 
    'branch-bandung', 
    'about', 
    'career', 
    'contact',
    'amienation', // Diperlukan untuk link di homepage
    'amie-udon'   // Diperlukan untuk link di homepage
];

// 3. Tentukan file yang akan dimuat
$pagePath = 'pages/'; // Path folder pages
$fileToInclude = $pagePath . '404.php'; // Default ke 404
$is404 = true;

if (in_array($page, $allowedPages)) {
    $fileToIncludeCheck = $pagePath . $page . '.php';
    // Cek apakah file halaman benar-benar ada
    if (file_exists($fileToIncludeCheck)) {
        $fileToInclude = $fileToIncludeCheck;
        $is404 = false;
    }
}

// 4. Muat Header
// Variabel $page dikirim ke header.php agar bisa mengatur link 'active'
include 'includes/header.php';

// 5. Muat Konten Halaman menggunakan Switch...Case
echo '<main id="content-area">';

// Kirim header 404 Not Found HANYA jika halaman tidak ditemukan
if ($is404) {
    header("HTTP/1.0 404 Not Found");
}

// Gunakan switch case untuk memuat file yang sudah divalidasi
switch ($page) {
    case 'home':
    case 'news':
    case 'branch-jakarta':
    case 'branch-bandung':
    case 'about':
    case 'career':
    case 'contact':
    case 'amienation':
    case 'amie-udon':
        include $fileToInclude; // $fileToInclude sudah berisi path (cth: 'pages/home.php')
        break;
    default:
        include $pagePath . '404.php';
        break;
}

echo '</main>';

// 6. Muat Footer
include 'includes/footer.php';

?>