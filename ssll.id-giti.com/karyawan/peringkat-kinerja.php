<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php'; 

// === KONFIGURASI FILTER ===
$filter_type = $_GET['type'] ?? 'monthly';
$bulan_filter = $_GET['bulan'] ?? date('m');
$tahun_filter = $_GET['tahun'] ?? date('Y');
$active_tab = $_GET['tab'] ?? 'performance'; // Tab aktif
$sort_by = $_GET['sort'] ?? 'score'; // Default sort
$sort_order = $_GET['order'] ?? 'desc'; // Default order

if ($tahun_filter < 2026) {
    $tahun_filter = 2026;
}

$start_date = "";
$end_date = "";
$label_periode = "";
$hari_ini_str = date('Y-m-d');

if ($filter_type == 'yearly') {
    $start_date = "$tahun_filter-01-01";
    $end_date = "$tahun_filter-12-31";
    $label_periode = "Tahun $tahun_filter";
} else {
    $start_date = "$tahun_filter-$bulan_filter-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    $label_periode = date('F Y', strtotime($start_date));
}

// === 1. AMBIL DATA KARYAWAN ===
$employees_data = [];
$sql_kar = "SELECT nip, nik, nama, pas_photo, shifting, pin_absen FROM karyawan WHERE status_karyawan='aktif' AND nip NOT IN ('001','70326') ORDER BY nama ASC";
$res_kar = $conn->query($sql_kar);

if ($res_kar) {
    while ($k = $res_kar->fetch_assoc()) {
        $employees_data[$k['nip']] = [
            'nama' => $k['nama'],
            'nik' => $k['nik'],
            'photo' => $k['pas_photo'],
            'pin' => $k['pin_absen'] ?? $k['nip'],
            'shifting_default' => $k['shifting'],
            'total_jam_kerja' => 0,
            'total_overtime' => 0,
            'total_telat_menit' => 0,
            'total_tidak_absen' => 0,
            'total_cuti_days' => 0,
            'performance_score' => 0
        ];
    }
}

// === 2. AMBIL DATA LIBUR ===
$holidays = [];
$res_libur = $conn->query("SELECT tanggal_merah FROM kalender_kerja WHERE libur='yes' AND YEAR(tanggal_merah) = '$tahun_filter'");
if ($res_libur) {
    while ($l = $res_libur->fetch_assoc()) $holidays[$l['tanggal_merah']] = true;
}

// === 3. SETUP LOOPING TANGGAL ===
$start_dt = new DateTime($start_date);
$end_dt = new DateTime($end_date);
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_dt, $interval, $end_dt->modify('+1 day'));

