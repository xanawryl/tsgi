
    <!-- Banner Area -->
    <section class="banner-area heading-only video-bg-live text-light bg-fixed" id="home-banner">
      <!-- Overlay full/cover -->
      <div class="banner-overlay"></div>
      <div class="banner-content content" data-animation="animated fadeInUpBig">
        <div class="typed-text mb-1" style="color: #ffffff;">We are</div>
        <h2>THE SAKURA GREEN</h2>
        <h2 style="color: #b30000;">INDONESIA</h2>
        <span id="typed"></span>
        <div class="mt-4">
          <a href="#about-area" class="btn btn-custom btn-gold">ABOUT US</a>
          <a href="https://www.youtube.com/watch?v=jKVwu9AqBkI" class="btn btn-custom btn-red" target="_blank">WATCH VIDEO</a>
        </div>
      </div>
      <div id="video-bg-container" data-property="{videoURL:'https://www.youtube.com/watch?v=jKVwu9AqBkI',containment:'#home-banner', showControls:false, autoPlay:true, zoom:0, loop:true, mute:true, startAt:0, opacity:1, quality:'default'}"></div>
    </section>
    <!-- About Area -->
    <!-- About Area -->
    <section id="about-area" style="background: #1a1a2e;">
      <div class="container">
        <div class="row align-items-center">
          <!-- Foto kiri -->
          <div class="col-md-6 mb-4 mb-md-0">
            <img src="assets/img/tsgi.png" alt="About TSGI" class="img-fluid rounded shadow-sm" />
          </div>
          <!-- Teks kanan -->
		<div class="col-md-6 text-light">
            <h2 class="section-title" style="color:#FFD700;">About TSGI</h2>
            
            <?php echo get_setting_raw('home_about_summary', $conn, '<p>Deskripsi perusahaan belum diatur.</p>'); ?>
            </hr>
            <a href="<?php echo BASE_URL; ?>/about" class="btn btn-gold btn-custom mt-3">MORE</a>
        </div>
        </div>
      </div>
    </section>
    <!-- Portfolio Area -->
    <section id="portfolio-area">
      <div class="header">
        <p class="subtitle">Showcasing Recent and Selected Works</p>
        <h1 class="main-title">Portfolio</h1>
      </div>
      <div class="slider-container" id="sliderContainer">
		<div class="slider-track" id="sliderTrack">
            <?php
            // Ambil data ALBUM untuk portfolio slider
            if (isset($conn)) {
                
                // === PERBAIKAN QUERY ===
                // Mengambil data dari tabel 'gallery_albums'
                // Kita batasi 11 album (sesuai jumlah asli slider Anda)
                $sql_portfolio = "SELECT cover_image, title, description, slug 
                                  FROM gallery_albums 
                                  WHERE cover_image IS NOT NULL AND cover_image != '' 
                                  ORDER BY sort_order ASC 
                                  LIMIT 11";
                // === AKHIR PERBAIKAN ===
                                  
                $result_portfolio = $conn->query($sql_portfolio);

                if ($result_portfolio && $result_portfolio->num_rows > 0) {
                    
                    // Fungsi untuk memotong deskripsi (karena deskripsi album dari Summernote)
                    // Didefinisikan di sini agar tidak konflik
                    if (!function_exists('get_home_excerpt')) {
                        function get_home_excerpt($text, $length = 150) {
                            $text = strip_tags($text); // Hapus tag HTML
                            if (strlen($text) > $length) {
                                $text = substr($text, 0, $length);
                                $text = substr($text, 0, strrpos($text, ' '));
                                $text .= '...';
                            }
                            return $text;
                        }
                    }

                    $card_index = 1;
                    while ($album = $result_portfolio->fetch_assoc()) { // Loop data album
                        
                        // === PERBAIKAN PATH GAMBAR ===
                        // Arahkan ke folder /covers/
                        $image_path = BASE_URL . '/assets/img/gallery/covers/' . htmlspecialchars($album['cover_image']);
                        // === AKHIR PERBAIKAN ===
                        
                        $card_title = htmlspecialchars($album['title']);
                        // Ambil deskripsi singkat dari album
                        $card_desc = htmlspecialchars(get_home_excerpt($album['description'])); 
                        $alt_text = "Album Cover " . $card_index;
            ?>
                        <div class="card" data-title="<?php echo $card_title; ?>" data-desc="<?php echo $card_desc; ?>">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $alt_text; ?>">
                            <div class="hover-overlay"><span>Click to see more</span></div>
                        </div>
            <?php
                        $card_index++;
                    } // Akhir while loop
                } else {
                    echo '<p class="text-light text-center w-100">Belum ada album galeri yang bisa ditampilkan.</p>';
                }
            } else {
                echo '<p class="text-light text-center w-100">Database connection failed.</p>';
            }
            // Jangan tutup koneksi di sini
            ?>
        </div>
      </div>
      <button class="close-btn" id="closeBtn">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
        </svg>
      </button>
      <div class="card-info" id="cardInfo">
        <h2 id="cardTitle"></h2>
        <p id="cardDesc"></p>
      </div>
    </section>

    <!-- News Area -->
