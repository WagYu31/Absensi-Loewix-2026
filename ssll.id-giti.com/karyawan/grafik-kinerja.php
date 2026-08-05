<?php
session_start();

if (!isset($_SESSION['nip']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

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
            'hadir_count' => 0,
            'performance_score' => 0
        ];
    }
}

$holidays = [];
$res_libur = $conn->query("SELECT tanggal_merah FROM kalender_kerja WHERE libur='yes' AND YEAR(tanggal_merah) = '$tahun_filter'");
while ($l = $res_libur->fetch_assoc()) $holidays[$l['tanggal_merah']] = true;

$start_dt = new DateTime($start_date);
$end_dt = new DateTime($end_date);
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_dt, $interval, $end_dt->modify('+1 day'));

foreach ($employees_data as $nip => &$emp) {
    $absen_harian = [];
    $target_nik = $emp['nik'];
    
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
    $nama_depan = explode(' ', $d['nama'])[0];
    $chart_labels[] = $nama_depan;
    $data_jam_kerja[] = $d['total_jam_kerja'];
    $data_overtime[] = $d['total_overtime'];
    $data_telat[] = $d['total_telat_menit'];
    $data_tidak_absen[] = $d['total_tidak_absen'];
    $data_cuti_days[] = $d['total_cuti_days'];
}

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
    <title>Grafik Kinerja - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .chart-card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.3s; background: #fff; }
        .chart-card:hover { transform: translateY(-5px); }
        .quote-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 20px; text-align: center; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(118, 75, 162, 0.4); }
        .quote-text { font-style: italic; font-size: 1.1rem; font-weight: 500; }
        .top-list-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #f0f0f0; }
        .top-list-item:last-child { border-bottom: none; }
        .medal-icon { font-size: 1.5rem; margin-right: 15px; width: 30px; text-align: center; }
        .medal-1 { color: #FFD700; } 
        .medal-2 { color: #C0C0C0; } 
        .medal-3 { color: #CD7F32; }
        .medal-other { color: #6c757d; font-size: 1rem; font-weight: bold; }
        .avatar-circle { width: 40px; height: 40px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #495057; margin-right: 10px; }
        .stat-value { font-weight: bold; font-size: 1.1rem; margin-left: auto; }
        .score-badge { font-size: 0.75rem; background: #e3f2fd; color: #0d6efd; padding: 2px 8px; border-radius: 10px; margin-top: 2px; display: inline-block; }
        @media (max-width: 768px) { .chart-container { height: 300px; } }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Statistik & Kinerja</h1>
                <p>Pantau performa tim secara realtime dengan cara yang asik!</p>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4">
                
                <div class="quote-box">
                    <i class="fas fa-quote-left fa-lg mb-2 opacity-50"></i>
                    <p class="quote-text mb-0">"<?php echo $random_quote; ?>"</p>
                </div>

                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <form method="GET" action="grafik-kinerja.php" class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="monthly" <?php if($filter_type == 'monthly') echo 'selected'; ?>>Bulanan</option>
                                    <option value="yearly" <?php if($filter_type == 'yearly') echo 'selected'; ?>>Tahunan</option>
                                </select>
                            </div>
                            <?php if ($filter_type == 'monthly'): ?>
                            <div class="col-md-3">
                                <select name="bulan" class="form-select form-select-sm">
                                    <?php foreach($bulanNames as $k => $v): ?>
                                        <option value="<?php echo $k; ?>" <?php if($bulan_filter == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-2">
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
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-sync-alt me-1"></i> Update</button>
                            </div>
                            <div class="col-md-2">
                                <a href="peringkat-kinerja.php" class="btn btn-warning btn-sm w-100"><i class="fas fa-info me-1"></i> Lihat Detail</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card chart-card h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 text-primary"><i class="fas fa-briefcase me-2"></i>Produktivitas Tim</h5>
                                <small class="text-muted">Jam Kerja & Overtime (<?php echo $label_periode; ?>)</small>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:350px;">
                                    <canvas id="productivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card chart-card h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 text-success"><i class="fas fa-medal me-2"></i>Top 5 Best Performance</h5>
                                <small class="text-muted">*Score = Jam Kerja + (0.5 x Overtime)</small>
                            </div>
                            <div class="card-body p-0">
                                <?php $rank = 1; $ada_best = false; foreach ($top_performance as $tp): if($tp['performance_score'] > 0) { $ada_best = true; ?>
                                <div class="top-list-item px-4">
                                    <div class="medal-icon medal-<?php echo $rank; ?>"><i class="fas fa-trophy"></i></div>
                                    <div class="avatar-circle bg-success text-white"><?php echo substr($tp['nama'], 0, 1); ?></div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($tp['nama']); ?></span>
                                        <div>
                                            <span class="score-badge">Score: <?php echo $tp['performance_score']; ?></span>
                                            <!--<small class="text-muted ms-1">(<?php echo $tp['total_jam_kerja']; ?>j + <?php echo $tp['total_overtime']; ?> lembur)</small>-->
                                        </div>
                                    </div>
                                </div>
                                <?php $rank++; } endforeach; if(!$ada_best): ?>
                                    <div class="text-center p-4 text-muted"><i class="fas fa-chart-line fa-2x mb-2"></i><br>Belum ada data kinerja.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card chart-card h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 text-danger"><i class="fas fa-user-clock me-2"></i>Kedisiplinan</h5>
                                <small class="text-muted">Keterlambatan & Lupa Absen (<?php echo $label_periode; ?>)</small>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:350px;">
                                    <canvas id="disciplineChart"></canvas>
                                </div>
                                <br>
                                <h5 class="mb-0 text-danger"><i class="fas fa-user-clock me-2"></i>Pasal 36</h5>
                                <small class="text-muted">Pelanggaran Tata Tertib Kerja</small>
                                <small class="text-muted">
                                    Jenis pelanggaran disiplin yang dapat dikenakan hukuman disiplin, ketentuan pelaksanaannya ditetapkan sebagai berikut:
                                <br>
                                Poin 1 (d) Pelanggaran - pelanggaran yang dikenakan berupa Teguran antara lain, sebagai berikut :
                                <br>
                                <ul>
                                    <li>Lebih dari 5 (lima) kali datang terlambat dan atau dispensasi non dinas total lebih dari 10 jam/bulan.</li>
                                    <li>Lebih dari 2 (dua) kali dalam satu bulan tidak melakukan check in atau check out.</li>
                                </ul>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card chart-card h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 text-info"><i class="fas fa-exclamation-circle me-2"></i>Sering Terlambat</h5>
                                <small class="text-muted">Top 5 Keterlambatan (Menit)</small>
                            </div>
                            <div class="card-body p-0">
                                <?php $rank = 1; $ada_telat = false; foreach ($top_telat as $tt): if($tt['total_telat_menit'] > 0) { $ada_telat = true; ?>
                                <div class="top-list-item px-4">
                                    <div class="medal-icon <?php echo ($rank <= 3) ? 'medal-'.$rank : 'medal-other'; ?>">
                                        <?php echo ($rank <= 3) ? '<i class="fas fa-exclamation-triangle"></i>' : '#'.$rank; ?>
                                    </div>
                                    <div class="avatar-circle bg-danger text-white"><?php echo substr($tt['nama'], 0, 1); ?></div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($tt['nama']); ?></span>
                                        <small class="text-muted"><?php echo $tt['nik']; ?></small>
                                    </div>
                                    <div class="stat-value text-danger"><?php echo $tt['total_telat_menit']; ?> m</div>
                                </div>
                                <?php $rank++; } endforeach; if(!$ada_telat): ?>
                                    <div class="text-center p-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2"></i><br>Semua disiplin, luar biasa!</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-lg-8">
                        <div class="card chart-card h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0" style="color: #6f42c1;"><i class="fas fa-umbrella-beach me-2"></i>Penggunaan Cuti</h5>
                                <small class="text-muted">Total Hari Cuti Disetujui (<?php echo $label_periode; ?>)</small>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:350px;">
                                    <canvas id="leaveChart"></canvas>
                                </div>
                                <br>
                                <h5 class="mb-0" style="color: #6f42c1;"><i class="fas fa-umbrella-beach me-2"></i>Pasal 10</h5>
                                <small class="text-muted">Cuti Tahunan</small>
                                <small class="text-muted">
                                Poin 5 Pelaksanaan cuti tahunan diatur sebagai berikut :
                                <br>
                                <ul>
                                    <li>Sebanyak - banyaknya 6 (enam) hari kerja yang dapat diambil dalam satu kali pengajuan pada minimal pada bulan ketiga yang sudah berjalan.</li>
                                    <li>Setiap bulan pengajuan cuti menggunakan metode +2, misalnya : Januari maksimal hanya dapat diambil 3 hari dan berlaku pada bulan selanjutnya.</li>
                                    <li>Sisanya diatur sendiri oleh masing - masing karyawan menurut kepentingannya, yang waktunya disesuaikan dengan kepentingan perusahaan.</li>
                                    <li>Jika cuti tahunan sudah habis akan dikenakan potongan gaji dengan perhitungan pro-rate berdasarkan gaji yang di dapatkan.</li>
                                </ul>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card chart-card h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0" style="color: #6f42c1;"><i class="fas fa-plane-departure me-2"></i>Paling Banyak Cuti</h5>
                                <small class="text-muted">Top 5 Hari Cuti (Efektif)</small>
                            </div>
                            <div class="card-body p-0">
                                <?php $rank = 1; $ada_cuti = false; foreach ($top_cuti as $tc): if($tc['total_cuti_days'] > 0) { $ada_cuti = true; ?>
                                <div class="top-list-item px-4">
                                    <div class="medal-icon <?php echo ($rank <= 3) ? 'medal-'.$rank : 'medal-other'; ?>">
                                        <?php echo ($rank <= 3) ? '<i class="fas fa-crown"></i>' : '#'.$rank; ?>
                                    </div>
                                    <div class="avatar-circle text-white" style="background-color: #6f42c1;"><?php echo substr($tc['nama'], 0, 1); ?></div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($tc['nama']); ?></span>
                                        <small class="text-muted"><?php echo $tc['nik']; ?></small>
                                    </div>
                                    <div class="stat-value" style="color: #6f42c1;"><?php echo $tc['total_cuti_days']; ?> Hari</div>
                                </div>
                                <?php $rank++; } endforeach; if(!$ada_cuti): ?>
                                    <div class="text-center p-4 text-muted"><i class="fas fa-briefcase fa-2x mb-2"></i><br>Semua rajin masuk, belum ada cuti!</div>
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
        
        const ctxProd = document.getElementById('productivityChart').getContext('2d');
        new Chart(ctxProd, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Jam Kerja',
                        data: dataJamKerja,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    },
                    {
                        label: 'Overtime (Jam)',
                        data: dataOvertime,
                        backgroundColor: 'rgba(255, 206, 86, 0.7)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1,
                        borderRadius: 5
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
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { 
                        grid: { display: false },
                        ticks: { autoSkip: false, maxRotation: 90, minRotation: 45 }
                    }
                }
            }
        });

        const ctxDisc = document.getElementById('disciplineChart').getContext('2d');
        new Chart(ctxDisc, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Terlambat (Menit)',
                        data: dataTelat,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Tidak Absen (Kali)',
                        data: dataTidakAbsen,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        type: 'bar',
                        borderWidth: 1,
                        borderRadius: 5,
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
                        grid: { color: '#f0f0f0' },
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
                        ticks: { autoSkip: false, maxRotation: 90, minRotation: 45 }
                    }
                }
            }
        });

        const ctxLeave = document.getElementById('leaveChart').getContext('2d');
        new Chart(ctxLeave, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Cuti (Hari)',
                        data: dataCuti,
                        backgroundColor: 'rgba(111, 66, 193, 0.6)', 
                        borderColor: 'rgba(111, 66, 193, 1)',
                        borderWidth: 1,
                        borderRadius: 5
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
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { 
                        grid: { display: false },
                        ticks: { autoSkip: false, maxRotation: 90, minRotation: 45 }
                    }
                }
            }
        });
    </script>
</body>
</html>