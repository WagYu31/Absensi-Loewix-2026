<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
$nip_session = $_SESSION['nip'] ?? '';
?>

<!-- Link Mobile Bottom Navigation CSS -->
<link rel="stylesheet" href="../assets/css/bottom-nav.css?v=<?php echo time(); ?>">

<!-- Unified 5-Item Mobile Bottom Navigation Bar -->
<div class="custom-mobile-nav-wrapper d-lg-none">
    <nav class="custom-mobile-nav">
        <a href="home.php" class="custom-nav__link <?php echo ($current_page_basename == 'home.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house-chimney-user"></i>
            <span class="custom-nav__text">Home</span>
        </a>

        <a href="riwayat-absen.php" class="custom-nav__link <?php echo ($current_page_basename == 'riwayat-absen.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock"></i>
            <span class="custom-nav__text">Absen</span>
        </a>

        <!-- Center Floating FAB Absen Camera Button (Points to Gambar 2: Presensi Online / absensi.php) -->
        <a href="absensi.php" class="custom-nav__fab-container text-decoration-none">
            <div class="custom-nav__fab-button <?php echo ($current_page_basename == 'absensi.php') ? 'active' : ''; ?>" title="Presensi Online">
                <i class="fa-solid fa-camera"></i>
            </div>
            <span class="custom-nav__fab-text <?php echo ($current_page_basename == 'absensi.php') ? 'active' : ''; ?>">Presensi</span>
        </a>

        <a href="riwayat-gaji.php" class="custom-nav__link <?php echo ($current_page_basename == 'riwayat-gaji.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span class="custom-nav__text">Gaji</span>
        </a>

        <a href="profile.php" class="custom-nav__link <?php echo ($current_page_basename == 'profile.php' || $current_page_basename == 'edit-profile.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-id-card-clip"></i>
            <span class="custom-nav__text">Profil</span>
        </a>
    </nav>
</div>
<script src="../assets/js/pwa-install.js?v=<?php echo time(); ?>"></script>