<section id="news-area">
    <div class="container">
        <h2 class="section-title">Latest News</h2>
        <div class="row g-4">
            
            <?php
            // Ambil 4 berita terbaru dari database
            // $conn sudah tersedia dari index.php
            
            $sql_news_home = "SELECT slug, title, image_url, created_at 
                              FROM news 
                              ORDER BY created_at DESC 
                              LIMIT 4"; // Kita hanya ambil 4
                              
            $result_news_home = $conn->query($sql_news_home);
            
            if ($result_news_home && $result_news_home->num_rows > 0):
                while ($row = $result_news_home->fetch_assoc()):
                    
                    $date = new DateTime($row['created_at']);
                    $formatted_date = strtoupper($date->format('d M, Y'));
                    $image_path = BASE_URL . '/assets/img/news/' . htmlspecialchars($row['image_url']);
                    $detail_url = BASE_URL . '/news/' . htmlspecialchars($row['slug']);
            ?>

                    <div class="col-md-3"> <div class="card mb-4 shadow-sm h-100">
                            <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-light text-dark mb-2 align-self-start">
                                    <?php echo $formatted_date; ?>
                                </span>
                                <h5 class="card-title" style="color:#222e23; font-size:18px; font-weight:700;">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h5>
                                <div class="d-flex align-items-center mb-2" style="color:#526b7b; font-size:13px;">
                                    <i class="bi bi-person-circle me-1"></i> ADMIN
                                </div>
                                <a href="<?php echo $detail_url; ?>" class="card-link text-decoration-none mt-auto" style="color:#EF1520; font-weight:500;">
                                    READ MORE <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

            <?php
                endwhile;
            else:
            ?>
                <div class="col-12 text-center text-dark">
                    <p>No news has been published yet.</p>
                </div>
            <?php
            endif;
            // Kita tidak perlu menutup koneksi di sini
            ?>

        </div> <div class="text-center mt-4">
             <a href="<?php echo BASE_URL; ?>/news" class="btn btn-red btn-custom">View All News</a>
        </div>
        
    </div>
</section>

<section id="mitra-area" class="bg-dark py-5"> <div class="container">
        <h2 class="section-title text-warning">Affiliation</h2> <?php
        // Ambil data partner dari database
        // $conn sudah tersedia dari index.php
        $partners_list = [];
        if (isset($conn)) {
            // Ambil 6 partner pertama, diurutkan
            $sql_partners_home = "SELECT partner_name, website_url, logo_url 
                                  FROM partners 
                                  ORDER BY sort_order ASC 
                                  LIMIT 6";
            $result_partners_home = $conn->query($sql_partners_home);
            if ($result_partners_home && $result_partners_home->num_rows > 0) {
                $partners_list = $result_partners_home->fetch_all(MYSQLI_ASSOC);
            }
        }
        ?>

        <?php if (!empty($partners_list)): ?>
            <div class="row text-center align-items-center g-4 justify-content-center">
                
                <?php foreach ($partners_list as $partner): 
                    $logo_path = BASE_URL . '/assets/img/partners/' . htmlspecialchars($partner['logo_url']);
                    $website_url = htmlspecialchars($partner['website_url']);
                ?>
                    <div class="col-lg-2 col-md-3 col-6">
                        <?php if (!empty($website_url)): // Jika ada URL, buat logonya bisa diklik ?>
                            <a href="<?php echo $website_url; ?>" target="_blank" rel="noopener noreferrer" title="<?php echo htmlspecialchars($partner['partner_name']); ?>">
                                <img src="<?php echo $logo_path; ?>" 
                                     alt="<?php echo htmlspecialchars($partner['partner_name']); ?> Logo" 
                                     class="img-fluid" 
                                     style="max-height: 60px; filter: transition: all 0.3s ease;">
                            </a>
                        <?php else: // Jika tidak ada URL, tampilkan sebagai gambar saja ?>
                            <img src="<?php echo $logo_path; ?>" 
                                 alt="<?php echo htmlspecialchars($partner['partner_name']); ?> Logo" 
                                 class="img-fluid" 
                                 style="max-height: 60px; ">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <p class="text-center text-light">Data afiliasi/mitra belum ditambahkan.</p>
        <?php endif; ?>
        
    </div>
</section>
	
