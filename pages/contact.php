<section id="contact-main" class="py-5">
    <div class="container">
        <h2 class="section-title bg-dark py-5">Contact Us</h2>
        <p class="text-center text-dark" style="max-width: 600px; margin: 0 auto 40px auto;">
            Kami siap membantu Anda. Silakan hubungi kami melalui informasi di
            bawah ini atau kirimkan pesan melalui formulir.
        </p>

        <div class="row g-5">
<div class="col-lg-5">
    <div class="card bg-secondary text-light p-4 shadow h-100">
        <h4 class="text-warning mb-4">Get in Touch</h4>

        <div class="d-flex mb-3">
            <i class="bi bi-geo-alt-fill text-warning fs-4 me-3"></i>
            <div>
                <strong>Alamat:</strong>
                <p class="mb-0">
                    <?php echo get_setting('contact_address', $conn, '[Alamat belum diatur]'); ?>
                </p>
            </div>
        </div>

        <div class="d-flex mb-3">
            <i class="bi bi-envelope-fill text-warning fs-4 me-3"></i>
            <div>
                <strong>Email:</strong>
                <p class="mb-0"><?php echo get_setting('contact_email', $conn, '[Email belum diatur]'); ?></p>
            </div>
        </div>

        <div class="d-flex mb-3">
            <i class="bi bi-telephone-fill text-warning fs-4 me-3"></i>
            <div>
                <strong>Telepon:</strong>
                <p class="mb-0"><?php echo get_setting('contact_phone', $conn, '[Telepon belum diatur]'); ?></p>
            </div>
        </div>

        <div class="d-flex mb-3">
            <i class="bi bi-clock-fill text-warning fs-4 me-3"></i>
            <div>
                <strong>Jam Kerja:</strong>
                <p class="mb-0"><?php echo get_setting('office_hours', $conn, '[Jam kerja belum diatur]'); ?></p>
            </div>
        </div>
    </div>
</div>

			<div class="col-lg-7">
                <h4 class="text-warning mb-4">Send Us a Message</h4>

                <?php
                // ===== AREA TAMPIL PESAN STATUS (BARU) =====
                // Cek apakah ada pesan status di session
                if (isset($_SESSION['form_status']) && isset($_SESSION['form_message'])):
                    
                    // Tentukan warna alert berdasarkan status
                    $alert_class = ($_SESSION['form_status'] == 'success') ? 'alert-success' : 'alert-danger';
                ?>
                    
                    <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['form_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                
                <?php
                    // Hapus session setelah ditampilkan agar tidak muncul lagi
                    unset($_SESSION['form_status']);
                    unset($_SESSION['form_message']);
                endif;
                // ===== AKHIR AREA TAMPIL PESAN =====
                ?>

                <form action="<?php echo BASE_URL; ?>/contact-submit" method="POST" class="contact-form">
                    <div class="row g-3">
                    <div class="col-md-6">
                            <label for="form_name" class="form-label text-dark">Nama Anda</label>
                            <input id="form_name" type="text" name="name" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label for="form_email" class="form-label text-dark">Email Anda</label>
                            <input id="form_email" type="email" name="email" class="form-control" required />
                        </div>
                        <div class="col-12">
                            <label for="form_subject" class="form-label text-dark">Subjek</label>
                            <input id="form_subject" type="text" name="subject" class="form-control" required />
                        </div>
                        <div class="col-12">
                            <label for="form_message" class="form-label text-dark">Pesan</label>
                            <textarea id="form_message" name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-gold btn-custom mt-3">
                                Kirim Pesan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section id="contact-map" class="p-0">
    <iframe
        src="<?php echo get_setting('google_maps_embed_url', $conn, 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126917.60565876759!2d106.6894307!3d-6.2297284!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3db8e1cf4bb%3A0xbfdd41f2619b25c1!2sJakarta%2C%20Indonesia!5e0!3m2!1sen!2sid!4v1697187150758!5m2!1sen!2sid'); // URL Default jika kosong ?>"
        width="100%"
        height="450"
        style="border: 0"
        allowfullscreen
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade" class="footer-maps"
    ></iframe>
</section>