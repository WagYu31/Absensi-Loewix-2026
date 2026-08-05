<?php
session_start();

// Cek keamanan: Hanya admin dan superadmin yang bisa akses
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}
include '../conn.php';

$loggedInUserNip = $_SESSION['nip']; // NIP pengguna yang sedang login
$loggedInUserRole = $_SESSION['role'] ?? 'karyawan'; // Ambil role, default ke karyawan

// NIK karyawan yang datanya akan ditampilkan, diambil dari GET parameter
if (isset($_GET['nik'])) {
    $nik_to_display = $_GET['nik'];
} else {
    // Jika tidak ada NIK di URL, mungkin default ke NIK pengguna yang login atau tampilkan error
    // Untuk contoh ini, kita akan default ke NIK pengguna yang login jika dia karyawan
    // Atau bisa juga mewajibkan NIK di GET parameter.
    if ($loggedInUserRole === 'karyawan') {
        $nik_to_display = $loggedInUserNip;
    } else {
        // Jika admin dan tidak ada NIK, bisa arahkan ke halaman pilih karyawan atau tampilkan pesan
        echo "NIK karyawan tidak ditemukan di URL.";
        exit(); // Atau handle error lainnya
    }
}

// Logika filter bulan dan tahun
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"]) && isset($_POST["tahun"])) {
    $bulan_filter = $_POST["bulan"];
    $tahun_filter = $_POST["tahun"];
} else {
    $currentMonth = date('m');
    $currentYear = date('Y');

    // Default ke bulan sebelumnya
    if ($currentMonth == '01') {
        $bulan_filter = '12';
        $tahun_filter = $currentYear - 1;
    } else {
        $bulan_filter = str_pad($currentMonth - 1, 2, '0', STR_PAD_LEFT);
        $tahun_filter = $currentYear;
    }
}

