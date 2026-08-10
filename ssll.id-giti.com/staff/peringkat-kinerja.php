<?php
session_start();
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}
include '../conn.php';

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

// -------------------------------------------------------------
// HIGH-PERFORMANCE BULK DATA FETCHING (BULLETPROOF & INSTANT)
// -------------------------------------------------------------

// 1. Fetch active employees
$employees_data = [];
$sql_kar = "SELECT nip, nik, nama, shifting, pin_absen 
            FROM karyawan 
            WHERE status_karyawan='aktif' 
              AND deleted_at IS NULL 
              AND nip NOT IN ('001','70326') 
              AND LOWER(nama) NOT LIKE '%admin%' 
              AND nip NOT IN (SELECT nip FROM users WHERE role IN ('superadmin', 'admin')) 
            ORDER BY nama ASC";
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

// 2. Fetch holidays in bulk
$holidays = [];
$res_libur = $conn->query("SELECT tanggal_merah FROM kalender_kerja WHERE libur='yes' AND YEAR(tanggal_merah) = '$tahun_filter' AND deleted_at IS NULL");
if ($res_libur) {
    while ($l = $res_libur->fetch_assoc()) {
        $holidays[$l['tanggal_merah']] = true;
    }
}

// 3. Fetch attendance scans in 1 bulk query
$all_scans = [];
$sql_bulk_absen = "SELECT nip, 
                         DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as tgl, 
                         MIN(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_masuk, 
                         MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) as jam_pulang 
                  FROM absen 
                  WHERE YEAR(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter' ";
if ($filter_type == 'monthly') {
    $sql_bulk_absen .= " AND MONTH(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan_filter' ";
}
$sql_bulk_absen .= " GROUP BY nip, tgl";

$res_bulk = $conn->query($sql_bulk_absen);
if ($res_bulk) {
    while ($r = $res_bulk->fetch_assoc()) {
        $all_scans[$r['nip']][$r['tgl']] = $r;
    }
}

// 4. Fetch leaves in 1 bulk query
$all_leaves = [];
$res_bulk_cuti = $conn->query("SELECT nip, tgl_mulai, tgl_selesai FROM cuti WHERE verif LIKE 'Disetujui%' AND deleted_at IS NULL AND tgl_mulai <= '$end_date' AND tgl_selesai >= '$start_date'");
if ($res_bulk_cuti) {
    while ($rc = $res_bulk_cuti->fetch_assoc()) {
        $all_leaves[$rc['nip']][] = $rc;
    }
}

// 5. Fetch shift requests in 1 bulk query
$all_shift_reqs = [];
$res_bulk_shifts = $conn->query("SELECT nip, tgl_mulai, tgl_selesai, shifting FROM shift_req WHERE YEAR(tgl_mulai) = '$tahun_filter'");
if ($res_bulk_shifts) {
    while ($rs = $res_bulk_shifts->fetch_assoc()) {
        $all_shift_reqs[$rs['nip']][] = $rs;
    }
}

$start_dt = new DateTime($start_date);
$end_dt = new DateTime($end_date);
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_dt, $interval, $end_dt->modify('+1 day'));

