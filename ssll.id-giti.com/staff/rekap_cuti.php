<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include '../get-kar-login-data.php'; 

$real_current_month = date('Y-m');
$selected_month = $_GET['month'] ?? $real_current_month;

if ($selected_month > $real_current_month) {
    $selected_month = $real_current_month;
}

$current_year = date('Y', strtotime($selected_month . '-01'));

try {
    $end_date_filter_str = date('Y-m-t', strtotime($selected_month . '-01'));
    $end_date_filter_dt = new DateTime($end_date_filter_str);
} catch (Exception $e) {
    $end_date_filter_str = $current_year . '-12-31';
    $end_date_filter_dt = new DateTime($end_date_filter_str);
}

$month_num = date('m', strtotime($selected_month . '-01'));
$start_of_year = $current_year . '-01-01';

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

$global_jatah_cuti = 0;
$result_quota = $conn->query("SELECT jumlah FROM jatah_cuti_tahunan WHERE tahun = '$current_year' LIMIT 1");
if ($result_quota && $result_quota->num_rows > 0) {
    $row_quota = $result_quota->fetch_assoc();
    $global_jatah_cuti = (int)$row_quota['jumlah'];
}
$result_quota->close();

$employees = [];
$nik_to_nip_map = [];
$result_kar = $conn->query("SELECT nip, nik, nama, tanggal_masuk FROM karyawan WHERE status_karyawan = 'aktif' AND nip != '001' ORDER BY nama ASC");
if ($result_kar) {
    while ($row = $result_kar->fetch_assoc()) {
        $nip = $row['nip'];
        $nik = $row['nik'];
        $nik_to_nip_map[$nik] = $nip;
        
        $jatah = 0; 
        $tanggal_masuk_str = $row['tanggal_masuk'];
        if (!empty($tanggal_masuk_str) && $tanggal_masuk_str != '0000-00-00') {
            try {
                $tgl_masuk_dt = new DateTime($tanggal_masuk_str);
                $tgl_masuk_plus_6_bulan = (new DateTime($tanggal_masuk_str))->modify('+6 months');
                if ($tgl_masuk_plus_6_bulan <= $end_date_filter_dt) {
                    $jatah = $global_jatah_cuti;
                }
            } catch (Exception $e) {
                $jatah = 0; 
            }
        }
        $employees[$nip] = [
            'nama' => $row['nama'],
            'nik' => $row['nik'],
            'cuti_hak' => 0,
            'cuti_khusus' => 0,
            'cuti_lainnya' => 0,
            'terpakai_potong' => 0,
            'tidak_potong' => 0,
            'hadir_bulan' => 0,
            'hadir_ytd' => 0,
            'jatah' => $jatah,
            'sisa' => $jatah 
        ];
    }
    $result_kar->close();
}

$sql_hadir_bulan = "SELECT nip, COUNT(DISTINCT DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'))) as total 
                   FROM absen 
                   WHERE MONTH(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$month_num' 
                   AND YEAR(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$current_year' 
                   GROUP BY nip";
$res_hadir_bulan = $conn->query($sql_hadir_bulan);
if ($res_hadir_bulan) {
    while ($row_h = $res_hadir_bulan->fetch_assoc()) {
        $h_nik = $row_h['nip'];
        if (isset($nik_to_nip_map[$h_nik])) {
            $target_nip = $nik_to_nip_map[$h_nik];
            $employees[$target_nip]['hadir_bulan'] = $row_h['total'];
        }
    }
}

$sql_hadir_ytd = "SELECT nip, COUNT(DISTINCT DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'))) as total 
                 FROM absen 
                 WHERE DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) >= '$start_of_year' 
                 AND DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) <= '$end_date_filter_str' 
                 GROUP BY nip";
$res_hadir_ytd = $conn->query($sql_hadir_ytd);
if ($res_hadir_ytd) {
    while ($row_y = $res_hadir_ytd->fetch_assoc()) {
        $y_nik = $row_y['nip'];
        if (isset($nik_to_nip_map[$y_nik])) {
            $target_nip = $nik_to_nip_map[$y_nik];
            $employees[$target_nip]['hadir_ytd'] = $row_y['total'];
        }
    }
}

$sql_cuti = "SELECT nip, tgl_mulai, tgl_selesai, jenis, potong_gaji 
             FROM cuti 
             WHERE verif = 'Disetujui' 
             AND deleted_at IS NULL
             AND tgl_selesai >= '$current_year-01-01' 
             AND tgl_mulai <= '$current_year-12-31'";
