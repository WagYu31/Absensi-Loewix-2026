<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'guest';
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
        <a href="grafik-kinerja.php" class="sidebar-brand">
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
        <a href="grafik-kinerja.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-chart-pie nav-icon"></i> <span>Dashboard</span></span>
        </a>

        <div class="sidebar-group-title">Manajemen</div>
        <a class="sidebar-nav-item" data-bs-toggle="collapse" href="#menuKepegawaian" role="button" aria-expanded="false">
            <span><i class="fa-solid fa-users nav-icon"></i> <span>Kepegawaian</span></span>
            <i class="fa-solid fa-chevron-down chevron-arrow"></i>
        </a>
        <div class="collapse sidebar-submenu" id="menuKepegawaian">
            <a href="data-karyawan.php">Data Karyawan</a>
            <a href="kalender_kerja.php">Kalender Kerja</a>
        </div>

        <a class="sidebar-nav-item" data-bs-toggle="collapse" href="#menuKehadiran" role="button" aria-expanded="false">
            <span><i class="fa-solid fa-clock nav-icon"></i> <span>Kehadiran</span></span>
            <i class="fa-solid fa-chevron-down chevron-arrow"></i>
        </a>
        <div class="collapse sidebar-submenu" id="menuKehadiran">
            <a href="absen.php">Data Absensi</a>
            <a href="data-absensi.php">Validasi Absen Manual</a>
            <a href="shifting.php">Jadwal Shifting</a>
            <a href="kelola_jatah_cuti.php">Kelola Jatah Cuti</a>
            <a href="cuti-karyawan.php">Pengajuan Cuti</a>
        </div>

        <a class="sidebar-nav-item" data-bs-toggle="collapse" href="#menuKeuangan" role="button" aria-expanded="false">
            <span><i class="fa-solid fa-wallet nav-icon"></i> <span>Keuangan</span></span>
            <i class="fa-solid fa-chevron-down chevron-arrow"></i>
        </a>
        <div class="collapse sidebar-submenu" id="menuKeuangan">
            <a href="tunjangan-karyawan.php">Biaya Pengganti</a>
            <a href="denda.php">Denda Karyawan</a>
            <?php if ($user_role === 'superadmin'): ?>
            <a href="cashbon.php">Cashbon</a>
            <a href="penggajian.php">Penggajian</a>
            <?php endif; ?>
        </div>

        <div class="sidebar-group-title">Pengaturan</div>
        <a href="profile.php" class="sidebar-nav-item">
            <span><i class="fa-solid fa-user-tie nav-icon"></i> <span>Profil Saya</span></span>
        </a>
        <a href="../logout.php" class="sidebar-nav-item text-danger mt-2">
            <span><i class="fa-solid fa-arrow-right-from-bracket nav-icon"></i> <span>Log Out</span></span>
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