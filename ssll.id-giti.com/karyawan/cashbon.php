<?php
session_start();

if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php'; // Menyediakan $nip, $nama, dll.

$loggedInUserNip = $_SESSION['nip'];
$nama_karyawan_login = $nama ?? 'Karyawan'; // $nama dari get-kar-login-data.php

// --- Ambil Riwayat Cashbon untuk ditampilkan ---
$riwayat_cashbon_list = [];
$limit_riwayat = 10; // Jumlah item per halaman
$page_riwayat = isset($_GET['page_cashbon']) ? (int)$_GET['page_cashbon'] : 1;
$page_riwayat = max($page_riwayat, 1);
$offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

// Hitung total data untuk pagination
$totalResultCashbon = $conn->query("SELECT COUNT(id_pc) as total FROM pengajuan_cashbon WHERE nip='$loggedInUserNip' AND deleted_at IS NULL");
$totalRowCashbon = $totalResultCashbon->fetch_assoc();
$totalDataCashbon = $totalRowCashbon['total'] ?? 0;
$totalPagesCashbon = ceil($totalDataCashbon / $limit_riwayat);

$stmt_riwayat = $conn->prepare("SELECT id_pc, tanggal, jumlah, cicil, keterangan, status, created_at FROM pengajuan_cashbon WHERE nip = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?");
if ($stmt_riwayat) {
    $stmt_riwayat->bind_param("sii", $loggedInUserNip, $limit_riwayat, $offset_riwayat);
    $stmt_riwayat->execute();
    $result_riwayat = $stmt_riwayat->get_result();
    while ($row_cashbon = $result_riwayat->fetch_assoc()) {
        $riwayat_cashbon_list[] = $row_cashbon;
    }
    $stmt_riwayat->close();
} else {
    // Handle error
    error_log("Gagal mengambil riwayat cashbon: " . $conn->error);
}

