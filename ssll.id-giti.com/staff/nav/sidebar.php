<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'guest';
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
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">Staff Panel</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="profile.php" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-medium text-secondary" style="font-size: 0.82rem;">
            <i class="fa-solid fa-user-circle me-1 text-primary"></i> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
        </a>
    </div>
</div>

<!-- Mobile Overlay Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar Menu -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <a href="grafik-kinerja.php" class="brand-logo">
            <div class="brand-icon">G</div>
            <h3>Gravitti Tech</h3>
        </a>
    </div>
    <div class="sidebar-menu">
        <div class="menu-category">Utama</div>
        <a href="grafik-kinerja.php" class="nav-item">
            <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
        </a>

        <div class="menu-category">Manajemen</div>
        <a class="dropdown-toggle nav-item" data-bs-toggle="collapse" href="#menuKepegawaian" role="button" aria-expanded="false" aria-controls="menuKepegawaian">
            <span><i class="fa-solid fa-users"></i> <span>Kepegawaian</span></span>
        </a>
        <div class="collapse" id="menuKepegawaian">
            <a href="data-karyawan.php">Data Karyawan</a>
            <a href="kalender_kerja.php">Kalender Kerja</a>
        </div>

        <a class="dropdown-toggle nav-item" data-bs-toggle="collapse" href="#menuWaktu" role="button" aria-expanded="false" aria-controls="menuWaktu">
            <span><i class="fa-solid fa-clock"></i> <span>Kehadiran</span></span>
        </a>
        <div class="collapse" id="menuWaktu">
            <a href="absen.php">Data Absensi</a>
            <a href="data-absensi.php">Validasi Absen Manual</a>
            <a href="shifting.php">Jadwal Shifting</a>
            <a href="kelola_jatah_cuti.php">Kelola Jatah Cuti</a>
            <a href="cuti-karyawan.php">Pengajuan Cuti</a>
        </div>

        <a class="dropdown-toggle nav-item" data-bs-toggle="collapse" href="#menuKeuangan" role="button" aria-expanded="false" aria-controls="menuKeuangan">
            <span><i class="fa-solid fa-wallet"></i> <span>Keuangan</span></span>
        </a>
        <div class="collapse" id="menuKeuangan">
            <a href="tunjangan-karyawan.php">Biaya Pengganti</a>
            <a href="denda.php">Denda Karyawan</a>
            <?php if ($user_role === 'superadmin'): ?>
            <a href="cashbon.php">Cashbon</a>
            <a href="penggajian.php">Penggajian</a>
            <?php endif; ?>
        </div>

        <div class="menu-category">Pengaturan</div>
        <a href="profile.php" class="nav-item"><i class="fa-solid fa-user-tie"></i> <span>Profil Saya</span></a>
        <a href="../logout.php" class="nav-item text-danger mt-3"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Log Out</span></a>
    </div>
</div>

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
    const pageMapping = {
        'view-profile-karyawan.php': 'data-karyawan.php',
        'edit-profile-karyawan.php': 'data-karyawan.php',
        'data-karyawan-baru.php': 'data-karyawan.php',
        'input-gaji.php': 'data-karyawan.php',
        'detail_cashbon.php': 'cashbon.php',
        'edit-profile.php': 'profile.php'
    };
    const targetPath = pageMapping[currentPath] || currentPath;
    
    document.querySelectorAll('.sidebar-menu a, .bottom-nav-mobile a.dropdown-item, .bottom-nav-mobile .mobile-link').forEach(link => {
        link.classList.remove('active');
    });

    const activeDesktopLink = document.querySelector('.sidebar-menu a[href="' + targetPath + '"]');
    if (activeDesktopLink) {
        activeDesktopLink.classList.add('active');
        const parentCollapse = activeDesktopLink.closest('.collapse');
        if (parentCollapse) {
            parentCollapse.classList.add('show');
            const toggleBtnCol = document.querySelector('[href="#' + parentCollapse.id + '"]');
            if (toggleBtnCol) toggleBtnCol.setAttribute('aria-expanded', 'true');
        }
    }

    const activeMobileSubLink = document.querySelector('.bottom-nav-mobile .dropdown-item[href="' + targetPath + '"]');
    const activeMobileDirectLink = document.querySelector('.bottom-nav-mobile > .nav-item-mobile > a[href="' + targetPath + '"]');

    if (activeMobileSubLink) {
        activeMobileSubLink.classList.add('active');
        const parentDropup = activeMobileSubLink.closest('.dropup');
        if (parentDropup) {
            const toggleLink = parentDropup.querySelector('.dropdown-toggle');
            if (toggleLink) toggleLink.classList.add('active');
        }
    } else if (activeMobileDirectLink) {
        activeMobileDirectLink.classList.add('active');
    }
});
</script>