<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php'; // Pastikan file ini menyediakan variabel sesi dengan benar

// Data dummy untuk pengembangan tampilan (gantilah dengan query database jika memungkinkan)
$absensi_bulan_ini = ['total_hari_kerja' => 22, 'hadir' => 20, 'izin' => 1, 'sakit' => 1, 'terlambat' => 2];
$slip_gaji_terbaru = ['periode' => date('F Y', strtotime('-1 month')), 'gaji_bersih' => 'Rp 10.000.000', 'tanggal_bayar' => date('25 F Y', strtotime('-1 month'))];
$pengumuman = [
    ['id' => 1, 'judul' => 'Pembaruan Kebijakan Cuti Tahunan', 'isi' => 'Harap perhatikan pembaruan terkait kebijakan cuti tahunan efektif per tanggal 1 Juni 2025. Detail dapat dilihat pada portal internal.', 'tanggal' => '20 Mei 2025', 'kategori' => 'Kebijakan'],
    ['id' => 2, 'judul' => 'Jadwal Pelatihan Keterampilan Komunikasi', 'isi' => 'Pelatihan keterampilan komunikasi akan diadakan pada tanggal 10-12 Juni 2025. Pendaftaran dibuka hingga 5 Juni.', 'tanggal' => '18 Mei 2025', 'kategori' => 'Pelatihan'],
    ['id' => 3, 'judul' => 'Pengingat: Pengisian Laporan Kinerja Mingguan', 'isi' => 'Mohon untuk segera melengkapi laporan kinerja mingguan Anda sebelum batas waktu hari Jumat ini pukul 17:00 WIB.', 'tanggal' => '15 Mei 2025', 'kategori' => 'Operasional'],
];

