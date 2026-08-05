<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

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
    --sb-mini-width: 70px;
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
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.2) !important;
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
    width: 34px !important;
    height: 34px !important;
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
    background: rgba(255, 255, 255, 0.18) !important;
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
    transition: transform 0.2s ease !important;
}

.sidebar-nav-item .chevron-arrow {
    font-size: 0.75rem !important;
    transition: transform 0.25s ease !important;
    margin-right: 0 !important;
    width: auto !important;
}

/* Submenu Links */
.sidebar .collapse a,
.sidebar-submenu a {
    padding-left: 46px !important;
    font-size: 0.84rem !important;
    color: #9ca3af !important;
    background-color: transparent !important;
}

.sidebar .collapse a:hover,
.sidebar-submenu a:hover {
    background-color: rgba(255, 255, 255, 0.04) !important;
    color: #ffffff !important;
}

.sidebar .collapse a.active,
.sidebar-submenu a.active {
    background-color: rgba(59, 130, 246, 0.12) !important;
    color: #60a5fa !important;
}

/* Desktop Collapsed State */
body.sidebar-collapsed .sidebar {
    width: var(--sb-mini-width) !important;
}

body.sidebar-collapsed .sidebar-brand span,
body.sidebar-collapsed .sidebar-group-title,
body.sidebar-collapsed .sidebar-nav-item span:not(.nav-icon),
body.sidebar-collapsed .chevron-arrow,
body.sidebar-collapsed .sidebar-submenu,
body.sidebar-collapsed .sidebar .collapse {
    display: none !important;
}

body.sidebar-collapsed .sidebar-nav-item,
body.sidebar-collapsed .sidebar a {
    justify-content: center !important;
    padding: 12px 0 !important;
}

body.sidebar-collapsed .sidebar-nav-item .nav-icon,
body.sidebar-collapsed .sidebar a i {
    margin-right: 0 !important;
    font-size: 1.2rem !important;
}

body.sidebar-collapsed .main-content-wrapper {
    margin-left: var(--sb-mini-width) !important;
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
    justify-content: space-between !important;
    padding: 0 16px !important;
    z-index: 1020 !important;
    border-bottom: 1px solid var(--sb-border) !important;
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