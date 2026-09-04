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
    <link rel="stylesheet" href="../assets/css/absen-styles.css?v=2026.09.05.1">
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
                                    $denda_detail_list = [];

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
                                            $is_error_pulang = false;

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
                                                        $is_error_pulang = true;
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
                                                        $is_error_pulang = true;
                                                    } else {
                                                        $jam_scan_pulang = $jam_scan_pulang_raw;
                                                    }

                                                    if (!$is_error_masuk && !$is_error_pulang) {
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

                                            $is_late = ($keterlambatan_menit_hari_ini > 0);
                                            if ($is_late || $is_error_masuk || $is_error_pulang) {
                                                $denda_detail_list[] = [
                                                    'tanggal_str' => $tgl_only_db,
                                                    'tanggal_display' => substr($nama_hari_idn, 0, 3) . ', ' . $tgl_display,
                                                    'shift_badge' => $shift_info[1] ?? '',
                                                    'shift_code' => $shift_info[0] ?? '-',
                                                    'jam_masuk' => $jam_scan_masuk,
                                                    'jam_pulang' => $jam_scan_pulang,
                                                    'terlambat_menit' => $keterlambatan_menit_hari_ini,
                                                    'durasi_kerja' => $durasi_kerja_display,
                                                    'is_late' => $is_late,
                                                    'is_miss_in' => $is_error_masuk,
                                                    'is_miss_out' => $is_error_pulang,
                                                    'is_alpha' => false,
                                                ];
                                            }

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
                        <div class="card summary-card-item h-100 clickable-summary-card" role="button" data-bs-toggle="modal" data-bs-target="#modalDetailDenda" style="cursor: pointer;" title="Klik untuk melihat rincian keterlambatan">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="summary-icon bg-warning text-white"><i class="fas fa-clock"></i></div>
                                    <div class="ms-3">
                                        <p class="summary-title text-muted mb-0">Total Terlambat</p>
                                        <h4 class="summary-value mb-0"><?php echo $jumlah_terlambat_total_menit; ?> <small>menit</small></h4>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="mb-0 text-sm">Denda: <strong class="text-warning">Rp <?php echo number_format($denda_keterlambatan, 0, ',', '.'); ?></strong></p>
                                    <small class="text-primary fw-semibold"><i class="fas fa-eye me-1"></i>Rincian</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card summary-card-item h-100 clickable-summary-card" role="button" data-bs-toggle="modal" data-bs-target="#modalDetailDenda" style="cursor: pointer;" title="Klik untuk melihat rincian tidak absen">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="summary-icon bg-danger text-white"><i class="fas fa-user-times"></i></div>
                                    <div class="ms-3">
                                        <p class="summary-title text-muted mb-0">Total Tidak Absen</p>
                                        <h4 class="summary-value mb-0"><?php echo ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang); ?> <small>kali</small></h4>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="mb-0 text-sm">Denda: <strong class="text-danger">Rp <?php echo number_format($denda_tidak_absen, 0, ',', '.'); ?></strong></p>
                                    <small class="text-primary fw-semibold"><i class="fas fa-eye me-1"></i>Rincian</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 col-12">
                        <div class="card summary-card-item summary-total-fine h-100 clickable-summary-card" role="button" data-bs-toggle="modal" data-bs-target="#modalDetailDenda" style="cursor: pointer;" title="Klik untuk melihat rincian tanggal kena denda">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="summary-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                    <div class="ms-3">
                                        <p class="summary-title mb-0">Akumulasi Denda</p>
                                        <h4 class="summary-value mb-0">Rp <?php echo number_format($total_denda_keseluruhan, 0, ',', '.'); ?></h4>
                                    </div>
                                </div>
                                <div class="mt-2 text-white-50 small d-flex align-items-center justify-content-between pt-1" style="border-top: 1px solid rgba(255,255,255,0.25);">
                                    <span><i class="fas fa-search-plus me-1"></i>Klik rincian tanggal denda</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
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

    <!-- Modal Rincian Detail Denda & Tanggal (Karyawan) -->
    <div class="modal fade" id="modalDetailDenda" tabindex="-1" aria-labelledby="modalDetailDendaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <!-- Modal Header with Gradient -->
                <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                            <i class="fas fa-receipt fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalDetailDendaLabel">Rincian Tanggal & Denda Presensi</h5>
                            <small class="text-white-50">
                                <?php echo htmlspecialchars($namaKaryawanDisplay); ?> &bull; <?php echo htmlspecialchars($bulanNames[$bulan_filter] . " " . $tahun_filter); ?>
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- Summary Mini Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4 col-12">
                            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #ffc107 !important;">
                                <div class="card-body p-3">
                                    <div class="text-muted small fw-semibold">Total Keterlambatan</div>
                                    <div class="fs-4 fw-bold text-dark my-1"><?php echo $jumlah_terlambat_total_menit; ?> <small class="fs-6 text-muted">menit</small></div>
                                    <div class="small text-warning fw-semibold">Denda: Rp <?php echo number_format($denda_keterlambatan, 0, ',', '.'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #dc3545 !important;">
                                <div class="card-body p-3">
                                    <div class="text-muted small fw-semibold">Total Tidak Absen</div>
                                    <div class="fs-4 fw-bold text-dark my-1"><?php echo ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang); ?> <small class="fs-6 text-muted">kali</small></div>
                                    <div class="small text-danger fw-semibold">Denda: Rp <?php echo number_format($denda_tidak_absen, 0, ',', '.'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="card border-0 shadow-sm rounded-3 text-white h-100" style="background: linear-gradient(135deg, #2979ff 0%, #1565c0 100%);">
                                <div class="card-body p-3">
                                    <div class="text-white-50 small fw-semibold">Total Akumulasi Denda</div>
                                    <div class="fs-4 fw-bold text-white my-1">Rp <?php echo number_format($total_denda_keseluruhan, 0, ',', '.'); ?></div>
                                    <div class="small text-white-50"><i class="fas fa-calendar-alt me-1"></i><?php echo count($denda_detail_list); ?> Tanggal Terdampak</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Tanggal yang Kena Denda / Pelanggaran -->
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-xmark text-danger me-2"></i>Daftar Tanggal Pelanggaran & Keterlambatan</h6>
                            <span class="badge bg-danger rounded-pill px-3 py-2"><?php echo count($denda_detail_list); ?> Kejadian</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.88rem;">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Masuk</th>
                                        <th>Pulang</th>
                                        <th>Keterlambatan</th>
                                        <th>Jenis Pelanggaran / Rincian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($denda_detail_list)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-success">
                                                <i class="fas fa-check-circle fa-3x mb-2 d-block text-success opacity-75"></i>
                                                <strong>Luar Biasa! Tidak ada pelanggaran atau denda di bulan ini.</strong><br>
                                                <small class="text-muted">Kehadiran tepat waktu dan presensi lengkap setiap hari kerja.</small>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no_d = 1; foreach ($denda_detail_list as $item): ?>
                                            <tr>
                                                <td class="text-center fw-bold text-muted"><?php echo $no_d++; ?></td>
                                                <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($item['tanggal_display']); ?></td>
                                                <td class="text-center">
                                                    <span class="shift-badge shift-<?php echo htmlspecialchars($item['shift_badge']); ?>">
                                                        <?php echo htmlspecialchars($item['shift_code']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><?php echo $item['jam_masuk']; ?></td>
                                                <td class="text-center"><?php echo $item['jam_pulang']; ?></td>
                                                <td class="text-center">
                                                    <?php if ($item['terlambat_menit'] > 0): ?>
                                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                                            <i class="fas fa-clock me-1"></i><?php echo $item['terlambat_menit']; ?> menit
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($item['is_late']): ?>
                                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning">
                                                                <i class="fas fa-hourglass-half me-1"></i>Telat <?php echo $item['terlambat_menit']; ?>m
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($item['is_miss_in']): ?>
                                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">
                                                                <i class="fas fa-user-xmark me-1"></i>Tidak Absen Masuk (+Rp 25.000)
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($item['is_miss_out']): ?>
                                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">
                                                                <i class="fas fa-arrow-right-from-bracket me-1"></i>Tidak Absen Pulang (+Rp 25.000)
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Breakdown Skema Perhitungan Denda -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-2 border-0">
                            <small class="fw-bold text-uppercase text-muted"><i class="fas fa-calculator me-1 text-primary"></i>Simulasi Rincian Skema Perhitungan</small>
                        </div>
                        <div class="card-body pt-0 pb-3" style="font-size: 0.85rem;">
                            <ul class="list-group list-group-flush">
                                <?php
                                $t1_menit = min($jumlah_terlambat_total_menit, 20);
                                $t2_menit = ($jumlah_terlambat_total_menit > 20) ? min($jumlah_terlambat_total_menit - 20, 60) : 0;
                                $t3_menit = ($jumlah_terlambat_total_menit > 80) ? min($jumlah_terlambat_total_menit - 80, 60) : 0;
                                $t4_menit = ($jumlah_terlambat_total_menit > 140) ? ($jumlah_terlambat_total_menit - 140) : 0;

                                $t1_denda = 0;
                                $t2_denda = $t2_menit * 300;
                                $t3_denda = $t3_menit * 600;
                                $t4_denda = $t4_menit * 2000;
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span>1. Toleransi 20 Menit Pertama (0 - 20 m): <strong><?php echo $t1_menit; ?> m</strong></span>
                                    <span class="badge bg-success bg-opacity-10 text-success">Gratis (Rp 0)</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span>2. Menit ke 21 s/d 80: <strong><?php echo $t2_menit; ?> m</strong> &times; Rp 300</span>
                                    <span class="fw-semibold">Rp <?php echo number_format($t2_denda, 0, ',', '.'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span>3. Menit ke 81 s/d 140: <strong><?php echo $t3_menit; ?> m</strong> &times; Rp 600</span>
                                    <span class="fw-semibold">Rp <?php echo number_format($t3_denda, 0, ',', '.'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span>4. Di atas 140 Menit: <strong><?php echo $t4_menit; ?> m</strong> &times; Rp 2.000</span>
                                    <span class="fw-semibold">Rp <?php echo number_format($t4_denda, 0, ',', '.'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                    <span>5. Denda Tidak Absen: <strong><?php echo ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang); ?> kali</strong> &times; Rp 25.000</span>
                                    <span class="fw-semibold">Rp <?php echo number_format($denda_tidak_absen, 0, ',', '.'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 bg-primary bg-opacity-10 mt-2 rounded-2 fw-bold text-primary">
                                    <span>TOTAL AKUMULASI DENDA</span>
                                    <span>Rp <?php echo number_format($total_denda_keseluruhan, 0, ',', '.'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-0 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'nav/bottom-nav.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>