<?php
// ========== FORM HANDLER (includes/handle-contact-form.php) ==========

// Kita perlu memanggil config.php secara manual karena file ini
// dipanggil oleh index.php SEBELUM header.php
require_once __DIR__ . '/../config.php';

// Mulai session untuk menyimpan pesan status
session_start();

// Cek apakah data dikirim menggunakan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil dan Bersihkan Data Form
    // htmlspecialchars() untuk keamanan dasar (mencegah XSS)
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    // 2. Validasi Sederhana
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message)) {
        // Jika ada data yang tidak valid
        $_SESSION['form_status'] = 'error';
        $_SESSION['form_message'] = 'Harap isi semua kolom dengan benar.';
        header('Location: ' . BASE_URL . '/contact');
        exit;
    }

    // 3. Siapkan Email
    $to = "contact@awryl.my.id"; // GANTI DENGAN EMAIL TUJUAN ANDA
    $email_subject = "Pesan Website TSGI Baru: " . $subject;
    
    $email_body = "Anda telah menerima pesan baru dari formulir kontak website TSGI.\n\n";
    $email_body .= "Nama: $name\n";
    $email_body .= "Email: $email\n\n";
    $email_body .= "Pesan:\n$message\n";
    
    // Header email
    $headers = "From: " . $name . " <" . $email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // 4. Kirim Email
    // mail() akan gagal di XAMPP tapi akan Bekerja di cPanel
    if (mail($to, $email_subject, $email_body, $headers)) {
        // Jika berhasil
        $_SESSION['form_status'] = 'success';
        $_SESSION['form_message'] = 'Terima kasih! Pesan Anda telah terkirim.';
    } else {
        // Jika gagal (ini yang akan terjadi di XAMPP)
        $_SESSION['form_status'] = 'error';
        $_SESSION['form_message'] = 'Maaf, terjadi kesalahan. Silakan coba lagi nanti.';
        
        // CATATAN: Untuk tes di XAMPP agar seolah-olah berhasil,
        // beri komentar pada baris di atas dan hapus komentar pada baris di bawah ini:
        // $_SESSION['form_status'] = 'success';
        // $_SESSION['form_message'] = 'Terima kasih! Pesan Anda telah terkirim. (Mode Tes)';
    }

} else {
    // Jika file diakses langsung, bukan via POST
    $_SESSION['form_status'] = 'error';
    $_SESSION['form_message'] = 'Akses tidak valid.';
}

// 5. Alihkan kembali ke halaman kontak
header('Location: ' . BASE_URL . '/contact');
exit;

?>