<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Top Floating Bar -->
<div class="mobile-top-bar">
    <div class="d-flex align-items-center gap-2">
        <div class="brand-icon-box" style="width:28px; height:28px; font-size:0.85rem;">G</div>
        <span class="fw-bold text-white" style="font-size: 0.95rem;">Gravitti Tech</span>
    </div>
    <button class="hamburger-toggle-btn" id="mobileHamburgerBtn" aria-label="Toggle Navigation">
        <i class="fa-solid fa-bars"></i>
    </button>
</div>

<!-- Backdrop Overlay -->
<div class="sidebar-backdrop-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation Container -->
<aside class="sidebar" id="appSidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="home.php" class="sidebar-brand">
            <div class="brand-icon-box">G</div>
            <span>Gravitti Tech</span>
        </a>
        <button class="hamburger-toggle-btn d-none d-lg-flex" id="desktopHamburgerBtn" title="Buka / Tutup Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Menu List -->
    <div class="sidebar-menu-body">
        <div class="sidebar-group-title">Utama</div>
        <a href="home.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-house-chimney-user nav-icon"></i> <span>Dashboard</span></span>
        </a>
        <a href="absensi.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-camera nav-icon"></i> <span>Absensi Online</span></span>
        </a>

        <div class="sidebar-group-title">Kehadiran & Cuti</div>
        <a href="riwayat-absen.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-list-check nav-icon"></i> <span>Riwayat Absensi</span></span>
        </a>
        <a href="kalender_kerja.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-calendar-check nav-icon"></i> <span>Kalender Kerja</span></span>
        </a>
        <a href="pengajuan-cuti.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-person-running nav-icon"></i> <span>Pengajuan Cuti</span></span>
        </a>
        <a href="grafik-kinerja.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-chart-line nav-icon"></i> <span>Statistik Kinerja</span></span>
        </a>

        <div class="sidebar-group-title">Keuangan & Profil</div>
        <a href="riwayat-gaji.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-file-invoice-dollar nav-icon"></i> <span>Slip Gaji</span></span>
        </a>
        <a href="profile.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-id-card-clip nav-icon"></i> <span>Profil Saya</span></span>
        </a>

        <a href="javascript:void(0)" onclick="window.triggerPWAInstall && window.triggerPWAInstall()" class="sidebar-nav-item text-primary font-weight-bold mt-2">
            <span><i class="fa-solid fa-mobile-screen-button nav-icon"></i> <span>Install App</span></span>
        </a>
        <a href="../logout.php" class="sidebar-nav-item text-danger mt-3">
            <span><i class="fa-solid fa-arrow-right-from-bracket nav-icon"></i> <span>Log Out</span></span>
        </a>
    </div>
</aside>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const desktopBtn = document.getElementById("desktopHamburgerBtn");
    const mobileBtn = document.getElementById("mobileHamburgerBtn");
    const overlay = document.getElementById("sidebarOverlay");
    const body = document.body;

    // Restore desktop collapsed state from localStorage
    if (localStorage.getItem("gravitti_sidebar_collapsed") === "true" && window.innerWidth >= 992) {
        body.classList.add("sidebar-collapsed");
    }

    if (desktopBtn) {
        desktopBtn.addEventListener("click", function() {
            body.classList.toggle("sidebar-collapsed");
            localStorage.setItem("gravitti_sidebar_collapsed", body.classList.contains("sidebar-collapsed"));
        });
    }

    if (mobileBtn) {
        mobileBtn.addEventListener("click", function() {
            body.classList.toggle("sidebar-mobile-open");
        });
    }

    if (overlay) {
        overlay.addEventListener("click", function() {
            body.classList.remove("sidebar-mobile-open");
        });
    }

    // Active Route Highlight
    const currentPath = "<?php echo $current_page_basename; ?>";
    document.querySelectorAll('.sidebar-menu-body a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});
</script>