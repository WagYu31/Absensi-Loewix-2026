<?php
session_start();

if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php'; // Menyediakan $nip, $nama, dll.

$loggedInUserNip = $_SESSION['nip'];
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajukan_cashbon'])) {
    $jumlah = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);
    $cicil = filter_input(INPUT_POST, 'cicil', FILTER_VALIDATE_INT);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tanggal_pengajuan = date('Y-m-d'); // Tanggal saat ini

    // Validasi
    if (empty($jumlah) || $jumlah <= 0) {
        $pesan_error = "Jumlah pengajuan cashbon harus diisi dan lebih besar dari 0.";
    } elseif (empty($cicil) || $cicil <= 0 || $cicil > 12) { // Batas cicilan misal 12 bulan
        $pesan_error = "Rencana cicilan harus diisi (antara 1 s.d. 12 bulan).";
    } elseif (empty($keterangan)) {
        $pesan_error = "Keterangan atau alasan pengajuan harus diisi.";
    } else {
        // Cek apakah ada pengajuan cashbon yang masih pending atau belum lunas
        // (Ini contoh validasi tambahan, sesuaikan dengan aturan bisnis Anda)
        $stmt_cek_cashbon = $conn->prepare("SELECT id_pc FROM pengajuan_cashbon WHERE nip = ? AND status NOT IN ('Lunas', 'Ditolak') AND deleted_at IS NULL");
        if ($stmt_cek_cashbon) {
            $stmt_cek_cashbon->bind_param("s", $loggedInUserNip);
            $stmt_cek_cashbon->execute();
            $stmt_cek_cashbon->store_result();
            if ($stmt_cek_cashbon->num_rows > 0) {
                $pesan_error = "Anda masih memiliki pengajuan cashbon yang aktif atau belum lunas. Selesaikan terlebih dahulu.";
            }
            $stmt_cek_cashbon->close();
        } else {
            $pesan_error = "Gagal memverifikasi data cashbon sebelumnya.";
        }


        if (empty($pesan_error)) {
            $stmt_insert = $conn->prepare("INSERT INTO pengajuan_cashbon (nip, tanggal, jumlah, cicil, keterangan, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt_insert) {
                $stmt_insert->bind_param("ssiis", $loggedInUserNip, $tanggal_pengajuan, $jumlah, $cicil, $keterangan);
                if ($stmt_insert->execute()) {
                    $_SESSION['pesan_sukses_cashbon'] = "Pengajuan cashbon Anda (Rp " . number_format($jumlah) . ") telah berhasil dikirim dan sedang menunggu persetujuan.";
                    header("Location: cashbon.php"); // Redirect ke halaman riwayat
                    exit();
                } else {
                    $pesan_error = "Gagal menyimpan pengajuan cashbon: " . $stmt_insert->error;
                    error_log("DB Execute Error (Insert Cashbon): " . $stmt_insert->error);
                }
                $stmt_insert->close();
            } else {
                $pesan_error = "Gagal menyiapkan statement database: " . $conn->error;
                error_log("DB Prepare Error (Insert Cashbon): " . $conn->error);
            }
        }
    }
}
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cashbon - <?php echo htmlspecialchars($nama ?? ''); ?> - Grav-Tech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/pengajuan-cashbon-styles.css">
</head>

