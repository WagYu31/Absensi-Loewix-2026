<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}
include '../conn.php';

$loggedInUserNip = $_SESSION['nip'];
$loggedInUserRole = $_SESSION['role'] ?? 'karyawan';

if (isset($_GET['nik'])) {
    $nik_to_display = $_GET['nik'];
} else {
    if ($loggedInUserRole === 'karyawan') {
        $nik_to_display = $loggedInUserNip;
    } else {
        exit("NIK karyawan tidak ditemukan.");
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"]) && isset($_POST["tahun"])) {
    $bulan_filter = $_POST["bulan"];
    $tahun_filter = $_POST["tahun"];
} else {
    $currentMonth = date('m');
    $currentYear = date('Y');
    // if ($currentMonth == '01') {
    //     $bulan_filter = '12';
    //     $tahun_filter = $currentYear - 1;
    // } else {
    //     $bulan_filter = str_pad($currentMonth - 1, 2, '0', STR_PAD_LEFT);
    //     $tahun_filter = $currentYear;
    // }
        $bulan_filter = $currentMonth;
        $tahun_filter = $currentYear;
}

$namaKaryawanDisplay = "Karyawan";
$shiftingKaryawan = "T";
$queryNama = "SELECT nama, shifting, nip AS nipKr FROM karyawan WHERE nik = '$nik_to_display' LIMIT 1";
$resultNama = $conn->query($queryNama);
if ($resultNama && $resultNama->num_rows > 0) {
    $dataNama = $resultNama->fetch_assoc();
    $namaKaryawanDisplay = $dataNama['nama'];
    $shiftingKaryawan = $dataNama['shifting'];
    $nipKr = $dataNama['nipKr'];
}

$holidays = [];
$queryHolidays = "SELECT tanggal_merah, libur, keterangan FROM kalender_kerja 
                  WHERE MONTH(tanggal_merah) = '$bulan_filter' 
                  AND YEAR(tanggal_merah) = '$tahun_filter' 
                  AND deleted_at IS NULL";
$resHolidays = $conn->query($queryHolidays);
while ($h = $resHolidays->fetch_assoc()) {
    $holidays[$h['tanggal_merah']] = $h;
}

$approvedLeaves = [];
$queryCuti = "SELECT tgl_mulai, tgl_selesai, keterangan, jenis FROM cuti 
              WHERE nip = '$nipKr' 
              AND verif LIKE 'Disetujui%' 
              AND deleted_at IS NULL";
$resCuti = $conn->query($queryCuti);
while ($c = $resCuti->fetch_assoc()) {
    $start = new DateTime($c['tgl_mulai']);
    $end = new DateTime($c['tgl_selesai']);
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    foreach ($period as $date) {
        $approvedLeaves[$date->format('Y-m-d')] = [
            'keterangan' => $c['keterangan'],
            'jenis' => $c['jenis']
        ];
    }
}

$attendanceData = [];
$sqlAbsen = "SELECT 
        MIN(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_in, 
        MAX(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out,
        a.pin
    FROM absen a
    WHERE a.nip = '$nik_to_display' AND
    MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan_filter' AND
    YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter'
    GROUP BY DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%Y-%m-%d')";
$resAbsen = $conn->query($sqlAbsen);
while ($rowA = $resAbsen->fetch_assoc()) {
    $dateKey = date('Y-m-d', strtotime($rowA['tgl_in']));
    $attendanceData[$dateKey] = $rowA;
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
$bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi <?php echo htmlspecialchars($namaKaryawanDisplay); ?> - Grav-Tech Salary</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/absen-styles.css?01">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <div class="header-banner absen-page-header">
            <div class="container-fluid px-lg-4">
                <h1>Data Absensi</h1>
                <p>Karyawan: <?php echo htmlspecialchars($namaKaryawanDisplay); ?> (NIK: <?php echo htmlspecialchars($nik_to_display); ?>)</p>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="container-fluid px-lg-4 px-0">
                <div class="card filter-form-card mb-4 no-print">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-filter title-icon"></i> Filter Data Absensi</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="detail-absen.php?nik=<?php echo htmlspecialchars($nik_to_display); ?>">
                            <div class="row align-items-end">
                                <div class="col-md-4 col-6 mb-3">
                                    <label for="bulan" class="form-label">Bulan:</label>
                                    <select id="bulan" name="bulan" class="form-select">
                                        <?php
                                        foreach ($bulanNames as $bulanNum => $bulanName) {
                                            $selected = ($bulanNum == $bulan_filter) ? 'selected' : '';
                                            echo "<option value='$bulanNum' $selected>$bulanName</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4 col-6 mb-3">
                                    <label for="tahun" class="form-label">Tahun:</label>
                                    <select id="tahun" name="tahun" class="form-select">
                                        <?php
                                        $tahunSekarang = date('Y');
                                        for ($i = $tahunSekarang; $i >= $tahunSekarang - 5; $i--) {
                                            $selected = ($i == $tahun_filter) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2 col-sm-6 mb-3"><button type="submit" class="btn btn-primary w-100">Tampilkan</button></div>
                                <div class="col-md-2 col-sm-6 mb-3"><button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100"><i class="fas fa-print me-1"></i>Cetak</button></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card attendance-table-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fa-solid fa-list-check title-icon"></i> Absensi Bulan: <?php echo htmlspecialchars($bulanNames[$bulan_filter] . " " . $tahun_filter); ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 attendance-table-custom">
                                <thead class="text-center">
                                    <tr>
                                        <th class="d-none d-md-table-cell" width="5%">No</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Masuk</th>
                                        <th>Pulang</th>
                                        <th class="d-none d-md-table-cell" width="10%">Terlambat</th>
                                        <th class="d-none d-md-table-cell" width="15%">Jam Kerja</th>
                                        <th class="d-none d-md-table-cell" width="15%">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $num_days = date('t', mktime(0, 0, 0, $bulan_filter, 1, $tahun_filter));
                                    $no = 1;
                                    $jumlah_terlambat_total_menit = 0; $totalJamKerja = 0; $totalMenitKerja = 0;
                                    $jumlah_tidak_absen_masuk = 0; $jumlah_tidak_absen_pulang = 0;
                                    $total_hari_kerja_efektif = 0; $total_cuti = 0;

                                    for ($d = 1; $d <= $num_days; $d++) {
                                        $currentDateStr = $tahun_filter . '-' . $bulan_filter . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                                        $currentDateObj = new DateTime($currentDateStr);
                                        $dayNameEng = $currentDateObj->format('l');
                                        $dayNameIdn = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'][$dayNameEng];
                                        
                                        $isSunday = ($dayNameEng == 'Sunday');
                                        $isHoliday = isset($holidays[$currentDateStr]) && $holidays[$currentDateStr]['libur'] == 'yes';
                                        $isWorkDay = !$isSunday && !$isHoliday;

                                        $hasData = isset($attendanceData[$currentDateStr]);
                                        $keterangan_row = ""; $row_class = "";

                                        if ($hasData) {
                                            $dataRow = $attendanceData[$currentDateStr];
                                            $tgl_scan_dt = new DateTime($dataRow['tgl_in']);
                                            $tgl_out_dt = new DateTime($dataRow['tgl_out']);
                                            $pinK = $dataRow['pin'];

                                            $current_shifting = $shiftingKaryawan;
                                            $query_req_shift = "SELECT shifting FROM shift_req WHERE nip = ? AND ? BETWEEN tgl_mulai AND tgl_selesai LIMIT 1";
                                            $stmt_req = $conn->prepare($query_req_shift);
                                            if ($stmt_req) { 
                                                $stmt_req->bind_param("ss", $pinK, $currentDateStr); 
                                                $stmt_req->execute(); 
                                                $result_req = $stmt_req->get_result();
                                                if ($result_req->num_rows > 0) { 
                                                    $row_req = $result_req->fetch_assoc(); 
                                                    $current_shifting = $row_req['shifting']; 
                                                }
                                                $stmt_req->close();
                                            }

                                            $jam_masuk_display = $tgl_scan_dt->format('H:i');
                                            $jam_pulang_display = ($tgl_out_dt != $tgl_scan_dt) ? $tgl_out_dt->format('H:i') : "-";

                                            if ($current_shifting === 'TEST' || $nik_to_display === '999001') {
                                                // TEST mode override: do not penalize time boundaries
                                            } else {
                                                if ($tgl_out_dt == $tgl_scan_dt) {
                                                    if (strtotime($jam_masuk_display) >= strtotime("12:00")) {
                                                        $jam_pulang_display = $jam_masuk_display;
                                                        $jam_masuk_display = "<span class='text-danger'>Tidak Absen Masuk</span>";
                                                        $jumlah_tidak_absen_masuk++;
                                                    } else {
                                                        $jam_pulang_display = "<span class='text-danger'>Tidak Absen Pulang</span>";
                                                        $jumlah_tidak_absen_pulang++;
                                                    }
                                                } else {
                                                    if (strtotime($jam_masuk_display) > strtotime("13:00")) { $jam_masuk_display = "<span class='text-danger'>Tidak Absen Masuk</span>"; $jumlah_tidak_absen_masuk++; }
                                                    if (strtotime($jam_pulang_display) < strtotime("11:00")) { $jam_pulang_display = "<span class='text-danger'>Tidak Absen Pulang</span>"; $jumlah_tidak_absen_pulang++; }
                                                }
                                            }

                                            $total_hari_kerja_efektif++;

                                            $durasi_kerja_display = "-";
                                            if (strpos($jam_masuk_display, 'danger') === false && strpos($jam_pulang_display, 'danger') === false && $tgl_out_dt != $tgl_scan_dt) {
                                                $selisih_detik = $tgl_out_dt->getTimestamp() - $tgl_scan_dt->getTimestamp();
                                                if ($selisih_detik > 0) {
                                                    $h_kerja = floor($selisih_detik / 3600); $m_sisa = floor(($selisih_detik % 3600) / 60);
                                                    $durasi_kerja_display = $h_kerja . "j " . $m_sisa . "m";
                                                    $totalJamKerja += $h_kerja; $totalMenitKerja += $m_sisa;
                                                }
                                            }

                                            if ($dayNameEng == "Saturday") $current_shifting = ($current_shifting == "T") ? "TW" : "W";
                                            $shift_display_map = ["P" => ["S1", "P"], "M" => ["S2", "M"], "N" => ["S3", "N"], "S" => ["S4", "S"], "T" => ["HC", "T"], "W" => ["Sbt", "W"], "TW" => ["HS", "TW"], "TEST" => ["TST", "TEST"]];
                                            $shift_info = $shift_display_map[$current_shifting] ?? [$current_shifting, ""];

                                            $keterlambatan_menit_hari_ini = 0;
                                            if (strpos($jam_masuk_display, 'danger') === false) {
                                                $waktu_masuk_seharusnya_unix = match ($current_shifting) { "P"=>strtotime($currentDateStr." 07:00:00"), "M"=>strtotime($currentDateStr." 08:30:00"), "N"=>strtotime($currentDateStr." 09:00:00"), "S"=>strtotime($currentDateStr." 09:30:00"), "T"=>strtotime($currentDateStr." 09:10:00"), "W"=>strtotime($currentDateStr." 08:30:00"), "TW"=>strtotime($currentDateStr." 09:10:00"), default=>strtotime($currentDateStr." 09:00:00") };
                                                if ($tgl_scan_dt->getTimestamp() > $waktu_masuk_seharusnya_unix) $keterlambatan_menit_hari_ini = floor(($tgl_scan_dt->getTimestamp() - $waktu_masuk_seharusnya_unix) / 60);
                                            }
                                            $jumlah_terlambat_total_menit += $keterlambatan_menit_hari_ini;
                                            $keterlambatan_display = $keterlambatan_menit_hari_ini > 0 ? $keterlambatan_menit_hari_ini . " m" : "-";
                                            if ($keterlambatan_menit_hari_ini > 0) $row_class = 'table-danger';

                                            echo "<tr class='$row_class'><td class='d-none d-md-table-cell text-center'>".$no++."</td><td><span class='ps-2'>".substr($dayNameIdn, 0, 3).", </span>".$currentDateObj->format('d/m/y')."</td><td class='text-center'><span class='shift-badge shift-".htmlspecialchars($shift_info[1])."'>".htmlspecialchars($shift_info[0])."</span></td><td class='text-center'>$jam_masuk_display</td><td class='text-center'>$jam_pulang_display</td><td class='d-none d-md-table-cell text-center ".($keterlambatan_menit_hari_ini > 0 ? 'text-danger fw-bold' : '')."'>$keterlambatan_display</td><td class='d-none d-md-table-cell text-center'>$durasi_kerja_display</td><td class='d-none d-md-table-cell text-center'><button type='button' class='btn btn-sm btn-outline-danger py-0 px-2' title='Hapus Presensi Tanggal Ini' onclick=\"hapusPresensiHarian('$nik_to_display', '$currentDateStr', '".$currentDateObj->format('d/m/Y')."')\"><i class='fas fa-trash-alt'></i></button></td></tr>";
                                        } else {
                                            if ($isWorkDay) {
                                                if (isset($approvedLeaves[$currentDateStr])) {
                                                    $text_cuti = $approvedLeaves[$currentDateStr]['keterangan'];
                                                    if (mb_strlen($text_cuti) > 40) {
                                                        $text_cuti = mb_substr($text_cuti, 0, 37) . '...';
                                                    }
                                                    $keterangan_row = "<span class='badge bg-info text-dark'>Cuti: ".htmlspecialchars($text_cuti)."</span>";
                                                    $total_cuti++;
                                                } else {
                                                    $keterangan_row = "<span class='text-danger fw-bold'>Tidak hadir tanpa keterangan</span>";
                                                }
                                                echo "<tr><td class='d-none d-md-table-cell text-center'>".$no++."</td><td><span class='ps-2'>".substr($dayNameIdn, 0, 3).", </span>".$currentDateObj->format('d/m/y')."</td><td class='text-center'>-</td><td class='text-center text-muted'>-</td><td class='text-center text-muted'>-</td><td class='d-none d-md-table-cell text-center'>-</td><td class='d-none d-md-table-cell text-center'>-</td><td class='d-none d-md-table-cell'>$keterangan_row</td></tr>";
                                            }
                                        }
                                    }
                                    $totalJamKerja += floor($totalMenitKerja / 60); $sisaMenitKerja = $totalMenitKerja % 60;
                                    ?>
                                    <tr class='table-active fw-bold d-none d-md-table-row'><td colspan='5' class='text-end'>TOTAL</td><td class='text-center <?php echo ($jumlah_terlambat_total_menit > 0 ? 'text-danger' : ''); ?>'><?php echo $jumlah_terlambat_total_menit; ?> m</td><td class='text-center'><?php echo $totalJamKerja . "j " . $sisaMenitKerja . "m"; ?></td><td></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <h5 class="section-title mt-4">Ringkasan Denda & Kehadiran</h5>
                <div class="row g-3 summary-cards-container">
                    <?php

                    $getdnd = "SELECT * FROM dnd WHERE id = 1";
                    $resdnd = $conn->query($getdnd);
                    if ($resdnd) {
                        while ($dnd = $resdnd->fetch_assoc()) {
                            $gabsen = $dnd['gabsen'];
                            $telatst = $dnd['telatst'];
                            $telatd = $dnd['telatd'];
                            $telattg = $dnd['telattg'];
                        }
                    }
                    
                    $denda_keterlambatan = 0;
                    if ($jumlah_terlambat_total_menit > 20) {
                        if ($jumlah_terlambat_total_menit <= 80) $denda_keterlambatan = ($jumlah_terlambat_total_menit - 20) * $telatst;
                        elseif ($jumlah_terlambat_total_menit <= 140) $denda_keterlambatan = (60 * $telatst) + (($jumlah_terlambat_total_menit - 80) * $telatd);
                        else $denda_keterlambatan = (60 * $telatst) + (60 * $telatd) + (($jumlah_terlambat_total_menit - 140) * $telattg);
                    }
                    $denda_tidak_absen = ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang) * $gabsen;
                    $total_denda_keseluruhan = $denda_keterlambatan + $denda_tidak_absen;
                    ?>
                    <div class="col-lg-3 col-md-6"><div class="card summary-card-item h-100"><div class="card-body"><div class="d-flex align-items-center"><div class="summary-icon bg-warning text-white"><i class="fas fa-clock"></i></div><div class="ms-3"><p class="summary-title text-muted mb-0">Total Terlambat</p><h4 class="summary-value mb-0"><?php echo $jumlah_terlambat_total_menit; ?> <small>menit</small></h4></div></div><hr class="my-2"><p class="mb-0 text-sm">Denda: <strong class="text-warning">Rp <?php echo number_format($denda_keterlambatan, 0, ',', '.'); ?></strong></p></div></div></div>
                    <div class="col-lg-3 col-md-6"><div class="card summary-card-item h-100"><div class="card-body"><div class="d-flex align-items-center"><div class="summary-icon bg-danger text-white"><i class="fas fa-user-times"></i></div><div class="ms-3"><p class="summary-title text-muted mb-0">Total Tidak Absen</p><h4 class="summary-value mb-0"><?php echo ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang); ?> <small>kali</small></h4></div></div><hr class="my-2"><p class="mb-0 text-sm">Denda: <strong class="text-danger">Rp <?php echo number_format($denda_tidak_absen, 0, ',', '.'); ?></strong></p></div></div></div>
                    <div class="col-lg-3 col-md-6"><div class="card summary-card-item h-100"><div class="card-body"><div class="d-flex align-items-center"><div class="summary-icon bg-primary text-white"><i class="fas fa-calendar-check"></i></div><div class="ms-3"><p class="summary-title text-muted mb-0">Efektif & Cuti</p><h5 class="mb-0"><?php echo $total_hari_kerja_efektif; ?> <small>Hari Kerja</small></h5><h5 class="mb-0 text-info"><?php echo $total_cuti; ?> <small>Hari Cuti</small></h5></div></div></div></div></div>
                    <div class="col-lg-3 col-md-6"><div class="card summary-card-item summary-total-fine h-100"><div class="card-body"><div class="d-flex align-items-center"><div class="summary-icon"><i class="fas fa-file-invoice-dollar"></i></div><div class="ms-3"><p class="summary-title mb-0">Akumulasi Denda</p><h4 class="summary-value mb-0">Rp <?php echo number_format($total_denda_keseluruhan, 0, ',', '.'); ?></h4></div></div></div></div></div>
                </div>

                <div class="card mt-4 mb-4 no-print"><div class="card-body"><h5 class="card-title mb-3"><i class="fa-solid fa-scale-balanced title-icon me-2"></i>Informasi Perhitungan Denda</h5><div class="fine-info-table"><div class="fine-info-row"><div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan : 20 menit pertama</div><div class="fine-info-amount">Gratis</div></div><div class="fine-info-row"><div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan menit ke 21 s/d 80</div><div class="fine-info-amount">Rp 300,- <span class="per-unit">/menit</span></div></div><div class="fine-info-row"><div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan menit ke 81 s/d 140</div><div class="fine-info-amount">Rp 600,- <span class="per-unit">/menit</span></div></div><div class="fine-info-row"><div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan setelah 140 menit</div><div class="fine-info-amount">Rp 2.000,- <span class="per-unit">/menit</span></div></div><div class="fine-info-row"><div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Tidak absen (masuk/pulang)</div><div class="fine-info-amount">Rp 25.000,- <span class="per-unit">/kejadian</span></div></div></div></div></div>
                <div class="footer no-print">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.<br><small>Version 1.2.2</small></div>
            </div>
        </div>
    </div>
    <?php include 'nav/bottom-nav.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var currentPath = "<?php echo $current_page_basename; ?>";
            var currentNikInUrl = "<?php echo htmlspecialchars($nik_to_display); ?>";
            var loggedInUserNik = "<?php echo htmlspecialchars($loggedInUserNip); ?>";
            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                var linkNik = $(this).attr('href').split("nik=")[1] || loggedInUserNik;
                if (linkHref === currentPath && linkNik === currentNikInUrl) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                }
            });
        });

        function hapusPresensiHarian(nik, tanggal, tglFmt) {
            Swal.fire({
                title: 'Hapus Presensi?',
                text: 'Data presensi pada tanggal ' + tglFmt + ' akan dihapus dari database.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'proses-hapus-absen-harian.php',
                        type: 'POST',
                        data: { nik: nik, tanggal: tanggal },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Terhapus!', res.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Gagal!', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>