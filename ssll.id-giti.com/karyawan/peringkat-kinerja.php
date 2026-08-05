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
$sql_kar = "SELECT nip, nik, nama, shifting, pin_absen FROM karyawan WHERE status_karyawan='aktif' AND nip NOT IN ('001','70326') ORDER BY nama ASC";
$res_kar = $conn->query($sql_kar);

if ($res_kar) {
    while ($k = $res_kar->fetch_assoc()) {
        $employees_data[$k['nip']] = [
            'nama' => $k['nama'],
            'nik' => $k['nik'],
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
while ($l = $res_libur->fetch_assoc()) $holidays[$l['tanggal_merah']] = true;

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

// List Performance (Sortable: jam, lembur, score)
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
    // Default sort score desc
    $list_performance = sortData($list_performance, 'performance_score', 'desc');
}

// List Discipline (Sortable: tidak_absen, terlambat)
$list_discipline = $employees_data;
if ($active_tab == 'discipline') {
    $sort_key = match($sort_by) {
        'tidak_absen' => 'total_tidak_absen',
        'terlambat' => 'total_telat_menit',
        default => 'total_telat_menit'
    };
    $list_discipline = sortData($list_discipline, $sort_key, $sort_order);
} else {
    // Default sort terlambat desc
    $list_discipline = sortData($list_discipline, 'total_telat_menit', 'desc');
}

// List Cuti (Default desc)
$list_cuti = $employees_data;
$list_cuti = sortData($list_cuti, 'total_cuti_days', 'desc');

// Helper function untuk URL sorting
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

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Karyawan - Grav-Tech Salary</title>
    <meta name="description" content="Halaman profil karyawan Grav-Tech Salary" />
    <meta name="keywords" content="profile, karyawan, salary, gaji, gravitti technology" />
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
    <style>
        .rank-badge { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.9rem; margin: 0 auto; }
        .rank-1 { background-color: #FFD700; color: #fff; box-shadow: 0 2px 5px rgba(255, 215, 0, 0.4); }
        .rank-2 { background-color: #C0C0C0; color: #fff; box-shadow: 0 2px 5px rgba(192, 192, 192, 0.4); }
        .rank-3 { background-color: #CD7F32; color: #fff; box-shadow: 0 2px 5px rgba(205, 127, 50, 0.4); }
        .rank-other { background-color: #e9ecef; color: #6c757d; }
        
        .table-custom th { background-color: #f8f9fa; vertical-align: middle; cursor: pointer; user-select: none; }
        .table-custom th a { text-decoration: none; color: inherit; display: block; width: 100%; height: 100%; }
        .table-custom td { vertical-align: middle; }
        
        /* Desktop Tabs */
        .nav-tabs .nav-link { color: #6c757d; font-weight: 500; border: none; border-bottom: 3px solid transparent; transition: all 0.3s; white-space: nowrap; }
        .nav-tabs .nav-link:hover { color: #0d6efd; background-color: transparent; border-color: transparent; }
        .nav-tabs .nav-link.active { color: #0d6efd; background-color: transparent; border-bottom: 3px solid #0d6efd; font-weight: 700; }
        
        .score-val { font-size: 1.1rem; font-weight: 700; }
        .avatar-cell { width: 50px; text-align: center; }
        .avatar-initial { width: 35px; height: 35px; background-color: #e9ecef; color: #495057; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto; }
        
        /* Mobile List View & Compact Styling */
        .mobile-list-item { 
            border-bottom: 1px solid #f0f0f0; 
            padding: 10px 10px; /* Reduced padding */
            display: flex; 
            align-items: center; 
            gap: 10px; /* Reduced gap */
        }
        .mobile-list-item:last-child { border-bottom: none; }
        .mobile-rank { min-width: 30px; text-align: center; }
        
        .mobile-rank .rank-badge {
            width: 26px; height: 26px; font-size: 0.8rem; /* Smaller badge for mobile */
        }
        
        .mobile-content { flex: 1; min-width: 0; /* Ensures text truncation works if needed */ }
        .mobile-content .name-text { font-size: 0.95rem; font-weight: 700; color: #212529; line-height: 1.2; }
        .mobile-content .nik-text { font-size: 0.75rem; color: #6c757d; margin-top: 1px;}
        
        .mobile-stats { 
            display: flex; 
            gap: 10px; 
            font-size: 0.75rem; 
            margin-top: 4px; 
            color: #6c757d; 
            align-items: center;
        }
        .mobile-stat-item { display: flex; align-items: center; gap: 4px; }
        
        .mobile-score { 
            font-weight: bold; 
            font-size: 1rem; 
            min-width: 40px; 
            text-align: right; 
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .table-responsive { display: none; } /* Hide table on mobile */
            .mobile-view-container { display: block; } /* Show list view */
            
            /* Compact Filter & Tabs */
            .card-body.p-3 { padding: 1rem !important; }
            .card-header-tabs { 
                flex-wrap: nowrap; 
                overflow-x: auto; 
                scrollbar-width: none; 
            }
            .card-header-tabs::-webkit-scrollbar { display: none; }
            .nav-tabs .nav-link { 
                font-size: 0.85rem; 
                padding: 0.5rem 0.5rem; 
            }
            .nav-item { flex: 1; text-align: center; } /* Distribute tabs evenly */
            
            /* Sort Options Compact */
            .sort-options { 
                display: flex; 
                gap: 8px; 
                overflow-x: auto; 
                padding-bottom: 5px; 
                margin-bottom: 10px; 
                white-space: nowrap;
            }
            .sort-options::-webkit-scrollbar { display: none; }
            .sort-btn { 
                font-size: 0.75rem; 
                padding: 4px 10px; 
                border-radius: 15px; 
                border: 1px solid #dee2e6; 
                background: #fff; 
                color: #6c757d; 
                text-decoration: none; 
            }
            .sort-btn.active { 
                background: #e7f1ff; 
                color: #0d6efd; 
                border-color: #0d6efd; 
                font-weight: 600; 
            }
        }
        @media (min-width: 769px) {
            .mobile-view-container { display: none; }
            .table-responsive { display: block; }
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Detail Peringkat Kinerja</h1>
                <p>Data lengkap urutan kinerja, kedisiplinan, dan cuti karyawan.</p>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <form method="GET" action="peringkat-kinerja.php" class="row g-2 align-items-center">
                            <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                            <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">

                            <div class="col-md-3 col-6">
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="monthly" <?php if($filter_type == 'monthly') echo 'selected'; ?>>Bulanan</option>
                                    <option value="yearly" <?php if($filter_type == 'yearly') echo 'selected'; ?>>Tahunan</option>
                                </select>
                            </div>
                            <?php if ($filter_type == 'monthly'): ?>
                            <div class="col-md-3 col-6">
                                <select name="bulan" class="form-select form-select-sm">
                                    <?php foreach($bulanNames as $k => $v): ?>
                                        <option value="<?php echo $k; ?>" <?php if($bulan_filter == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3 col-6">
                                <select name="tahun" class="form-select form-select-sm">
                                    <?php 
                                    $tahun_mulai = 2026; 
                                    $tahun_skrg = date('Y');
                                    $tahun_akhir = max($tahun_mulai, $tahun_skrg);
                                    for($i = $tahun_mulai; $i <= $tahun_akhir; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php if($tahun_filter == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-sync-alt me-1"></i> Update</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-2 pb-0 px-0">
                        <ul class="nav nav-tabs card-header-tabs nav-fill mx-0" id="rankingTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=performance&sort=score&order=desc" class="nav-link <?php if($active_tab == 'performance') echo 'active'; ?>"><i class="fas fa-trophy d-none d-md-inline me-1"></i>Performance</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=discipline&sort=terlambat&order=desc" class="nav-link <?php if($active_tab == 'discipline') echo 'active'; ?>"><i class="fas fa-exclamation-circle d-none d-md-inline me-1"></i>Terlambat</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=leaves" class="nav-link <?php if($active_tab == 'leaves') echo 'active'; ?>"><i class="fas fa-umbrella-beach d-none d-md-inline me-1"></i>Cuti</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <div class="tab-pane fade <?php if($active_tab == 'performance') echo 'show active'; ?>" id="performance">
                                <div class="px-3 pt-2 pb-1 bg-light-subtle d-block d-md-none">
                                    <small class="text-muted fst-italic" style="font-size: 0.7rem;">*Score = Jam Kerja + (0.5 x Overtime)</small>
                                </div>
                                <div class="px-4 py-2 d-none d-md-block">
                                    <small class="text-muted">*Score = Jam Kerja + (0.5 x Overtime)</small>
                                </div>
                                
                                <div class="mobile-view-container px-3 pt-2">
                                    <div class="sort-options">
                                        <a href="<?php echo getSortLink('performance', 'score', $sort_by, $sort_order); ?>" class="sort-btn <?php echo ($sort_by == 'score') ? 'active' : ''; ?>">Score <?php echo getSortIcon('score', $sort_by, $sort_order); ?></a>
                                        <a href="<?php echo getSortLink('performance', 'jam', $sort_by, $sort_order); ?>" class="sort-btn <?php echo ($sort_by == 'jam') ? 'active' : ''; ?>">Jam Kerja <?php echo getSortIcon('jam', $sort_by, $sort_order); ?></a>
                                        <a href="<?php echo getSortLink('performance', 'lembur', $sort_by, $sort_order); ?>" class="sort-btn <?php echo ($sort_by == 'lembur') ? 'active' : ''; ?>">Lembur <?php echo getSortIcon('lembur', $sort_by, $sort_order); ?></a>
                                    </div>
                                    
                                    <?php $rank = 1; foreach ($list_performance as $p): 
                                        $badge_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : (($rank == 3) ? 'rank-3' : 'rank-other'));
                                    ?>
                                    <div class="mobile-list-item">
                                        <div class="mobile-rank"><div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div></div>
                                        <div class="mobile-content">
                                            <div class="name-text"><?php echo htmlspecialchars($p['nama']); ?></div>
                                            <!--<div class="nik-text"><?php echo $p['nik']; ?></div>-->
                                            <div class="mobile-stats">
                                                <span class="mobile-stat-item"><i class="far fa-clock"></i> <?php echo $p['total_jam_kerja']; ?>h</span>
                                                <span class="mobile-stat-item text-success fw-bold"><i class="fas fa-plus-circle"></i> <?php echo $p['total_overtime']; ?>h</span>
                                            </div>
                                        </div>
                                        <div class="mobile-score text-primary"><?php echo $p['performance_score']; ?></div>
                                    </div>
                                    <?php $rank++; endforeach; ?>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 table-custom">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" width="80">Rank</th>
                                                <th class="text-center" width="80">Avatar</th>
                                                <th>Nama Karyawan</th>
                                                <th>NIK</th>
                                                <th class="text-center"><a href="<?php echo getSortLink('performance', 'jam', $sort_by, $sort_order); ?>">Jam Kerja <?php echo getSortIcon('jam', $sort_by, $sort_order); ?></a></th>
                                                <th class="text-center"><a href="<?php echo getSortLink('performance', 'lembur', $sort_by, $sort_order); ?>">Overtime <?php echo getSortIcon('lembur', $sort_by, $sort_order); ?></a></th>
                                                <th class="text-center bg-primary-subtle"><a href="<?php echo getSortLink('performance', 'score', $sort_by, $sort_order); ?>">Total Score <?php echo getSortIcon('score', $sort_by, $sort_order); ?></a></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; foreach ($list_performance as $p): 
                                                $badge_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : (($rank == 3) ? 'rank-3' : 'rank-other'));
                                            ?>
                                            <tr>
                                                <td><div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div></td>
                                                <td class="avatar-cell"><div class="avatar-initial"><?php echo substr($p['nama'], 0, 1); ?></div></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($p['nama']); ?></td>
                                                <td class="text-muted"><?php echo $p['nik']; ?></td>
                                                <td class="text-center"><?php echo $p['total_jam_kerja']; ?> Jam</td>
                                                <td class="text-center text-success fw-bold">+<?php echo $p['total_overtime']; ?> Jam</td>
                                                <td class="text-center bg-primary-subtle text-primary score-val"><?php echo $p['performance_score']; ?></td>
                                            </tr>
                                            <?php $rank++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade <?php if($active_tab == 'discipline') echo 'show active'; ?>" id="discipline">
                                <div class="p-3 bg-light-subtle mb-0 mx-0 rounded-0 border-bottom">
                                    <h6 class="text-danger fw-bold mb-1" style="font-size: 0.9rem;"><i class="fas fa-gavel me-2"></i>Pasal 36 (Tata Tertib)</h6>
                                    <p class="small text-muted mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        Teguran jika: Terlambat > 5x (atau total > 10 jam) atau Tidak absen > 2x dalam sebulan.
                                    </p>
                                </div>

                                <div class="mobile-view-container px-3 pt-2">
                                    <div class="sort-options">
                                        <a href="<?php echo getSortLink('discipline', 'terlambat', $sort_by, $sort_order); ?>" class="sort-btn <?php echo ($sort_by == 'terlambat') ? 'active' : ''; ?>">Terlambat <?php echo getSortIcon('terlambat', $sort_by, $sort_order); ?></a>
                                        <a href="<?php echo getSortLink('discipline', 'tidak_absen', $sort_by, $sort_order); ?>" class="sort-btn <?php echo ($sort_by == 'tidak_absen') ? 'active' : ''; ?>">Tidak Absen <?php echo getSortIcon('tidak_absen', $sort_by, $sort_order); ?></a>
                                    </div>

                                    <?php $rank = 1; foreach ($list_discipline as $d): 
                                        $badge_class = ($rank <= 3) ? 'bg-danger text-white' : 'rank-other';
                                        $style_ta = ($d['total_tidak_absen'] > 2) ? 'text-danger fw-bold' : 'text-muted';
                                    ?>
                                    <div class="mobile-list-item">
                                        <div class="mobile-rank"><div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div></div>
                                        <div class="mobile-content">
                                            <div class="name-text"><?php echo htmlspecialchars($d['nama']); ?></div>
                                            <div class="mobile-stats">
                                                <span class="mobile-stat-item <?php echo $style_ta; ?>"><i class="fas fa-user-times"></i> Tidak Absen : <?php echo $d['total_tidak_absen']; ?>x</span>
                                            </div>
                                        </div>
                                        <div class="mobile-score text-danger"><?php echo $d['total_telat_menit']; ?> m</div>
                                    </div>
                                    <?php $rank++; endforeach; ?>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 table-custom">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" width="80">Rank</th>
                                                <th class="text-center" width="80">Avatar</th>
                                                <th>Nama Karyawan</th>
                                                <th>NIK</th>
                                                <th class="text-center"><a href="<?php echo getSortLink('discipline', 'tidak_absen', $sort_by, $sort_order); ?>">Tidak Absen <?php echo getSortIcon('tidak_absen', $sort_by, $sort_order); ?></a></th>
                                                <th class="text-center bg-danger-subtle"><a href="<?php echo getSortLink('discipline', 'terlambat', $sort_by, $sort_order); ?>">Total Terlambat <?php echo getSortIcon('terlambat', $sort_by, $sort_order); ?></a></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; foreach ($list_discipline as $d): 
                                                $badge_class = ($rank <= 3) ? 'bg-danger text-white' : 'rank-other';
                                                $style_ta = ($d['total_tidak_absen'] > 2) ? 'text-danger fw-bold' : 'text-muted';
                                            ?>
                                            <tr>
                                                <td><div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div></td>
                                                <td class="avatar-cell"><div class="avatar-initial"><?php echo substr($d['nama'], 0, 1); ?></div></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($d['nama']); ?></td>
                                                <td class="text-muted"><?php echo $d['nik']; ?></td>
                                                <td class="text-center <?php echo $style_ta; ?>"><?php echo $d['total_tidak_absen']; ?> Kali</td>
                                                <td class="text-center bg-danger-subtle text-danger score-val"><?php echo $d['total_telat_menit']; ?> Menit</td>
                                            </tr>
                                            <?php $rank++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade <?php if($active_tab == 'leaves') echo 'show active'; ?>" id="leaves">
                                <div class="p-3 bg-light-subtle mb-0 mx-0 rounded-0 border-bottom">
                                    <h6 class="text-primary fw-bold mb-1" style="color: #6f42c1 !important; font-size: 0.9rem;"><i class="fas fa-info-circle me-2"></i>Pasal 10 (Cuti Tahunan)</h6>
                                    <p class="small text-muted mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        Maksimal ambil 6 hari/pengajuan. Sistem +2 per bulan. Jika habis = potong gaji.
                                    </p>
                                </div>

                                <div class="mobile-view-container px-3 pt-2">
                                    <?php $rank = 1; foreach ($list_cuti as $c): 
                                        $badge_class = ($rank <= 3) ? 'bg-info text-white' : 'rank-other';
                                    ?>
                                    <div class="mobile-list-item">
                                        <div class="mobile-rank"><div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div></div>
                                        <div class="mobile-content">
                                            <div class="name-text"><?php echo htmlspecialchars($c['nama']); ?></div>
                                            <div class="nik-text"><?php echo $c['nik']; ?></div>
                                        </div>
                                        <div class="mobile-score text-info"><?php echo $c['total_cuti_days']; ?> Hari</div>
                                    </div>
                                    <?php $rank++; endforeach; ?>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 table-custom">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" width="80">Rank</th>
                                                <th class="text-center" width="80">Avatar</th>
                                                <th>Nama Karyawan</th>
                                                <th>NIK</th>
                                                <th class="text-center bg-info-subtle">Total Cuti</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; foreach ($list_cuti as $c): 
                                                $badge_class = ($rank <= 3) ? 'bg-info text-white' : 'rank-other';
                                            ?>
                                            <tr>
                                                <td><div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div></td>
                                                <td class="avatar-cell"><div class="avatar-initial"><?php echo substr($c['nama'], 0, 1); ?></div></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($c['nama']); ?></td>
                                                <td class="text-muted"><?php echo $c['nik']; ?></td>
                                                <td class="text-center bg-info-subtle text-info score-val"><?php echo $c['total_cuti_days']; ?> Hari</td>
                                            </tr>
                                            <?php $rank++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>