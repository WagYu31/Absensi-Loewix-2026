<?php
session_start();

// Cek apakah pengguna telah login (Semua peran bisa melihat halaman bantuan)
if (!isset($_SESSION['nip'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php'; // Untuk konsistensi, meskipun mungkin tidak ada query DB di halaman ini
// get-kar-login-data.php menyediakan $nip (session NIP), $nama, $jabatan, $nik (database NIK)
include 'get-kar-login-data.php';

$loggedInUserNip = $_SESSION['nip'];
$nama_karyawan_login = $nama ?? 'Karyawan';

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan - Grav-Tech</title>
    <meta name="description" content="Informasi kontak penting dan bantuan di Grav-Tech" />
    <meta name="keywords" content="bantuan, help, kontak, support, gravitti technology" />
    <meta name="author" content="Irviani & AI Assistant" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/bantuan-styles.css">
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner page-specific-header bantuan-header-banner no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pusat Bantuan</h1>
                <p>Temukan informasi kontak penting dan panduan lainnya di sini.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">

                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-users title-icon me-2"></i>Human Resources Department (HRD)</h5>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-user kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Narahubung Utama:</span>
                                        <span class="kontak-info-value">Ibu Chika Retno A.</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-envelope kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Email:</span>
                                        <span class="kontak-info-value"><a href="mailto:info@grav-tech.com">info@grav-tech.com</a></span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-phone kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Ekstensi Telepon:</span>
                                        <span class="kontak-info-value">812</span>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <strong class="kontak-info-label d-block mb-2">Area Tanggung Jawab:</strong>
                                <ul class="tanggung-jawab-list">
                                    <li>Pengelolaan Gaji dan Tunjangan</li>
                                    <li>Pengelolaan Absensi</li>
                                    <li>Proses Pengajuan Cuti</li>
                                    <li>Rekrutmen dan Pengembangan Karyawan</li>
                                    <li>Keluhan dan Konsultasi Karyawan</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-laptop-code title-icon me-2"></i>IT Support</h5>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-headset kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Tim Bantuan Teknis:</span>
                                        <span class="kontak-info-value">IT Helpdesk</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-envelope kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Email:</span>
                                        <span class="kontak-info-value"><a href="mailto:it.support@grav-tech.com">it.support@grav-tech.com</a></span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-phone kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Ekstensi Telepon:</span>
                                        <span class="kontak-info-value">814</span>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <strong class="kontak-info-label d-block mb-2">Area Tanggung Jawab:</strong>
                                <ul class="tanggung-jawab-list">
                                    <li>Masalah Perangkat Keras (Komputer, Printer, dll.)</li>
                                    <li>Gangguan Jaringan Internet & Intranet</li>
                                    <li>Akses dan Masalah Sistem Aplikasi Internal</li>
                                    <li>Keamanan Data dan Akun Pengguna</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-building-user title-icon me-2"></i>Bagian Umum</h5>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-user-tie kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Narahubung:</span>
                                        <span class="kontak-info-value">Rosalina Megawati</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-phone kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Ekstensi Telepon:</span>
                                        <span class="kontak-info-value">813</span>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <strong class="kontak-info-label d-block mb-2">Area Tanggung Jawab:</strong>
                                <ul class="tanggung-jawab-list">
                                    <li>Fasilitas dan Pemeliharaan Kantor</li>
                                    <li>Pengadaan Alat Tulis Kantor (ATK)</li>
                                    <li>Peminjaman Barang</li>
                                    <!-- <li>Jadwal Ruang Meeting</li> -->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="card kontak-card shadow-sm">
                            <div class="card-header bg-danger-subtle border-danger-subtle">
                                <h5 class="card-title mb-0 text-danger-emphasis"><i class="fa-solid fa-triangle-exclamation title-icon me-2"></i>Kontak Darurat Internal</h5>
                            </div>
                            <div class="card-body">
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-shield-halved kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Informasi Gedung (24 Jam):</span>
                                        <span class="kontak-info-value">Ext. 810</span>
                                    </div>
                                </div>
                                <div class="kontak-info-item">
                                    <i class="fa-solid fa-briefcase-medical kontak-icon"></i>
                                    <div>
                                        <span class="kontak-info-label">Petugas P3K / Klinik Internal:</span>
                                        <span class="kontak-info-value">Ext. 813</span>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <p class="small text-muted">Segera hubungi kontak di atas jika terjadi situasi darurat di area kantor.</p>
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
    </div> <?php include 'nav/bottom-nav.php'; // Menggunakan partial untuk bottom nav 
            ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var currentPath = "<?php echo $current_page_basename; ?>";

            // Logika untuk menandai link aktif di sidebar
            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            if (currentPath === "bantuan.php" && !$('.sidebar-menu a[href="bantuan.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="bantuan.php"]').addClass('active');
            }

            // Untuk Navigasi Bawah Mobile (Halaman Bantuan tidak ada di bottom nav utama)
            $('.custom-nav__link.active').removeClass('active');
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
                // FAB is visually distinct
            } else if (currentPath === "dashboard_karyawan.php") {
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }
        });
    </script>
</body>

</html>