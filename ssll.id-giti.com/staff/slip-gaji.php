<?php
session_start();

if (!isset($_SESSION['nip'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$nip = null;

if (isset($_GET['nip']) && !empty($_GET['nip'])) {
    $nip = $_GET['nip'];
}
else if (isset($_POST['nip']) && !empty($_POST['nip'])) {
    $nip = $_POST['nip'];
}
else if (isset($_SESSION['nip']) && !empty($_SESSION['nip'])) {
    $nip = $_SESSION['nip'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulan"]) && isset($_POST["tahun"])) {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
}
else if (isset($_GET["bulan"]) && isset($_GET["tahun"])) {
    $bulan = $_GET["bulan"];
    $tahun = $_GET["tahun"];
}
else {
    $bulan = date('m');
    $tahun = date('Y');
}

if ($nip === null) {
    die("Akses tidak valid. NIP karyawan tidak ditemukan.");
}

$tahun_int = (int)$tahun;
$bulan_int = (int)$bulan;

$tanggal = new DateTime();
$tanggal->setDate($tahun_int, $bulan_int, 1); 
$tanggal->modify('last day of this month'); 

while ($tanggal->format('N') != 6) {
    $tanggal->modify('-1 day');
}
$tanggalPembayaranObjek = $tanggal;
$tanggalPembayaranFormatted = $tanggalPembayaranObjek->format('d F Y'); 
$end_date_filter_dt = $tanggalPembayaranObjek; 

$query = "SELECT karyawan.*, 
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = '$bulan' AND YEAR(tunjangan_lainnya.tanggal) = '$tahun') AS total_tunjangan_lainnya,
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = '$bulan' AND YEAR(tunjangan_lainnya.tanggal) = '$tahun' AND tunjangan_lainnya.ket1 = 'ganti') AS total_tunjangan_lainnya_ganti,
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = '$bulan' AND YEAR(tunjangan_lainnya.tanggal) = '$tahun' AND tunjangan_lainnya.ket1 = 'bonus') AS total_tunjangan_lainnya_bonus,
                (SELECT SUM(jumlah) FROM denda WHERE denda.nip = karyawan.nip AND MONTH(denda.tanggal) = '$bulan' AND YEAR(denda.tanggal) = '$tahun') AS total_denda,
                (SELECT SUM(bayar) FROM bayar_cashbon WHERE bayar_cashbon.nip = karyawan.nip AND MONTH(bayar_cashbon.tanggal) = '$bulan' AND YEAR(bayar_cashbon.tanggal) = '$tahun') AS total_cashbon
        FROM karyawan
        WHERE karyawan.nip = '$nip'";

$result = $conn->query($query);
if (!$result) die("Query execution failed: " . $conn->error);

$query2 = "SELECT tunjangan_lainnya.* FROM tunjangan_lainnya 
            WHERE nip = '$nip' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'";
$result2 = $conn->query($query2);

include '../get-query-4.php'; 
include '../get-query-5.php'; 
include '../get-query-6.php';

$query3 = "SELECT denda.* FROM denda 
            WHERE nip = '$nip' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'";
$result3 = $conn->query($query3);

$employee = $result->fetch_assoc();
include '../sa-get-tunjangan-masa-kerja.php';
$tunjangan_masa_kerja = $dataTMK['tunjangan_masa_kerja'] ?? 0; 

$query9 = "SELECT gaji, tunjangan_jabatan FROM rincian_gaji 
            WHERE nip = '$nip' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'";
$result9 = $conn->query($query9);
$emp = $result9->fetch_assoc();

$gajiIt = ($emp && $emp['gaji'] != 0) ? $emp['gaji'] : $employee['gaji_pokok'];
$gaji1 = ($employee['jenis_gaji'] == 'mingguan') ? $employee['gaji_1'] : 0;
$tunJabatan = ($emp && $emp['tunjangan_jabatan'] != 0) ? $emp['tunjangan_jabatan'] : 0;

$total_gapok = $gajiIt + 
    ($tunJabatan ?? 0) +
    ($tunjangan_masa_kerja ?? 0);

if (!function_exists('hitungHariKerjaCuti')) {
    function hitungHariKerjaCuti($tgl_mulai, $tgl_selesai, $holidays) {
        if (empty($tgl_mulai) || empty($tgl_selesai) || $tgl_mulai == '0000-00-00' || $tgl_selesai == '0000-00-00') {
            return 0;
        }
        try {
            $start = new DateTime($tgl_mulai);
            $end = new DateTime($tgl_selesai);
            if ($start > $end) return 0;
            
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);
            $duration = 0;
            
            foreach ($period as $date) {
                $dayOfWeek = $date->format('N');
                $dateString = $date->format('Y-m-d');
                if ($dayOfWeek != 7 && !isset($holidays[$dateString])) {
                    $duration++;
                }
            }
            return $duration;
        } catch (Exception $e) {
            return 0;
        }
    }
}

