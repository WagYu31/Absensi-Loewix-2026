<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!-- Top Header Bar with Animated Hamburger Toggle -->
<div class="top-header-bar">
    <div class="d-flex align-items-center gap-3">
        <button class="hamburger-btn" id="sidebarToggleBtn" title="Buka / Tutup Sidebar" aria-label="Toggle Sidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark" style="font-size: 1.05rem; letter-spacing: 0.3px;">Gravitti System</span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">Karyawan</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="profile.php" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-medium text-secondary" style="font-size: 0.82rem;">
            <i class="fa-solid fa-user-circle me-1 text-primary"></i> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Karyawan'); ?>
        </a>
    </div>
</div>

<!-- Mobile Overlay Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar Container -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <a href="home.php" class="brand-logo">
            <div class="brand-icon">G</div>
            <h3>Gravitti Tech</h3>
        </a>
    </div>
    <div class="sidebar-menu">
        <div class="menu-category">Utama</div>
        <a href="home.php" class="nav-item"><i class="fa-solid fa-house-chimney-user"></i> <span>Dashboard</span></a>
        <a href="absensi.php" class="nav-item"><i class="fa-solid fa-camera"></i> <span>Absensi Online</span></a>
        
        <div class="menu-category">Kehadiran & Cuti</div>
        <a href="riwayat-absen.php" class="nav-item"><i class="fa-solid fa-list-check"></i> <span>Riwayat Absensi</span></a>
        <a href="kalender_kerja.php" class="nav-item"><i class="fa-solid fa-calendar-check"></i> <span>Kalender Kerja</span></a>
        <a href="pengajuan-cuti.php" class="nav-item"><i class="fa-solid fa-person-running"></i> <span>Pengajuan Cuti</span></a>
        <a href="grafik-kinerja.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Statistik Kinerja</span></a>
        
        <div class="menu-category">Keuangan & Profil</div>
        <a href="riwayat-gaji.php" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Slip Gaji</span></a>
        <a href="profile.php" class="nav-item"><i class="fa-solid fa-id-card-clip"></i> <span>Profil Saya</span></a>
        
        <a href="javascript:void(0)" onclick="window.triggerPWAInstall && window.triggerPWAInstall()" class="nav-item text-primary fw-bold mt-2">
            <i class="fa-solid fa-mobile-screen-button"></i> <span>Install App</span>
        </a>
        <a href="../logout.php" class="nav-item text-danger mt-3"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Log Out</span></a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById("sidebarToggleBtn");
    const backdrop = document.getElementById("sidebarBackdrop");
    const body = document.body;

    // Restore saved sidebar collapsed state on desktop
    const savedState = localStorage.getItem("gravitti_sidebar_collapsed");
    if (savedState === "true" && window.innerWidth >= 992) {
        body.classList.add("sidebar-collapsed");
        if (toggleBtn) toggleBtn.classList.add("is-active");
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function() {
            if (window.innerWidth < 992) {
                // Mobile slide drawer
                body.classList.toggle("sidebar-open-mobile");
                toggleBtn.classList.toggle("is-active");
            } else {
                // Desktop collapse mode
                body.classList.toggle("sidebar-collapsed");
                toggleBtn.classList.toggle("is-active");
                localStorage.setItem("gravitti_sidebar_collapsed", body.classList.contains("sidebar-collapsed"));
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener("click", function() {
            body.classList.remove("sidebar-open-mobile");
            if (toggleBtn) toggleBtn.classList.remove("is-active");
        });
    }

    // Active Route Highlight Logic
    const currentPath = "<?php echo $current_page_basename; ?>";
    document.querySelectorAll('.sidebar-menu a.nav-item').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});
</script>