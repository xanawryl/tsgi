<?php
// Set variabel default jika tidak ada
if (!isset($page_title)) {
    $page_title = 'Company Profile';
}
if (!isset($current_page)) {
    $current_page = 'home'; // Halaman default
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo htmlspecialchars($page_title); ?> | The Sakura Green Indonesia</title>
	<?php if (!empty($page_meta_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page_meta_description); ?>">
    <?php else: ?>
    <meta name="description" content="The Sakura Green Indonesia (TSGI) adalah perusahaan pengadaan dan penjualan PKS dan Palm Oil berkualitas internasional."> 
    <?php endif; ?>
    <link href="<?php echo BASE_URL; ?>/favicon.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>

    <?php 
    $navbar_class = ($current_page == 'home') ? 'navbar-transparent' : 'navbar-solid';
    ?>
    <nav id="main-navbar" class="navbar navbar-expand-lg navbar-fixed-top fixed-top <?php echo $navbar_class; ?>">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/">
                <span class="navbar-brand-logo">
                    <img src="<?php echo BASE_URL; ?>/assets/img/tsgi.png" alt="TSG Logo" style="width: 180px; max-width:100%; height:auto;display:block;">
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
			<ul class="navbar-nav ms-auto">
                    <?php
                    // Ambil menu items dari database
                    if (isset($conn)) {
                        $all_menu_items = [];
                        $sql_menu = "SELECT id, label, url, parent_id FROM menu_items ORDER BY parent_id ASC, sort_order ASC";
                        $result_menu = $conn->query($sql_menu);

                        if ($result_menu && $result_menu->num_rows > 0) {
                            // 1. Ambil semua item dan kelompokkan
                            $parent_items = [];
                            $child_items = [];
                            while ($item = $result_menu->fetch_assoc()) {
                                if ($item['parent_id'] == 0) {
                                    $parent_items[] = $item; // Item menu utama
                                } else {
                                    $child_items[$item['parent_id']][] = $item; // Item anak, dikelompokkan berdasarkan parent_id
                                }
                            }

                            // 2. Loop dan tampilkan menu utama (parent)
                            foreach ($parent_items as $parent) {
                                $label = htmlspecialchars($parent['label']);
                                $url = htmlspecialchars($parent['url']);
                                $parent_id = $parent['id'];
                                $has_children = isset($child_items[$parent_id]); // Cek apakah punya anak

                                // Tentukan 'active' state
                                $is_active = false;
                                $menu_slug = trim($url, '/');
                                // Khusus 'business' atau 'news', kita cek apakah halaman saat ini adalah 'business' ATAU 'business-detail'
                                $parent_slug_base = strtok($menu_slug, '/');
                                if (empty($menu_slug) && $current_page == 'home') { // Cek home
                                    $is_active = true;
                                } elseif (!empty($parent_slug_base) && strpos($current_page, $parent_slug_base) === 0) {
                                    // Cek jika $current_page diawali dengan $parent_slug_base
                                    // misal: $current_page 'business-detail' diawali 'business'
                                    $is_active = true;
                                }

                                // Jika punya anak, buat sebagai dropdown
                                if ($has_children) {
                                    echo '<li class="nav-item dropdown">';
                                    echo '<a class="nav-link dropdown-toggle ' . ($is_active ? 'active' : '') . '" href="' . $url . '" id="navbarDropdown' . $parent_id . '" role="button" data-bs-toggle="dropdown" aria-expanded="false">' . $label . '</a>';
                                    echo '<ul class="dropdown-menu" aria-labelledby="navbarDropdown' . $parent_id . '">';
                                    
                                    // Loop dan tampilkan anak-anaknya
                                    foreach ($child_items[$parent_id] as $child) {
                                        $child_label = htmlspecialchars($child['label']);
                                        $child_url = htmlspecialchars($child['url']);
                                        $target = (strpos($child_url, 'http') === 0) ? '_blank' : '_self'; // Buka link eksternal di tab baru
                                        
                                        // Cek 'active' state untuk anak (lebih spesifik)
                                        $is_child_active = false;
                                        if (isset($business_slug) && $child_url == (BASE_URL . '/business/' . $business_slug)) {
                                             $is_child_active = true;
                                        }

                                        echo '<li><a class="dropdown-item ' . ($is_child_active ? 'active' : '') . '" href="' . $child_url . '" target="' . $target . '">' . $child_label . '</a></li>';
                                    }
                                    
                                    echo '</ul></li>'; // Tutup dropdown
                                }
                                // Jika tidak punya anak, buat sebagai link biasa
                                else {
                                    echo '<li class="nav-item">';
                                    echo '<a class="nav-link ' . ($is_active ? 'active' : '') . '" href="' . BASE_URL . $url . '">' . $label . '</a>';
                                    echo '</li>';
                                }
                            } // Akhir foreach parent_items
                        } else {
                            echo '<li class="nav-item"><span class="nav-link text-warning">Menu belum diatur</span></li>';
                        }
                    } else {
                        echo '<li class="nav-item"><span class="nav-link text-danger">Koneksi DB Gagal</span></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($current_page != 'home'): ?>
    <main class="page-wrapper">
    <?php else: ?>
    <main>
    <?php endif; ?>