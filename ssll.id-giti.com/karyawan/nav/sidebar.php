<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!-- Link Mobile Bottom Navigation CSS -->
<link rel="stylesheet" href="../assets/css/bottom-nav.css?v=<?php echo time(); ?>">

<style>
/* ==========================================
   ULTRA-SLEEK MODERN SAAS SIDEBAR & HAMBURGER
   ========================================== */
:root {
    --sb-bg: #111827;
    --sb-border: rgba(255, 255, 255, 0.08);
    --sb-text: #9ca3af;
    --sb-text-hover: #ffffff;
    --sb-active-bg: rgba(59, 130, 246, 0.15);
    --sb-active-color: #60a5fa;
    --sb-width: 250px;
}

/* Base Reset & Lock for Sidebar Layout */
.sidebar,
#appSidebar {
    height: 100vh !important;
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: var(--sb-width) !important;
    background-color: var(--sb-bg) !important;
    color: #f3f4f6 !important;
    z-index: 1035 !important;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.25) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow-x: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Header */
.sidebar-header {
    height: 65px !important;
    padding: 0 18px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    border-bottom: 1px solid var(--sb-border) !important;
    flex-shrink: 0 !important;
    background: transparent !important;
}

.sidebar-brand {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    text-decoration: none !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 1.05rem !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    padding: 0 !important;
    background: transparent !important;
}

.brand-icon-box {
    width: 34px !important;
    height: 34px !important;
    border-radius: 9px !important;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1rem !important;
    font-weight: 800 !important;
    box-shadow: 0 3px 10px rgba(59, 130, 246, 0.35) !important;
    flex-shrink: 0 !important;
}

.hamburger-toggle-btn {
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    color: #9ca3af !important;
    width: 36px !important;
    height: 36px !important;
    border-radius: 8px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    outline: none !important;
    flex-shrink: 0 !important;
}

.hamburger-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
}

/* Sidebar Menu Body */
.sidebar-menu-body {
    flex: 1 !important;
    padding: 15px 10px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

.sidebar-group-title {
    font-size: 0.68rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 1px !important;
    color: #6b7280 !important;
    padding: 14px 12px 6px !important;
    white-space: nowrap !important;
}

/* Sidebar Links */
.sidebar a,
.sidebar-menu a,
.sidebar-nav-item {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    color: var(--sb-text) !important;
    text-decoration: none !important;
    padding: 10px 12px !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    margin-bottom: 3px !important;
    transition: all 0.2s ease !important;
    border-left: none !important;
    background: transparent !important;
    box-sizing: border-box !important;
    cursor: pointer !important;
}

.sidebar a:hover,
.sidebar-menu a:hover,
.sidebar-nav-item:hover {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.05) !important;
}

.sidebar a.active,
.sidebar-menu a.active,
.sidebar-nav-item.active {
    color: var(--sb-active-color) !important;
    background-color: var(--sb-active-bg) !important;
    font-weight: 600 !important;
}

.sidebar-nav-item .nav-icon,
.sidebar a i,
.sidebar-menu i {
    width: 22px !important;
    text-align: center !important;
    font-size: 1.05rem !important;
    margin-right: 12px !important;
    flex-shrink: 0 !important;
}

div.sidebar-logo-frame,
.sidebar-logo-frame {
    background-color: #ffffff !important;
    background: #ffffff !important;
    padding: 6px 14px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
    border: none !important;
    transition: transform 0.2s ease !important;
}

.sidebar-logo-frame:hover {
    transform: scale(1.03) !important;
}

.brand-logo-img {
    height: 26px !important;
    width: auto !important;
    max-width: 130px !important;
    object-fit: contain !important;
}

/* Floating Hamburger Button (Appears when sidebar is closed) */
.floating-hamburger-btn {
    position: fixed !important;
    top: 15px !important;
    left: 18px !important;
    z-index: 1040 !important;
    background: #0f172a !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    width: 40px !important;
    height: 40px !important;
    border-radius: 10px !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25) !important;
    transition: all 0.2s ease !important;
}

.floating-hamburger-btn:hover {
    background: #1e293b !important;
    transform: scale(1.05) !important;
}

/* Desktop Collapsed State */
body.sidebar-collapsed .sidebar,
body.sidebar-collapsed #appSidebar {
    transform: translateX(-100%) !important;
}

body.sidebar-collapsed .main-content-wrapper {
    margin-left: 0 !important;
}

body.sidebar-collapsed .floating-hamburger-btn {
    display: flex !important;
}

@media (min-width: 992px) {
    .main-content-wrapper {
        margin-left: var(--sb-width) !important;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
}

@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%) !important;
    }
    body.sidebar-mobile-open .sidebar {
        transform: translateX(0) !important;
    }
    .main-content-wrapper {
        margin-left: 0 !important;
        padding-top: 54px !important;
    }
}

