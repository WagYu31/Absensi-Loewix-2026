<?php
$current_page_basename = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'guest';
?>
<style>
.sidebar-menu .collapse a { padding-left: 2.8rem; font-size: 0.9em; background-color: rgba(0, 0, 0, 0.03); border-left: 3px solid transparent; }
.sidebar-menu .collapse a:hover, .sidebar-menu .collapse a.active { border-left-color: #0d6efd; background-color: rgba(13, 110, 253, 0.05); }
.sidebar-menu .dropdown-toggle { cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
.sidebar-menu .dropdown-toggle::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; transition: transform 0.3s ease; border: none; }
.sidebar-menu .dropdown-toggle[aria-expanded="true"]::after { transform: rotate(180deg); }
.sidebar-menu .dropdown-toggle i { width: 25px; text-align: center; margin-right: 10px; }

.bottom-nav-mobile { position: fixed; bottom: 0; left: 0; width: 100%; background: #ffffff; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-around; padding: 10px 0 5px 0; z-index: 1030; border-top: 1px solid #e9ecef; }
.bottom-nav-mobile .nav-item-mobile { flex: 1; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
.bottom-nav-mobile .nav-item-mobile > a { color: #6c757d; text-decoration: none; font-size: 0.75rem; display: flex; flex-direction: column; align-items: center; transition: all 0.2s; width: 100%; }
.bottom-nav-mobile .nav-item-mobile > a i { font-size: 1.25rem; margin-bottom: 4px; }
.bottom-nav-mobile .nav-item-mobile > a.active { color: #0d6efd; font-weight: 600; }
.bottom-nav-mobile .nav-item-mobile > a.active i { transform: scale(1.1); }
.bottom-nav-mobile .dropdown-toggle::after { display: none; }
.bottom-nav-mobile .dropdown-menu { border-radius: 12px; border: 1px solid #dee2e6; box-shadow: 0 -5px 20px rgba(0,0,0,0.15); padding: 8px 0; margin-bottom: 15px !important; min-width: 190px; z-index: 1050; }
.bottom-nav-mobile .dropdown-item { font-size: 0.85rem; padding: 10px 15px; color: #495057; display: flex; align-items: center; }
.bottom-nav-mobile .dropdown-item i { width: 20px; text-align: center; margin-right: 12px; color: #6c757d; font-size: 1rem; }
.bottom-nav-mobile .dropdown-item:hover, .bottom-nav-mobile .dropdown-item.active { background-color: rgba(13, 110, 253, 0.08); color: #0d6efd; font-weight: 500; }
.bottom-nav-mobile .dropdown-item.active i { color: #0d6efd; }
.bottom-nav-mobile .dropdown-divider { margin: 4px 0; }

@media (max-width: 767.98px) { body { padding-bottom: 80px; } }
</style>

<div class="sidebar d-none d-md-block">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-g"></i> Gravitti Tech</h3>
    </div>
    <div class="sidebar-menu">
        <a href="grafik-kinerja.php" class="nav-item"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>

        <a class="dropdown-toggle nav-item" data-bs-toggle="collapse" href="#menuKepegawaian" role="button" aria-expanded="false" aria-controls="menuKepegawaian">
            <span><i class="fa-solid fa-users"></i> Kepegawaian</span>
        </a>
        <div class="collapse" id="menuKepegawaian">
            <a href="data-karyawan.php">Data Karyawan</a>
            <a href="kalender_kerja.php">Kalender Kerja</a>
        </div>

        <a class="dropdown-toggle nav-item" data-bs-toggle="collapse" href="#menuWaktu" role="button" aria-expanded="false" aria-controls="menuWaktu">
            <span><i class="fa-solid fa-clock"></i> Kehadiran</span>
        </a>
        <div class="collapse" id="menuWaktu">
            <a href="absen.php">Data Absensi</a>
            <a href="shifting.php">Jadwal Shifting</a>
            <a href="kelola_jatah_cuti.php">Kelola Jatah Cuti</a>
            <a href="cuti-karyawan.php">Pengajuan Cuti</a>
        </div>

        <a class="dropdown-toggle nav-item" data-bs-toggle="collapse" href="#menuKeuangan" role="button" aria-expanded="false" aria-controls="menuKeuangan">
            <span><i class="fa-solid fa-wallet"></i> Keuangan</span>
        </a>
        <div class="collapse" id="menuKeuangan">
            <a href="tunjangan-karyawan.php">Biaya Pengganti</a>
            <a href="denda.php">Denda Karyawan</a>
            <?php if ($user_role === 'superadmin'): ?>
            <a href="cashbon.php">Cashbon</a>
            <a href="penggajian.php">Penggajian</a>
            <?php endif; ?>
        </div>

        <a href="profile.php" class="nav-item"><i class="fa-solid fa-user-tie"></i> Profil Saya</a>
        <a href="../logout.php" class="nav-item text-danger mt-3"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log Out</a>
    </div>
</div>

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
            const toggleBtn = document.querySelector('[href="#' + parentCollapse.id + '"]');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
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