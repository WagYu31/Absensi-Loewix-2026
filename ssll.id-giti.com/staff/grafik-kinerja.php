<?php
session_start();
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}
include '../conn.php';

$filter_type = $_GET['type'] ?? 'monthly';
$bulan_filter = $_GET['bulan'] ?? date('m');
$tahun_filter = $_GET['tahun'] ?? date('Y');

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
            'hadir_count' => 0,
            'performance_score' => 0
        ];
    }
}

$holidays = [];
$res_libur = $conn->query("SELECT tanggal_merah FROM kalender_kerja WHERE libur='yes' AND YEAR(tanggal_merah) = '$tahun_filter' AND deleted_at IS NULL");
if ($res_libur) {
    while ($l = $res_libur->fetch_assoc()) {
        $holidays[$l['tanggal_merah']] = true;
    }
}

$start_dt = new DateTime($start_date);
$end_dt = new DateTime($end_date);
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_dt, $interval, $end_dt->modify('+1 day'));

foreach ($employees_data as $nip => &$emp) {
    $absen_harian = [];
    $target_nik = $emp['nik'];
    
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

    $req_shifts = [];
    $res_req = $conn->query("SELECT tgl_mulai, tgl_selesai, shifting FROM shift_req WHERE nip='".$emp['pin']."' AND YEAR(tgl_mulai) = '$tahun_filter'");
    if ($res_req) {
        while ($rq = $res_req->fetch_assoc()) {
            $r_start = new DateTime($rq['tgl_mulai']);
            $r_end = new DateTime($rq['tgl_selesai']);
            $r_end->modify('+1 day');
            foreach (new DatePeriod($r_start, $interval, $r_end) as $d) {
                $req_shifts[$d->format('Y-m-d')] = $rq['shifting'];
            }
        }
    }

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

    foreach ($period as $date) {
        $tgl = $date->format('Y-m-d');
        if ($tgl >= $hari_ini_str) break;

        $dayName = $date->format('l');
        $is_sunday = ($dayName == 'Sunday');
        $is_holiday = isset($holidays[$tgl]);
        
        if (isset($absen_harian[$tgl])) {
            $emp['hadir_count']++;
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
                    if ($tgl < $hari_ini_str) $emp['total_tidak_absen']++;
                }
            } else {
                if ($jam_masuk_str > "13:00") { 
                    $emp['total_tidak_absen']++; 
                    $is_error_absen = true; 
                }
                if ($jam_pulang_str < "11:00") {
                    if ($tgl < $hari_ini_str) $emp['total_tidak_absen']++;
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

foreach ($employees_data as $nip => &$d) {
    $d['performance_score'] = $d['total_jam_kerja'] + ($d['total_overtime'] * 0.5);
}
unset($d);

$chart_labels = [];
$data_jam_kerja = [];
$data_overtime = [];
$data_telat = [];
$data_tidak_absen = [];
$data_cuti_days = [];

foreach ($employees_data as $nip => $d) {
    $nama_depan = explode(' ', trim($d['nama']))[0];
    $chart_labels[] = $nama_depan;
    $data_jam_kerja[] = $d['total_jam_kerja'];
    $data_overtime[] = $d['total_overtime'];
    $data_telat[] = $d['total_telat_menit'];
    $data_tidak_absen[] = $d['total_tidak_absen'];
    $data_cuti_days[] = $d['total_cuti_days'];
}

// Summary Metrics
$total_karyawan_count = count($employees_data);
$sum_jam_kerja = array_sum($data_jam_kerja);
$sum_telat_menit = array_sum($data_telat);
$sum_cuti_days = array_sum($data_cuti_days);

$top_performance = $employees_data;
usort($top_performance, function($a, $b) { return $b['performance_score'] <=> $a['performance_score']; });
$top_performance = array_slice($top_performance, 0, 5);

$top_telat = $employees_data;
usort($top_telat, function($a, $b) { return $b['total_telat_menit'] <=> $a['total_telat_menit']; });
$top_telat = array_slice($top_telat, 0, 5);

$top_cuti = $employees_data;
usort($top_cuti, function($a, $b) { return $b['total_cuti_days'] <=> $a['total_cuti_days']; });
$top_cuti = array_slice($top_cuti, 0, 5);

$quotes = [
    "Rejeki gak kemana, tapi kalau gak kerja ya gak ada.",
    "Kerja keraslah sampai tetangga mengira kamu pesugihan.",
    "Jalan ninjaku: Datang on-time, pulang on-time, gajian full-time.",
    "Dompet boleh kosong, tapi semangat kerja harus tetap sombong!",
    "Ingat cicilan, ingat liburan. Yuk kerja lagi!",
    "Jangan lupa bahagia, tapi jangan lupa absen juga ya.",
    "Kerja itu ibadah, kalau capek itu wajar, kalau nyerah itu jangan."
];
$random_quote = $quotes[array_rand($quotes)];

$bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik & Kinerja - Gravitti Tech</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            background: #f8fafc;
        }

        /* Top Summary Stat Widgets */
        .stat-widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-widget-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-widget-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
        }

        .widget-val {
            font-weight: 800;
            font-size: 1.6rem;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .widget-lbl {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
        }

        .widget-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .widget-icon-box.blue { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
        .widget-icon-box.emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
        .widget-icon-box.rose { background: rgba(244, 63, 94, 0.12); color: #e11d48; }
        .widget-icon-box.purple { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }

        /* Filter Card */
        .filter-card-dash {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        /* Chart & Leaderboard Cards */
        .chart-card-dash {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .quote-banner-dash {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            color: #ffffff;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(49, 46, 129, 0.25);
            position: relative;
            overflow: hidden;
        }

        .leader-item {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        .leader-item:last-child {
            border-bottom: none;
        }

        .leader-item:hover {
            background: #f8fafc;
        }

        .avatar-initial {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .rule-box-info {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin-top: 1.25rem;
            font-size: 0.8rem;
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
                <h1>Statistik & Kinerja Tim</h1>
                <p>Pantau performa jam kerja, lembur, kedisiplinan, dan histori cuti karyawan secara akurat.</p>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <!-- Quote Banner -->
                <div class="quote-banner-dash">
                    <i class="fas fa-quote-left fa-lg mb-2 opacity-50"></i>
                    <p class="quote-text mb-0 fw-medium font-italic">"<?php echo htmlspecialchars($random_quote); ?>"</p>
                </div>

                <!-- KPI Summary Widgets -->
                <div class="stat-widget-grid">
                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val"><?php echo $total_karyawan_count; ?></div>
                            <div class="widget-lbl">Total Karyawan Aktif</div>
                        </div>
                        <div class="widget-icon-box blue"><i class="fa-solid fa-users"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val"><?php echo number_format($sum_jam_kerja, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">Jam</span></div>
                            <div class="widget-lbl">Total Jam Kerja Tim</div>
                        </div>
                        <div class="widget-icon-box emerald"><i class="fa-solid fa-briefcase"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val text-danger"><?php echo number_format($sum_telat_menit, 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">Menit</span></div>
                            <div class="widget-lbl">Akumulasi Terlambat</div>
                        </div>
                        <div class="widget-icon-box rose"><i class="fa-solid fa-clock"></i></div>
                    </div>

                    <div class="stat-widget-card">
                        <div>
                            <div class="widget-val" style="color: #7c3aed;"><?php echo $sum_cuti_days; ?> <span class="fs-6 fw-normal text-muted">Hari</span></div>
                            <div class="widget-lbl">Total Hari Cuti Disetujui</div>
                        </div>
                        <div class="widget-icon-box purple"><i class="fa-solid fa-plane-departure"></i></div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="filter-card-dash no-print">
                    <form method="GET" action="grafik-kinerja.php" class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-filter me-1"></i> Tipe Periode</label>
                            <select name="type" class="form-select rounded-3" onchange="this.form.submit()">
                                <option value="monthly" <?php if($filter_type == 'monthly') echo 'selected'; ?>>Bulanan</option>
                                <option value="yearly" <?php if($filter_type == 'yearly') echo 'selected'; ?>>Tahunan</option>
                            </select>
                        </div>
                        <?php if ($filter_type == 'monthly'): ?>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar me-1"></i> Bulan</label>
                            <select name="bulan" class="form-select rounded-3">
                                <?php foreach($bulanNames as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php if($bulan_filter == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-secondary small mb-1"><i class="fa-solid fa-calendar-days me-1"></i> Tahun</label>
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
                        <div class="col-md-2 mt-md-4">
                            <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2"><i class="fas fa-sync-alt me-1"></i> Update Data</button>
                        </div>
                        <div class="col-md-2 mt-md-4">
                            <a href="peringkat-kinerja.php" class="btn btn-outline-primary w-100 rounded-3 fw-bold py-2"><i class="fas fa-list-ol me-1"></i> Peringkat Rinci</a>
                        </div>
                    </form>
                </div>

                <!-- Row 1: Produktivitas Tim & Top 5 Performance -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="chart-card-dash h-100 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-briefcase text-primary me-2"></i>Produktivitas Tim</h5>
                                    <small class="text-muted">Jam Kerja & Overtime Lembur (<?php echo $label_periode; ?>)</small>
                                </div>
                            </div>
                            <div style="position: relative; height:340px;">
                                <canvas id="productivityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="chart-card-dash h-100">
                            <div class="p-4 border-bottom bg-white">
                                <h5 class="fw-bold text-success mb-0"><i class="fas fa-trophy me-2"></i>Top 5 Performance</h5>
                                <small class="text-muted">*Score = Jam Kerja + (0.5 x Overtime)</small>
                            </div>
                            <div class="p-0">
                                <?php $rank = 1; $ada_best = false; foreach ($top_performance as $tp): if($tp['performance_score'] > 0) { $ada_best = true; 
                                    $words = explode(' ', trim($tp['nama']));
                                    $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                ?>
                                <div class="leader-item">
                                    <div class="me-3 text-center" style="width:24px; font-weight:800; color:<?php echo ($rank==1)?'#eab308':(($rank==2)?'#94a3b8':'#b45309'); ?>;">
                                        <?php if($rank <= 3): ?><i class="fa-solid fa-trophy fs-6"></i><?php else: echo '#'.$rank; endif; ?>
                                    </div>
                                    <div class="avatar-initial bg-success text-white"><?php echo $init; ?></div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold text-dark text-truncate small"><?php echo htmlspecialchars($tp['nama']); ?></div>
                                        <div class="small"><span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">Score: <?php echo $tp['performance_score']; ?></span></div>
                                    </div>
                                    <div class="text-end small font-monospace text-secondary fw-semibold">
                                        <?php echo $tp['total_jam_kerja']; ?>j <?php if($tp['total_overtime']>0) echo "<span class='text-warning'>(+".$tp['total_overtime']."j)</span>"; ?>
                                    </div>
                                </div>
                                <?php $rank++; } endforeach; if(!$ada_best): ?>
                                    <div class="text-center p-4 text-muted"><i class="fas fa-chart-line fa-2x mb-2 opacity-50"></i><br>Belum ada data kinerja.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Kedisiplinan & Top 5 Sering Terlambat -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="chart-card-dash h-100 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold text-danger mb-0"><i class="fas fa-user-clock text-danger me-2"></i>Kedisiplinan & Keterlambatan</h5>
                                    <small class="text-muted">Grafik Keterlambatan & Lupa Absen (<?php echo $label_periode; ?>)</small>
                                </div>
                            </div>
                            <div style="position: relative; height:320px;">
                                <canvas id="disciplineChart"></canvas>
                            </div>
                            
                            <!-- Pasal Rule Callout -->
                            <div class="rule-box-info">
                                <div class="fw-bold text-danger mb-1"><i class="fa-solid fa-scale-balanced me-1.5"></i> Tata Tertib Kedisiplinan (Pasal 36):</div>
                                <ul class="mb-0 ps-3">
                                    <li>Sanksi Teguran diberikan jika <strong>> 5x terlambat</strong> atau dispensasi non-dinas > 10 jam/bulan.</li>
                                    <li>Sanksi Teguran diberikan jika <strong>> 2x dalam sebulan</strong> tidak melakukan Check-In / Check-Out.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="chart-card-dash h-100">
                            <div class="p-4 border-bottom bg-white">
                                <h5 class="fw-bold text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Top 5 Sering Terlambat</h5>
                                <small class="text-muted">Akumulasi Keterlambatan Terbanyak (Menit)</small>
                            </div>
                            <div class="p-0">
                                <?php $rank = 1; $ada_telat = false; foreach ($top_telat as $tt): if($tt['total_telat_menit'] > 0) { $ada_telat = true; 
                                    $words = explode(' ', trim($tt['nama']));
                                    $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                ?>
                                <div class="leader-item">
                                    <div class="me-3 text-center fw-bold text-danger" style="width:24px;">
                                        #<?php echo $rank; ?>
                                    </div>
                                    <div class="avatar-initial bg-danger text-white"><?php echo $init; ?></div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold text-dark text-truncate small"><?php echo htmlspecialchars($tt['nama']); ?></div>
                                        <div class="small text-muted">Tidak Absen: <strong class="<?php echo ($tt['total_tidak_absen']>2)?'text-danger':'text-dark'; ?>"><?php echo $tt['total_tidak_absen']; ?>x</strong></div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1"><?php echo number_format($tt['total_telat_menit'], 0, ',', '.'); ?> m</span>
                                    </div>
                                </div>
                                <?php $rank++; } endforeach; if(!$ada_telat): ?>
                                    <div class="text-center p-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2 text-success opacity-75"></i><br>Semua karyawan disiplin tepat waktu!</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Penggunaan Cuti & Top 5 Hari Cuti -->
                <div class="row g-4 mb-5">
                    <div class="col-lg-8">
                        <div class="chart-card-dash h-100 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold mb-0" style="color: #6f42c1;"><i class="fas fa-umbrella-beach me-2"></i>Penggunaan Cuti Tim</h5>
                                    <small class="text-muted">Total Hari Cuti Disetujui (<?php echo $label_periode; ?>)</small>
                                </div>
                            </div>
                            <div style="position: relative; height:320px;">
                                <canvas id="leaveChart"></canvas>
                            </div>

                            <!-- Pasal Cuti Callout -->
                            <div class="rule-box-info">
                                <div class="fw-bold mb-1" style="color: #6f42c1;"><i class="fa-solid fa-umbrella-beach me-1.5"></i> Ketentuan Cuti Tahunan (Pasal 10):</div>
                                <ul class="mb-0 ps-3">
                                    <li>Maksimal 6 (enam) hari kerja dalam satu kali pengajuan.</li>
                                    <li>Jika jatah cuti tahunan habis, berlaku pemotongan gaji secara pro-rate.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="chart-card-dash h-100">
                            <div class="p-4 border-bottom bg-white">
                                <h5 class="fw-bold mb-0" style="color: #6f42c1;"><i class="fas fa-plane-departure me-2"></i>Top 5 Pengambil Cuti</h5>
                                <small class="text-muted">Total Hari Cuti Efektif Disetujui</small>
                            </div>
                            <div class="p-0">
                                <?php $rank = 1; $ada_cuti = false; foreach ($top_cuti as $tc): if($tc['total_cuti_days'] > 0) { $ada_cuti = true; 
                                    $words = explode(' ', trim($tc['nama']));
                                    $init = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                ?>
                                <div class="leader-item">
                                    <div class="me-3 text-center fw-bold" style="width:24px; color:#6f42c1;">
                                        #<?php echo $rank; ?>
                                    </div>
                                    <div class="avatar-initial text-white" style="background-color: #6f42c1;"><?php echo $init; ?></div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold text-dark text-truncate small"><?php echo htmlspecialchars($tc['nama']); ?></div>
                                        <div class="small text-muted">NIK: <?php echo htmlspecialchars($tc['nik']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-purple-subtle fw-bold px-2 py-1" style="background: rgba(111, 66, 193, 0.12); color: #6f42c1; border: 1px solid rgba(111, 66, 193, 0.2);"><?php echo $tc['total_cuti_days']; ?> Hari</span>
                                    </div>
                                </div>
                                <?php $rank++; } endforeach; if(!$ada_cuti): ?>
                                    <div class="text-center p-4 text-muted"><i class="fas fa-briefcase fa-2x mb-2 opacity-50"></i><br>Semua rajin masuk, belum ada cuti.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const dataJamKerja = <?php echo json_encode($data_jam_kerja); ?>;
        const dataOvertime = <?php echo json_encode($data_overtime); ?>;
        const dataTelat = <?php echo json_encode($data_telat); ?>;
        const dataTidakAbsen = <?php echo json_encode($data_tidak_absen); ?>;
        const dataCuti = <?php echo json_encode($data_cuti_days); ?>;
        
        // 1. Chart Produktivitas
        const ctxProd = document.getElementById('productivityChart').getContext('2d');
        new Chart(ctxProd, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Jam Kerja',
                        data: dataJamKerja,
                        backgroundColor: 'rgba(37, 99, 235, 0.75)',
                        borderColor: '#1d4ed8',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Overtime Lembur (Jam)',
                        data: dataOvertime,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        borderColor: '#b45309',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.6)' } },
                    x: { 
                        grid: { display: false },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
                    }
                }
            }
        });

        // 2. Chart Kedisiplinan
        const ctxDisc = document.getElementById('disciplineChart').getContext('2d');
        new Chart(ctxDisc, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Terlambat (Menit)',
                        data: dataTelat,
                        backgroundColor: 'rgba(225, 29, 72, 0.12)',
                        borderColor: '#e11d48',
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#e11d48',
                        pointRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Tidak Absen (Kali)',
                        data: dataTidakAbsen,
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderColor: '#047857',
                        type: 'bar',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Menit Telat' },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Kali Tidak Absen' },
                        grid: { display: false },
                        suggestedMax: 5,
                        beginAtZero: true
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
                    }
                }
            }
        });

        // 3. Chart Cuti
        const ctxLeave = document.getElementById('leaveChart').getContext('2d');
        new Chart(ctxLeave, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Cuti (Hari)',
                        data: dataCuti,
                        backgroundColor: 'rgba(111, 66, 193, 0.75)', 
                        borderColor: '#5b21b6',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(226, 232, 240, 0.6)' } },
                    x: { 
                        grid: { display: false },
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
                    }
                }
            }
        });
    </script>
</body>
</html>