// Nama file halaman saat ini untuk active state menu
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Grav-Tech Salary</title>
    <meta name="description" content="Dashboard profesional untuk karyawan Grav-Tech Salary" />
    <meta name="keywords" content="salary, gaji, gravitti technology, dashboard, karyawan" />
    <meta name="author" content="Irviani" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">

    <script>
        // Set page background color for notch effect (if different from default CSS body)
        // document.body.style.backgroundColor = '#E6E0F8'; // Warna lavender muda (sesuaikan jika perlu)
    </script>
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner">
            <div class="container-fluid px-lg-4">
                <h1>Selamat Datang Kembali, <?php echo htmlspecialchars(explode(' ', $nama)[0]); ?>!</h1>
                <p>Semoga harimu produktif dan menyenangkan di Gravitti Technology.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4 px-1">
                <div class="row">
                    <div class="col-xl-5 mb-4">
                        <div class="card profile-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="profile-img-container">
                                    <?php
                                    $base_upload_path = '../uploads/';
                                    $universal_default_image = $base_upload_path . 'default_avatar.png'; // PASTIKAN GAMBAR INI ADA
                                    $image_source_for_profile = '';

                                    if (!empty($photo)) {
                                        $image_source_for_profile = htmlspecialchars($base_upload_path . $photo);
                                    } else {
                                        $initial = !empty($nama) ? strtoupper(substr($nama, 0, 1)) : 'U';
                                        $image_source_for_profile = 'https://via.placeholder.com/80/2979ff/ffffff?Text=' . $initial;
                                    }
                                    ?>
                                    <img src="<?php echo $image_source_for_profile; ?>"
                                        alt="Foto Profil" class="profile-img"
                                        onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($universal_default_image); ?>';">
                                </div>
                                <div class="profile-info">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($nama); ?></h5>
                                    <p class="text-muted mb-1">NIK: <?php echo htmlspecialchars($nik); ?></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($jabatan); ?></p>
                                    <a href="profile.php" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">
                                        <i class="fa-solid fa-user-pen me-1"></i> Edit Profil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php include "data.php"; ?>

                    <div class="col-xl-7 mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fa-solid fa-calendar-days title-icon"></i>Ringkasan Absensi
                                            <small>(<?php echo htmlspecialchars($periode_absensi_display_dash); ?>)</small>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="stat-item"><span>Total Hari Kerja Efektif:</span> <strong><?php echo $total_hari_kerja_dash; ?></strong></div>
                                        <div class="stat-item"><span>Hadir:</span> <strong class="text-success"><?php echo $jumlah_hadir_dash; ?></strong></div>
                                        <div class="stat-item"><span>Cuti (Disetujui):</span> <strong class="text-warning"><?php echo $jumlah_izin_sakit_dash; ?></strong></div>
                                        <div class="stat-item"><span>Total Terlambat:</span> <strong class="text-danger"><?php echo $total_menit_terlambat_dash; ?> <small>menit</small></strong></div>
                                        <a href="<?php echo $link_ke_detail_absen_dash; ?>" class="card-link mt-3 d-inline-block">Lihat Detail Absensi <i class="fa-solid fa-arrow-right-long"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0"><i class="fa-solid fa-money-check-dollar title-icon text-success"></i>Info Gaji Bulan Ini</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="stat-item"><span>Periode:</span> <strong><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['periode']); ?></strong></div>
                                        <div class="stat-item"><span>Perkiraan Gaji Bersih:</span> <strong class="text-success"><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['gaji_bersih_rp']); ?></strong></div>
                                        <div class="stat-item"><span>Estimasi Tgl. Bayar:</span> <strong><?php echo htmlspecialchars($info_gaji_bulan_ini_dash['tanggal_bayar']); ?></strong></div>
                                        <a href="<?php echo $link_ke_riwayat_gaji_dash; ?>" class="card-link mt-3 d-inline-block">Lihat Riwayat Gaji <i class="fa-solid fa-arrow-right-long"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="section-title">Akses Cepat</h5>
                    <div class="quick-action-grid">
                        <a href="profile.php" class="quick-action-card">
                            <i class="fa-solid fa-address-card"></i>
                            <span>Profil Saya</span>
                        </a>
                        <a href="absen.php?nik=<?php echo htmlspecialchars($nik); ?>#form-absen" class="quick-action-card">
                            <i class="fa-solid fa-user-check"></i>
                            <span>Absen</span>
                        </a>
                        <a href="riwayat-gaji.php" class="quick-action-card">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Lihat Gaji</span>
                        </a>
                        <!-- <a href="cuti.php" class="quick-action-card" data-bs-toggle="modal" data-bs-target="#pengajuanCutiModal"> -->
                        <a href="cuti.php" class="quick-action-card">
                            <i class="fa-solid fa-person-walking-luggage"></i>
                            <span>Ajukan Cuti</span>
                        </a>
                        <!--<a href="pengumuman.php" class="quick-action-card"> <i class="fa-solid fa-bell"></i>-->
                        <!--    <span>Pengumuman</span>-->
                        <!--</a>-->
                        <a href="kalender_kerja.php" class="quick-action-card"> <i class="fa-solid fa-calendar-check"></i>
                            <span>Kalender Kerja</span>
                        </a>
                        <a href="peringkat-kinerja.php" class="quick-action-card"> <i class="fa-solid fa-bar-chart"></i>
                            <span>Statistik & Kinerja</span>
                        </a>
                        <a href="help.php" class="quick-action-card">
                            <!-- <a href="help.php" class="quick-action-card" data-bs-toggle="modal" data-bs-target="#bantuanModal"> -->
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Bantuan</span>
                        </a>
                    </div>
                </div>

                <?php

                // --- AMBIL DATA PENGUMUMAN DARI DATABASE ---
                $pengumuman_list_db = []; // Inisialisasi array untuk menyimpan hasil query
                $sql_pengumuman = "SELECT id, judul, isi, jenis, created_at, gambar, media 
                   FROM pengumuman 
                   WHERE deleted_at IS NULL 
                   ORDER BY created_at DESC 
                   LIMIT 3"; // Ambil 3 pengumuman terbaru

                $result_pengumuman = $conn->query($sql_pengumuman);
                if ($result_pengumuman && $result_pengumuman->num_rows > 0) {
                    while ($row_pengumuman = $result_pengumuman->fetch_assoc()) {
                        $pengumuman_list_db[] = $row_pengumuman;
                    }
                } else {
                    // Tidak ada error query, tapi mungkin memang tidak ada pengumuman
                    // error_log("Dashboard - Tidak ada pengumuman ditemukan atau query error: " . $conn->error);
                }

                // --- AKHIR BLOK PENGAMBILAN PENGUMUMAN ---
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title"><i class="fa-solid fa-bullhorn title-icon"></i>Pengumuman Terbaru</h6>
                                <a href="pengumuman.php" class="card-link small">Lihat Semua <i class="fa-solid fa-angle-right"></i></a>
                            </div>
                            <div class="card-body pt-2 pb-3">
                                <?php if (!empty($pengumuman_list_db)): ?>
                                    <?php foreach ($pengumuman_list_db as $item_db): ?>
                                        <div class="announcement-item">
                                            <a href="#" class="announcement-title" data-bs-toggle="modal" data-bs-target="#announcementDetailModal_<?php echo htmlspecialchars($item_db['id']); ?>">
                                                <?php echo htmlspecialchars($item_db['judul']); ?>
                                            </a>
                                            <div class="announcement-meta">
                                                <span class="me-2">
                                                    <i class="fa-regular fa-calendar-alt me-1"></i>
                                                    <?php
                                                    // Format tanggal dari created_at
                                                    try {
                                                        $tanggal_pengumuman = new DateTime($item_db['created_at']);
                                                        echo htmlspecialchars($tanggal_pengumuman->format('d M Y, H:i'));
                                                    } catch (Exception $e) {
                                                        echo htmlspecialchars($item_db['created_at']); // Fallback jika format tidak standar
                                                    }
                                                    ?>
                                                </span>
                                                <?php if (!empty($item_db['jenis'])): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?php echo htmlspecialchars($item_db['jenis']); // Menggunakan kolom 'jenis' sebagai kategori 
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="announcement-content d-none d-md-block">
                                                <?php
                                                // Menampilkan ringkasan isi
                                                $isi_ringkas = strip_tags($item_db['isi']); // Hapus tag HTML untuk ringkasan
                                                echo htmlspecialchars(substr($isi_ringkas, 0, 120)) . (strlen($isi_ringkas) > 120 ? '...' : '');
                                                ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-muted my-3">Tidak ada pengumuman terbaru saat ini.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>
            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <div class="modal fade" id="pengajuanCutiModal" tabindex="-1" aria-labelledby="pengajuanCutiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pengajuanCutiModalLabel"><i class="fa-solid fa-person-walking-luggage me-2"></i>Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Fitur pengajuan cuti online saat ini sedang dalam tahap finalisasi. Untuk sementara, silakan ajukan cuti melalui formulir manual ke HRD.</p>
                    <p>Terima kasih atas pengertiannya.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bantuanModal" tabindex="-1" aria-labelledby="bantuanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bantuanModalLabel"><i class="fa-solid fa-circle-question me-2"></i>Pusat Bantuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Mengalami kendala atau butuh bantuan terkait penggunaan sistem? Hubungi tim support kami:</p>
                    <ul>
                        <li>Email: <a href="mailto:support@gravtech.com">support@gravtech.com</a></li>
                        <li>Telepon: (021) 123-4567 ext. 101</li>
                        <li>Jam Operasional: Senin - Jumat, 08:00 - 17:00 WIB</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($pengumuman)): ?>
        <?php foreach ($pengumuman as $item): ?>
            <div class="modal fade" id="announcementDetailModal_<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="announcementDetailModalLabel_<?php echo $item['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="announcementDetailModalLabel_<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['judul']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fa-regular fa-calendar-alt me-1"></i> Diposting: <?php echo htmlspecialchars($item['tanggal']); ?> |
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle"><?php echo htmlspecialchars($item['kategori']); ?></span>
                                </small>
                            </div>
                            <hr>
                            <p><?php echo nl2br(htmlspecialchars($item['isi'])); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var currentPath = window.location.pathname.substring(window.location.pathname.lastIndexOf("/") + 1);
            if (currentPath === "") { // Jika path kosong (biasanya root atau index)
                currentPath = "<?php echo $current_page_basename; ?>"; // Gunakan nama file PHP saat ini
            }

            // Active state untuk Sidebar Desktop
            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active'); // Hapus active dari semua
                    $(this).addClass('active'); // Tambah active ke yang cocok
                    return false;
                }
            });
            // Jika tidak ada yang cocok di sidebar (misal halaman dengan parameter), dan currentPath adalah halaman dashboard
            if ($('.sidebar-menu a.active').length === 0 && currentPath === "<?php echo $current_page_basename; ?>") {
                $('.sidebar-menu a[href="<?php echo $current_page_basename; ?>"]').addClass('active');
            }


            // Active state untuk Custom Mobile Bottom Navigation
            $('.custom-nav__link').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.custom-nav__link.active').removeClass('active'); // Hapus active dari semua
                    $(this).addClass('active'); // Tambah active ke yang cocok
                    // Jika yang aktif adalah FAB, pastikan FAB button juga dapat style active jika perlu
                    // (Saat ini FAB button tidak memiliki class 'active' terpisah, hanya link parent)
                    return false;
                }
            });
            // Jika tidak ada yang cocok di bottom nav (misal halaman dengan parameter), dan currentPath adalah halaman dashboard
            if ($('.custom-nav__link.active').length === 0 && currentPath === "<?php echo $current_page_basename; ?>") {
                $('.custom-nav__link[href="<?php echo $current_page_basename; ?>"]').addClass('active');
            }


            // Inisialisasi Tooltip Bootstrap jika ada
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>