$tanggal_periode_sebelumnya = (new DateTime("$tahun-$bulan-01"))->modify('-1 month');
$bulan_sebelumnya = $tanggal_periode_sebelumnya->format('m');
$tahun_sebelumnya = $tanggal_periode_sebelumnya->format('Y');

$holidays = [];
$stmt_holidays = $conn->prepare("SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND (YEAR(tanggal_merah) = ? OR YEAR(tanggal_merah) = ?) AND deleted_at IS NULL");
$stmt_holidays->bind_param("ss", $tahun, $tahun_sebelumnya);
$stmt_holidays->execute();
$result_holidays = $stmt_holidays->get_result();
while ($row = $result_holidays->fetch_assoc()) {
    if (!empty($row['tanggal_merah'])) {
        $holidays[$row['tanggal_merah']] = true;
    }
}
$stmt_holidays->close();

$global_jatah_cuti = 0;
$stmt_quota = $conn->prepare("SELECT jumlah FROM jatah_cuti_tahunan WHERE tahun = ? LIMIT 1");
$stmt_quota->bind_param("s", $tahun_sebelumnya);
$stmt_quota->execute();
$result_quota = $stmt_quota->get_result();
if ($result_quota->num_rows > 0) {
    $global_jatah_cuti = (int)$result_quota->fetch_assoc()['jumlah'];
}
$stmt_quota->close();

$jatah_cuti_karyawan_ini = 0;
$tanggal_masuk_str = $employee['tanggal_masuk'];
$end_date_filter_dt_for_cuti = new DateTime("last day of $tahun_sebelumnya-$bulan_sebelumnya-01");

if (!empty($tanggal_masuk_str) && $tanggal_masuk_str != '0000-00-00') {
    try {
        $tgl_masuk_plus_6_bulan = (new DateTime($tanggal_masuk_str))->modify('+6 months');
        if ($tgl_masuk_plus_6_bulan <= $end_date_filter_dt_for_cuti) {
            $jatah_cuti_karyawan_ini = $global_jatah_cuti;
        }
    } catch (Exception $e) { }
}

$total_denda_cuti = 0;
$total_cuti_terpakai_ytd = 0;
$total_cuti_terpakai_bulan_sebelumnya = 0;

$year_start_str = "$tahun_sebelumnya-01-01";
$end_date_filter_str = $end_date_filter_dt_for_cuti->format('Y-m-d');

$stmt_cuti_ytd = $conn->prepare("SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip = ? AND verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND tgl_selesai >= ? AND tgl_mulai <= ?");
$stmt_cuti_ytd->bind_param("sss", $nip, $year_start_str, $end_date_filter_str);
$stmt_cuti_ytd->execute();
$result_cuti_ytd = $stmt_cuti_ytd->get_result();

while ($cuti_row = $result_cuti_ytd->fetch_assoc()) {
    $cuti_start = new DateTime(max($cuti_row['tgl_mulai'], $year_start_str));
    $cuti_end = new DateTime(min($cuti_row['tgl_selesai'], $end_date_filter_str));
    $total_cuti_terpakai_ytd += hitungHariKerjaCuti($cuti_start->format('Y-m-d'), $cuti_end->format('Y-m-d'), $holidays);
}
$stmt_cuti_ytd->close();

$start_date_bulan_sebelumnya_str = "$tahun_sebelumnya-$bulan_sebelumnya-01";
$end_date_bulan_sebelumnya_str = $end_date_filter_str;

$stmt_cuti_bulan = $conn->prepare("SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip = ? AND verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND tgl_selesai >= ? AND tgl_mulai <= ?");
$stmt_cuti_bulan->bind_param("sss", $nip, $start_date_bulan_sebelumnya_str, $end_date_bulan_sebelumnya_str);
$stmt_cuti_bulan->execute();
$result_cuti_bulan = $stmt_cuti_bulan->get_result();

