<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

function getDistanceBetweenPoints($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'meters')
{
    $earthRadius = ($unit === 'kilometers') ? 6371 : 6371000;

    $latFrom = deg2rad(floatval($latitude1));
    $lonFrom = deg2rad(floatval($longitude1));
    $latTo = deg2rad(floatval($latitude2));
    $lonTo = deg2rad(floatval($longitude2));

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

define('TARGET_OFFICE_LAT', -6.130189784035325);
define('TARGET_OFFICE_LON', 106.75142085117402);
define('MAX_OFFICE_RADIUS_METERS', 150);

$currentDay = date('l');
$isSaturday = ($currentDay === 'Saturday');

$current_page_basename = basename($_SERVER['PHP_SELF']);

$filter_bulan = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$nama_bulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Presensi - Gravitti HRIS</title>
    <meta name="description" content="Halaman presensi online karyawan Gravitti Technology" />
    <meta name="author" content="Gravitti Technology" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/presensi-styles.css">
    
    <style>
        :root {
            --hris-primary: #0f172a;
            --hris-accent: #2563eb;
            --hris-bg: #f8fafc;
            --hris-card-border: #e2e8f0;
        }

        body { 
            background-color: var(--hris-bg); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }

        /* Corporate Executive Top Header */
        .hris-header-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e40af 100%) !important;
            color: #ffffff;
            padding: 1.8rem 0 4.5rem 0 !important;
            margin-bottom: -55px !important;
            position: relative;
            z-index: 5;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hris-title {
            font-weight: 800 !important;
            font-size: 1.35rem !important;
            letter-spacing: -0.3px;
            color: #ffffff !important;
        }

        .hris-clock-badge {
            font-size: 1.15rem !important;
            font-weight: 800 !important;
            background: rgba(255, 255, 255, 0.12) !important;
            padding: 5px 16px !important;
            border-radius: 50px !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff !important;
        }

        .hris-user-card {
            background: rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-radius: 16px !important;
            padding: 1rem 1.25rem !important;
        }

        /* Summary Metric KPI Bar */
        .kpi-summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        @media (max-width: 991.98px) {
            .kpi-summary-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }

        .kpi-summary-card {
            background: #ffffff;
            border: 1px solid var(--hris-card-border);
            border-radius: 16px;
            padding: 14px 16px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .kpi-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .kpi-value {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
            color: #0f172a;
        }

        .kpi-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Filter Section */
        .filter-card { 
            border-radius: 16px !important; 
            background: #ffffff !important; 
            border: 1px solid var(--hris-card-border) !important;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03) !important;
        }

        .filter-card .form-select {
            border-radius: 10px !important;
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            padding: 8px 12px !important;
        }

        .btn-filter-submit {
            background: #2563eb !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            border-radius: 10px !important;
            padding: 8px 18px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
            transition: all 0.2s ease !important;
        }

        .btn-filter-submit:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35) !important;
        }

        /* Corporate Attendance Table (Desktop) */
        .table-hris-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid var(--hris-card-border);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .table-hris {
            margin-bottom: 0;
            width: 100%;
            vertical-align: middle;
        }

        .table-hris thead th {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-hris tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.15s ease;
        }

        .table-hris tbody tr:hover {
            background-color: #f8fafc;
        }

        .table-hris tbody td {
            padding: 14px 16px;
        }

        /* Date Column Badge */
        .hris-date-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 6px 12px;
            min-width: 54px;
            text-align: center;
        }

        .hris-date-num {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1;
            color: #0f172a;
        }

        .hris-date-month {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #2563eb;
            margin-top: 2px;
        }

        /* Attendance Photo Thumbnail (Fixed Aspect Ratio 4:3, Centered Face) */
        .att-photo-thumb {
            width: 84px;
            height: 96px;
            object-fit: cover !important;
            object-position: center top !important; /* Ensures human faces are ALWAYS centered and visible! */
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #f1f5f9;
        }

        .att-photo-thumb:hover {
            transform: scale(1.06);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
            border-color: #2563eb;
        }

        /* Mobile Attendance Cards (<768px) */
        .mobile-riwayat-card {
            background: #ffffff;
            border: 1px solid var(--hris-card-border);
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
        }

        .mobile-photo-box {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-top: 8px;
            background: #f1f5f9;
        }

        .mobile-photo-img {
            width: 100%;
            height: 130px;
            object-fit: cover !important;
            object-position: center 35% !important; /* Perfect human face focal alignment */
            display: block;
            transition: transform 0.2s ease;
        }

        .mobile-photo-img:active {
            transform: scale(0.97);
        }

        .status-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        @media (max-width: 767.98px) {
            .main-content-wrapper {
                padding-bottom: 110px !important; /* Prevents floating bottom nav from overlapping cards */
            }
            .dashboard-content {
                padding-bottom: 30px !important;
            }
            .kpi-summary-row {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
            }
            .kpi-summary-card {
                padding: 10px 12px !important;
            }
            .kpi-value {
                font-size: 1.15rem !important;
            }
            .kpi-icon-box {
                width: 36px !important;
                height: 36px !important;
                font-size: 0.95rem !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <!-- Header Section -->
        <div class="hris-header-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <div>
                        <h5 class="hris-title mb-0">
                            <i class="fa-solid fa-clipboard-user me-2 text-info"></i>RIWAYAT PRESENSI
                        </h5>
                        <small class="text-white-50" style="font-size: 0.78rem;">Laporan & Catatan Kehadiran Karyawan</small>
                    </div>
                    <div class="text-end">
                        <span id="realTimeClockDisplay" class="hris-clock-badge"><?php echo date('H:i:s'); ?></span>
                        <small class="d-block mt-1 text-white-50" style="font-size: 0.72rem;"><?php echo date('d F Y'); ?></small>
                    </div>
                </div>

                <!-- User Info Bar -->
                <div class="hris-user-card card border-0 mx-1">
                    <div class="d-flex align-items-center">
                        <?php
                        $user_photo_src = (!empty($photo) && file_exists(__DIR__ . '/../uploads/' . $photo)) ? '../uploads/' . htmlspecialchars($photo) : '';
                        $first_letter = strtoupper(substr($nama, 0, 1));
                        $avatar_svg_fallback = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'><rect width='60' height='60' rx='30' fill='%232563eb'/><text x='50%' y='55%' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='sans-serif' font-size='24' font-weight='bold'>{$first_letter}</text></svg>";
                        ?>
                        <img src="<?php echo !empty($user_photo_src) ? $user_photo_src : $avatar_svg_fallback; ?>"
                            alt="Foto Profil" class="me-3 rounded-circle shadow-sm"
                            style="width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(255,255,255,0.4);"
                            onerror="this.onerror=null; this.src='<?php echo $avatar_svg_fallback; ?>';">
                        <div>
                            <h6 class="mb-0 fw-bold text-white fs-6"><?php echo htmlspecialchars($nama); ?></h6>
                            <small class="text-white-50" style="font-size: 0.8rem;"><?php echo htmlspecialchars($jabatan); ?> &bull; NIK: <?php echo htmlspecialchars($nik); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content px-lg-4 px-md-3 px-2 mt-4">
            <div class="container p-0">

                <!-- Single DB Query & Monthly Summary Statistics -->
                <?php
                $nip_session = $_SESSION['nip'];
                $limit_riwayat = 10;
                $page_riwayat = isset($_GET['page_manual']) ? (int)$_GET['page_manual'] : 1;
                $page_riwayat = max($page_riwayat, 1);
                $offset_riwayat = ($page_riwayat - 1) * $limit_riwayat;

                $where_clause = "nip='$nip_session' AND MONTH(tgl_absen)='$filter_bulan' AND YEAR(tgl_absen)='$filter_tahun'";

                // 1 Single Optimized DB Query for the month
                $query_all = "SELECT tipe_absen, DATE(tgl_absen) AS tgl_date, TIME(tgl_absen) AS jam, verif, image, lokasi_absen, lokasi_koordinat 
                              FROM absen_manual 
                              WHERE $where_clause 
                              ORDER BY tgl_absen DESC";
                $result_all = mysqli_query($conn, $query_all);

                $grouped_by_date = [];
                $count_terlambat = 0;
                $total_seconds_month = 0;

                if ($result_all && mysqli_num_rows($result_all) > 0) {
                    while ($row = mysqli_fetch_assoc($result_all)) {
                        $tdate = $row['tgl_date'];
                        if (!isset($grouped_by_date[$tdate])) {
                            $grouped_by_date[$tdate] = ['masuk' => null, 'pulang' => null];
                        }
                        if ($row['tipe_absen'] === 'masuk' && !$grouped_by_date[$tdate]['masuk']) {
                            $grouped_by_date[$tdate]['masuk'] = $row;
                            if (strtotime($row['jam']) > strtotime('09:05:00')) {
                                $count_terlambat++;
                            }
                        } elseif ($row['tipe_absen'] === 'pulang' && !$grouped_by_date[$tdate]['pulang']) {
                            $grouped_by_date[$tdate]['pulang'] = $row;
                        }
                    }

                    // Calculate total work seconds for month
                    foreach ($grouped_by_date as $td => $recs) {
                        if ($recs['masuk'] && $recs['pulang']) {
                            $tm = strtotime($td . ' ' . $recs['masuk']['jam']);
                            $tp = strtotime($td . ' ' . $recs['pulang']['jam']);
                            if ($tp > $tm) {
                                $total_seconds_month += ($tp - $tm);
                            }
                        }
                    }
                }

                $total_hadir = count($grouped_by_date);
                $total_jam_month = floor($total_seconds_month / 3600);
                $total_menit_month = floor(($total_seconds_month % 3600) / 60);

                $totalDataRiwayat = $total_hadir;
                $totalPagesRiwayat = max(ceil($totalDataRiwayat / $limit_riwayat), 1);
                $all_dates = array_keys($grouped_by_date);
                $paged_dates = array_slice($all_dates, $offset_riwayat, $limit_riwayat);
                $query_params = "&bulan=$filter_bulan&tahun=$filter_tahun";
                ?>

                <!-- KPI Summary Cards -->
                <div class="kpi-summary-row">
                    <div class="kpi-summary-card">
                        <div class="kpi-icon-box bg-emerald-50 text-emerald-600" style="background: #ecfdf5; color: #059669;">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div>
                            <div class="kpi-value"><?php echo $total_hadir; ?> <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Hari</span></div>
                            <div class="kpi-label">Total Hadir</div>
                        </div>
                    </div>
                    
                    <div class="kpi-summary-card">
                        <div class="kpi-icon-box" style="background: #fff7ed; color: #ea580c;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <div class="kpi-value text-amber-600" style="color: #d97706;"><?php echo $count_terlambat; ?> <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Kali</span></div>
                            <div class="kpi-label">Terlambat</div>
                        </div>
                    </div>

                    <div class="kpi-summary-card">
                        <div class="kpi-icon-box" style="background: #eff6ff; color: #2563eb;">
                            <i class="fa-solid fa-business-time"></i>
                        </div>
                        <div>
                            <div class="kpi-value" style="color: #2563eb;"><?php echo $total_jam_month; ?><span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">j <?php echo $total_menit_month; ?>m</span></div>
                            <div class="kpi-label">Total Jam Kerja</div>
                        </div>
                    </div>

                    <div class="kpi-summary-card">
                        <div class="kpi-icon-box" style="background: #f8fafc; color: #475569;">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div>
                            <div class="kpi-value" style="font-size: 1.1rem; color: #334155;"><?php echo $nama_bulan[(int)$filter_bulan - 1]; ?> <?php echo $filter_tahun; ?></div>
                            <div class="kpi-label">Periode Laporan</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card filter-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <form method="GET" action="" class="row g-2 align-items-end">
                            <div class="col-6 col-md-4">
                                <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 600; margin-bottom: 4px;">
                                    <i class="fa-regular fa-calendar me-1"></i>Pilih Bulan
                                </label>
                                <select name="bulan" class="form-select">
                                    <?php
                                    for ($i = 1; $i <= 12; $i++) {
                                        $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        $selected = ($val == $filter_bulan) ? 'selected' : '';
                                        echo "<option value=\"$val\" $selected>{$nama_bulan[$i - 1]}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 600; margin-bottom: 4px;">
                                    <i class="fa-regular fa-calendar-days me-1"></i>Pilih Tahun
                                </label>
                                <select name="tahun" class="form-select">
                                    <?php
                                    $tahun_sekarang = date('Y');
                                    for ($t = $tahun_sekarang; $t >= $tahun_sekarang - 3; $t--) {
                                        $selected = ($t == $filter_tahun) ? 'selected' : '';
                                        echo "<option value=\"$t\" $selected>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-filter-submit w-100 py-2">
                                    <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Desktop Enterprise Table View (>= 768px) -->
                <div class="d-none d-md-block mb-4">
                    <div class="table-hris-card">
                        <?php if (count($paged_dates) > 0): ?>
                            <table class="table table-hris">
                                <thead>
                                    <tr>
                                        <th style="width: 14%;">Tanggal</th>
                                        <th style="width: 36%;">Masuk (Check-In)</th>
                                        <th style="width: 36%;">Pulang (Check-Out)</th>
                                        <th style="width: 14%; text-align: center;">Durasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paged_dates as $tgl_riwayat): 
                                        $data_hari_ini = $grouped_by_date[$tgl_riwayat];
                                        $timestamp = strtotime($tgl_riwayat);
                                        $daftar_hari = [
                                            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                                            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                                        ];
                                        $nama_hari_id = $daftar_hari[date('l', $timestamp)];

                                        // Work duration calculation
                                        $durasi_display = '-';
                                        if ($data_hari_ini['masuk'] && $data_hari_ini['pulang']) {
                                            $tm = strtotime($tgl_riwayat . ' ' . $data_hari_ini['masuk']['jam']);
                                            $tp = strtotime($tgl_riwayat . ' ' . $data_hari_ini['pulang']['jam']);
                                            if ($tp > $tm) {
                                                $d_sec = $tp - $tm;
                                                $durasi_display = floor($d_sec / 3600) . 'j ' . floor(($d_sec % 3600) / 60) . 'm';
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <!-- Date Cell -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="hris-date-badge">
                                                        <span class="hris-date-num"><?php echo date('d', $timestamp); ?></span>
                                                        <span class="hris-date-month"><?php echo date('M', $timestamp); ?></span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark fs-6"><?php echo $nama_hari_id; ?></div>
                                                        <div class="text-muted small" style="font-size: 0.72rem;"><?php echo date('Y', $timestamp); ?></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Check-In Cell -->
                                            <td>
                                                <?php if ($data_hari_ini['masuk']): 
                                                    $rec_m = $data_hari_ini['masuk'];
                                                    $lokasi_text_m = htmlspecialchars($rec_m['lokasi_absen'] ?: 'Di Kantor');
                                                    $img_filename_m = $rec_m['image'];
                                                    $img_src_m = '';
                                                    if (!empty($img_filename_m)) {
                                                        if (file_exists(__DIR__ . '/../uploads/attendance/' . $img_filename_m)) {
                                                            $img_src_m = '../uploads/attendance/' . htmlspecialchars($img_filename_m);
                                                        } elseif (file_exists(__DIR__ . '/../uploads/' . $img_filename_m)) {
                                                            $img_src_m = '../uploads/' . htmlspecialchars($img_filename_m);
                                                        } else {
                                                            $img_src_m = '../uploads/attendance/' . htmlspecialchars($img_filename_m);
                                                        }
                                                    }
                                                    $svg_fallback = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='120' viewBox='0 0 100 120'><rect width='100' height='120' rx='8' fill='%23f1f5f9'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='sans-serif' font-size='10' font-weight='bold'>Tidak Ada</text></svg>";
                                                ?>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($img_src_m)): ?>
                                                            <img src="<?php echo $img_src_m; ?>" 
                                                                 alt="Foto Masuk" 
                                                                 class="att-photo-thumb"
                                                                 loading="lazy"
                                                                 onclick="previewImage('<?php echo $img_src_m; ?>')"
                                                                 data-bs-toggle="modal" data-bs-target="#imagePreviewModal"
                                                                 onerror="if(this.src.indexOf('/uploads/attendance/')!=-1){this.src=this.src.replace('/uploads/attendance/','/uploads/');}else if(this.src.indexOf('/uploads/')!=-1){this.src=this.src.replace('/uploads/','/uploads/attendance/');}else{this.onerror=null;this.src='<?php echo $svg_fallback; ?>';}">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-extrabold text-dark fs-5 lh-1 mb-1"><?php echo htmlspecialchars($rec_m['jam']); ?></div>
                                                            <div class="text-muted small text-truncate mb-1" style="max-width: 220px;" title="<?php echo $lokasi_text_m; ?>">
                                                                <i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo $lokasi_text_m; ?>
                                                            </div>
                                                            <div>
                                                                <?php if ($rec_m['verif'] === 'Yes'): ?>
                                                                    <span class="status-pill bg-success-subtle text-success-emphasis"><i class="fa-solid fa-check"></i>Terverifikasi</span>
                                                                <?php elseif ($rec_m['verif'] === 'No'): ?>
                                                                    <span class="status-pill bg-danger-subtle text-danger-emphasis"><i class="fa-solid fa-xmark"></i>Ditolak</span>
                                                                <?php else: ?>
                                                                    <span class="status-pill bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-clock"></i>Pending</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small fw-medium"><i class="fa-regular fa-clock me-1 opacity-50"></i>Belum Absen Masuk</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Check-Out Cell -->
                                            <td>
                                                <?php if ($data_hari_ini['pulang']): 
                                                    $rec_p = $data_hari_ini['pulang'];
                                                    $lokasi_text_p = htmlspecialchars($rec_p['lokasi_absen'] ?: 'Di Kantor');
                                                    $img_filename_p = $rec_p['image'];
                                                    $img_src_p = '';
                                                    if (!empty($img_filename_p)) {
                                                        if (file_exists(__DIR__ . '/../uploads/attendance/' . $img_filename_p)) {
                                                            $img_src_p = '../uploads/attendance/' . htmlspecialchars($img_filename_p);
                                                        } elseif (file_exists(__DIR__ . '/../uploads/' . $img_filename_p)) {
                                                            $img_src_p = '../uploads/' . htmlspecialchars($img_filename_p);
                                                        } else {
                                                            $img_src_p = '../uploads/attendance/' . htmlspecialchars($img_filename_p);
                                                        }
                                                    }
                                                ?>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($img_src_p)): ?>
                                                            <img src="<?php echo $img_src_p; ?>" 
                                                                 alt="Foto Pulang" 
                                                                 class="att-photo-thumb"
                                                                 loading="lazy"
                                                                 onclick="previewImage('<?php echo $img_src_p; ?>')"
                                                                 data-bs-toggle="modal" data-bs-target="#imagePreviewModal"
                                                                 onerror="if(this.src.indexOf('/uploads/attendance/')!=-1){this.src=this.src.replace('/uploads/attendance/','/uploads/');}else if(this.src.indexOf('/uploads/')!=-1){this.src=this.src.replace('/uploads/','/uploads/attendance/');}else{this.onerror=null;this.src='<?php echo $svg_fallback; ?>';}">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-extrabold text-dark fs-5 lh-1 mb-1"><?php echo htmlspecialchars($rec_p['jam']); ?></div>
                                                            <div class="text-muted small text-truncate mb-1" style="max-width: 220px;" title="<?php echo $lokasi_text_p; ?>">
                                                                <i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo $lokasi_text_p; ?>
                                                            </div>
                                                            <div>
                                                                <?php if ($rec_p['verif'] === 'Yes'): ?>
                                                                    <span class="status-pill bg-success-subtle text-success-emphasis"><i class="fa-solid fa-check"></i>Terverifikasi</span>
                                                                <?php elseif ($rec_p['verif'] === 'No'): ?>
                                                                    <span class="status-pill bg-danger-subtle text-danger-emphasis"><i class="fa-solid fa-xmark"></i>Ditolak</span>
                                                                <?php else: ?>
                                                                    <span class="status-pill bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-clock"></i>Pending</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small fw-medium"><i class="fa-regular fa-clock me-1 opacity-50"></i>Belum Absen Pulang</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Work Duration Cell -->
                                            <td class="text-center">
                                                <span class="badge bg-blue-50 text-blue-700 fw-bold px-3 py-2" style="background: #eff6ff; color: #1d4ed8; font-size: 0.82rem; border-radius: 8px;">
                                                    <?php echo $durasi_display; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="p-5 text-center">
                                <i class="fa-solid fa-folder-open fa-3x text-muted opacity-40 mb-3"></i>
                                <h6 class="fw-bold text-dark mb-1">Belum Ada Presensi</h6>
                                <p class="text-muted small mb-0">Tidak ada catatan presensi pada periode bulan & tahun ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mobile Responsive Cards View (< 768px) -->
                <div class="d-block d-md-none mb-4">
                    <?php if (count($paged_dates) > 0): ?>
                        <?php foreach ($paged_dates as $tgl_riwayat): 
                            $data_hari_ini = $grouped_by_date[$tgl_riwayat];
                            $timestamp = strtotime($tgl_riwayat);
                            $daftar_hari = [
                                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                            ];
                            $nama_hari_id = $daftar_hari[date('l', $timestamp)];
                        ?>
                            <div class="mobile-riwayat-card">
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="hris-date-badge py-1 px-2" style="min-width: 46px;">
                                            <span class="hris-date-num fs-5"><?php echo date('d', $timestamp); ?></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 mb-0"><?php echo $nama_hari_id; ?></div>
                                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;"><?php echo date('d F Y', $timestamp); ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <?php foreach (['masuk', 'pulang'] as $tipe_presensi): ?>
                                        <div class="col-6">
                                            <div class="p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; height: 100%;">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="text-capitalize fw-bold text-slate-700 small"><?php echo $tipe_presensi; ?></span>
                                                    <?php if ($data_hari_ini[$tipe_presensi]): ?>
                                                        <span class="status-pill bg-success-subtle text-success-emphasis" style="font-size: 0.65rem; padding: 2px 6px;">OK</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($data_hari_ini[$tipe_presensi]): 
                                                    $rec_mob = $data_hari_ini[$tipe_presensi];
                                                    $img_filename_mob = $rec_mob['image'];
                                                    $img_src_mob = '';
                                                    if (!empty($img_filename_mob)) {
                                                        if (file_exists(__DIR__ . '/../uploads/attendance/' . $img_filename_mob)) {
                                                            $img_src_mob = '../uploads/attendance/' . htmlspecialchars($img_filename_mob);
                                                        } elseif (file_exists(__DIR__ . '/../uploads/' . $img_filename_mob)) {
                                                            $img_src_mob = '../uploads/' . htmlspecialchars($img_filename_mob);
                                                        } else {
                                                            $img_src_mob = '../uploads/attendance/' . htmlspecialchars($img_filename_mob);
                                                        }
                                                    }
                                                ?>
                                                    <div class="fw-bold text-dark fs-6 mb-1"><?php echo htmlspecialchars($rec_mob['jam']); ?></div>
                                                    
                                                    <?php if (!empty($img_src_mob)): ?>
                                                        <div class="mobile-photo-box" onclick="previewImage('<?php echo $img_src_mob; ?>')" data-bs-toggle="modal" data-bs-target="#imagePreviewModal">
                                                            <img src="<?php echo $img_src_mob; ?>" 
                                                                 alt="Foto <?php echo $tipe_presensi; ?>" 
                                                                 class="mobile-photo-img"
                                                                 loading="lazy"
                                                                 onerror="if(this.src.indexOf('/uploads/attendance/')!=-1){this.src=this.src.replace('/uploads/attendance/','/uploads/');}else if(this.src.indexOf('/uploads/')!=-1){this.src=this.src.replace('/uploads/','/uploads/attendance/');}else{this.onerror=null;this.src='<?php echo $svg_fallback; ?>';}">
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="text-muted small opacity-60 py-3 text-center">- Belum -</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card border-0 rounded-4 shadow-sm p-4 text-center bg-white my-3">
                            <div class="text-muted small">Belum ada data presensi pada bulan ini.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination Bar -->
                <?php if ($totalPagesRiwayat > 1): ?>
                    <nav aria-label="Navigasi Halaman Presensi" class="mt-3 mb-5">
                        <ul class="pagination pagination-md justify-content-center gap-1 mb-0">
                            <li class="page-item <?php echo ($page_riwayat <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-3 px-3 py-2 fw-semibold" href="?page_manual=<?php echo $page_riwayat - 1 . $query_params; ?>">
                                    <i class="fa-solid fa-chevron-left me-1"></i> Prev
                                </a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page_riwayat - 1);
                            $end_page = min($totalPagesRiwayat, $page_riwayat + 1);
                            for ($p = $start_page; $p <= $end_page; $p++): 
                            ?>
                                <li class="page-item <?php echo ($p == $page_riwayat) ? 'active' : ''; ?>">
                                    <a class="page-link rounded-3 px-3 py-2 fw-bold" href="?page_manual=<?php echo $p . $query_params; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page_riwayat >= $totalPagesRiwayat) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-3 px-3 py-2 fw-semibold" href="?page_manual=<?php echo $page_riwayat + 1 . $query_params; ?>">
                                    Next <i class="fa-solid fa-chevron-right ms-1"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>
        
        <div class="footer mt-5 pb-4">
            <div class="container text-center">
                <small class="text-muted fw-medium">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>.<br>Version 1.2.0</small>
            </div>
        </div>

    </div>
        
    <!-- Image Modal Preview -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg bg-dark text-white overflow-hidden">
                <div class="modal-header border-0 p-3 bg-slate-900 text-white d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold m-0"><i class="fa-solid fa-image me-2 text-info"></i>Preview Foto Presensi Fullsize</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center bg-black d-flex align-items-center justify-content-center" style="min-height: 380px;">
                    <img src="" id="modalPreviewImage" class="img-fluid w-100" style="max-height: 82vh; object-fit: contain;" alt="Preview Foto Absen">
                </div>
            </div>
        </div>
    </div>
        
    <?php include 'nav/bottom-nav.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function previewImage(imageSrc) {
        document.getElementById('modalPreviewImage').src = imageSrc;
    }
    </script>
</body>
</html>