<?php
// ========== PROSES LOGIN ADMIN (/admin/login-process.php) ==========

// 1. Mulai Session
session_start();

// 2. Muat Konfigurasi Utama (untuk $conn dan BASE_URL)
require_once __DIR__ . '/../config.php';

// 3. Tentukan Base URL Admin
$admin_base = BASE_URL . '/admin';

// 4. Cek apakah ini metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. Ambil data dari form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 6. Siapkan Query (Gunakan Prepared Statements untuk Keamanan!)
    $sql = "SELECT id, username, password, full_name, role 
            FROM admin_users 
            WHERE username = ? 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        // Gagal menyiapkan query
        die("Error preparing statement: " . $conn->error);
    }
    
    // 's' berarti parameter adalah string
    $stmt->bind_param("s", $username);
    
    // Eksekusi query
    $stmt->execute();
    
    // Ambil hasilnya
    $result = $stmt->get_result();

    // 7. Verifikasi Data
    if ($result && $result->num_rows == 1) {
        // Username ditemukan, ambil datanya
        $admin = $result->fetch_assoc();
		
        // Verifikasi password yang di-hash
        if (password_verify($password, $admin['password'])) {
            // == LOGIN BERHASIL ==
            
            // 1. Regenerasi ID session (keamanan)
            session_regenerate_id(true);
            
            // 2. Set variabel Session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_full_name'] = $admin['full_name'];
			$_SESSION['admin_role'] = $admin['role'];
            
            // 3. Alihkan ke Dashboard
            header('Location: ' . $admin_base . '/dashboard.php');
            exit;
            
        } else {
            // Password salah
            header('Location: ' . $admin_base . '/index.php?error=1');
            exit;
        }
        
    } else {
        // Username tidak ditemukan
        header('Location: ' . $admin_base . '/index.php?error=1');
        exit;
    }

    $stmt->close();
    $conn->close();

} else {
    // Jika file diakses langsung (bukan via POST)
    // Lempar kembali ke halaman login
    header('Location: ' . $admin_base . '/index.php');
    exit;
}
?>