while ($cuti_row_bulan = $result_cuti_bulan->fetch_assoc()) {
    $cuti_start_bulan = new DateTime(max($cuti_row_bulan['tgl_mulai'], $start_date_bulan_sebelumnya_str));
    $cuti_end_bulan = new DateTime(min($cuti_row_bulan['tgl_selesai'], $end_date_bulan_sebelumnya_str));
    $total_cuti_terpakai_bulan_sebelumnya += hitungHariKerjaCuti($cuti_start_bulan->format('Y-m-d'), $cuti_end_bulan->format('Y-m-d'), $holidays);
}
$stmt_cuti_bulan->close();

$hari_kena_denda = 0;
$sisa_cuti_sebelum_bulan_lalu = $jatah_cuti_karyawan_ini - ($total_cuti_terpakai_ytd - $total_cuti_terpakai_bulan_sebelumnya);

if ($sisa_cuti_sebelum_bulan_lalu < $total_cuti_terpakai_bulan_sebelumnya) {
    $hari_kena_denda = $total_cuti_terpakai_bulan_sebelumnya - max(0, $sisa_cuti_sebelum_bulan_lalu);
}

if ($hari_kena_denda > 0) {
    $total_gaji_untuk_denda = $total_gapok;
    $denda_per_hari = $total_gaji_untuk_denda / 26;
    $total_denda_cuti = $denda_per_hari * $hari_kena_denda;
}

$totalGaji = $gajiIt +
    ($tunJabatan ?? 0) +
    ($tunjangan_masa_kerja ?? 0) +
    ($employee['total_tunjangan_lainnya_ganti'] ?? 0) +
    ($employee['total_tunjangan_lainnya_bonus'] ?? 0) -
    ($employee['total_denda'] ?? 0) -
    ($total_denda_cuti ?? 0) -
    ($employee['total_cashbon'] ?? 0);

$nik_karyawan = $employee['nik'] ?? 'N/A'; 
$nik = $employee['nik'] ?? 'N/A'; 
$nama_karyawan = $employee['nama'] ?? 'Nama Karyawan';
$jabatan_karyawan = $employee['jabatan'] ?? 'Jabatan';

$periodeGajiTerpilihFormatted = date('F Y', mktime(0, 0, 0, (int)$bulan, 1, (int)$tahun));

$rincianPendapatan = [];
$rincianPendapatan[] = ['deskripsi' => 'Gaji Pokok', 'jumlah' => (float)$gajiIt];
if (!empty($tunJabatan) && (float)$tunJabatan > 0) {
    $rincianPendapatan[] = ['deskripsi' => 'Tunjangan Jabatan', 'jumlah' => (float)$tunJabatan];
}
if (!empty($tunjangan_masa_kerja) && (float)$tunjangan_masa_kerja > 0) {
    $rincianPendapatan[] = ['deskripsi' => 'Tunjangan Masa Kerja', 'jumlah' => (float)$tunjangan_masa_kerja];
}
if (!empty($employee['total_tunjangan_lainnya_bonus']) && (float)$employee['total_tunjangan_lainnya_bonus'] > 0) {
    $rincianPendapatan[] = ['deskripsi' => 'Bonus', 'jumlah' => (float)$employee['total_tunjangan_lainnya_bonus']];
}
if (!empty($employee['total_tunjangan_lainnya_ganti']) && (float)$employee['total_tunjangan_lainnya_ganti'] > 0) {
    $rincianPendapatan[] = ['deskripsi' => 'Pendapatan Lain (Ganti)', 'jumlah' => (float)$employee['total_tunjangan_lainnya_ganti']];
}

$subTotalPendapatan = 0;
foreach ($rincianPendapatan as $item) {
    $subTotalPendapatan += $item['jumlah'];
}

$rincianPotongan = [];
if (!empty($employee['total_denda']) && (float)$employee['total_denda'] > 0) {
    $rincianPotongan[] = ['deskripsi' => 'Denda', 'jumlah' => (float)$employee['total_denda']];
}
if (!empty($total_denda_cuti) && (float)$total_denda_cuti > 0) {
    $rincianPotongan[] = ['deskripsi' => 'Denda Cuti', 'jumlah' => (float)$total_denda_cuti];
}
if (!empty($employee['total_cashbon']) && (float)$employee['total_cashbon'] > 0) {
    $rincianPotongan[] = ['deskripsi' => 'Bayar Cashbon', 'jumlah' => (float)$employee['total_cashbon']];
}
if ($employee['jenis_gaji'] == 'mingguan' && (float)$gaji1 > 0) {
    $rincianPotongan[] = ['deskripsi' => 'Gaji Mingguan Telah Dibayar', 'jumlah' => (float)$gaji1];
}