<body>
    <?php include 'nav/sidebar.php'; // Menggunakan partial untuk sidebar 
    ?>

    <div class="main-content-wrapper">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Pengajuan Cashbon</h1>
                <p>Formulir untuk mengajukan pinjaman dana (cashbon) perusahaan.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <?php if (!empty($pesan_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($pesan_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-7 col-md-12 order-lg-1 order-md-2">
                        <div class="card form-pengajuan-cashbon-card mb-4 shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fa-solid fa-hand-holding-dollar title-icon"></i>Formulir Pengajuan Cashbon</h5>
                            </div>
                            <div class="card-body p-lg-4">
                                <form action="pengajuan-cashbon.php" method="POST">
                                    <div class="mb-3">
                                        <label for="jumlah" class="form-label">Jumlah Pinjaman (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm" id="jumlah" name="jumlah" placeholder="Contoh: 500000" required min="50000" step="50000">
                                        <small class="form-text text-muted">Minimal Rp 50.000, kelipatan Rp 50.000.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cicil" class="form-label">Rencana Cicilan (Bulan) <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="cicil" name="cicil" required>
                                            <option value="">-- Pilih Jumlah Bulan --</option>
                                            <?php for ($c = 1; $c <= 24; $c++): // Maksimal cicilan 12 bulan 
                                            ?>
                                                <option value="<?php echo $c; ?>"><?php echo $c; ?> Bulan</option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="keterangan" class="form-label">Keterangan/Alasan Pengajuan <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" id="keterangan" name="keterangan" rows="4" required placeholder="Jelaskan alasan pengajuan cashbon Anda..."></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" value="" id="setujuSnK" required>
                                        <label class="form-check-label" for="setujuSnK">
                                            Saya telah membaca dan menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuanCashbon">Syarat & Ketentuan Cashbon</a> yang berlaku.
                                        </label>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="ajukan_cashbon" class="btn btn-primary">
                                            <i class="fa-solid fa-paper-plane me-2"></i>Ajukan Cashbon
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12 order-lg-2 order-md-1">
                        <div class="card info-cashbon-card-trigger mb-4 shadow-sm sticky-lg-top">
                            <div class="card-body text-center">
                                <i class="fa-solid fa-file-contract fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Penting Diketahui!</h5>
                                <p class="card-text small text-muted">Sebelum mengajukan cashbon, pastikan Anda telah memahami sepenuhnya syarat dan ketentuan yang berlaku.</p>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuanCashbon">
                                    Baca Syarat & Ketentuan Cashbon
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer no-print">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>
            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <div class="modal fade" id="modalSyaratKetentuanCashbon" tabindex="-1" aria-labelledby="modalSyaratKetentuanCashbonLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSyaratKetentuanCashbonLabel">
                        <i class="fa-solid fa-file-contract me-2 text-primary"></i>Syarat dan Ketentuan Cashbon Karyawan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="info-cashbon-intro">Fasilitas cashbon (pinjaman dana) disediakan untuk membantu karyawan dalam mengatasi kebutuhan finansial yang bersifat mendesak dan penting.</p>
                    <div class="syarat-cashbon-section">
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-bullseye"></i>Tujuan Penggunaan</h6>
                            <p class="syarat-deskripsi">Diutamakan untuk kebutuhan darurat seperti biaya medis, pendidikan, musibah, atau kebutuhan penting lainnya yang tidak terduga. Penggunaan untuk tujuan konsumtif tidak dianjurkan.</p>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-rupiah-sign"></i>Plafon Pinjaman</h6>
                            <p class="syarat-deskripsi">Jumlah maksimal pinjaman yang dapat diajukan adalah <strong>hingga 1 (satu) kali gaji pokok</strong> bulanan karyawan, dengan batas maksimal absolut <strong>Rp 5.000.000,-</strong> (Lima Juta Rupiah), mana yang lebih rendah.</p>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-calendar-alt"></i>Periode Cicilan</h6>
                            <p class="syarat-deskripsi">Jangka waktu pengembalian pinjaman (cicilan) fleksibel mulai dari <strong>1 hingga maksimal 24 bulan</strong>.</p>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-percent"></i>Biaya dan Bunga</h6>
                            <p class="syarat-deskripsi">Fasilitas cashbon ini diberikan <strong>tanpa bunga</strong>.</p>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-money-bill-transfer"></i>Metode Pengembalian</h6>
                            <p class="syarat-deskripsi">Pengembalian cashbon akan dilakukan secara otomatis melalui <strong>potongan gaji bulanan</strong> sesuai dengan jumlah cicilan yang disepakati.</p>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-user-check"></i>Syarat Pengaju</h6>
                            <ul class="syarat-deskripsi ps-4">
                                <li>Berstatus sebagai karyawan tetap Gravitti Technology.</li>
                                <li>Telah melewati masa percobaan dan memiliki masa kerja minimal <strong>6 (enam) bulan</strong>.</li>
                                <li>Tidak sedang memiliki tunggakan cashbon atau pinjaman lain yang belum lunas di perusahaan.</li>
                                <li>Memiliki riwayat kinerja dan kedisiplinan yang baik.</li>
                            </ul>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-gavel"></i>Proses Persetujuan</h6>
                            <p class="syarat-deskripsi">Setiap pengajuan cashbon akan ditinjau dan memerlukan persetujuan dari Manajemen. Keputusan persetujuan sepenuhnya merupakan kewenangan perusahaan.</p>
                            <p class="syarat-deskripsi">Perusahaan berhak menolak pengajuan tanpa perlu memberikan alasan detail.</p>
                        </div>
                        <div class="syarat-item">
                            <h6 class="syarat-title"><i class="fa-solid fa-file-alt"></i>Dokumen Pendukung</h6>
                            <p class="syarat-deskripsi">Manajemen dapat meminta dokumen pendukung jika dianggap perlu untuk memverifikasi alasan pengajuan (misalnya, kuitansi biaya medis untuk pengajuan terkait kesehatan).</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <!-- <p class="small text-muted footer-info-cashbon"><i class="fa-solid fa-user-tie me-1"></i>Untuk informasi lebih lanjut atau pertanyaan, silakan hubungi HRD (Ibu Chika Retno A.).</p> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Script untuk menu aktif (sesuaikan dengan nama file PHP yang benar)
            var currentPath = "<?php echo $current_page_basename; ?>";
            var cashbonFormPage = "form-pengajuan-cashbon.php";
            var cashbonHistoryPage = "riwayat-cashbon.php";
            var cashbonMenuLink = $('.sidebar-menu a[href="' + cashbonFormPage + '"], .sidebar-menu a[href="' + cashbonHistoryPage + '"]');

            $('.sidebar-menu a.active').removeClass('active');
            if (currentPath === cashbonFormPage || currentPath === cashbonHistoryPage) {
                cashbonMenuLink.addClass('active');
            } else {
                $('.sidebar-menu a').each(function() {
                    if ($(this).attr('href').split("?")[0] === currentPath) {
                        $(this).addClass('active');
                    }
                });
            }

            // Untuk Navigasi Bawah Mobile
            $('.custom-nav__link.active').removeClass('active');
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
                // FAB is visually distinct
            } else if (currentPath === "dashboard_karyawan.php") { // Ganti dengan nama file dashboard Anda
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }
            // Halaman pengajuan cashbon tidak ada di bottom nav utama, jadi tidak perlu set active di sana.
        });
    </script>
</body>

</html>