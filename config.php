<?php
// ========== PENGATURAN ERROR (Untuk Development) ==========
// Tampilkan semua error agar kita tahu apa yang salah
ini_set('display_errors', 1);
error_reporting(E_ALL);


// ========== PENGATURAN DASAR ==========
// Ganti 'tsgi' jika nama folder Anda di htdocs berbeda
define('BASE_PATH', '/'); 
define('BASE_URL', 'https://tsgi.awryl.my.id');

// ========== DEFINISI PERAN ADMIN ==========
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_EDITOR', 'editor');
// =========================================

// ========== KONEKSI DATABASE (MySQLi) ==========

// 1. Informasi Database
// Ini adalah pengaturan default untuk XAMPP
define('DB_HOST', 'localhost');  // Server database Anda
define('DB_USER', 'fnwucupr_tsgi');       // Username database (default XAMPP)
define('DB_PASS', 'Agustus4886@');           // Password database (default XAMPP kosong)
define('DB_NAME', 'fnwucupr_tsgi');    // Nama database yang kita buat tadi

// 2. Buat Koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 3. Cek Koneksi
if ($conn->connect_error) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// 4. Set karakter encoding ke UTF-8 (Sangat disarankan)
$conn->set_charset("utf8mb4");

// 
// Alternatif: Koneksi menggunakan PDO (Jika Anda lebih suka)
// try {
//     $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     $pdo->exec("SET NAMES 'utf8mb4'");
// } catch (PDOException $e) {
//     die("Koneksi Database Gagal: " . $e->getMessage());
// }
// 

// Anda bisa menyertakan fungsi-fungsi global di sini nanti
// function sanitize($data) { ... }

// ========== FUNGSI HELPER PENGATURAN SITUS ==========

// Variabel global untuk menyimpan semua setting (agar tidak query berulang)
$site_settings = null;

/**
 * Mengambil semua pengaturan dari database dan menyimpannya di variabel global.
 * @param mysqli $db_conn Koneksi database
 * @return array Array asosiatif ['setting_key' => 'setting_value']
 */
function load_site_settings(mysqli $db_conn): array {
    global $site_settings; // Gunakan variabel global

    // Jika sudah dimuat sebelumnya, kembalikan saja
    if ($site_settings !== null) {
        return $site_settings;
    }

    $settings = [];
    $sql = "SELECT setting_key, setting_value FROM site_settings";
    $result = $db_conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } else {
        // Handle error jika query gagal (misal: log error)
        error_log("Gagal memuat pengaturan situs: " . $db_conn->error);
    }
    $site_settings = $settings; // Simpan ke variabel global
    return $site_settings;
}

/**
 * Mendapatkan nilai pengaturan tertentu.
 * Memanggil load_site_settings() jika belum dimuat.
 * @param string $key Nama pengaturan (misal: 'contact_email')
 * @param mysqli $db_conn Koneksi database
 * @param string $default Nilai default jika setting tidak ditemukan
 * @return string Nilai pengaturan
 */
function get_setting(string $key, mysqli $db_conn, string $default = ''): string {
    global $site_settings;

    // Muat settings jika belum ada
    if ($site_settings === null) {
        load_site_settings($db_conn);
    }

    // Kembalikan nilai setting atau nilai default
    return isset($site_settings[$key]) ? htmlspecialchars($site_settings[$key]) : $default;
}

// ==========================================================

// Panggil load_site_settings() satu kali saat config.php dimuat
// Ini memastikan $site_settings terisi saat file lain include config.php
// Pastikan $conn sudah didefinisikan sebelum baris ini
if (isset($conn)) {
    load_site_settings($conn);
}


function get_setting_raw(string $key, mysqli $db_conn, string $default = ''): string {
    global $site_settings;

    // Muat settings jika belum ada
    if ($site_settings === null) {
        load_site_settings($db_conn);
    }

    // Kembalikan nilai setting mentah atau nilai default
    return isset($site_settings[$key]) ? $site_settings[$key] : $default;
}
// ========== FUNGSI KEAMANAN CSRF ==========

/**
 * Membuat token CSRF baru atau mengembalikan yang sudah ada di session.
 * @return string Token CSRF
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        // Buat token acak yang aman (32 byte = 64 karakter hex)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memvalidasi token CSRF yang dikirim dari form.
 * @param string $token_from_form Token dari input POST
 * @return bool True jika valid, False jika tidak.
 */
function csrf_verify(string $token_from_form): bool {
    if (empty($_SESSION['csrf_token']) || empty($token_from_form)) {
        return false;
    }
    // Bandingkan dengan aman menggunakan hash_equals
    return hash_equals($_SESSION['csrf_token'], $token_from_form);
}

/**
 * Menghasilkan input field hidden untuk token CSRF.
 * @return string HTML untuk input hidden
 */
function csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
// ========== FUNGSI OPTIMASI GAMBAR (GD LIBRARY) ==========

/**
 * Mengoptimalkan gambar yang diunggah (resize & compress).
 * @param string $source_path Path ke file gambar asli (misal: $_FILES['tmp_name'])
 * @param string $destination_path Path tujuan untuk menyimpan gambar teroptimasi
 * @param int $max_width Lebar maksimum gambar (pixel)
 * @param int $quality Kualitas kompresi JPEG/WEBP (0-100)
 * @return bool True jika berhasil, False jika gagal.
 */
