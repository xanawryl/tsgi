<?php
// 1. Cek Auth (HARUS PALING ATAS)
require_once __DIR__ . '/../../../includes/auth-check.php';
// Editor boleh mengelola ini
require_role(ROLE_EDITOR);

// Set header JSON
header('Content-Type: application/json');

// Respon default (gagal)
$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

// 2. Validasi Request (POST, CSRF, Data)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $response['message'] = 'Token CSRF tidak valid.';
    } 
    // Validasi data yang dikirim AJAX
    elseif (!isset($_POST['order']) || !is_array($_POST['order']) || !isset($_POST['album_id'])) {
        $response['message'] = 'Data urutan atau ID album tidak lengkap.';
    } else {
        $imageOrder = $_POST['order'];
        $album_id = filter_var($_POST['album_id'], FILTER_VALIDATE_INT);

        if ($album_id === false) {
             $response['message'] = 'ID Album tidak valid.';
        } else {
            // 3. Update Database
            // Pastikan koneksi $conn ada
            if (!isset($conn) || !$conn instanceof mysqli || !isset($conn->thread_id) ) {
                 require_once __DIR__ . '/../../../../config.php';
            }

            $conn->begin_transaction();
            $all_ok = true;

            try {
                // Siapkan statement di luar loop
                $sql = "UPDATE gallery_images SET sort_order = ? WHERE id = ? AND album_id = ?"; // Tambahkan cek album_id
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                     throw new Exception("Gagal menyiapkan statement: " . $conn->error);
                }

                // Loop melalui array urutan baru
                foreach ($imageOrder as $index => $imageId) {
                    $imageId = filter_var($imageId, FILTER_VALIDATE_INT);
                    if ($imageId === false) {
                        throw new Exception("ID gambar tidak valid: " . $imageId);
                    }
                    $sortOrder = $index; // Urutan dimulai dari 0

                    // 'iii' = integer, integer, integer
                    $stmt->bind_param("iii", $sortOrder, $imageId, $album_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Gagal mengupdate gambar ID $imageId: " . $stmt->error);
                    }
                }
                $stmt->close();

                // Jika semua berhasil
                $conn->commit();
                $response = ['success' => true, 'message' => 'Urutan gambar berhasil disimpan.'];

            } catch (Exception $e) {
                $conn->rollback();
                $all_ok = false;
                $response['message'] = $e->getMessage();
                error_log("Gagal update urutan gambar: " . $e->getMessage()); // Log error
            }

            // Tutup koneksi
            if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
                $conn->close();
            }
        } // akhir else validasi album_id
    } // Akhir else validasi
} // Akhir if POST

// 4. Kirim Response JSON
echo json_encode($response);
exit;
?>