// Fungsi untuk format status
function formatStatusCashbon($status)
{
    switch (ucfirst(strtolower($status))) {
        case 'Pending':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        case 'Disetujui':
            return '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="fa-solid fa-check-circle me-1"></i>Disetujui</span>';
        case 'Ditolak':
            return '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="fa-solid fa-times-circle me-1"></i>Ditolak</span>';
        case 'Lunas':
            return '<span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Lunas</span>';
        case 'Dibatalkan':
            return '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"><i class="fa-solid fa-ban me-1"></i>Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . htmlspecialchars($status) . '</span>';
    }
}
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pengajuan Cashbon - <?php echo htmlspecialchars($nama_karyawan_login); ?> - Grav-Tech</title>
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
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Riwayat Pengajuan Cashbon</h1>
                <p>Lihat status dan detail pengajuan cashbon Anda.</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <?php
                // Pesan sukses dari halaman form setelah redirect
                if (isset($_SESSION['pesan_sukses_cashbon'])):
                ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                        <i class="fa-solid fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['pesan_sukses_cashbon']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php
                    unset($_SESSION['pesan_sukses_cashbon']); // Hapus setelah ditampilkan
                endif;
                ?>

                <div class="d-flex justify-content-end mb-3 no-print">
                    <a href="pengajuan-cashbon.php" class="btn btn-danger">
                        <i class="fa-solid fa-plus me-2"></i>Ajukan Cashbon Baru
                    </a>
                </div>


                <div class="card riwayat-cashbon-card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-history title-icon"></i>Daftar Pengajuan Cashbon Anda</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($riwayat_cashbon_list)): ?>
                            <div class="text-center p-4 text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                                <p>Belum ada riwayat pengajuan cashbon.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover table-striped mb-0 riwayat-cashbon-table">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th>No.</th>
                                            <th>Tgl. Pengajuan</th>
                                            <th>Jumlah</th>
                                            <th>Cicilan</th>
                                            <th class="d-none d-lg-table-cell">Keterangan</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no_r_cb_desktop = $offset_riwayat + 1;
                                        foreach ($riwayat_cashbon_list as $cb_item): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no_r_cb_desktop++; ?></td>
                                                <td><?php echo date('d M Y', strtotime($cb_item['tanggal'])); ?></td>
                                                <td>Rp <?php echo number_format($cb_item['jumlah'], 0, ',', '.'); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($cb_item['cicil']); ?> bln</td>
                                                <td class="keterangan-cashbon d-none d-lg-table-cell" title="<?php echo htmlspecialchars($cb_item['keterangan']); ?>">
                                                    <?php echo htmlspecialchars(substr($cb_item['keterangan'], 0, 50)) . (strlen($cb_item['keterangan']) > 50 ? '...' : ''); ?>
                                                </td>
                                                <td class="text-center"><?php echo formatStatusCashbon($cb_item['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="riwayat-cashbon-list-mobile d-md-none p-3">
                                <?php foreach ($riwayat_cashbon_list as $cb_item): ?>
                                    <div class="card riwayat-cashbon-item-mobile mb-3 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title-mobile mb-0">Pengajuan Tgl: <?php echo date('d M Y', strtotime($cb_item['tanggal'])); ?></h6>
                                                <div class="status-mobile ms-2"><?php echo formatStatusCashbon($cb_item['status']); ?></div>
                                            </div>
                                            <p class="card-text-mobile-meta small text-muted mb-2">
                                                Diajukan pada: <?php echo date('d M Y, H:i', strtotime($cb_item['created_at'])); ?>
                                            </p>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <strong class="label-mobile">Jumlah:</strong>
                                                    <span class="value-mobile d-block">Rp <?php echo number_format($cb_item['jumlah'], 0, ',', '.'); ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <strong class="label-mobile">Cicilan:</strong>
                                                    <span class="value-mobile d-block"><?php echo htmlspecialchars($cb_item['cicil']); ?> bulan</span>
                                                </div>
                                            </div>
                                            <?php if (!empty($cb_item['keterangan'])): ?>
                                                <div class="keterangan-mobile-container mt-2">
                                                    <strong class="label-mobile">Keterangan:</strong>
                                                    <p class="card-text-mobile keterangan-mobile mb-0">
                                                        <?php echo nl2br(htmlspecialchars($cb_item['keterangan'])); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalPagesCashbon > 1): ?>
                        <div class="card-footer bg-light no-print">
                            <nav aria-label="Page navigation Riwayat Cashbon">
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php if ($page_riwayat > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?page_cashbon=<?php echo $page_riwayat - 1; ?>">Sebelumnya</a></li>
                                    <?php endif; ?>
                                    <?php
                                    $start_page_cb = max(1, $page_riwayat - 2);
                                    $end_page_cb = min($totalPagesCashbon, $page_riwayat + 2);
                                    if ($page_riwayat <= 3) $end_page_cb = min($totalPagesCashbon, 5);
                                    if ($page_riwayat >= $totalPagesCashbon - 2) $start_page_cb = max(1, $totalPagesCashbon - 4);

                                    if ($start_page_cb > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    for ($pcb = $start_page_cb; $pcb <= $end_page_cb; $pcb++):
                                    ?>
                                        <li class="page-item <?php echo ($pcb == $page_riwayat) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page_cashbon=<?php echo $pcb; ?>"><?php echo $pcb; ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($end_page_cb < $totalPagesCashbon) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    ?>
                                    <?php if ($page_riwayat < $totalPagesCashbon): ?>
                                        <li class="page-item"><a class="page-link" href="?page_cashbon=<?php echo $page_riwayat + 1; ?>">Berikutnya</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="footer no-print">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>
            </div>
        </div>
    </div>
    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Skrip JS untuk menu aktif (sama seperti di form-pengajuan-cashbon.php)
        $(document).ready(function() {
            var currentPath = "<?php echo $current_page_basename; ?>";
            var cashbonFormPage = "form-pengajuan-cashbon.php";
            var cashbonHistoryPage = "riwayat-cashbon.php";
            // Target link menu cashbon, bisa jadi satu link untuk kedua halaman
            var cashbonMenuLink = $('.sidebar-menu a[href="' + cashbonFormPage + '"], .sidebar-menu a[href="' + cashbonHistoryPage + '"]');

            // Cek apakah ada link yang secara eksplisit menargetkan cashbonFormPage (misalnya dari menu lain)
            // Untuk sidebar, jika ada link "Pengajuan Cashbon" yang mungkin sama untuk kedua halaman:
            var targetSidebarLinkSelector = '.sidebar-menu a[href="form-pengajuan-cashbon.php"]';
            if (!$('.sidebar-menu a[href="riwayat-cashbon.php"]').length) { // Jika tidak ada link spesifik ke riwayat
                targetSidebarLinkSelector = '.sidebar-menu a[href*="cashbon"]'; // Ambil yang mengandung 'cashbon'
            }


            $('.sidebar-menu a.active').removeClass('active');
            if (currentPath === cashbonFormPage || currentPath === cashbonHistoryPage) {
                $(targetSidebarLinkSelector).addClass('active');
            } else {
                $('.sidebar-menu a').each(function() { // Fallback jika bukan halaman cashbon
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
            } else if (currentPath === "dashboard_karyawan.php") {
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }
        });
    </script>
</body>

</html>