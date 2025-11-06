<?php
// ========== HALAMAN LOGIN ADMIN (/admin/index.php) ==========

// 1. Mulai Session
// (Harus ada di baris paling atas)
session_start();

// 2. Muat Konfigurasi Utama
// (Kita perlu BASE_URL dari file config.php di folder root)
require_once __DIR__ . '/../config.php';

// 3. Tentukan Base URL untuk Admin
// (Ini untuk mempermudah link ke CSS dan JS admin)
$admin_base = BASE_URL . '/admin';

// 4. Cek Jika Sudah Login
// Jika admin sudah login, lempar langsung ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ' . $admin_base . '/dashboard.php');
    exit;
}

// 5. Siapkan Pesan Error (jika ada)
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') {
        $error_message = 'Username atau password salah.';
    } elseif ($_GET['error'] == '2') {
        $error_message = 'Anda harus login terlebih dahulu.';
    } elseif ($_GET['error'] == '3') { // <-- TAMBAHKAN INI
        $error_message = 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Admin Login - TSGI</title>
    
    <link href="<?php echo $admin_base; ?>/css/styles.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        /* Sedikit kustomisasi agar pas dengan tema Anda */
        body {
            background-color: #222e23; /* Latar belakang gelap */
        }
        .card-header {
            background-color: #f8f9fa; /* Header kartu terang */
        }
        .btn-gold {
            background: #FFD700;
            color: #000000;
            border: none;
        }
        .btn-gold:hover {
            background: #e6c300;
            color: #000000;
        }
    </style>
</head>
<body>
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">
                                        Admin Login TSGI
                                    </h3>
                                </div>
                                <div class="card-body">
                                    
                                    <form action="login-process.php" method="POST">
                                        
                                        <?php
                                        // Tampilkan pesan error jika ada
                                        if (!empty($error_message)):
                                        ?>
                                            <div class="alert alert-danger">
                                                <?php echo $error_message; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="username" name="username" type="text" placeholder="username" required />
                                            <label for="username">Username</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="password" name="password" type="password" placeholder="Password" required />
                                            <label for="password">Password</label>
                                        </div>
                                        
                                        <div class="d-grid mt-4 mb-0">
                                            <button type="submit" class="btn btn-gold btn-lg">Login</button>
                                        </div>
                                    </form>
                                    
                                </div>
                                <div class="card-footer text-center py-3">
                                    <div class="small">
                                        <a href="<?php echo BASE_URL; ?>/" class="text-dark">
                                            <i class="bi bi-arrow-left-circle"></i> Kembali ke Website Utama
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="<?php echo $admin_base; ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $admin_base; ?>/js/scripts.js"></script>
</body>
</html>