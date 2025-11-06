<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Utama</div>
                    <a class="nav-link" href="<?php echo $admin_base; ?>/dashboard.php">
                        <div class="sb-nav-link-icon"><i class="bi bi-speedometer2"></i></div>
                        Dashboard
                    </a>
                    
                    <div class="sb-sidenav-menu-heading">Konten</div>
                    <a class="nav-link" href="<?php echo $admin_base; ?>/pages/news/">
                        <div class="sb-nav-link-icon"><i class="bi bi-newspaper"></i></div>
                        Manage News
                    </a>
					<a class="nav-link" href="<?php echo $admin_base; ?>/pages/business/">
                        <div class="sb-nav-link-icon"><i class="bi bi-briefcase-fill"></i></div>
                        Manage Business
                    </a>
					<a class="nav-link" href="<?php echo $admin_base; ?>/pages/gallery/albums/">
                        <div class="sb-nav-link-icon"><i class="bi bi-collection-fill"></i></div>
                        Manage Gallery
                    </a>
					<a class="nav-link" href="<?php echo $admin_base; ?>/pages/members/">
                        <div class="sb-nav-link-icon"><i class="bi bi-person-video3"></i></div>
                        Manage Board Members
                    </a>
					<a class="nav-link" href="<?php echo $admin_base; ?>/pages/partners/">
                        <div class="sb-nav-link-icon"><i class="bi bi-person-lines-fill"></i></div>
                        Manage Partners
                    </a>
                    
					<div class="sb-sidenav-menu-heading">Admin</div>
                    <?php // Tampilkan hanya untuk Superadmin
                    if (has_role(ROLE_SUPERADMIN)): ?>
                    <a class="nav-link" href="<?php echo $admin_base; ?>/pages/users/">
                        <div class="sb-nav-link-icon"><i class="bi bi-people-fill"></i></div>
                        Manage Users
                    </a>
                    <a class="nav-link" href="<?php echo $admin_base; ?>/pages/site-settings.php">
                        <div class="sb-nav-link-icon"><i class="bi bi-gear-fill"></i></div>
                        Site Settings
                    </a>
					<a class="nav-link" href="<?php echo $admin_base; ?>/pages/menu/">
                    <div class="sb-nav-link-icon"><i class="bi bi-menu-button-wide-fill"></i></div>
                    Manage Menu
					</a>
                    <?php endif; // Akhir cek Superadmin ?>
                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Logged in as:</div>
                <?php echo htmlspecialchars($admin_username); ?>
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>