function optimize_image(string $source_path, string $destination_path, int $max_width = 1200, int $quality = 80): bool {
    $img_info = @getimagesize($source_path); // Use @ to suppress warning on invalid files
    if ($img_info === false) {
        error_log("Gagal membaca info gambar (getimagesize): " . $source_path);
        return false;
    }
    $mime = $img_info['mime'];
    $original_width = $img_info[0];
    $original_height = $img_info[1];

    $image_create_func = '';
    $image_save_func = '';
    $new_image_ext = ''; // Extension to use, might differ from original

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $image_create_func = 'imagecreatefromjpeg';
            $image_save_func = 'imagejpeg';
            $new_image_ext = 'jpg';
            break;
        case 'image/png':
            $image_create_func = 'imagecreatefrompng';
            $image_save_func = 'imagepng';
            // PNG quality is 0 (no compression) to 9 (max compression). Inverse of JPEG quality.
            $quality = round(9 - (($quality / 100) * 9));
            $new_image_ext = 'png';
            break;
        case 'image/gif':
            $image_create_func = 'imagecreatefromgif';
            $image_save_func = 'imagegif';
            $new_image_ext = 'gif'; // GIF doesn't have quality param for save func
            break;
        case 'image/webp':
             // Check if WebP support exists
             if (!function_exists('imagecreatefromwebp')) {
                 error_log("Fungsi WebP tidak tersedia di GD.");
                 return false;
             }
             $image_create_func = 'imagecreatefromwebp';
             $image_save_func = 'imagewebp';
             $new_image_ext = 'webp';
             break;
        default:
            error_log("Tipe gambar tidak didukung untuk optimasi: " . $mime);
            return false;
    }

    $image = @$image_create_func($source_path);
    if (!$image) {
         error_log("Gagal membuat gambar dari sumber (imagecreatefrom): " . $source_path . " Tipe: " . $mime);
         return false;
    }

    // Calculate new dimensions if resizing is needed
    $new_width = $original_width;
    $new_height = $original_height;
    if ($original_width > $max_width) {
        $ratio = $max_width / $original_width;
        $new_width = $max_width;
        $new_height = floor($original_height * $ratio); // Use floor to avoid fractions
    }

    // Create new true color image canvas
    $new_image = imagecreatetruecolor($new_width, $new_height);
    if (!$new_image){
        error_log("Gagal membuat kanvas gambar baru (imagecreatetruecolor).");
        imagedestroy($image);
        return false;
    }


    // Handle transparency for PNG and GIF
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagealphablending($new_image, false); // Turn off blending
        imagesavealpha($new_image, true);      // Enable saving alpha channel
        $transparent_index = imagecolorallocatealpha($new_image, 255, 255, 255, 127); // Fully transparent white
        imagefill($new_image, 0, 0, $transparent_index); // Fill the background
        // For GIF, find transparent color if exists
        if ($mime == 'image/gif') {
             $transparent_source_index = imagecolortransparent($image);
             if ($transparent_source_index >= 0) {
                 $transparent_color = imagecolorsforindex($image, $transparent_source_index);
                 $transparent_index = imagecolorallocatealpha($new_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue'], 127);
                 imagefill($new_image, 0, 0, $transparent_index);
                 imagecolortransparent($new_image, $transparent_index);
             }
        }
    }

    // Resize and copy image
    if (!imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height)) {
         error_log("Gagal menyalin dan resize gambar (imagecopyresampled).");
         imagedestroy($image);
         imagedestroy($new_image);
         return false;
    }


    // Adjust destination path extension if needed (e.g., if converting to WebP)
    // For now, we save with the standard extension based on MIME.
    // Ensure the destination filename uses the determined extension.
    $path_parts = pathinfo($destination_path);
    $final_destination_path = $path_parts['dirname'] . '/' . $path_parts['filename'] . '.' . $new_image_ext;


    // Save the optimized image
    $save_result = false;
    if ($mime == 'image/gif') {
        $save_result = $image_save_func($new_image, $final_destination_path); // GIF doesn't take quality
    } else {
        $save_result = $image_save_func($new_image, $final_destination_path, $quality);
    }

    if (!$save_result) {
         error_log("Gagal menyimpan gambar teroptimasi: " . $final_destination_path);
    }

    // Free memory
    imagedestroy($image);
    imagedestroy($new_image);

    return $save_result;
}
// ==========================================================

// ========== FUNGSI PENGECEKAN ROLE ==========

/**
 * Memeriksa apakah admin yang login memiliki peran tertentu.
 * @param string $required_role Peran yang dibutuhkan (gunakan konstanta ROLE_SUPERADMIN atau ROLE_EDITOR)
 * @return bool True jika memiliki akses, False jika tidak.
 */
function has_role(string $required_role): bool {
    // Pastikan session sudah dimulai dan role ada
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); // Mulai session jika belum
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_role'])) {
        return false; // Tidak login atau role tidak ada
    }

    // Superadmin memiliki akses ke SEMUA peran
    if ($_SESSION['admin_role'] === ROLE_SUPERADMIN) {
        return true;
    }

    // Cek jika peran pengguna cocok dengan peran yang dibutuhkan
    return $_SESSION['admin_role'] === $required_role;
}

/**
 * Menghentikan eksekusi dan mengalihkan jika pengguna tidak memiliki peran yang dibutuhkan.
 * Panggil fungsi ini di awal file admin yang aksesnya terbatas.
 * @param string $required_role Peran yang dibutuhkan.
 */
function require_role(string $required_role) {
    if (!has_role($required_role)) {
        // Set pesan error (opsional)
        $_SESSION['flash_message'] = 'Anda tidak memiliki izin untuk mengakses halaman ini.';
        $_SESSION['flash_message_type'] = 'danger';

        // Alihkan ke dashboard (atau halaman lain)
        global $admin_base; // Ambil base URL admin
         if (!isset($admin_base) && defined('BASE_URL')) { // Fallback jika $admin_base belum ada
             $admin_base = BASE_URL . '/admin';
         }
        header('Location: ' . ($admin_base ?? '/tsgi/admin') . '/dashboard.php');
        exit;
    }
}
// ==================================================
?>