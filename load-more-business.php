<?php
// ========== AJAX Handler (load-more-business.php) ==========

require_once 'config.php';

$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$items_per_page = filter_input(INPUT_GET, 'items', FILTER_VALIDATE_INT);

if ($current_page === false || $current_page < 1 || $items_per_page === false || $items_per_page < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter tidak valid.']);
    exit;
}

$offset = ($current_page - 1) * $items_per_page;

$sql = "SELECT slug, title, description, thumbnail_url
        FROM business_items
        ORDER BY sort_order ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyiapkan query: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$output_html = '';
$items_loaded = 0;

/** @ignore */ // Fungsi helper excerpt (harus ada di sini juga)
function get_excerpt($text, $length = 100) {
    $text = strip_tags($text);
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}

if ($result && $result->num_rows > 0) {
    $items_loaded = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $title = htmlspecialchars($row['title']);
        $slug = htmlspecialchars($row['slug']);
        $excerpt = htmlspecialchars(get_excerpt($row['description']));
        $thumb_path = BASE_URL . '/assets/img/business/' . htmlspecialchars($row['thumbnail_url']);
        $detail_url = BASE_URL . '/business/' . $slug;

        // Buat HTML untuk satu card (PASTIKAN KELAS KOLOM SAMA: col-md-6 col-lg-3)
        $output_html .= '
        <div class="col-md-6 col-lg-3">
            <a href="' . $detail_url . '" class="text-decoration-none">
                <div class="card bg-secondary text-light p-3 h-100 shadow card-hover-effect">
                    <img src="' . $thumb_path . '"
                         class="card-img-top rounded mb-3"
                         alt="' . $title . '"
                         style="aspect-ratio: 16/9; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-warning">' . $title . '</h5>
                        <p class="card-text mb-4" style="font-size: 0.9rem;">' . $excerpt . '</p>
                        <span class="btn btn-gold btn-custom mt-auto align-self-start" style="max-width: 150px;">
                            Learn More <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>';
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'html' => $output_html,
    'items_loaded' => $items_loaded
]);
exit;
?>