// Process metrics for all employees in memory (Instant 0.01s)
foreach ($employees_data as $nip => &$emp) {
    $target_nik = $emp['nik'];
    $pin = $emp['pin'];
    
    $absen_harian = $all_scans[$target_nik] ?? [];
    
    $req_shifts = [];
    if (isset($all_shift_reqs[$pin])) {
        foreach ($all_shift_reqs[$pin] as $rq) {
            $r_start = new DateTime($rq['tgl_mulai']);
            $r_end = new DateTime($rq['tgl_selesai']);
            $r_end->modify('+1 day');
            foreach (new DatePeriod($r_start, $interval, $r_end) as $d) {
                $req_shifts[$d->format('Y-m-d')] = $rq['shifting'];
            }
        }
    }

    if (isset($all_leaves[$nip])) {
        foreach ($all_leaves[$nip] as $rc) {
            $c_start = new DateTime($rc['tgl_mulai']);
            $c_end = new DateTime($rc['tgl_selesai']);
            $c_end->modify('+1 day');
            foreach (new DatePeriod($c_start, $interval, $c_end) as $dt_c) {
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
                    default:  $jam_masuk_target = "09:00"; break;
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

// Calculate scores
foreach ($employees_data as $nip => &$d) {
    $d['performance_score'] = $d['total_jam_kerja'] + ($d['total_overtime'] * 0.5);
}
unset($d);

// === SORTING LOGIC ===
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

$list_cuti = $employees_data;
$list_cuti = sortData($list_cuti, 'total_cuti_days', 'desc');

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peringkat Kinerja - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0284c7 100%);
            --card-radius-lg: 24px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f1f5f9 !important;
            color: #0f172a;
        }

        .main-content-wrapper {
            background: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%) !important;
            min-height: 100vh;
        }

        /* Hero Header Banner */
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

        /* Filter Card */
        .filter-card-dash {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
        }

        /* Main Leaderboard Card */
        .leader-card-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--card-radius-lg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        /* 3D Segmented Nav Tabs */
        .nav-pills-3d {
            gap: 8px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
        }

        .nav-pills-3d .nav-link {
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.88rem;
            color: #64748b;
            padding: 8px 18px;
            transition: all 0.2s ease;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .nav-pills-3d .nav-link:hover {
            color: #2563eb;
            background: #ffffff;
        }

        .nav-pills-3d .nav-link.active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            color: #ffffff !important;
            border-color: #1d4ed8 !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Rank Badges */
        .rank-badge-3d {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .rank-1 { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; }
        .rank-2 { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: #ffffff; }
        .rank-3 { background: linear-gradient(135deg, #b45309 0%, #78350f 100%); color: #ffffff; }
        .rank-other { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .emp-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .table-custom-head {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom-head th {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 2px solid #e2e8f0;
        }

        .rule-box-info {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin: 1.25rem;
            font-size: 0.82rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper p-0">
        <!-- Header Banner -->
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Detail Peringkat Kinerja Tim</h1>
                <p class="small opacity-80 mb-0">Tabel lengkap urutan performa jam kerja, kedisiplinan keterlambatan, dan histori cuti karyawan.</p>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- Filter Bar Card -->
                <div class="filter-card-dash no-print">
                    <form method="GET" action="peringkat-kinerja.php" class="row g-2.5 align-items-center">
                        <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                        <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                        <input type="hidden" name="order" value="<?php echo $sort_order; ?>">

                        <div class="col-6 col-md-3">
                            <label class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-filter me-1 text-primary"></i> Tipe Periode</label>
                            <select name="type" class="form-select rounded-3" onchange="this.form.submit()">
                                <option value="monthly" <?php if($filter_type == 'monthly') echo 'selected'; ?>>Bulanan</option>
                                <option value="yearly" <?php if($filter_type == 'yearly') echo 'selected'; ?>>Tahunan</option>
                            </select>
                        </div>
                        <?php if ($filter_type == 'monthly'): ?>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i> Bulan</label>
                            <select name="bulan" class="form-select rounded-3">
                                <?php foreach($bulanNames as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php if($bulan_filter == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar-days me-1 text-primary"></i> Tahun</label>
                            <select name="tahun" class="form-select rounded-3">
                                <?php 
                                $tahun_mulai = 2026; 
                                $tahun_skrg = date('Y');
                                $tahun_akhir = max($tahun_mulai, $tahun_skrg);
                                for($i = $tahun_mulai; $i <= $tahun_akhir; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if($tahun_filter == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 mt-md-4">
                            <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2"><i class="fas fa-sync-alt me-1"></i> Update Data</button>
                        </div>
                    </form>
                </div>

                <!-- Main Leaderboard Card -->
                <div class="leader-card-main">
                    <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <ul class="nav nav-pills nav-pills-3d mb-0" id="rankingTabs" role="tablist">
                            <li class="nav-item">
                                <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=performance&sort=score&order=desc" class="nav-link <?php if($active_tab == 'performance') echo 'active'; ?>">
                                    <i class="fas fa-trophy me-1.5 text-warning"></i> Ranking Performance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=discipline&sort=terlambat&order=desc" class="nav-link <?php if($active_tab == 'discipline') echo 'active'; ?>">
                                    <i class="fas fa-exclamation-triangle me-1.5 text-danger"></i> Ranking Terlambat
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="?type=<?php echo $filter_type; ?>&bulan=<?php echo $bulan_filter; ?>&tahun=<?php echo $tahun_filter; ?>&tab=leaves" class="nav-link <?php if($active_tab == 'leaves') echo 'active'; ?>">
                                    <i class="fas fa-umbrella-beach me-1.5" style="color:#8b5cf6;"></i> Ranking Cuti
                                </a>
                            </li>
                        </ul>

                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="searchRankInput" class="form-control border-start-0 bg-light" placeholder="Cari nama karyawan / NIK...">
                        </div>
                    </div>

                    <div class="p-0">
                        <div class="tab-content">
                            
                            <!-- TAB 1: PERFORMANCE -->
                            <div class="tab-pane fade <?php if($active_tab == 'performance') echo 'show active'; ?>" id="performance">
                                <div class="px-3 pt-3 pb-2 text-muted small"><i class="fa-solid fa-circle-info me-1 text-primary"></i> *Score = Jam Kerja + (0.5 x Overtime Lembur)</div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="rankTablePerf" style="font-size: 0.875rem;">
                                        <thead class="table-custom-head">
                                            <tr>
                                                <th class="text-center" width="70">Rank</th>
                                                <th>Nama Karyawan</th>
                                                <th>NIK</th>
                                                <th class="text-center">
                                                    <a href="<?php echo getSortLink('performance', 'jam', $sort_by, $sort_order); ?>" class="text-decoration-none text-dark">
                                                        Jam Kerja <?php echo getSortIcon('jam', $sort_by, $sort_order); ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">
                                                    <a href="<?php echo getSortLink('performance', 'lembur', $sort_by, $sort_order); ?>" class="text-decoration-none text-dark">
                                                        Overtime <?php echo getSortIcon('lembur', $sort_by, $sort_order); ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">
                                                    <a href="<?php echo getSortLink('performance', 'score', $sort_by, $sort_order); ?>" class="text-decoration-none text-dark">
                                                        Total Score <?php echo getSortIcon('score', $sort_by, $sort_order); ?>
                                                    </a>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; foreach ($list_performance as $p): 
                                                $badge_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : (($rank == 3) ? 'rank-3' : 'rank-other'));
                                                $words = explode(' ', trim($p['nama']));
                                                $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                            ?>
                                            <tr class="rank-row">
                                                <td class="text-center"><div class="rank-badge-3d <?php echo $badge_class; ?>"><?php echo $rank; ?></div></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="emp-avatar"><?php echo $init; ?></span>
                                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($p['nama']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($p['nik']); ?></td>
                                                <td class="text-center fw-medium"><?php echo $p['total_jam_kerja']; ?> Jam</td>
                                                <td class="text-center">
                                                    <?php if($p['total_overtime'] > 0): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">+<?php echo $p['total_overtime']; ?> Jam</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0 Jam</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold fs-6 px-3 py-1"><?php echo $p['performance_score']; ?></span>
                                                </td>
                                            </tr>
                                            <?php $rank++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 2: DISCIPLINE -->
                            <div class="tab-pane fade <?php if($active_tab == 'discipline') echo 'show active'; ?>" id="discipline">
                                <div class="rule-box-info">
                                    <div class="fw-bold text-danger mb-1"><i class="fa-solid fa-scale-balanced me-1.5"></i> Ketentuan Sanksi Kedisiplinan (Pasal 36):</div>
                                    <ul class="mb-0 ps-3">
                                        <li>Sanksi Teguran diberikan jika <strong>> 5x datang terlambat</strong> atau dispensasi non-dinas > 10 jam/bulan.</li>
                                        <li>Sanksi Teguran diberikan jika <strong>> 2x dalam sebulan</strong> tidak melakukan Check-In / Check-Out.</li>
                                    </ul>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="rankTableDisc" style="font-size: 0.875rem;">
                                        <thead class="table-custom-head">
                                            <tr>
                                                <th class="text-center" width="70">Rank</th>
                                                <th>Nama Karyawan</th>
                                                <th>NIK</th>
                                                <th class="text-center">
                                                    <a href="<?php echo getSortLink('discipline', 'tidak_absen', $sort_by, $sort_order); ?>" class="text-decoration-none text-dark">
                                                        Tidak Absen <?php echo getSortIcon('tidak_absen', $sort_by, $sort_order); ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">
                                                    <a href="<?php echo getSortLink('discipline', 'terlambat', $sort_by, $sort_order); ?>" class="text-decoration-none text-dark">
                                                        Total Terlambat <?php echo getSortIcon('terlambat', $sort_by, $sort_order); ?>
                                                    </a>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; foreach ($list_discipline as $d): 
                                                $badge_class = ($rank <= 3) ? 'rank-1' : 'rank-other';
                                                $words = explode(' ', trim($d['nama']));
                                                $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                            ?>
                                            <tr class="rank-row">
                                                <td class="text-center"><div class="rank-badge-3d <?php echo $badge_class; ?>"><?php echo $rank; ?></div></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="emp-avatar"><?php echo $init; ?></span>
                                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($d['nama']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($d['nik']); ?></td>
                                                <td class="text-center">
                                                    <?php if($d['total_tidak_absen'] > 2): ?>
                                                        <span class="badge bg-danger text-white fw-bold px-2 py-1"><?php echo $d['total_tidak_absen']; ?> Kali</span>
                                                    <?php elseif($d['total_tidak_absen'] > 0): ?>
                                                        <span class="badge bg-warning-subtle text-dark border border-warning fw-bold px-2 py-1"><?php echo $d['total_tidak_absen']; ?> Kali</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0 Kali</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($d['total_telat_menit'] > 0): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6 px-3 py-1"><?php echo number_format($d['total_telat_menit'], 0, ',', '.'); ?> Menit</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0 Menit</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php $rank++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 3: LEAVES -->
                            <div class="tab-pane fade <?php if($active_tab == 'leaves') echo 'show active'; ?>" id="leaves">
                                <div class="rule-box-info">
                                    <div class="fw-bold mb-1" style="color:#8b5cf6;"><i class="fa-solid fa-umbrella-beach me-1.5"></i> Ketentuan Cuti Tahunan (Pasal 10):</div>
                                    <ul class="mb-0 ps-3">
                                        <li>Sebanyak-banyaknya 6 (enam) hari kerja dalam satu kali pengajuan.</li>
                                        <li>Jika cuti tahunan habis, berlaku pemotongan gaji secara pro-rate.</li>
                                    </ul>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="rankTableLeaves" style="font-size: 0.875rem;">
                                        <thead class="table-custom-head">
                                            <tr>
                                                <th class="text-center" width="70">Rank</th>
                                                <th>Nama Karyawan</th>
                                                <th>NIK</th>
                                                <th class="text-center">Total Cuti Disetujui</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; foreach ($list_cuti as $c): 
                                                $badge_class = ($rank <= 3) ? 'rank-1' : 'rank-other';
                                                $words = explode(' ', trim($c['nama']));
                                                $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                            ?>
                                            <tr class="rank-row">
                                                <td class="text-center"><div class="rank-badge-3d <?php echo $badge_class; ?>"><?php echo $rank; ?></div></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="emp-avatar"><?php echo $init; ?></span>
                                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($c['nama']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($c['nik']); ?></td>
                                                <td class="text-center">
                                                    <?php if($c['total_cuti_days'] > 0): ?>
                                                        <span class="badge bg-purple-subtle fw-bold fs-6 px-3 py-1" style="background: rgba(139, 92, 246, 0.12); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.2);"><?php echo $c['total_cuti_days']; ?> Hari</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0 Hari</span>
                                                    <?php endif; ?>
                                                </td>
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
    <script>
        $(document).ready(function() {
            // Instant Table Live Search across active tabs
            $('#searchRankInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('tr.rank-row').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>
</body>
</html>