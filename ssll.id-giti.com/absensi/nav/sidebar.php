<?php
// Mengambil nama file dari halaman yang sedang dibuka. Contoh: "sa-data-karyawan.php"
$current_page_basename = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'guest'; // Ambil peran dari session
?>

<div class="sidebar d-none d-lg-block">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-g"></i> Gravitti Tech</h3>
    </div>
    <div class="sidebar-menu">
        <a href="../staff/data-karyawan.php"><i class="fa-solid fa-house-chimney-user"></i> Data Karyawan</a>
        <a href="../staff/kalender_kerja.php"><i class="fa-solid fa-calendar"></i> Kalender Kerja</a>
        
        <?php if ($user_role === 'superadmin'): ?>
            <a href="../staff/penggajian.php"><i class="fa-solid fa-money-bill-wave"></i> Penggajian</a>
        <?php endif; ?>
        
        <!--<a href="upload-absen.php"><i class="fa-solid fa-upload"></i> Upload Absensi</a>-->
        <a href="../staff/absen.php"><i class="fa-solid fa-id-card-clip"></i> Absensi</a>
        <a href="../staff/shifting.php"><i class="fa-solid fa-person-running"></i> Shifting</a>
        <a href="../staff/kelola_jatah_cuti.php"><i class="fa-solid fa-folder-open"></i> Kelola Cuti</a>
        <a href="../staff/cuti-karyawan.php"><i class="fa-solid fa-calendar-check"></i> Cuti</a>
        <a href="../staff/denda.php"><i class="fa-solid fa-hand-scissors"></i> Denda</a>

        <?php if ($user_role === 'superadmin'): ?>
            <a href="../staff/cashbon.php"><i class="fa-solid fa-money-bill-transfer"></i> Cashbon</a>
        <?php endif; ?>
        
        <a href="../logout.php" class="mt-3"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log Out</a>
    </div>
</div>


<script>
$(document).ready(function() {
    // Ambil nama file saat ini dari PHP
    const currentPath = "<?php echo $current_page_basename; ?>";

    const pageMapping = {
        'view-profile-karyawan.php': 'data-karyawan.php',
        'edit-profile-karyawan.php': 'data-karyawan.php',
        'data-karyawan-baru.php': 'data-karyawan.php',
        'input-gaji.php': 'data-karyawan.php',
        'detail_cashbon.php': 'cashbon.php'
    };

    // Tentukan target link yang harus aktif
    const targetPath = pageMapping[currentPath] || currentPath;

    // Hapus class 'active' dari semua link menu terlebih dahulu
    $('.sidebar-menu a').removeClass('active');

    // Cari link yang href-nya sama dengan targetPath, lalu tambahkan class 'active'
    $('.sidebar-menu a[href="' + targetPath + '"]').addClass('active');
});
</script>