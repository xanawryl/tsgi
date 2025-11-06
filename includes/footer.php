    </main> <footer class="footer">
        <div class="container">
            <div class="row align-items-center footer-main" style="display:flex;justify-content:space-between;">
<div class="col-md-4 mb-3">
            <div class="footer-logo">
              <img
                src="<?php echo BASE_URL; ?>/assets/img/tsgi.png"
                alt="TSG Logo"
                style="
                  width: 180px;
                  max-width: 100%;
                  height: auto;
                  display: block;
                "
              />
            </div>
</div>
                <div class="col-md-4 mb-3">
				<ul class="footer-menu text-center">
                    <?php
                    // Ambil menu items (hanya level atas, parent_id = 0)
                    if (isset($conn)) {
                        $sql_footer_menu = "SELECT label, url FROM menu_items WHERE parent_id = 0 ORDER BY sort_order ASC";
                        $result_footer_menu = $conn->query($sql_footer_menu);

                        if ($result_footer_menu && $result_footer_menu->num_rows > 0) {
                            while ($menu = $result_footer_menu->fetch_assoc()) {
                                $label = htmlspecialchars($menu['label']);
                                $url = htmlspecialchars($menu['url']);

                                // Logika Khusus:
                                // Jika URL adalah pemicu dropdown (seperti '#'),
                                // kita ubah agar link-nya ke halaman arsip utama.
                                if ($url == '#') {
                                    if (strtoupper($label) == 'BUSINESS') {
                                        $url = '/business'; // Arahkan 'BUSINESS' ke /business
                                    } elseif (strtoupper($label) == 'AFFILIATION') {
                                        continue; // Lewati (sembunyikan) 'AFFILIATION' dari footer
                                    }
                                }

                                // Tampilkan item menu
                                echo '<li><a href="' . BASE_URL . $url . '">' . $label . '</a></li>';
                            }
                        } else {
                            echo '<li><span class="text-warning">Menu belum diatur</span></li>';
                        }
                    }
                    ?>
                </ul>
                </div>
                <div class="col-md-4 mb-3">
    <p class="text-light text-end mb-0">PT. The Sakura Green Indonesia</p>
    <p class="text-light text-end mb-0">
        <?php echo get_setting('company_short_address', $conn, '[Alamat Singkat Belum Diatur]'); ?>
    </p>
                </div>
            </div>
            <div class="text-center mt-3" style="color:#FFD700;font-size:14px;"> © <?php echo date('Y'); ?> The Sakura Green Indonesia. All Rights Reserved. </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mb.YTPlayer/3.3.9/jquery.mb.YTPlayer.min.js"></script>

    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>

    <script>
        $(function () {
            // Cek jika elemen video ada sebelum memanggil YTPlayer
            if ($("#video-bg-container").length) {
                $("#video-bg-container").YTPlayer();
            }
        });
    </script>
<script>
    $(document).ready(function() {
        var loadMoreBtn = $('#load-more-business-btn');
        if(loadMoreBtn.length) { // Cek jika tombol ada
            loadMoreBtn.on('click', function() {
                var button = $(this);
                var nextPage = button.data('page');
                var itemsPerPage = button.data('items');
                var totalItems = button.data('total');
                var businessGrid = $('#business-grid'); // Target grid bisnis
                var spinner = button.find('.spinner-border');

                spinner.removeClass('d-none');
                button.prop('disabled', true);

                $.ajax({
                    url: '<?php echo BASE_URL; ?>/load-more-business.php', // Panggil handler bisnis
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        page: nextPage,
                        items: itemsPerPage
                    },
                    success: function(response) {
                        if (response.html && response.html.trim() !== '') {
                            businessGrid.append(response.html);
                            button.data('page', nextPage + 1);
                            // **PERBAIKAN HITUNGAN: Gunakan class kolom yang benar**
                            var currentTotalDisplayed = businessGrid.children('.col-lg-3').length;
                            if (currentTotalDisplayed >= totalItems) {
                                button.fadeOut();
                            }
                        } else {
                            button.fadeOut();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error (Business):", textStatus, errorThrown);
                        alert('Gagal memuat item bisnis. Silakan coba lagi.');
                    },
                    complete: function() {
                        spinner.addClass('d-none');
                        button.prop('disabled', false);
                    }
                });
            });
        } // Akhir if(loadMoreBtn.length)
    });
    </script>
<script>
    $(document).ready(function() {
        // Cek dulu apakah tombolnya ada di halaman ini
        var loadMoreBtn = $('#load-more-news-btn');
        if(loadMoreBtn.length) { 
            loadMoreBtn.on('click', function() {
                var button = $(this);
                var nextPage = button.data('page');
                var itemsPerPage = button.data('items');
                var totalItems = button.data('total');
                var newsGrid = $('#news-grid');
                var spinner = button.find('.spinner-border');

                spinner.removeClass('d-none');
                button.prop('disabled', true);

                $.ajax({
                    url: '<?php echo BASE_URL; ?>/load-more-news.php', 
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        page: nextPage,
                        items: itemsPerPage
                    },
                    success: function(response) {
                        if (response.html && response.html.trim() !== '') {
                            newsGrid.append(response.html);
                            button.data('page', nextPage + 1);
                            var currentTotalDisplayed = newsGrid.children('.col-md-4').length;
                            if (currentTotalDisplayed >= totalItems) {
                                button.fadeOut();
                            }
                        } else {
                            button.fadeOut();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        alert('Gagal memuat berita. Silakan coba lagi.');
                    },
                    complete: function() {
                        spinner.addClass('d-none');
                        button.prop('disabled', false);
                    }
                });
            });
        } // Akhir if(loadMoreBtn.length)
    });
    </script>	
</body>
</html>