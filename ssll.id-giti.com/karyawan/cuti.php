<?php
session_start();

if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

$nama_karyawan_login = $nama ?? $_SESSION['nama'] ?? 'Karyawan';

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
        case 'dipotong': return 'Cuti Lainnya';
        case 'khusus': return 'Cuti Khusus';
        case 'hak': return 'Cuti Hak';
        default: return ucfirst($jenis);
    }
}

function formatStatusVerif($status)
{
    $status_str = $status ? ucfirst(strtolower($status)) : '';
    switch ($status_str) {
        case 'Pending':
            return '<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        case 'Disetujui':
            return '<span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Disetujui</span>';
        case 'Ditolak':
            return '<span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>Ditolak</span>';
        default:
            return '<span class="badge bg-secondary rounded-pill px-3 py-1 fw-bold">' . htmlspecialchars($status ?? '') . '</span>';
    }
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti 3D - Gravitti Tech</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/footer.css?v=<?php echo $asset_version; ?>">

    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            --card-radius-lg: 24px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background: #f1f5f9 !important;
        }

        .main-content-wrapper {
            background: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%) !important;
            min-height: 100vh;
            padding-bottom: 100px !important;
        }

        .page-specific-header {
            background: var(--header-gradient) !important;
            color: #ffffff;
            padding: 2.25rem 0 4.5rem 0 !important;
            margin-bottom: -50px !important;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-specific-header h1 {
            font-weight: 800 !important;
            font-size: 1.65rem !important;
            letter-spacing: -0.5px;
            color: #ffffff !important;
        }

        /* 3D Glass Stat Cards Grid */
        .stat-card-3d {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05) !important;
            padding: 1rem 0.8rem !important;
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-card-3d:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.12) !important;
        }

        .stat-card-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .stat-card-val {
            font-size: 1.5rem;
            font-weight: 900;
            color: #1e293b;
            line-height: 1;
        }

        .stat-card-terpakai {
            background: linear-gradient(135deg, rgba(254, 226, 226, 0.8), rgba(254, 202, 202, 0.8)) !important;
            border: 1.5px solid #fca5a5 !important;
        }

        .stat-card-terpakai .stat-card-val {
            color: #dc2626 !important;
        }

        .stat-card-sisa {
            background: linear-gradient(135deg, rgba(209, 250, 229, 0.8), rgba(167, 243, 208, 0.8)) !important;
            border: 1.5px solid #6ee7b7 !important;
        }

        .stat-card-sisa .stat-card-val {
            color: #059669 !important;
        }

        /* 3D Action Card */
        .main-card-3d {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.12) !important;
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }

        .btn-pengajuan-3d {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.9rem !important;
            border-radius: 14px !important;
            border: none !important;
            padding: 10px 20px !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
            transition: all 0.2s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-pengajuan-3d:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.45), 0 4px 0 #1e40af !important;
            color: #ffffff !important;
        }

        .cuti-item-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            padding: 14px 18px;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
            transition: all 0.2s ease;
        }

        .cuti-item-card:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.12);
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-plane-departure me-2 text-primary-light"></i>Pengajuan Cuti Karyawan</h1>
                <p class="small mb-0 opacity-80">Ajukan cuti kerja Anda dan pantau status persetujuan secara real-time di sini.</p>
            </div>
        </div>

        <div class="dashboard-content px-0 pt-2">
            <div class="container-fluid px-lg-4">

                <!-- 3D Stat Summary Grid -->
                <div class="main-card-3d no-print mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-extrabold text-dark mb-0 fs-6"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Rekap Jatah Cuti Anda (<?php echo $current_year; ?>)</h6>
                        <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold text-white fs-7"><?php echo htmlspecialchars($nama_karyawan_login); ?></span>
                    </div>

                    <div class="row g-2">
                        <div class="col-4 col-md-2">
                            <div class="stat-card-3d">
                                <div class="stat-card-title">Jatah Cuti</div>
                                <div class="stat-card-val"><?php echo $stats['jatah']; ?></div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-card-3d">
                                <div class="stat-card-title">Cuti Hak</div>
                                <div class="stat-card-val text-primary"><?php echo $stats['cuti_hak']; ?></div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-card-3d">
                                <div class="stat-card-title">Cuti Khusus</div>
                                <div class="stat-card-val text-warning"><?php echo $stats['cuti_khusus']; ?></div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-card-3d">
                                <div class="stat-card-title">Cuti Lainnya</div>
                                <div class="stat-card-val text-purple"><?php echo $stats['cuti_lainnya']; ?></div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-card-3d stat-card-terpakai">
                                <div class="stat-card-title text-danger">Terpakai</div>
                                <div class="stat-card-val"><?php echo $stats['terpakai_potong']; ?></div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="stat-card-3d stat-card-sisa">
                                <div class="stat-card-title text-success">Sisa Cuti</div>
                                <div class="stat-card-val"><?php echo $stats['sisa']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar & Legend -->
                <div class="main-card-3d no-print mb-4 p-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-circle me-1"></i>Cuti Khusus</span>
                            <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-circle me-1"></i>Cuti Lainnya</span>
                            <span class="badge bg-success rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-circle me-1"></i>Cuti Hak</span>
                        </div>
                        <a href="pengajuan-cuti.php" class="btn btn-pengajuan-3d">
                            <i class="fa-solid fa-circle-plus fs-6"></i>Pengajuan Cuti Baru
                        </a>
                    </div>
                </div>

                <!-- History List -->
                <div class="main-card-3d p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-extrabold text-dark mb-0 fs-6"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Riwayat Pengajuan Cuti Anda</h6>
                        <span class="badge bg-light text-secondary border fw-bold rounded-pill px-3 py-1 fs-7"><?php echo count($riwayat_cuti_list); ?> Pengajuan</span>
                    </div>

                    <?php if (empty($riwayat_cuti_list)): ?>
                    <div class="text-center py-5 text-muted">
                        <div class="fs-1 mb-2 opacity-50">📂</div>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Pengajuan Cuti</h6>
                        <p class="small text-secondary mb-0">Klik tombol <strong>"Pengajuan Cuti Baru"</strong> di atas untuk mengajukan izin cuti kerja Anda.</p>
                    </div>
                    <?php else: ?>
                    <div>
                        <?php foreach ($riwayat_cuti_list as $row): 
                            $durasi = hitungDurasiCuti($row['tgl_mulai'], $row['tgl_selesai'], $holidays);
                            $tglMulaiFormatted = date('d M Y', strtotime($row['tgl_mulai']));
                            $tglSelesaiFormatted = date('d M Y', strtotime($row['tgl_selesai']));
                        ?>
                        <div class="cuti-item-card">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-extrabold text-primary fs-6"><?php echo formatJenisCuti($row['jenis']); ?></span>
                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small fw-bold"><i class="far fa-calendar-alt me-1"></i><?php echo $durasi; ?> Hari</span>
                                </div>
                                <div><?php echo formatStatusVerif($row['verif']); ?></div>
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="fa-regular fa-clock me-1 text-primary"></i><strong><?php echo $tglMulaiFormatted; ?></strong> s/d <strong><?php echo $tglSelesaiFormatted; ?></strong>
                            </div>
                            <?php if (!empty($row['keterangan'])): ?>
                            <div class="small text-secondary bg-light p-2 rounded-3 mt-2 border border-slate-100">
                                <strong>Keterangan:</strong> <?php echo htmlspecialchars($row['keterangan']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="footer mt-4">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.<br>
                    <small>Version 1.1.0</small>
                </div>

            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>