$subTotalPotongan = 0;
foreach ($rincianPotongan as $item) {
    $subTotalPotongan += $item['jumlah'];
}
$gajiBersihFinal = $totalGaji - $gaji1;

$current_page_basename = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?php echo htmlspecialchars($nama_karyawan); ?> - <?php echo $periodeGajiTerpilihFormatted; ?></title>
    <meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
    <meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech" />
    <meta name="author" content="Irviani" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/slip-gaji-styles.css">
</head>

<body>

    <?php include 'nav/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="header-banner slip-gaji-header no-print">
            <div class="container-fluid px-lg-4">
                <h1>Slip Gaji Karyawan</h1>
                <p>Detail perhitungan gaji Anda untuk periode terpilih.</p>
            </div>
        </div>

        <div class="dashboard-content slip-gaji-content">
            <div class="container-fluid px-lg-4 px-1">

                <div class="card mb-4 no-print filter-slip-card">
                    <div class="card-body">
                        <form method="POST" action="slip-gaji.php" class="row gx-2 gy-3 align-items-end">
                            <div class="col-md-4 col-sm-6">
                                <label for="bulan" class="form-label">Bulan :</label>
                                <select id="bulan" name="bulan" class="form-select form-select-sm">
                                    <?php
                                    $bulanNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                    foreach ($bulanNames as $bulanNum => $bulanName) {
                                        $selected = ($bulanNum == $bulan) ? 'selected' : '';
                                        echo "<option value='$bulanNum' $selected>$bulanName</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label for="tahun" class="form-label">Tahun :</label>
                                <select id="tahun" name="tahun" class="form-select form-select-sm">
                                    <?php
                                    $tahunSekarang = date('Y');
                                    for ($i = $tahunSekarang; $i >= $tahunSekarang - 5; $i--) {
                                        $selected = ($i == $tahun) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <input type="hidden" name="nip" value="<?php echo $nip;?>">
                            <div class="col-md-2 col-sm-6">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Tampilkan</button>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-print me-1"></i> Cetak
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="salary-slip-container mx-auto">
                    <div class="salary-slip">
                        <div class="slip-header no-print">
                            <div class="company-logo me-0 d-none d-md-flex">
                                <i class="fa-solid fa-building-columns fa-2x text-primary"></i>
                            </div>
                            <div class="company-details">
                                <i class="fa-solid fa-building-columns fa-2x text-primary my-2 d-md-none"></i>
                                <h2 class="company-name me-0">Gravitti Technology</h2>
                                <p class="company-address">Jl. Ruko Toho Pantai Indah Blok L No. 8, Kapuk, Daerah Khusus Ibukota Jakarta 14470</p>
                            </div>
                            <div class="slip-title-section">
                                <h3 class="slip-title">SLIP GAJI KARYAWAN</h3>
                                <p class="slip-period">Periode : <?php echo htmlspecialchars($periodeGajiTerpilihFormatted); ?></p>
                                <p class="slip-paydate">Tanggal Pembayaran : <?php echo htmlspecialchars($tanggalPembayaranFormatted); ?></p>
                            </div>
                        </div>

                        <div class="slip-header-for-print">
                            <div class="row">
                                <div class="slip-header col-6" style="border:none;">
                                    <div class="company-details">
                                        <h2 class="company-name me-0">Gravitti Technology</h2>
                                        <p class="company-address">Jl. Ruko Toho Pantai Indah Blok L No. 8, Kapuk, Daerah Khusus Ibukota Jakarta 14470</p>
                                    </div>
                                </div>

                                <div class="slip-title-section col-6">
                                    <h3 class="slip-title">SLIP GAJI KARYAWAN</h3>
                                    <p class="slip-period">Periode : <?php echo htmlspecialchars($periodeGajiTerpilihFormatted); ?></p>
                                    <p class="slip-paydate">Pembayaran : <?php echo htmlspecialchars($tanggalPembayaranFormatted); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="employee-details-slip">
                            <div class="row">
                                <div class="col-md-7 my-0">
                                    <p><strong>Nama</strong> : <?php echo htmlspecialchars($nama_karyawan); ?></p>
                                    <p><strong>NIK</strong> : <?php echo htmlspecialchars($nik_karyawan); ?>
                                    </p>
                                </div>
                                <div class="col-md-5 my-0">
                                    <p><strong>Jabatan</strong> : <?php echo htmlspecialchars($jabatan_karyawan); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="salary-details">
                            <div class="row">
                                <div class="col-md-6 section-earnings mb-3 mb-md-0">
                                    <h4 class="section-title-slip">PENDAPATAN</h4>
                                    <table class="table table-sm table-borderless details-table">
                                        <tbody>
                                            <?php if (empty($rincianPendapatan)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-muted text-center"><em>Tidak ada data pendapatan.</em></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($rincianPendapatan as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['deskripsi']); ?></td>
                                                        <td class="text-end">Rp <?php echo number_format($item['jumlah'], 0, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-row">
                                                <th class="text-uppercase">Subtotal Pendapatan</th>
                                                <th class="text-end">Rp <?php echo number_format($subTotalPendapatan, 0, ',', '.'); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="col-md-6 section-deductions">
                                    <h4 class="section-title-slip">POTONGAN</h4>
                                    <table class="table table-sm table-borderless details-table">
                                        <tbody>
                                            <?php if (empty($rincianPotongan)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-muted text-center"><em>Tidak ada data potongan.</em></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($rincianPotongan as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['deskripsi']); ?></td>
                                                        <td class="text-end text-danger">- Rp <?php echo number_format($item['jumlah'], 0, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-row">
                                                <th class="text-uppercase">Subtotal Potongan</th>
                                                <th class="text-end text-danger">- Rp <?php echo number_format($subTotalPotongan, 0, ',', '.'); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="net-salary-section">
                            <div class="row justify-content-end">
                                <div class="col-md-7 col-lg-6">
                                    <table class="table table-sm table-borderless net-salary-table">
                                        <tbody>
                                            <tr>
                                                <td>Total Pendapatan (A)</td>
                                                <td class="text-end">Rp <?php echo number_format($subTotalPendapatan, 0, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Total Potongan (B)</td>
                                                <td class="text-end">Rp <?php echo number_format($subTotalPotongan, 0, ',', '.'); ?></td>
                                            </tr>
                                            <tr class="net-pay-row">
                                                <th class="text-uppercase">GAJI DITERIMA (A - B)</th>
                                                <th class="text-end">Rp <?php echo number_format($gajiBersihFinal, 0, ',', '.'); ?></th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($employee['nama_bank']) && !empty($employee['nomor_rekening'])): ?>
                            <div class="bank-details-slip mt-3">
                                <p class="mb-1 small text-muted">Rincian Transfer:</p>
                                <p class="mb-0 small"><strong>Bank Tujuan</strong> : <span class="text-uppercase"><?php echo htmlspecialchars($employee['nama_bank']); ?></span></p>
                                <p class="mb-0 small"><strong>Nomor Rekening</strong> : <?php echo htmlspecialchars($employee['nomor_rekening']); ?></p>
                                <p class="mb-0 small"><strong>Atas Nama</strong> : <?php echo htmlspecialchars($employee['nama_pemilik_rekening']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="slip-footer mt-4">
                            <p>Ini adalah slip gaji yang sah dan dikeluarkan secara resmi oleh Gravitti Technology.</p>
                            <p class="small">Dicetak pada: <?php echo date('d F Y, H:i:s'); ?> oleh <?php echo htmlspecialchars($nama_karyawan); ?></p>
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

            $('.sidebar-menu a').each(function() {
                var linkHref = $(this).attr('href').split("?")[0];
                if (linkHref === currentPath) {
                    $('.sidebar-menu a.active').removeClass('active');
                    $(this).addClass('active');
                } else {
                }
            });
            if (currentPath === "slip-gaji.php" && !$('.sidebar-menu a[href="slip-gaji.php"]').hasClass('active')) {
                $('.sidebar-menu a.active').removeClass('active');
                $('.sidebar-menu a[href="slip-gaji.php"]').addClass('active');
            }

            $('.custom-nav__link.active').removeClass('active');
            var fabLinkTarget = "absensi.php";
            if (currentPath === fabLinkTarget) {
            } else if (currentPath === "dashboard_karyawan.php") { 
                $('.custom-nav__link[href="dashboard_karyawan.php"]').addClass('active');
            } else if (currentPath === "profile.php") {
                $('.custom-nav__link[href="profile.php"]').addClass('active');
            }

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>
<?php
if (isset($conn)) { 
    $conn->close();
}
?>