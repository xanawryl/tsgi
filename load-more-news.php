<?php
// ========== AJAX Handler (load-more-news.php) ==========

// 1. Muat Konfigurasi (untuk $conn dan BASE_URL)
require_once 'config.php';

// 2. Ambil Parameter dari AJAX Request (jQuery akan mengirim via GET)
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$items_per_page = filter_input(INPUT_GET, 'items', FILTER_VALIDATE_INT);

// Validasi
if ($current_page === false || $current_page < 1 || $items_per_page === false || $items_per_page < 1) {
    // Kirim response error jika parameter tidak valid
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Parameter tidak valid.']);
    exit;
}

// 3. Hitung OFFSET
$offset = ($current_page - 1) * $items_per_page;

// 4. Siapkan Query SQL (dengan LIMIT dan OFFSET)
$sql = "SELECT id, slug, title, image_url, created_at
        FROM news
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Gagal menyiapkan query: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// 5. Siapkan Output HTML
$output_html = '';
$items_loaded = 0;

if ($result && $result->num_rows > 0) {
    $items_loaded = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        // Format data (sama seperti di news.php)
        $date = new DateTime($row['created_at']);
        $formatted_date = strtoupper($date->format('d M, Y'));
        $image_path = BASE_URL . '/assets/img/news/' . htmlspecialchars($row['image_url']);
        $detail_url = BASE_URL . '/news/' . htmlspecialchars($row['slug']);

        // Buat HTML untuk satu card
        $output_html .= '
        <div class="col-md-4 col-lg-3">
            <div class="card mb-4 shadow-sm h-100">
                <img src="' . $image_path . '" class="card-img-top" alt="' . htmlspecialchars($row['title']) . '">
                <div class="card-body d-flex flex-column">
                    <span class="badge bg-light text-dark mb-2 align-self-start">' . $formatted_date . '</span>
                    <h5 class="card-title" style="color:#222e23; font-size:18px; font-weight:700;">' . htmlspecialchars($row['title']) . '</h5>
                    <div class="d-flex align-items-center mb-2" style="color:#526b7b; font-size:13px;"><i class="bi bi-person-circle me-1"></i> ADMIN</div>
                    <a href="' . $detail_url . '" class="card-link text-decoration-none mt-auto" style="color:#EF1520; font-weight:500;">READ MORE <i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
        </div>';
    }
}

$stmt->close();
$conn->close();

// 6. Kirim Response sebagai JSON
// Kita kirim HTML-nya dan info apakah masih ada item lagi
header('Content-Type: application/json');
echo json_encode([
    'html' => $output_html,
    'items_loaded' => $items_loaded // Berapa banyak item yang berhasil dimuat kali ini
]);
exit;
?>