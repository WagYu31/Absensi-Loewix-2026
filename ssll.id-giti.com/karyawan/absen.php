<?php
session_start();

if (!isset($_SESSION['nip'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$loggedInUserNip = $_SESSION['nip']; 
$loggedInUserRole = $_SESSION['role'] ?? 'karyawan'; 

$queryNk = "SELECT * FROM karyawan WHERE karyawan.nip = '$loggedInUserNip'";
$resultNik = $conn->query($queryNk);
if ($resultNik->num_rows > 0) {
    $rowNk = $resultNik->fetch_assoc();
    $nik_to_display = $rowNk['nik'];
} else {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"]) && isset($_POST["tahun"])) {
    $bulan_filter = $_POST["bulan"];
    $tahun_filter = $_POST["tahun"];
} else {
    $bulan_filter = date('m');
    $tahun_filter = date('Y');
}

$namaKaryawanDisplay = "Karyawan"; 
$queryNama = "SELECT nama FROM karyawan WHERE nik = '$nik_to_display' LIMIT 1";
$resultNama = $conn->query($queryNama);
if ($resultNama && $resultNama->num_rows > 0) {
    $dataNama = $resultNama->fetch_assoc();
    $namaKaryawanDisplay = $dataNama['nama'];
}

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi <?php echo htmlspecialchars($namaKaryawanDisplay); ?> - Grav-Tech Salary</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/absen-styles.css">
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
                        <form method="post" action="absen.php?nik=<?php echo htmlspecialchars($nik_to_display); ?>">
                            <div class="row align-items-end">
                                <div class="col-md-4 col-6 mb-3">
                                    <label for="bulan" class="form-label">Bulan:</label>
                                    <select id="bulan" name="bulan" class="form-select">
                                        <?php
                                        $bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
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
                                <div class="col-md-2 col-sm-6 mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                </div>
                                <div class="col-md-2 col-sm-6 mb-3">
                                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card attendance-table-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fa-solid fa-list-check title-icon"></i>
                            Absensi Bulan: <?php echo htmlspecialchars($bulanNames[$bulan_filter] . " " . $tahun_filter); ?>
                        </h5>
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
                                    $sql = "SELECT 
                                            MIN(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_scan, 
                                            a.nip, a.pin, k.nik, k.nama AS nama_karyawan, k.shifting
                                        FROM absen a
                                        JOIN karyawan k ON a.nip = k.nik
                                        WHERE k.nik = '$nik_to_display' AND
                                            MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan_filter' AND
                                            YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter'
                                        GROUP BY a.nip, DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%Y-%m-%d')
                                        ORDER BY tgl_scan ASC";
                                    $result = $conn->query($sql);

                                    $no = 1;
                                    $jumlah_terlambat_total_menit = 0;
                                    $totalJamKerja = 0;
                                    $totalMenitKerja = 0;
                                    $jumlah_tidak_absen_masuk = 0;
                                    $jumlah_tidak_absen_pulang = 0;

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $tgl_scan_dt = new DateTime($row['tgl_scan']);
                                            $jam_scan_masuk_raw = $tgl_scan_dt->format('H:i');
                                            $tgl_only_db = $tgl_scan_dt->format('Y-m-d');
                                            $tgl_display = $tgl_scan_dt->format('d/m/y');
                                            $pinK = $row['pin'];

                                            $nama_hari_eng = $tgl_scan_dt->format('l');
                                            $nama_hari_map = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
                                            $nama_hari_idn = $nama_hari_map[$nama_hari_eng] ?? $nama_hari_eng;

                                            $query_out = "SELECT MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
                                                        FROM absen 
                                                        WHERE nip = '" . $row['nip'] . "' AND DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '" . $tgl_only_db . "'";
                                            $res_out = $conn->query($query_out);
                                            $data_out = $res_out->fetch_assoc();
                                            
                                            $jam_scan_masuk = $jam_scan_masuk_raw;
                                            $jam_scan_pulang = "-";
                                            $durasi_kerja_display = "-";
                                            $is_error_masuk = false;

                                            if ($data_out && $data_out['tgl_out']) {
                                                $tgl_out_dt = new DateTime($data_out['tgl_out']);
                                                if ($tgl_out_dt == $tgl_scan_dt) {
                                                    if (strtotime($jam_scan_masuk_raw) >= strtotime("12:00")) {
                                                        $jam_scan_masuk = "<span class='text-danger'>-</span>";
                                                        $jam_scan_pulang = $jam_scan_masuk_raw;
                                                        $jumlah_tidak_absen_masuk++;
                                                        $is_error_masuk = true;
                                                    } else {
                                                        $jam_scan_pulang = "<span class='text-danger'>-</span>";
                                                        $jumlah_tidak_absen_pulang++;
                                                    }
                                                } else {
                                                    if (strtotime($jam_scan_masuk_raw) > strtotime("13:00")) {
                                                        $jam_scan_masuk = "<span class='text-danger'>-</span>";
                                                        $jumlah_tidak_absen_masuk++;
                                                        $is_error_masuk = true;
                                                    }
                                                    
                                                    $jam_scan_pulang_raw = $tgl_out_dt->format('H:i');
                                                    if (strtotime($jam_scan_pulang_raw) < strtotime("11:00")) {
                                                        $jam_scan_pulang = "<span class='text-danger'>-</span>";
                                                        $jumlah_tidak_absen_pulang++;
                                                    } else {
                                                        $jam_scan_pulang = $jam_scan_pulang_raw;
                                                    }

                                                    if (!$is_error_masuk && $jam_scan_pulang !== "<span class='text-danger'>-</span>") {
                                                        $selisih_detik = $tgl_out_dt->getTimestamp() - $tgl_scan_dt->getTimestamp();
                                                        if ($selisih_detik > 0) {
                                                            $j_kerja = floor($selisih_detik / 3600);
                                                            $m_kerja = floor(($selisih_detik % 3600) / 60);
                                                            $durasi_kerja_display = $j_kerja . "j " . $m_kerja . "m";
                                                            $totalJamKerja += $j_kerja;
                                                            $totalMenitKerja += $m_kerja;
                                                        }
                                                    }
                                                }
                                            }

                                            $current_shifting = $row["shifting"];
                                            $query_req_shift = "SELECT shifting FROM shift_req WHERE nip = ? AND ? BETWEEN tgl_mulai AND tgl_selesai LIMIT 1";
                                            $stmt_req = $conn->prepare($query_req_shift);
                                            if ($stmt_req) {
                                                $stmt_req->bind_param("ss", $pinK, $tgl_only_db);
                                                $stmt_req->execute();
                                                $result_req = $stmt_req->get_result();
                                                if ($result_req->num_rows > 0) {
                                                    $row_req = $result_req->fetch_assoc();
                                                    $current_shifting = $row_req['shifting'];
                                                }
                                                $stmt_req->close();
                                            }
                                            
                                            if ($nama_hari_eng == "Saturday") {
                                                $current_shifting = ($current_shifting == "T") ? "TW" : "W";
                                            }

                                            $shift_display_map = ["P" => ["S1", "P"], "M" => ["S2", "M"], "N" => ["S3", "N"], "S" => ["S4", "S"], "T" => ["HC", "T"], "W" => ["Sbt", "W"], "TW" => ["HS", "TW"]];
                                            $shift_info = $shift_display_map[$current_shifting] ?? [$current_shifting, ""];

                                            $keterlambatan_menit_hari_ini = 0;
                                            if (!$is_error_masuk) {
                                                $waktu_masuk_seharusnya_unix = match ($current_shifting) {
                                                    "P" => strtotime($tgl_only_db . " 07:00:00"),
                                                    "M" => strtotime($tgl_only_db . " 08:30:00"),
                                                    "N" => strtotime($tgl_only_db . " 09:00:00"),
                                                    "S" => strtotime($tgl_only_db . " 09:30:00"),
                                                    "T", "TW" => strtotime($tgl_only_db . " 09:10:00"),
                                                    "W" => strtotime($tgl_only_db . " 08:30:00"),
                                                    default => strtotime($tgl_only_db . " 09:00:00")
                                                };
                                                if ($tgl_scan_dt->getTimestamp() > $waktu_masuk_seharusnya_unix) {
                                                    $keterlambatan_menit_hari_ini = floor(($tgl_scan_dt->getTimestamp() - $waktu_masuk_seharusnya_unix) / 60);
                                                }
                                            }
                                            $jumlah_terlambat_total_menit += $keterlambatan_menit_hari_ini;
                                            $keterlambatan_display = $keterlambatan_menit_hari_ini > 0 ? $keterlambatan_menit_hari_ini . " m" : "-";

                                            $row_class = ($keterlambatan_menit_hari_ini > 0 || $is_error_masuk) ? 'table-danger' : '';

                                            echo "<tr class='" . $row_class . "'>";
                                            echo "<td class='d-none d-md-table-cell text-center'>" . $no++ . "</td>";
                                            echo "<td><span class='ps-2'>" . substr($nama_hari_idn, 0, 3) . ", </span>" . $tgl_display . "</td>";
                                            echo "<td class='text-center'><span class='shift-badge shift-" . htmlspecialchars($shift_info[1]) . "'>" . htmlspecialchars($shift_info[0]) . "</span></td>";
                                            echo "<td class='text-center'>" . $jam_scan_masuk . "</td>";
                                            echo "<td class='text-center'>" . $jam_scan_pulang . "</td>";
                                            echo "<td class='d-none d-md-table-cell text-center " . ($keterlambatan_menit_hari_ini > 0 ? 'text-danger fw-bold' : '') . "'>" . $keterlambatan_display . "</td>";
                                            echo "<td class='d-none d-md-table-cell text-center'>" . $durasi_kerja_display . "</td>";
                                            echo "<td class='d-none d-md-table-cell'></td>";
                                            echo "</tr>";
                                        }
                                        $totalJamKerja += floor($totalMenitKerja / 60);
                                        $sisaMenitKerja = $totalMenitKerja % 60;

                                        echo "<tr class='table-active fw-bold d-none d-md-table-row'>";
                                        echo "<td colspan='5' class='text-end'>TOTAL</td>";
                                        echo "<td class='text-center " . ($jumlah_terlambat_total_menit > 0 ? 'text-danger' : '') . "'>" . $jumlah_terlambat_total_menit . " m</td>";
                                        echo "<td class='text-center'>" . $totalJamKerja . "j " . $sisaMenitKerja . "m</td>";
                                        echo "<td></td>";
                                        echo "</tr>";
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center py-4'>Tidak ada data absensi untuk periode ini.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <h5 class="section-title mt-4">Ringkasan Denda Bulan Ini</h5>
                <div class="row g-3 summary-cards-container">
                    <?php
                    $denda_keterlambatan = 0;
                    if ($jumlah_terlambat_total_menit > 20) {
                        if ($jumlah_terlambat_total_menit <= 80) $denda_keterlambatan = ($jumlah_terlambat_total_menit - 20) * 300;
                        elseif ($jumlah_terlambat_total_menit <= 140) $denda_keterlambatan = (60 * 300) + (($jumlah_terlambat_total_menit - 80) * 600);
                        else $denda_keterlambatan = (60 * 300) + (60 * 600) + (($jumlah_terlambat_total_menit - 140) * 2000);
                    }
                    $denda_tidak_absen = ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang) * 25000;
                    $total_denda_keseluruhan = $denda_keterlambatan + $denda_tidak_absen;
                    ?>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card summary-card-item h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="summary-icon bg-warning text-white"><i class="fas fa-clock"></i></div>
                                    <div class="ms-3">
                                        <p class="summary-title text-muted mb-0">Total Terlambat</p>
                                        <h4 class="summary-value mb-0"><?php echo $jumlah_terlambat_total_menit; ?> <small>menit</small></h4>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <p class="mb-0 text-sm">Denda: <strong class="text-warning">Rp <?php echo number_format($denda_keterlambatan, 0, ',', '.'); ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card summary-card-item h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="summary-icon bg-danger text-white"><i class="fas fa-user-times"></i></div>
                                    <div class="ms-3">
                                        <p class="summary-title text-muted mb-0">Total Tidak Absen</p>
                                        <h4 class="summary-value mb-0"><?php echo ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang); ?> <small>kali</small></h4>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <p class="mb-0 text-sm">Denda: <strong class="text-danger">Rp <?php echo number_format($denda_tidak_absen, 0, ',', '.'); ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 col-12">
                        <div class="card summary-card-item summary-total-fine h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="summary-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                    <div class="ms-3">
                                        <p class="summary-title mb-0">Akumulasi Denda</p>
                                        <h4 class="summary-value mb-0">Rp <?php echo number_format($total_denda_keseluruhan, 0, ',', '.'); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4 mb-4 no-print">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fa-solid fa-scale-balanced title-icon me-2"></i>Informasi Perhitungan Denda</h5>
                        <div class="fine-info-table">
                            <div class="fine-info-row">
                                <div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan : 20 menit pertama</div>
                                <div class="fine-info-amount">Gratis</div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan menit ke 21 s/d 80</div>
                                <div class="fine-info-amount">Rp 300,- <span class="per-unit">/menit</span></div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan menit ke 81 s/d 140</div>
                                <div class="fine-info-amount">Rp 600,- <span class="per-unit">/menit</span></div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan setelah 140 menit</div>
                                <div class="fine-info-amount">Rp 2.000,- <span class="per-unit">/menit</span></div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition"><i class="fa-solid fa-circle-dot fine-info-icon"></i>Tidak absen (masuk/pulang)</div>
                                <div class="fine-info-amount">Rp 25.000,- <span class="per-unit">/kejadian</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer no-print">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                </div>
            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>