// === 4. PROSES HITUNG DATA ===
foreach ($employees_data as $nip => &$emp) {
    $absen_harian = [];
    $target_nik = $emp['nik'];
    
    // Query Absen
    $sql_absen = "";
    if ($filter_type == 'yearly') {
        $sql_absen = "SELECT DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as tgl, 
                      MIN(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_masuk, 
                      MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_pulang 
                      FROM absen 
                      WHERE nip='$target_nik' 
                      AND YEAR(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter'
                      GROUP BY tgl";
    } else {
        $sql_absen = "SELECT DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as tgl, 
                      MIN(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_masuk, 
                      MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_pulang 
                      FROM absen 
                      WHERE nip='$target_nik' 
                      AND MONTH(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan_filter'
                      AND YEAR(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter'
                      GROUP BY tgl";
    }

    $res_absen = $conn->query($sql_absen);
    if ($res_absen) {
        while ($row = $res_absen->fetch_assoc()) {
            $absen_harian[$row['tgl']] = $row;
        }
    }

    // Query Shift
    $req_shifts = [];
    $res_req = $conn->query("SELECT tgl_mulai, tgl_selesai, shifting FROM shift_req WHERE nip='".$emp['pin']."' AND YEAR(tgl_mulai) = '$tahun_filter'");
    if($res_req){
        while ($rq = $res_req->fetch_assoc()) {
            $r_start = new DateTime($rq['tgl_mulai']);
            $r_end = new DateTime($rq['tgl_selesai']);
            $r_end->modify('+1 day');
            foreach (new DatePeriod($r_start, $interval, $r_end) as $d) {
                $req_shifts[$d->format('Y-m-d')] = $rq['shifting'];
            }
        }
    }

    // Query Cuti
    $sql_cuti = "SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip='$nip' AND verif LIKE 'Disetujui%' AND deleted_at IS NULL AND tgl_mulai <= '$end_date' AND tgl_selesai >= '$start_date'";
    $res_cuti = $conn->query($sql_cuti);
    if ($res_cuti) {
        while ($rc = $res_cuti->fetch_assoc()) {
            $c_start = new DateTime($rc['tgl_mulai']);
            $c_end = new DateTime($rc['tgl_selesai']);
            $c_end->modify('+1 day');
            $period_cuti = new DatePeriod($c_start, $interval, $c_end);
            
            foreach ($period_cuti as $dt_c) {
                $tgl_c = $dt_c->format('Y-m-d');
                if ($tgl_c < $start_date || $tgl_c > $end_date) continue;
                if ($tgl_c >= $hari_ini_str) continue;
                
                $is_sun = ($dt_c->format('N') == 7);
                $is_hol = isset($holidays[$tgl_c]);
                
                if (!$is_sun && !$is_hol) {
                    $emp['total_cuti_days']++;
                }
            }
        }
    }

    // Loop Hari
    foreach ($period as $date) {
        $tgl = $date->format('Y-m-d');
        if ($tgl >= $hari_ini_str) break;

        $dayName = $date->format('l');
        $is_sunday = ($dayName == 'Sunday');
        $is_holiday = isset($holidays[$tgl]);
        
        if (isset($absen_harian[$tgl])) {
            $data_absen = $absen_harian[$tgl];
            
            $masuk = new DateTime($data_absen['jam_masuk']);
            $pulang = new DateTime($data_absen['jam_pulang']);
            $jam_masuk_str = $masuk->format('H:i');
            $jam_pulang_str = ($masuk != $pulang) ? $pulang->format('H:i') : "-";
            
            $is_error_absen = false;
            
            if ($masuk == $pulang) {
                if ($jam_masuk_str >= "12:00") {
                    $emp['total_tidak_absen']++;
                    $is_error_absen = true;
                } else {
                    $emp['total_tidak_absen']++;
                }
            } else {
                if ($jam_masuk_str > "13:00") { 
                    $emp['total_tidak_absen']++; 
                    $is_error_absen = true; 
                }
                if ($jam_pulang_str < "11:00") {
                    $emp['total_tidak_absen']++;
                }
            }

            if (!$is_error_absen) {
                $current_shift = $req_shifts[$tgl] ?? $emp['shifting_default'];
                if ($dayName == "Saturday") $current_shift = ($current_shift == "T") ? "TW" : "W";

                $jam_masuk_target = "09:00";
                $std_hours = 9;

                if ($is_sunday || $is_holiday) $std_hours = 0;
                elseif ($dayName == "Saturday") $std_hours = 4.5;
                else $std_hours = 9;
                
                switch ($current_shift) {
                    case "P": $jam_masuk_target = "07:00"; break;
                    case "M": $jam_masuk_target = "08:30"; break;
                    case "N": $jam_masuk_target = "09:00"; break;
                    case "S": $jam_masuk_target = "09:30"; break;
                    case "T": $jam_masuk_target = "09:10"; break;
                    case "W": $jam_masuk_target = "08:30"; break;
                    case "TW": $jam_masuk_target = "09:10"; break;
                    default: $jam_masuk_target = "09:00"; break;
                }

                $durasi_detik = $pulang->getTimestamp() - $masuk->getTimestamp();
                $durasi_jam = $durasi_detik / 3600;
                $emp['total_jam_kerja'] += floor($durasi_jam);
                
                if ($durasi_jam > $std_hours) $emp['total_overtime'] += floor($durasi_jam - $std_hours);

                $target_ts = strtotime("$tgl $jam_masuk_target:00");
                if ($masuk->getTimestamp() > $target_ts) {
                    $emp['total_telat_menit'] += floor(($masuk->getTimestamp() - $target_ts) / 60);
                }
            }
        }
    }
}

// Hitung Skor
foreach ($employees_data as $nip => &$d) {
    $d['performance_score'] = $d['total_jam_kerja'] + ($d['total_overtime'] * 0.5);
}
unset($d);

// === 5. LOGIKA SORTING ===
function sortData($data, $key, $order) {
    usort($data, function($a, $b) use ($key, $order) {
        if ($a[$key] == $b[$key]) return 0;
        if ($order == 'asc') return ($a[$key] < $b[$key]) ? -1 : 1;
        return ($a[$key] > $b[$key]) ? -1 : 1;
    });
    return $data;
}

$list_performance = $employees_data;
if ($active_tab == 'performance') {
    $sort_key = match($sort_by) {
        'jam' => 'total_jam_kerja',
        'lembur' => 'total_overtime',
        'score' => 'performance_score',
        default => 'performance_score'
    };
    $list_performance = sortData($list_performance, $sort_key, $sort_order);
} else {
    $list_performance = sortData($list_performance, 'performance_score', 'desc');
}

$list_discipline = $employees_data;
if ($active_tab == 'discipline') {
    $sort_key = match($sort_by) {
        'tidak_absen' => 'total_tidak_absen',
        'terlambat' => 'total_telat_menit',
        default => 'total_telat_menit'
    };
    $list_discipline = sortData($list_discipline, $sort_key, $sort_order);
} else {
    $list_discipline = sortData($list_discipline, 'total_telat_menit', 'desc');
}

$list_cuti = sortData($employees_data, 'total_cuti_days', 'desc');

function getSortLink($tab, $col, $currentSort, $currentOrder) {
    global $filter_type, $bulan_filter, $tahun_filter;
    $newOrder = ($currentSort == $col && $currentOrder == 'desc') ? 'asc' : 'desc';
    return "peringkat-kinerja.php?type=$filter_type&bulan=$bulan_filter&tahun=$tahun_filter&tab=$tab&sort=$col&order=$newOrder";
}

function getSortIcon($col, $currentSort, $currentOrder) {
    if ($currentSort != $col) return '<i class="fas fa-sort text-muted ms-1 opacity-25"></i>';
    return ($currentOrder == 'desc') ? '<i class="fas fa-sort-down ms-1"></i>' : '<i class="fas fa-sort-up ms-1"></i>';
}

$bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringkat Kinerja 3D - Gravitti Tech</title>

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

        /* 3D Glass Filter Card */
        .filter-card-3d {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.12) !important;
            padding: 1.25rem !important;
            margin-bottom: 1.25rem !important;
        }

        .btn-update-3d {
            background: var(--primary-3d) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            border-radius: 14px !important;
            border: none !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35), 0 3px 0 #1d4ed8 !important;
            transition: all 0.2s ease !important;
        }

        .btn-update-3d:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.45), 0 4px 0 #1e40af !important;
            color: #ffffff !important;
        }

        /* 3D Glass Tabs */
        .rank-tabs-wrapper {
            background: #e2e8f0;
            padding: 5px;
            border-radius: 18px;
            display: flex;
            gap: 6px;
            margin-bottom: 1.25rem;
        }

        .rank-tab-link {
            flex: 1;
            text-align: center;
            padding: 10px 14px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.85rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .rank-tab-link.active {
            background: #ffffff;
            color: #2563eb;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }

        /* 3D Rank Badges */
        .rank-badge-3d {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .rank-1-3d {
            background: linear-gradient(135deg, #fbbf24, #f59e0b) !important;
            color: #451a03 !important;
            border: 2px solid #ffffff;
            box-shadow: 0 6px 18px rgba(245, 158, 11, 0.45) !important;
        }

        .rank-2-3d {
            background: linear-gradient(135deg, #cbd5e1, #94a3b8) !important;
            color: #0f172a !important;
            border: 2px solid #ffffff;
            box-shadow: 0 6px 18px rgba(148, 163, 184, 0.45) !important;
        }

        .rank-3-3d {
            background: linear-gradient(135deg, #f97316, #ea580c) !important;
            color: #ffffff !important;
            border: 2px solid #ffffff;
            box-shadow: 0 6px 18px rgba(234, 88, 12, 0.45) !important;
        }

        .rank-other-3d {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        /* Rank Row Item Card */
        .rank-item-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 12px 18px;
            margin-bottom: 10px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            transition: all 0.2s ease;
        }

        .rank-item-card:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.12);
        }

        .user-avatar-ring {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .sort-chip {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .sort-chip.active {
            background: rgba(37, 99, 235, 0.1);
            border-color: #2563eb;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1><i class="fa-solid fa-trophy me-2 text-warning"></i>Detail Peringkat Kinerja</h1>
                <p class="small mb-0 opacity-80">Data lengkap urutan kinerja, kedisiplinan jam kerja, dan pengajuan cuti karyawan.</p>
            </div>
        </div>

        <div class="dashboard-content px-0 pt-2">
            <div class="container-fluid px-lg-4">

                <!-- 3D Filter Card -->
                <div class="filter-card-3d no-print">
                    <form method="GET" action="peringkat-kinerja.php" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                        <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                        <input type="hidden" name="order" value="<?php echo $sort_order; ?>">

                        <div class="col-md-3 col-6">
                            <select name="type" class="form-select form-select-sm rounded-3 fw-semibold" onchange="this.form.submit()">
                                <option value="monthly" <?php if($filter_type == 'monthly') echo 'selected'; ?>>Bulanan</option>
                                <option value="yearly" <?php if($filter_type == 'yearly') echo 'selected'; ?>>Tahunan</option>
                            </select>
                        </div>
                        <?php if ($filter_type == 'monthly'): ?>
                        <div class="col-md-3 col-6">
                            <select name="bulan" class="form-select form-select-sm rounded-3 fw-semibold">
                                <?php foreach($bulanNames as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php if($bulan_filter == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3 col-6">
                            <select name="tahun" class="form-select form-select-sm rounded-3 fw-semibold">
                                <?php 
                                $tahun_mulai = 2026; 
                                $tahun_skrg = date('Y');
                                $tahun_akhir = max($tahun_mulai, $tahun_skrg);
                                for($i = $tahun_mulai; $i <= $tahun_akhir; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if($tahun_filter == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-6">
                            <button type="submit" class="btn btn-update-3d btn-sm w-100 py-2"><i class="fas fa-sync-alt me-1"></i> Update Data</button>
                        </div>
                    </form>
                </div>

                <!-- 3D Glass Tabs -->
                <div class="rank-tabs-wrapper">
                    <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=performance&sort=score&order=desc" class="rank-tab-link <?php if($active_tab == 'performance') echo 'active'; ?>"><i class="fas fa-trophy me-1 text-warning"></i>Performance</a>
                    <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=discipline&sort=terlambat&order=desc" class="rank-tab-link <?php if($active_tab == 'discipline') echo 'active'; ?>"><i class="fas fa-exclamation-triangle me-1 text-danger"></i>Terlambat</a>
                    <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=leaves" class="rank-tab-link <?php if($active_tab == 'leaves') echo 'active'; ?>"><i class="fas fa-umbrella-beach me-1 text-primary"></i>Cuti</a>
                </div>

                <!-- Tab 1: Performance -->
                <?php if ($active_tab == 'performance'): ?>
                <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1">
                    <span class="small fw-bold text-secondary me-2">Urutkan:</span>
                    <a href="<?php echo getSortLink('performance', 'score', $sort_by, $sort_order); ?>" class="sort-chip <?php echo ($sort_by == 'score') ? 'active' : ''; ?>">Total Score <?php echo getSortIcon('score', $sort_by, $sort_order); ?></a>
                    <a href="<?php echo getSortLink('performance', 'jam', $sort_by, $sort_order); ?>" class="sort-chip <?php echo ($sort_by == 'jam') ? 'active' : ''; ?>">Jam Kerja <?php echo getSortIcon('jam', $sort_by, $sort_order); ?></a>
                    <a href="<?php echo getSortLink('performance', 'lembur', $sort_by, $sort_order); ?>" class="sort-chip <?php echo ($sort_by == 'lembur') ? 'active' : ''; ?>">Lembur <?php echo getSortIcon('lembur', $sort_by, $sort_order); ?></a>
                </div>

                <div>
                    <?php $rank = 1; foreach ($list_performance as $p): 
                        $badge_class = ($rank == 1) ? 'rank-1-3d' : (($rank == 2) ? 'rank-2-3d' : (($rank == 3) ? 'rank-3-3d' : 'rank-other-3d'));
                        $photo_url = $p['photo'] ? "../uploads/".$p['photo'] : "https://via.placeholder.com/50/2563eb/ffffff?Text=".substr($p['nama'], 0, 1);
                    ?>
                    <div class="rank-item-card">
                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                            <div class="rank-badge-3d <?php echo $badge_class; ?>"><?php echo $rank; ?></div>
                            <img src="<?php echo $photo_url; ?>" class="user-avatar-ring" onerror="this.onerror=null; this.src='https://via.placeholder.com/50/2563eb/ffffff?Text=👤';">
                            <div style="min-width: 0;">
                                <div class="fw-bold text-dark fs-6" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($p['nama']); ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    <span><i class="far fa-clock me-1 text-primary"></i><?php echo $p['total_jam_kerja']; ?>h</span>
                                    <span class="ms-2 text-success fw-bold"><i class="fas fa-plus-circle me-1"></i><?php echo $p['total_overtime']; ?>h Lembur</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-extrabold text-primary fs-5"><?php echo $p['performance_score']; ?></div>
                            <div class="text-muted small" style="font-size: 0.7rem;">Score</div>
                        </div>
                    </div>
                    <?php $rank++; endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Tab 2: Discipline (Terlambat) -->
                <?php if ($active_tab == 'discipline'): ?>
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-gavel text-danger fs-5"></i>
                        <div>
                            <strong class="small">Pasal 36 (Tata Tertib Keterlambatan)</strong>
                            <div class="small opacity-80">Teguran jika Terlambat > 5x (atau total > 10 jam) atau Tidak Absen > 2x dalam sebulan.</div>
                        </div>
                    </div>
                </div>

                <div>
                    <?php $rank = 1; foreach ($list_discipline as $d): 
                        $badge_class = ($rank <= 3) ? 'bg-danger text-white border-0 shadow-sm' : 'rank-other-3d';
                        $photo_url = $d['photo'] ? "../uploads/".$d['photo'] : "https://via.placeholder.com/50/dc2626/ffffff?Text=".substr($d['nama'], 0, 1);
                    ?>
                    <div class="rank-item-card">
                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                            <div class="rank-badge-3d <?php echo $badge_class; ?>"><?php echo $rank; ?></div>
                            <img src="<?php echo $photo_url; ?>" class="user-avatar-ring" onerror="this.onerror=null; this.src='https://via.placeholder.com/50/dc2626/ffffff?Text=👤';">
                            <div style="min-width: 0;">
                                <div class="fw-bold text-dark fs-6" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($d['nama']); ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    <span class="text-danger fw-semibold"><i class="fas fa-user-times me-1"></i>Tidak Absen: <?php echo $d['total_tidak_absen']; ?>x</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-extrabold text-danger fs-5"><?php echo $d['total_telat_menit']; ?> <span class="fs-7 fw-bold">m</span></div>
                            <div class="text-muted small" style="font-size: 0.7rem;">Terlambat</div>
                        </div>
                    </div>
                    <?php $rank++; endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Tab 3: Cuti -->
                <?php if ($active_tab == 'leaves'): ?>
                <div>
                    <?php $rank = 1; foreach ($list_cuti as $c): 
                        $badge_class = ($rank <= 3) ? 'bg-primary text-white border-0 shadow-sm' : 'rank-other-3d';
                        $photo_url = $c['photo'] ? "../uploads/".$c['photo'] : "https://via.placeholder.com/50/2563eb/ffffff?Text=".substr($c['nama'], 0, 1);
                    ?>
                    <div class="rank-item-card">
                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                            <div class="rank-badge-3d <?php echo $badge_class; ?>"><?php echo $rank; ?></div>
                            <img src="<?php echo $photo_url; ?>" class="user-avatar-ring" onerror="this.onerror=null; this.src='https://via.placeholder.com/50/2563eb/ffffff?Text=👤';">
                            <div style="min-width: 0;">
                                <div class="fw-bold text-dark fs-6" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($c['nama']); ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;">NIK: <?php echo $c['nik']; ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-extrabold text-primary fs-5"><?php echo $c['total_cuti_days']; ?> <span class="fs-7 fw-bold">Hari</span></div>
                            <div class="text-muted small" style="font-size: 0.7rem;">Total Cuti</div>
                        </div>
                    </div>
                    <?php $rank++; endforeach; ?>
                </div>
                <?php endif; ?>

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