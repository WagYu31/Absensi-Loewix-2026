<?php
session_start();

if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$pesan_sukses_flash = '';
if (isset($_SESSION['pesan_sukses_cuti'])) {
    $pesan_sukses_flash = $_SESSION['pesan_sukses_cuti'];
    unset($_SESSION['pesan_sukses_cuti']);
}

$loggedInUserNip = $_SESSION['nip'];
$pesan_error = '';
$current_year = '2026';

$holidays = [];
$sql_holidays = "SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND YEAR(tanggal_merah) = $current_year";
$result_holidays = $conn->query($sql_holidays);
if ($result_holidays) {
    while ($row = $result_holidays->fetch_assoc()) {
        if (!empty($row['tanggal_merah'])) {
            $holidays[$row['tanggal_merah']] = true;
        }
    }
    $result_holidays->close();
}

function hitungDurasiCuti($tgl_mulai, $tgl_selesai, $holidays = [])
{
    if (empty($tgl_mulai) || empty($tgl_selesai) || $tgl_mulai == '0000-00-00' || $tgl_selesai == '0000-00-00') {
        return 0;
    }
    try {
        $start = new DateTime($tgl_mulai);
        $end = new DateTime($tgl_selesai);
        if ($start > $end) return 0;
        
        $end->modify('+1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $duration = 0;
        
        foreach ($period as $date) {
            $dayOfWeek = $date->format('N');
            $dateString = $date->format('Y-m-d');
            if ($dayOfWeek != 7 && !isset($holidays[$dateString])) {
                $duration++;
            }
        }
        return $duration;
    } catch (Exception $e) {
        return 0;
    }
}

$global_jatah_cuti = 0;
$result_quota = $conn->query("SELECT jumlah FROM jatah_cuti_tahunan WHERE tahun = '$current_year' LIMIT 1");
if ($result_quota && $result_quota->num_rows > 0) {
    $row_quota = $result_quota->fetch_assoc();
    $global_jatah_cuti = (int)$row_quota['jumlah'];
}
$result_quota->close();

$tanggal_masuk_karyawan = null;
$stmt_tgl = $conn->prepare("SELECT tanggal_masuk FROM karyawan WHERE nip = ? LIMIT 1");
$stmt_tgl->bind_param("s", $loggedInUserNip);
$stmt_tgl->execute();
$result_tgl = $stmt_tgl->get_result();
if ($row_tgl = $result_tgl->fetch_assoc()) {
    $tanggal_masuk_karyawan = $row_tgl['tanggal_masuk'];
}
$stmt_tgl->close();

$jatah_cuti_karyawan_ini = 0;
if (!empty($tanggal_masuk_karyawan) && $tanggal_masuk_karyawan != '0000-00-00') {
    try {
        $today = new DateTime();
        $tgl_masuk_plus_6_bulan = (new DateTime($tanggal_masuk_karyawan))->modify('+6 months');
        if ($tgl_masuk_plus_6_bulan <= $today) {
            $jatah_cuti_karyawan_ini = $global_jatah_cuti;
        }
    } catch (Exception $e) {
        $jatah_cuti_karyawan_ini = 0;
    }
}

$stats = [
    'cuti_hak' => 0,
    'cuti_khusus' => 0,
    'cuti_lainnya' => 0,
    'terpakai_potong' => 0,
    'tidak_potong' => 0,
    'jatah' => $jatah_cuti_karyawan_ini,
    'sisa' => $jatah_cuti_karyawan_ini 
];

$sql_stats = $conn->prepare("SELECT tgl_mulai, tgl_selesai, jenis, potong_gaji 
                            FROM cuti 
                            WHERE verif = 'Disetujui' 
                            AND deleted_at IS NULL
                            AND nip = ?
                            AND tgl_selesai >= ? 
                            AND tgl_mulai <= ?");
$year_start = "$current_year-01-01";
$year_end = "$current_year-12-31";
$sql_stats->bind_param("sss", $loggedInUserNip, $year_start, $year_end);
$sql_stats->execute();
$result_stats = $sql_stats->get_result();

if ($result_stats) {
    while ($cuti = $result_stats->fetch_assoc()) {
        $durasi_hari_ini = hitungDurasiCuti($cuti['tgl_mulai'], $cuti['tgl_selesai'], $holidays);
        
        $jenis = strtolower($cuti['jenis']);
        $potong = (int)$cuti['potong_gaji'];

        if ($jenis == 'hak') {
            $stats['cuti_hak'] += $durasi_hari_ini;
        } elseif ($jenis == 'khusus') {
            $stats['cuti_khusus'] += $durasi_hari_ini;
        } elseif ($jenis == 'dipotong') {
            $stats['cuti_lainnya'] += $durasi_hari_ini;
        }

        if ($potong == 1) {
            $stats['terpakai_potong'] += $durasi_hari_ini;
        } else {
            $stats['tidak_potong'] += $durasi_hari_ini;
        }
    }
    $result_stats->close();
}
$sql_stats->close();
$stats['sisa'] = $stats['jatah'] - $stats['terpakai_potong'];


$riwayat_cuti_list = [];
$limit_riwayat = 25; 
$page_riwayat = isset($_GET['page_cuti']) ? (int)$_GET['page_cuti'] : 1;
$page_riwayat = max($page_riwayat, 1);
$offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

$totalResultCuti = $conn->query("SELECT COUNT(id) as total FROM cuti WHERE nip='$loggedInUserNip' AND deleted_at IS NULL");
$totalRowCuti = $totalResultCuti->fetch_assoc();
$totalDataCuti = $totalRowCuti['total'] ?? 0;
$totalPagesCuti = ceil($totalDataCuti / $limit_riwayat);


$stmt_riwayat = $conn->prepare("SELECT id, tgl_mulai, tgl_selesai, jenis, keterangan, bukti, verif, potong_gaji, created_at FROM cuti WHERE nip = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?");
if ($stmt_riwayat) {
    $stmt_riwayat->bind_param("sii", $loggedInUserNip, $limit_riwayat, $offset_riwayat);
    $stmt_riwayat->execute();
    $result_riwayat = $stmt_riwayat->get_result();
    while ($row_cuti = $result_riwayat->fetch_assoc()) {
        $riwayat_cuti_list[] = $row_cuti;
    }
    $stmt_riwayat->close();
} else {
    $pesan_error = "Gagal mengambil riwayat cuti: " . $conn->error;
}


function formatJenisCuti($jenis)
{
    switch (strtolower($jenis)) {
        case 'dipotong':
            return 'Cuti Lainnya';
        case 'khusus':
            return 'Cuti Khusus';
        case 'hak':
            return 'Cuti Hak';
        default:
            return ucfirst($jenis);
    }
}

function formatStatusVerif($status)
{
    switch (ucfirst(strtolower($status))) {
        case 'Pending':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        case 'Disetujui':
            return '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="fa-solid fa-check-circle me-1"></i>Disetujui</span>';
        case 'Ditolak':
            return '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="fa-solid fa-times-circle me-1"></i>Ditolak</span>';
        case 'Dibatalkan':
            return '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"><i class="fa-solid fa-ban me-1"></i>Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . htmlspecialchars($status) . '</span>';
    }
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
$nama_karyawan_login = $nama; 
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti - <?php echo htmlspecialchars($nama_karyawan_login); ?> - Grav-Tech</title>
    <meta name="description" content="Halaman pengajuan dan riwayat cuti karyawan Grav-Tech" />
    <meta name="keywords" content="cuti, pengajuan cuti, leave request, gravitti technology" />
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
    <link rel="stylesheet" href="../assets/css/pengajuan-cuti-styles.css">
    <style>
        .stat-card {
            background-color: #ffffff;
        }
        .stat-box {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }
        .stat-title {
            font-size: 0.8rem;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0;
        }
        .stat-value-sisa {
            font-size: 1.25rem;
            font-weight: 700;
            color: #198754;
            margin-bottom: 0;
        }
        .stat-value-terpakai {
            font-size: 1.25rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 0;
        }
        .stat-box-sisa {
            background-color: #d1e7dd;
            border-radius: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #b2d8c3;
        }
        .stat-box-terpakai {
            background-color: #f8d7da;
            border-radius: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #f5c2c7;
        }
    </style>
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">

        <div class="header-banner page-specific-header no-print pt-3">
            <div class="container-fluid px-lg-4">
                <h1>Pengajuan Cuti Karyawan</h1>
                <p>Ajukan cuti Anda dan lihat riwayat pengajuan di sini.</p>
            </div>
        </div>

    
        <div class="dashboard-content py-4 px-lg-4 px-3">
            <div class="card shadow-sm mb-3 stat-card">
                <div class="card-body p-3 p-lg-4">
                    <h5 class="card-title mb-3">Rekap Cuti Anda (<?php echo $current_year; ?>)</h5>
                    <div class="row g-2 g-lg-3 text-center">
                        <div class="col-4 col-md-4 col-lg-2">
                            <div class="stat-box">
                                <h6 class="stat-title">Jatah Cuti</h6>
                                <p class="stat-value"><?php echo $stats['jatah']; ?></p>
                            </div>
                        </div>
                        <div class="col-4 col-md-4 col-lg-2">
                            <div class="stat-box">
                                <h6 class="stat-title">Cuti Hak</h6>
                                <p class="stat-value"><?php echo $stats['cuti_hak']; ?></p>
                            </div>
                        </div>
                        <div class="col-4 col-md-4 col-lg-2">
                            <div class="stat-box">
                                <h6 class="stat-title">Cuti Khusus</h6>
                                <p class="stat-value"><?php echo $stats['cuti_khusus']; ?></p>
                            </div>
                        </div>
                        <div class="col-4 col-md-4 col-lg-2">
                            <div class="stat-box">
                                <h6 class="stat-title">Cuti Lainnya</h6>
                                <p class="stat-value"><?php echo $stats['cuti_lainnya']; ?></p>
                            </div>
                        </div>
                        <div class="col-4 col-md-4 col-lg-2">
                            <div class="stat-box-terpakai">
                                <h6 class="stat-title">Terpakai</h6>
                                <p class="stat-value-terpakai"><?php echo $stats['terpakai_potong']; ?></p>
                            </div>
                        </div>
                        <div class="col-4 col-md-4 col-lg-2">
                            <?php
                            $sisa_cuti_class = ($stats['sisa'] < 0) ? 'stat-value-terpakai' : 'stat-value-sisa';
                            $sisa_box_class = ($stats['sisa'] < 0) ? 'stat-box-terpakai' : 'stat-box-sisa';
                            ?>
                            <div class="<?php echo $sisa_box_class; ?>">
                                <h6 class="stat-title">Sisa Cuti</h6>
                                <p class="<?php echo $sisa_cuti_class; ?>"><?php echo $stats['sisa']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-content pt-0 mt-0">
            <div class="container-fluid px-lg-4 px-0">

                <?php if (!empty($pesan_sukses_flash)): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                        <i class="fa-solid fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($pesan_sukses_flash); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($pesan_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($pesan_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-0" id="pengajuan-cuti-card">
                    <div class="card-header d-md-flex d-block justify-content-between align-items-center">
                        
                        <div>
                            <span class="badge bg-warning text-dark"> </span>
                            <small class="text-muted ms-1 me-3">Cuti Khusus</small>
                            <span class="badge bg-danger"> </span>
                            <small class="text-muted ms-1 me-3">Cuti Lainnya</small>
                            <span class="badge bg-success"> </span>
                            <small class="text-muted ms-1">Cuti Hak</small>
                        </div>
                        
                        <a href="pengajuan-cuti.php" class="btn btn-primary mt-md-0 mt-3">
                             Pengajuan Cuti Baru
                        </a>
                    </div>
                </div>

                <div class="card-body p-0 mt-0"> <?php if (empty($riwayat_cuti_list)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                            <p>Belum ada riwayat pengajuan cuti.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover table-striped mb-0 riwayat-cuti-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>Tgl. Pengajuan</th>
                                        <th>Jenis Cuti</th>
                                        <th>Potong Cuti</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th>Durasi</th>
                                        <th class="d-none d-xl-table-cell">Keterangan</th>
                                        <th>Status</th>
                                        <th class="d-none d-lg-table-cell">Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no_riwayat_desktop = $offset_riwayat + 1;
                                            foreach ($riwayat_cuti_list as $cuti_desktop): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no_riwayat_desktop++; ?></td>
                                            <td><?php echo date('d M Y, H:i', strtotime($cuti_desktop['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $jenis_cuti_lower = strtolower($cuti_desktop['jenis']);
                                                $badge_class = '';
                                                if ($jenis_cuti_lower == 'hak') {
                                                    $badge_class = 'bg-success';
                                                } elseif ($jenis_cuti_lower == 'khusus') {
                                                    $badge_class = 'bg-warning text-dark';
                                                } elseif ($jenis_cuti_lower == 'dipotong') {
                                                    $badge_class = 'bg-danger';
                                                } else {
                                                    $badge_class = 'bg-secondary';
                                                }
                                                echo '<span class="badge ' . $badge_class . '">' . formatJenisCuti($cuti_desktop['jenis']) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo ($cuti_desktop['potong_gaji'] == 1) ? '<span class="text-danger fw-bold">Ya</span>' : 'Tidak'; ?>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($cuti_desktop['tgl_mulai'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($cuti_desktop['tgl_selesai'])); ?></td>
                                            <td class="text-center"><?php echo hitungDurasiCuti($cuti_desktop['tgl_mulai'], $cuti_desktop['tgl_selesai'], $holidays); ?> hr</td>
                                            <td class="keterangan-cuti d-none d-xl-table-cell" title="<?php echo htmlspecialchars($cuti_desktop['keterangan']); ?>">
                                                <?php echo htmlspecialchars(substr($cuti_desktop['keterangan'], 0, 40)) . (strlen($cuti_desktop['keterangan']) > 40 ? '...' : ''); ?>
                                            </td>
                                            <td class="text-center"><?php echo formatStatusVerif($cuti_desktop['verif']); ?></td>
                                            <td class="text-center d-none d-lg-table-cell">
                                                <?php if (!empty($cuti_desktop['bukti'])): ?>
                                                    <a href="../uploads/bukti_cuti/<?php echo htmlspecialchars($cuti_desktop['bukti']); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1" title="Lihat Bukti">
                                                        <i class="fa-solid fa-paperclip"></i>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="riwayat-cuti-list-mobile d-md-none p-0 mt-3">
                            <?php foreach ($riwayat_cuti_list as $cuti_mobile):
                                        $jenis_display_mob = formatJenisCuti($cuti_mobile['jenis']);
                                        $status_display_mob = formatStatusVerif($cuti_mobile['verif']);
                                        $durasi_display_mob = hitungDurasiCuti($cuti_mobile['tgl_mulai'], $cuti_mobile['tgl_selesai'], $holidays) . " hari";
                                        $tgl_pengajuan_display_mob = date('d M Y, H:i', strtotime($cuti_mobile['created_at']));
                                        $tgl_mulai_display_mob = date('d M Y', strtotime($cuti_mobile['tgl_mulai']));
                                        $tgl_selesai_display_mob = date('d M Y', strtotime($cuti_mobile['tgl_selesai']));
                                ?>
                                <div class="card riwayat-cuti-item-mobile mb-3 shadow-sm">
                                    <div class="card-body">
                                        <?php 
                                        $jenis_cuti_lower_mob = strtolower($cuti_mobile['jenis']);
                                        $badge_class_mob = '';
                                        if ($jenis_cuti_lower_mob == 'hak') {
                                            $badge_class_mob = 'badge bg-success';
                                        } elseif ($jenis_cuti_lower_mob == 'khusus') {
                                            $badge_class_mob = 'badge bg-warning text-dark';
                                        } elseif ($jenis_cuti_lower_mob == 'dipotong') {
                                            $badge_class_mob = 'badge bg-danger text-light';
                                        } else {
                                            $badge_class_mob = 'badge bg-secondary';
                                        }
                                        ?>
                                        
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title-mobile mb-0 <?php echo $badge_class_mob; ?>" style="font-size: 0.9rem;">
                                                <?php echo $jenis_display_mob; ?>
                                            </h6>
                                            <div class="status-mobile ms-2"><?php echo $status_display_mob; ?></div>
                                        </div>
                                        <p class="card-text-mobile-meta small text-muted mb-2">
                                            Diajukan: <?php echo $tgl_pengajuan_display_mob; ?>
                                        </p>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <strong class="label-mobile">Mulai:</strong>
                                                <span class="value-mobile d-block"><?php echo $tgl_mulai_display_mob; ?></span>
                                            </div>
                                            <div class="col-6">
                                                <strong class="label-mobile">Selesai:</strong>
                                                <span class="value-mobile d-block"><?php echo $tgl_selesai_display_mob; ?></span>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <strong class="label-mobile">Durasi:</strong>
                                                <span class="value-mobile"><?php echo $durasi_display_mob; ?></span>
                                            </div>
                                            <div class="col-6">
                                                <strong class="label-mobile">Potong Cuti:</strong>
                                                <span class="value-mobile"><?php echo ($cuti_mobile['potong_gaji'] == 1) ? '<span class="text-danger fw-bold">Ya</span>' : 'Tidak'; ?></span>
                                            </div>
                                        </div>
                                        <?php if (!empty($cuti_mobile['keterangan'])): ?>
                                            <div class="keterangan-mobile-container mt-2">
                                                <strong class="label-mobile">Keterangan:</strong>
                                                <p class="card-text-mobile keterangan-mobile mb-0">
                                                    <?php echo nl2br(htmlspecialchars($cuti_mobile['keterangan'])); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($cuti_mobile['bukti'])): ?>
                                            <p class="card-text-mobile mt-2 mb-0">
                                                <strong class="label-mobile">Bukti:</strong>
                                                <a href="../uploads/bukti_cuti/<?php echo htmlspecialchars($cuti_mobile['bukti']); ?>" target="_blank" class="link-bukti-mobile">
                                                    <i class="fa-solid fa-paperclip"></i> Lihat Bukti
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($totalPagesCuti > 1): ?>
                    <div class="card-footer bg-light no-print">
                        <nav aria-label="Page navigation Riwayat Cuti">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <?php if ($page_riwayat > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page_cuti=<?php echo $page_riwayat - 1; ?>#riwayat-cuti-card">Sebelumnya</a></li>
                                <?php endif; ?>
                                <?php
                                $start_page = max(1, $page_riwayat - 2);
                                $end_page = min($totalPagesCuti, $page_riwayat + 2);
                                if ($page_riwayat <= 3) $end_page = min($totalPagesCuti, 5);
                                if ($page_riwayat >= $totalPagesCuti - 2) $start_page = max(1, $totalPagesCuti - 4);

                                if ($start_page > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                for ($p = $start_page; $p <= $end_page; $p++):
                                ?>
                                    <li class="page-item <?php echo ($p == $page_riwayat) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page_cuti=<?php echo $p; ?>#riwayat-cuti-card"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor;
                                if ($end_page < $totalPagesCuti) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                ?>
                                <?php if ($page_riwayat < $totalPagesCuti): ?>
                                    <li class="page-item"><a class="page-link" href="?page_cuti=<?php echo $page_riwayat + 1; ?>#riwayat-cuti-card">Berikutnya</a></li>
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
        $(document).ready(function() {
            $('#tgl_mulai, #tgl_selesai').on('change', function() {
                const tglMulai = $('#tgl_mulai').val();
                const tglSelesai = $('#tgl_selesai').val();
                if (tglMulai && tglSelesai && (new Date(tglSelesai) < new Date(tglMulai))) {
                    alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                    $('#tgl_selesai').val(tglMulai); 
                }
                if (tglMulai) {
                    $('#tgl_selesai').attr('min', tglMulai);
                }
            });

            var today = new Date();
            today.setDate(today.getDate() + 1); 
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); 
            var yyyy = today.getFullYear();
            var minDate = yyyy + '-' + mm + '-' + dd;
            $('#tgl_mulai').attr('min', minDate);


            var currentPath = "<?php echo $current_page_basename; ?>";

            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            if (currentPath === "pengajuan-cuti.php" && !$('.sidebar-menu a[href="pengajuan-cuti.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="pengajuan-cuti.php"]').addClass('active');
            }

            $('.custom-nav__link.active').removeClass('active'); 
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
            } else if (currentPath === "dashboard_karyawan.php") {
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>