.mobile-top-bar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 54px !important;
    background: rgba(17, 24, 39, 0.95) !important;
    backdrop-filter: blur(10px) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 16px !important;
    z-index: 1020 !important;
    border-bottom: 1px solid var(--sb-border) !important;
}

.mobile-top-bar #mobileHamburgerBtn {
    position: absolute !important;
    right: 16px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
}

@media (min-width: 992px) {
    .mobile-top-bar {
        display: none !important;
    }
}

.sidebar-backdrop-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(3px) !important;
    z-index: 1025 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: all 0.3s ease !important;
}

body.sidebar-mobile-open .sidebar-backdrop-overlay {
    opacity: 1 !important;
    visibility: visible !important;
}
</style>

<!-- Floating Hamburger Button (Appears when sidebar is closed) -->
<button class="floating-hamburger-btn" id="floatingOpenBtn" title="Buka Sidebar Navigasi">
    <i class="fa-solid fa-bars"></i>
</button>

<!-- Mobile Top Floating Bar -->
<div class="mobile-top-bar">
    <a href="home.php" class="d-inline-flex align-items-center text-decoration-none" style="background: transparent !important; padding: 0 !important; border: none !important;">
        <div class="sidebar-logo-frame">
            <img src="../img/giti.png" alt="Gravitti Tech Logo" class="brand-logo-img" onerror="this.style.display='none'; document.getElementById('mob-brand-text').style.display='inline-block';">
            <span class="fw-bold text-dark" id="mob-brand-text" style="font-size: 0.95rem; display: none;">Gravitti Tech</span>
        </div>
    </a>
    <button class="hamburger-toggle-btn" id="mobileHamburgerBtn" aria-label="Toggle Navigation">
        <i class="fa-solid fa-bars"></i>
    </button>
</div>

<!-- Backdrop Overlay -->
<div class="sidebar-backdrop-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation Container (Desktop & Mobile Drawer) -->
<aside class="sidebar" id="appSidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="home.php" class="d-inline-flex align-items-center text-decoration-none" style="background: transparent !important; padding: 0 !important; border: none !important;">
            <div class="sidebar-logo-frame">
                <img src="../img/giti.png" alt="Gravitti Tech Logo" class="brand-logo-img" onerror="this.style.display='none'; document.getElementById('sb-brand-text').style.display='inline-block';">
                <span class="fw-bold text-dark" id="sb-brand-text" style="font-size: 1.05rem; display: none;">Gravitti Tech</span>
            </div>
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

<!-- Custom Mobile Bottom Navigation Bar (Appears on Mobile screens <992px) -->
<div class="custom-mobile-nav-wrapper d-lg-none">
    <nav class="custom-mobile-nav">
        <a href="home.php" class="custom-nav__link <?php echo ($current_page_basename == 'home.php') ? 'active' : ''; ?>">
            <div class="custom-nav__icon-box"><i class="fa-solid fa-house-chimney-user"></i></div>
            <span class="custom-nav__text">Home</span>
        </a>

        <a href="riwayat-absen.php" class="custom-nav__link <?php echo ($current_page_basename == 'riwayat-absen.php') ? 'active' : ''; ?>">
            <div class="custom-nav__icon-box"><i class="fa-solid fa-clock"></i></div>
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
            <div class="custom-nav__icon-box"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <span class="custom-nav__text">Gaji</span>
        </a>

        <a href="profile.php" class="custom-nav__link <?php echo ($current_page_basename == 'profile.php' || $current_page_basename == 'edit-profile.php') ? 'active' : ''; ?>">
            <div class="custom-nav__icon-box"><i class="fa-solid fa-id-card-clip"></i></div>
            <span class="custom-nav__text">Profil</span>
        </a>
    </nav>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const desktopBtn = document.getElementById("desktopHamburgerBtn");
    const floatingBtn = document.getElementById("floatingOpenBtn");
    const mobileBtn = document.getElementById("mobileHamburgerBtn");
    const overlay = document.getElementById("sidebarOverlay");
    const body = document.body;

    // Toggle Desktop Sidebar Open/Close Completely
    function toggleSidebar() {
        body.classList.toggle("sidebar-collapsed");
        localStorage.setItem("gravitti_sidebar_collapsed", body.classList.contains("sidebar-collapsed"));
    }

    if (desktopBtn) {
        desktopBtn.addEventListener("click", toggleSidebar);
    }

    if (floatingBtn) {
        floatingBtn.addEventListener("click", toggleSidebar);
    }

    // Toggle Mobile Drawer
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

    // Active Route Highlight for Sidebar & Mobile Bottom Nav
    const currentPath = "<?php echo $current_page_basename; ?>";
    document.querySelectorAll('.sidebar-menu-body a, .custom-mobile-nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});
</script>