// Ambil nama karyawan yang datanya ditampilkan (untuk judul halaman)
$namaKaryawanDisplay = "Karyawan"; // Default
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi <?php echo htmlspecialchars($namaKaryawanDisplay); ?> - Grav-Tech Salary</title>
    <meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
    <meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech, absensi" />
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
                        <form method="post" action="detail-absen.php?nik=<?php echo htmlspecialchars($nik_to_display); ?>">
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
                                        for ($i = $tahunSekarang; $i >= $tahunSekarang - 5; $i--) { // Rentang 5 tahun
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
                                    // Query SQL Anda untuk mengambil data absensi
                                    // (Saya akan menggunakan placeholder untuk hasil query agar fokus ke struktur)
                                    $sql = "SELECT 
                                            MIN(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_scan, 
                                            a.nip, 
                                            a.pin, 
                                            k.nik, 
                                            k.nama AS nama_karyawan, 
                                            k.shifting
                                        FROM 
                                            absen a
                                        JOIN 
                                            karyawan k ON a.nip = k.nik
                                        WHERE
                                            k.nik = '$nik_to_display' AND
                                            MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan_filter' AND
                                            YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter'
                                        GROUP BY 
                                            a.nip, DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%Y-%m-%d')
                                        ORDER BY tgl_scan ASC"; // Urutkan berdasarkan tanggal
                                    $result = $conn->query($sql);

                                    $no = 1;
                                    $jumlah_terlambat_total_menit = 0;
                                    $totalJamKerja = 0;
                                    $totalMenitKerja = 0;
                                    $jumlah_tidak_absen_masuk = 0; // Anda perlu logika untuk menghitung ini
                                    $jumlah_tidak_absen_pulang = 0; // Anda perlu logika untuk menghitung ini

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $tgl_scan_dt = new DateTime($row['tgl_scan']);
                                            $jam_scan_masuk = $tgl_scan_dt->format('H:i');
                                            $tgl_only_db = $tgl_scan_dt->format('Y-m-d'); // Format Y-m-d untuk query
                                            $tgl_display = $tgl_scan_dt->format('d/m/y');

                                            $nama_hari_eng = $tgl_scan_dt->format('l');
                                            $nama_hari_map = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
                                            $nama_hari_idn = $nama_hari_map[$nama_hari_eng] ?? $nama_hari_eng;

                                            // Ambil jam pulang
                                            $query_out = "SELECT MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
                                                        FROM absen 
                                                        WHERE nip = '" . $row['nip'] . "' AND DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '" . $tgl_only_db . "'";
                                            $res_out = $conn->query($query_out);
                                            $data_out = $res_out->fetch_assoc();
                                            $jam_scan_pulang = "-";
                                            $durasi_kerja_display = "-";

                                            if ($data_out && $data_out['tgl_out']) {
                                                $tgl_out_dt = new DateTime($data_out['tgl_out']);
                                                // Hanya set jam pulang jika berbeda dengan jam masuk (menghindari kasus hanya 1x scan)
                                                if ($tgl_out_dt != $tgl_scan_dt) {
                                                    $jam_scan_pulang = $tgl_out_dt->format('H:i');
                                                    // Hitung durasi kerja
                                                    $selisih_detik = $tgl_out_dt->getTimestamp() - $tgl_scan_dt->getTimestamp();
                                                    if ($selisih_detik > 0) {
                                                        $jam_kerja = floor($selisih_detik / 3600);
                                                        $menit_sisa = floor(($selisih_detik % 3600) / 60);
                                                        $durasi_kerja_display = $jam_kerja . "j " . $menit_sisa . "m";
                                                        $totalJamKerja += $jam_kerja;
                                                        $totalMenitKerja += $menit_sisa;
                                                    }
                                                } else {
                                                    // Kasus hanya scan sekali, dianggap tidak absen pulang jika scan setelah jam 12 siang
                                                    if ($tgl_scan_dt->format('H') >= 12) { // Scan sekali setelah jam 12, dianggap scan pulang
                                                        $jam_scan_masuk = "<span class='text-danger'>-</span>";
                                                        $jam_scan_pulang = $tgl_scan_dt->format('H:i');
                                                        $jumlah_tidak_absen_masuk++;
                                                    } else { // Scan sekali sebelum jam 12, dianggap scan masuk
                                                        $jam_scan_pulang = "<span class='text-danger'>-</span>";
                                                        $jumlah_tidak_absen_pulang++;
                                                    }
                                                }
                                            } else {
                                                $jumlah_tidak_absen_pulang++; // Tidak ada data scan pulang
                                            }


                                            $current_shifting = $row["shifting"];
                                            if ($nama_hari_eng == "Saturday") {
                                                $current_shifting = ($current_shifting == "T") ? "TW" : "W";
                                            }

                                            $shift_display_map = ["P" => ["S1", "P"], "M" => ["S2", "M"], "S" => ["S3", "S"], "T" => ["HC", "T"], "W" => ["Sbt", "W"], "TW" => ["HS", "TW"]];
                                            $shift_info = $shift_display_map[$current_shifting] ?? [$current_shifting, ""];

                                            // Hitung keterlambatan
                                            $keterlambatan_menit_hari_ini = 0;
                                            if ($jam_scan_masuk !== "<span class='text-danger'>-</span>") {
                                                $waktu_masuk_seharusnya_unix = match ($current_shifting) {
                                                    "P" => strtotime($tgl_only_db . " 07:00:00"),
                                                    "M" => strtotime($tgl_only_db . " 08:30:00"),
                                                    "S", "T", "TW" => strtotime($tgl_only_db . " 09:30:00"),
                                                    "W" => strtotime($tgl_only_db . " 08:30:00"),
                                                    default => strtotime($tgl_only_db . " 09:00:00")
                                                };
                                                $waktu_scan_masuk_unix = $tgl_scan_dt->getTimestamp();
                                                if ($waktu_scan_masuk_unix > $waktu_masuk_seharusnya_unix) {
                                                    $keterlambatan_menit_hari_ini = floor(($waktu_scan_masuk_unix - $waktu_masuk_seharusnya_unix) / 60);
                                                }
                                            }
                                            $jumlah_terlambat_total_menit += $keterlambatan_menit_hari_ini;
                                            $keterlambatan_display = $keterlambatan_menit_hari_ini > 0 ? $keterlambatan_menit_hari_ini . " m" : "-";

                                            // Tentukan kelas untuk baris berdasarkan keterlambatan
                                            $row_class = '';
                                            if ($keterlambatan_menit_hari_ini > 0) {
                                                $row_class = 'table-danger'; // Kelas Bootstrap untuk background merah pucat
                                            }

                                            // Modifikasi echo untuk <tr> dengan menambahkan $row_class
                                            echo "<tr class='" . $row_class . "'>";
                                            echo "<td class='d-none d-md-table-cell text-center'>" . $no++ . "</td>";
                                            echo "<td><span class='ps-2'>" . substr($nama_hari_idn, 0, 3) . ", </span>" . $tgl_display . "</td>";
                                            echo "<td class='text-center'><span class='shift-badge shift-" . htmlspecialchars($shift_info[1]) . "'>" . htmlspecialchars($shift_info[0]) . "</span></td>";
                                            echo "<td class='text-center'>" . $jam_scan_masuk . "</td>";
                                            echo "<td class='text-center'>" . $jam_scan_pulang . "</td>";
                                            // Kelas text-danger dan fw-bold sudah ada untuk sel keterlambatan, akan tetap berfungsi dengan baik
                                            echo "<td class='d-none d-md-table-cell text-center " . ($keterlambatan_menit_hari_ini > 0 ? 'text-danger fw-bold' : '') . "'>" . $keterlambatan_display . "</td>";
                                            echo "<td class='d-none d-md-table-cell text-center'>" . $durasi_kerja_display . "</td>";
                                            echo "<td class='d-none d-md-table-cell'></td>"; // Keterangan
                                            echo "</tr>";
                                        }
                                        // Konversi total menit kerja ke jam dan menit
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
                    // Inisialisasi variabel denda keterlambatan dengan nilai awal 0
                    $denda_keterlambatan = 0;
                    
                    // Cek apakah total keterlambatan lebih dari 20 menit (karena menit 1-20 gratis)
                    if ($jumlah_terlambat_total_menit > 20) {
                    
                        // Jika terlambat antara 21 - 80 menit
                        if ($jumlah_terlambat_total_menit <= 80) {
                            // Denda adalah sisa menit setelah 20 menit dikali 300
                            $denda_keterlambatan = ($jumlah_terlambat_total_menit - 20) * 300;
                        } 
                        // Jika terlambat antara 81 - 140 menit
                        elseif ($jumlah_terlambat_total_menit <= 140) {
                            // Denda penuh untuk jenjang pertama (60 menit * 300)
                            $denda_jenjang_1 = 60 * 300; 
                            // Ditambah denda untuk sisa menit di jenjang kedua (total menit dikurangi 80) dikali 600
                            $denda_jenjang_2 = ($jumlah_terlambat_total_menit - 80) * 600;
                            $denda_keterlambatan = $denda_jenjang_1 + $denda_jenjang_2;
                        } 
                        // Jika terlambat lebih dari 140 menit
                        else {
                            // Denda penuh untuk jenjang pertama (60 menit * 300)
                            $denda_jenjang_1 = 60 * 300;
                            // Denda penuh untuk jenjang kedua (60 menit * 600)
                            $denda_jenjang_2 = 60 * 600;
                            // Ditambah denda untuk sisa menit di jenjang ketiga (total menit dikurangi 140) dikali 2000
                            $denda_jenjang_3 = ($jumlah_terlambat_total_menit - 140) * 2000;
                            $denda_keterlambatan = $denda_jenjang_1 + $denda_jenjang_2 + $denda_jenjang_3;
                        }
                    }
                    
                    // Menghitung denda karena tidak absen (logika ini tidak diubah)
                    $denda_tidak_absen = ($jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang) * 25000;
                    
                    // Menjumlahkan total denda dari keterlambatan dan tidak absen
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
                                <div class="fine-info-condition">
                                    <i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan : 20 menit pertama
                                </div>
                                <div class="fine-info-amount">Gratis</div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition">
                                    <i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan menit ke 21 s/d 80
                                </div>
                                <div class="fine-info-amount">Rp 300,- <span class="per-unit">/menit</span></div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition">
                                    <i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan menit ke 81 s/d 140
                                </div>
                                <div class="fine-info-amount">Rp 600,- <span class="per-unit">/menit</span></div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition">
                                    <i class="fa-solid fa-circle-dot fine-info-icon"></i>Keterlambatan setelah 140 menit
                                </div>
                                <div class="fine-info-amount">Rp 2.000,- <span class="per-unit">/menit</span></div>
                            </div>
                            <div class="fine-info-row">
                                <div class="fine-info-condition">
                                    <i class="fa-solid fa-circle-dot fine-info-icon"></i>Tidak absen (masuk/pulang)
                                </div>
                                <div class="fine-info-amount">Rp 25.000,- <span class="per-unit">/kejadian</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer no-print">
                    Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.
                    <br><small>Version 1.1.0</small>
                </div>

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

            // Active state untuk Sidebar Desktop
            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                var linkNik = $(this).attr('href').split("nik=")[1] || loggedInUserNik; // Ambil NIK dari link sidebar

                if (linkHref === currentPath && linkNik === loggedInUserNik) { // Hanya aktifkan jika NIK di link sidebar = NIK user login
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            if (currentPath === "absen.php" && !$('.sidebar-menu a[href^="absen.php"]').hasClass('active') && loggedInUserNik === currentNikInUrl) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="absen.php?nik=' + loggedInUserNik + '"]').addClass('active');
            }


            // Active state untuk Custom Mobile Bottom Navigation
            $('.custom-nav__link').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.custom-nav__link.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
            // FAB button active state (jika halaman absen yang NIKnya = NIK user login)
            var fabLinkAbsen = $('.custom-nav__fab-button').attr('href').split("?")[0];
            var fabNik = $('.custom-nav__fab-button').attr('href').split("nik=")[1] || "";

            if (currentPath === fabLinkAbsen && currentNikInUrl === loggedInUserNik) {
                // Anda bisa menambahkan style khusus untuk FAB aktif jika diinginkan,
                // misal mengubah warna background atau shadow, tapi link parentnya (Home/Profile) harus non-aktif
                $('.custom-nav__link.active').removeClass('active');
                // Tidak ada class .active standard untuk FAB, tapi textnya sudah berwarna
            }
            // Jika tidak ada custom-nav-link yang aktif dan ini halaman absen.php dengan NIK user login, maka jangan aktifkan Home/Profile
            if (currentPath === "absen.php" && currentNikInUrl === loggedInUserNik && $('.custom-nav__link.active').length > 0) {
                $('.custom-nav__link.active').removeClass('active');
            }


            // Inisialisasi Tooltip Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>