$result_cuti = $conn->query($sql_cuti);
$interval = new DateInterval('P1D');
if ($result_cuti) {
    while ($cuti = $result_cuti->fetch_assoc()) {
        $nip = $cuti['nip'];
        if (!isset($employees[$nip])) continue; 
        if (empty($cuti['tgl_mulai']) || $cuti['tgl_mulai'] == '0000-00-00' || empty($cuti['tgl_selesai'])) continue; 
        try {
            $start = new DateTime($cuti['tgl_mulai']);
            $end = new DateTime($cuti['tgl_selesai']);
            if ($start > $end) continue;
            $end->modify('+1 day'); 
            $period = new DatePeriod($start, $interval, $end);
            $jenis = strtolower($cuti['jenis']);
            $potong = (int)$cuti['potong_gaji'];
            foreach ($period as $date) {
                if ($date->format('Y') != $current_year || $date > $end_date_filter_dt) continue; 
                $dayOfWeek = $date->format('N'); 
                $dateString = $date->format('Y-m-d');
                if ($dayOfWeek == 7 || isset($holidays[$dateString])) continue; 
                if ($jenis == 'hak') $employees[$nip]['cuti_hak']++;
                elseif ($jenis == 'khusus') $employees[$nip]['cuti_khusus']++;
                elseif ($jenis == 'dipotong') $employees[$nip]['cuti_lainnya']++;
                if ($potong == 1) $employees[$nip]['terpakai_potong']++;
                else $employees[$nip]['tidak_potong']++;
            }
        } catch (Throwable $t) {
            continue;
        }
    }
    $result_cuti->close();
}

foreach ($employees as &$data) { 
    $data['sisa'] = $data['jatah'] - $data['terpakai_potong'];
}
unset($data); 

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Cuti & Kehadiran (<?php echo $current_year; ?>) - Grav-Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
        .table-sm-custom { font-size: 0.82rem; white-space: nowrap; }
        .table-sm-custom th, .table-sm-custom td { padding: 0.5rem 0.4rem; vertical-align: middle; }
        .table-sm-custom th { background-color: #f8f9fa; }
        .text-bold { font-weight: 600; }
        .text-danger-custom { color: #d9534f; font-weight: 600; }
        .text-success-custom { color: #5cb85c; font-weight: 600; }
        .bg-presence { background-color: #eef7ff !important; }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper p-0">
        <div class="header-banner page-specific-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Rekap Cuti & Kehadiran (<?php echo $current_year; ?>)</h1>
                <p>Rekapitulasi total durasi cuti dan kehadiran karyawan sepanjang tahun <?php echo $current_year; ?>.</p>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4 px-0">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="card-title mb-0">Rekap s/d <?php echo date('F Y', strtotime($end_date_filter_str)); ?></h5>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET" class="d-flex gap-2" style="width:40%;">
                            <label for="month" class="col-form-label col-form-label-sm">Bulan:</label>
                            <input type="month" class="form-control form-control-sm" id="month" name="month" value="<?php echo htmlspecialchars($selected_month); ?>" max="<?php echo date('Y-m'); ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($employees)): ?>
                            <div class="text-center p-4 text-muted">Data karyawan tidak ditemukan.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-bordered mb-0 table-sm-custom" id="rekapCutiTable">
                                    <thead class="text-center">
                                        <tr>
                                            <th rowspan="2">No.</th>
                                            <th rowspan="2">NIK</th>
                                            <th rowspan="2">Nama Karyawan</th>
                                            <th colspan="2" class="bg-presence">Kehadiran (Hari)</th>
                                            <th colspan="3">Jenis Cuti</th>
                                            <th colspan="2">Status Potong</th>
                                            <th colspan="3">Rekap Jatah Cuti</th>
                                        </tr>
                                        <tr>
                                            <th class="bg-presence">Bulan Ini</th>
                                            <th class="bg-presence">YTD (Jan-<?php echo date('M', strtotime($end_date_filter_str)); ?>)</th>
                                            <th>Hak</th>
                                            <th>Khusus</th>
                                            <th>Lainnya</th>
                                            <th>Tdk Potong</th>
                                            <th>Terpakai</th>
                                            <th>Jatah</th>
                                            <th>Terpakai</th>
                                            <th>Sisa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($employees as $nip => $data): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($data['nik']); ?></td>
                                                <td><?php echo htmlspecialchars($data['nama']); ?></td>
                                                <td class="text-center text-bold bg-presence"><?php echo $data['hadir_bulan']; ?></td>
                                                <td class="text-center text-bold bg-presence"><?php echo $data['hadir_ytd']; ?></td>
                                                <td class="text-center"><?php echo $data['cuti_hak']; ?></td>
                                                <td class="text-center"><?php echo $data['cuti_khusus']; ?></td>
                                                <td class="text-center"><?php echo $data['cuti_lainnya']; ?></td>
                                                <td class="text-center text-bold <?php echo ($data['tidak_potong'] > 0) ? 'text-success-custom' : ''; ?>"><?php echo $data['tidak_potong']; ?></td>
                                                <td class="text-center text-bold <?php echo ($data['terpakai_potong'] > 0) ? 'text-danger-custom' : ''; ?>"><?php echo $data['terpakai_potong']; ?></td>
                                                <td class="text-center text-bold"><?php echo $data['jatah']; ?></td>
                                                <td class="text-center text-bold text-danger-custom"><?php echo $data['terpakai_potong']; ?></td>
                                                <?php $sisa_cuti_class = ($data['sisa'] < 0) ? 'text-danger' : 'text-success'; ?>
                                                <td class="text-center text-bold <?php echo $sisa_cuti_class; ?>"><?php echo $data['sisa']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>