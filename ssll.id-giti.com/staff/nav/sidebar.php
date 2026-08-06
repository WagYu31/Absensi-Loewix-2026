<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'guest';
?>

<style>
/* ==========================================
   ULTRA-SLEEK FULL TOGGLE SIDEBAR (OPEN/CLOSE)
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

.nav-icon {
    width: 22px !important;
    text-align: center !important;
    font-size: 1.05rem !important;
    margin-right: 12px !important;
    flex-shrink: 0 !important;
}

.chevron-arrow {
    font-size: 0.75rem !important;
    transition: transform 0.25s ease !important;
}

.sidebar-nav-item[aria-expanded="true"] .chevron-arrow {
    transform: rotate(180deg) !important;
}

/* Submenu Links */
.sidebar .collapse a,
.sidebar-submenu a {
    padding-left: 32px !important;
    font-size: 0.84rem !important;
    color: #9ca3af !important;
    background-color: transparent !important;
    justify-content: flex-start !important;
    display: flex !important;
    align-items: center !important;
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

.sub-icon {
    font-size: 0.85rem !important;
    width: 20px !important;
    text-align: center !important;
    margin-right: 8px !important;
    opacity: 0.85;
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

/* Floating Hamburger Button when Sidebar Closed */
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

/* Desktop Collapsed State -> Completely Hide Sidebar (-100%) & Expand Main Content (100%) */
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
    height: 56px !important;
    background: rgba(15, 23, 42, 0.95) !important;
    backdrop-filter: blur(16px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 0 16px !important;
    z-index: 1020 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
}

.mobile-top-bar #mobileHamburgerBtn {
    position: relative !important;
    right: auto !important;
    top: auto !important;
    transform: none !important;
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
    width: 40px !important;
    height: 40px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.1rem !important;
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.mobile-top-bar #mobileHamburgerBtn:active {
    transform: scale(0.92) !important;
    background: rgba(37, 99, 235, 0.4) !important;
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
    <a href="grafik-kinerja.php" class="d-inline-flex align-items-center text-decoration-none" style="background: transparent !important; padding: 0 !important; border: none !important;">
        <div class="sidebar-logo-frame">
            <img src="../img/giti.png" alt="Gravitti Tech Logo" class="brand-logo-img" onerror="this.style.display='none'; document.getElementById('mob-staff-brand-text').style.display='inline-block';">
            <span class="fw-bold text-dark" id="mob-staff-brand-text" style="font-size: 0.95rem; display: none;">Gravitti Tech</span>
        </div>
    </a>
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
        <a href="grafik-kinerja.php" class="d-inline-flex align-items-center text-decoration-none" style="background: transparent !important; padding: 0 !important; border: none !important;">
            <div class="sidebar-logo-frame">
                <img src="../img/giti.png" alt="Gravitti Tech Logo" class="brand-logo-img" onerror="this.style.display='none'; document.getElementById('sb-staff-brand-text').style.display='inline-block';">
                <span class="brand-text text-dark" id="sb-staff-brand-text" style="display: none;">Gravitti Tech</span>
            </div>
        </a>
        <button class="hamburger-toggle-btn d-none d-lg-flex" id="desktopHamburgerBtn" title="Tutup Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Menu List -->
    <div class="sidebar-menu-body">
        <div class="sidebar-group-title">Utama</div>
        <a href="grafik-kinerja.php" class="sidebar-nav-item">
            <span class="d-flex align-items-center">
                <i class="fa-solid fa-chart-pie nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </span>
        </a>

        <div class="sidebar-group-title">Manajemen</div>
        <a class="sidebar-nav-item" data-bs-toggle="collapse" href="#menuKepegawaian" role="button" aria-expanded="false">
            <span class="d-flex align-items-center">
                <i class="fa-solid fa-users nav-icon"></i>
                <span class="nav-text">Kepegawaian</span>
            </span>
            <i class="fa-solid fa-chevron-down chevron-arrow"></i>
        </a>
        <div class="collapse sidebar-submenu" id="menuKepegawaian">
            <a href="data-karyawan.php"><i class="fa-solid fa-house-chimney-user sub-icon"></i><span class="nav-text">Data Karyawan</span></a>
            <a href="kalender_kerja.php"><i class="fa-solid fa-calendar-days sub-icon"></i><span class="nav-text">Kalender Kerja</span></a>
        </div>

        <a class="sidebar-nav-item" data-bs-toggle="collapse" href="#menuKehadiran" role="button" aria-expanded="false">
            <span class="d-flex align-items-center">
                <i class="fa-solid fa-clock nav-icon"></i>
                <span class="nav-text">Kehadiran</span>
            </span>
            <i class="fa-solid fa-chevron-down chevron-arrow"></i>
        </a>
        <div class="collapse sidebar-submenu" id="menuKehadiran">
            <a href="absen.php"><i class="fa-solid fa-id-card-clip sub-icon"></i><span class="nav-text">Data Absensi</span></a>
            <a href="data-absensi.php"><i class="fa-solid fa-camera sub-icon"></i><span class="nav-text">Validasi Absen Manual</span></a>
            <a href="shifting.php"><i class="fa-solid fa-person-running sub-icon"></i><span class="nav-text">Jadwal Shifting</span></a>
            <a href="kelola_jatah_cuti.php"><i class="fa-solid fa-folder-open sub-icon"></i><span class="nav-text">Kelola Jatah Cuti</span></a>
            <a href="cuti-karyawan.php"><i class="fa-solid fa-calendar-check sub-icon"></i><span class="nav-text">Pengajuan Cuti</span></a>
        </div>

        <a class="sidebar-nav-item" data-bs-toggle="collapse" href="#menuKeuangan" role="button" aria-expanded="false">
            <span class="d-flex align-items-center">
                <i class="fa-solid fa-wallet nav-icon"></i>
                <span class="nav-text">Keuangan</span>
            </span>
            <i class="fa-solid fa-chevron-down chevron-arrow"></i>
        </a>
        <div class="collapse sidebar-submenu" id="menuKeuangan">
            <a href="tunjangan-karyawan.php"><i class="fa-solid fa-money-bill-trend-up sub-icon"></i><span class="nav-text">Biaya Pengganti</span></a>
            <a href="denda.php"><i class="fa-solid fa-hand-scissors sub-icon"></i><span class="nav-text">Denda Karyawan</span></a>
            <?php if ($user_role === 'superadmin'): ?>
            <a href="cashbon.php"><i class="fa-solid fa-money-bill-transfer sub-icon"></i><span class="nav-text">Cashbon</span></a>
            <a href="penggajian.php"><i class="fa-solid fa-money-bill-wave sub-icon"></i><span class="nav-text">Penggajian</span></a>
            <?php endif; ?>
        </div>

        <div class="sidebar-group-title">Pengaturan</div>
        <a href="profile.php" class="sidebar-nav-item">
            <span class="d-flex align-items-center">
                <i class="fa-solid fa-user-tie nav-icon"></i>
                <span class="nav-text">Profil Saya</span>
            </span>
        </a>
        <a href="../logout.php" class="sidebar-nav-item text-danger mt-2">
            <span class="d-flex align-items-center">
                <i class="fa-solid fa-arrow-right-from-bracket nav-icon text-danger"></i>
                <span class="nav-text text-danger">Log Out</span>
            </span>
        </a>
    </div>
</aside>

<!-- Mobile Bottom Navigation -->
<div class="bottom-nav-mobile d-md-none">
    <div class="nav-item-mobile">
        <a href="grafik-kinerja.php" class="mobile-link"><i class="fa-solid fa-chart-pie"></i><span>Home</span></a>
    </div>

    <div class="nav-item-mobile dropup">
        <a href="#" class="dropdown-toggle mobile-link" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,15">
            <i class="fa-solid fa-users"></i><span>Staff</span>
        </a>
        <ul class="dropdown-menu shadow">
            <li><a class="dropdown-item" href="data-karyawan.php"><i class="fa-solid fa-house-chimney-user"></i> Data Karyawan</a></li>
            <li><a class="dropdown-item" href="kalender_kerja.php"><i class="fa-solid fa-calendar"></i> Kalender Kerja</a></li>
        </ul>
    </div>

    <div class="nav-item-mobile dropup">
        <a href="#" class="dropdown-toggle mobile-link" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,15">
            <i class="fa-solid fa-clock"></i><span>Absen</span>
        </a>
        <ul class="dropdown-menu shadow dropdown-menu-center">
            <li><a class="dropdown-item" href="absen.php"><i class="fa-solid fa-id-card-clip"></i> Data Absen</a></li>
            <li><a class="dropdown-item" href="data-absensi.php"><i class="fa-solid fa-camera"></i> Validasi Foto</a></li>
            <li><a class="dropdown-item" href="shifting.php"><i class="fa-solid fa-person-running"></i> Shifting</a></li>
            <li><a class="dropdown-item" href="kelola_jatah_cuti.php"><i class="fa-solid fa-folder-open"></i> Kelola Cuti</a></li>
            <li><a class="dropdown-item" href="cuti-karyawan.php"><i class="fa-solid fa-calendar-check"></i> Pengajuan Cuti</a></li>
        </ul>
    </div>

    <div class="nav-item-mobile dropup">
        <a href="#" class="dropdown-toggle mobile-link" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,15">
            <i class="fa-solid fa-wallet"></i><span>Keuangan</span>
        </a>
        <ul class="dropdown-menu shadow dropdown-menu-end">
            <li><a class="dropdown-item" href="tunjangan-karyawan.php"><i class="fa-solid fa-money-bill-trend-up"></i> Biaya Pengganti</a></li>
            <li><a class="dropdown-item" href="denda.php"><i class="fa-solid fa-hand-scissors"></i> Denda</a></li>
            <?php if ($user_role === 'superadmin'): ?>
            <li><a class="dropdown-item" href="cashbon.php"><i class="fa-solid fa-money-bill-transfer"></i> Cashbon</a></li>
            <li><a class="dropdown-item" href="penggajian.php"><i class="fa-solid fa-money-bill-wave"></i> Penggajian</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="nav-item-mobile dropup">
        <a href="#" class="dropdown-toggle mobile-link" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,15">
            <i class="fa-solid fa-user-tie"></i><span>Profil</span>
        </a>
        <ul class="dropdown-menu shadow dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-id-badge"></i> Profil Saya</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log Out</a></li>
        </ul>
    </div>
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

    // Active Route Highlight
    const currentPath = "<?php echo $current_page_basename; ?>";
    const pageMapping = {
        'view-profile-karyawan.php': 'data-karyawan.php',
        'edit-profile-karyawan.php': 'data-karyawan.php',
        'data-karyawan-baru.php': 'data-karyawan.php',
        'input-gaji.php': 'data-karyawan.php',
        'detail_cashbon.php': 'cashbon.php',
        'edit-profile.php': 'profile.php'
    };
    const targetPath = pageMapping[currentPath] || currentPath;
    
    document.querySelectorAll('.sidebar-menu-body a, .bottom-nav-mobile a').forEach(link => {
        if (link.getAttribute('href') === targetPath) {
            link.classList.add('active');
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
                const toggleTrigger = document.querySelector('[href="#' + parentCollapse.id + '"]');
                if (toggleTrigger) {
                    toggleTrigger.classList.add('active');
                    toggleTrigger.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
});
</script>