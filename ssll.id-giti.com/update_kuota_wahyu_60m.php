<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

$is_cli = (php_sapi_name() === 'cli');

// 1. Ambil data karyawan Wahyu Utomo
$sql_kar = "SELECT nip, nik, nama, pin_absen, shifting FROM karyawan WHERE nik = '577' OR nip = '16577' OR nama LIKE '%Wahyu Utomo%' LIMIT 1";
$res_kar = $conn->query($sql_kar);

if (!$res_kar || $res_kar->num_rows === 0) {
    if ($is_cli) {
        echo "[ERROR] Karyawan Wahyu Utomo (NIK: 577) tidak ditemukan di database.\n";
        exit(1);
    } else {
        echo "<!DOCTYPE html><html><body class='p-4'><div class='alert alert-danger'>Karyawan Wahyu Utomo (NIK: 577) tidak ditemukan.</div></body></html>";
        exit();
    }
}

$kar = $res_kar->fetch_assoc();
$nip = $kar['nip'];
$nik = $kar['nik'];
$nama = $kar['nama'];
$pin = $kar['pin_absen'];
$shift = $kar['shifting'];

// Parameter kuota 60 menit khusus Wahyu Utomo
$b1 = 60;
$b2 = 120;
$b3 = 180;

$telatst = 300;
$telatd = 600;
$telattg = 2000;
$gabsen = 25000;

$getdnd = "SELECT * FROM dnd WHERE id = 1";
$resdnd = $conn->query($getdnd);
if ($resdnd && $dnd = $resdnd->fetch_assoc()) {
    $gabsen = $dnd['gabsen'] ?? 25000;
    $telatst = $dnd['telatst'] ?? 300;
    $telatd = $dnd['telatd'] ?? 600;
    $telattg = $dnd['telattg'] ?? 2000;
}

// 2. Hitung total menit keterlambatan saat ini di Oktober 2026
$sql_calc = "SELECT 
        MIN(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_in
    FROM absen 
    WHERE (nip = '$nik' OR nip = '$nip') AND
    MONTH(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = 10 AND
    YEAR(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = 2026
    GROUP BY DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'))";
$res_calc = $conn->query($sql_calc);

$total_menit = 0;
$detail_telat = [];
if ($res_calc) {
    while ($rc = $res_calc->fetch_assoc()) {
        $dt_in = new DateTime($rc['tgl_in']);
        $date_str = $dt_in->format('Y-m-d');
        $waktu_masuk_unix = strtotime($date_str . " 09:00:00");
        if ($dt_in->getTimestamp() > $waktu_masuk_unix) {
            $late_m = floor(($dt_in->getTimestamp() - $waktu_masuk_unix) / 60);
            if ($late_m > 0) {
                $total_menit += $late_m;
                $detail_telat[] = [
                    'tgl' => $date_str,
                    'jam_masuk' => $dt_in->format('H:i:s'),
                    'late_m' => $late_m
                ];
            }
        }
    }
}

// 3. Hitung denda dengan kuota 60 menit
$denda_akhir = 0;
if ($total_menit > $b1) {
    if ($total_menit <= $b2) {
        $denda_akhir = ($total_menit - $b1) * $telatst;
    } elseif ($total_menit <= $b3) {
        $denda_akhir = (60 * $telatst) + (($total_menit - $b2) * $telatd);
    } else {
        $denda_akhir = (60 * $telatst) + (60 * $telatd) + (($total_menit - $b3) * $telattg);
    }
}

$conn->begin_transaction();
$sukses = false;
$pesan_error = '';

try {
    // 4. Update tabel denda & rincian_gaji
    $ket_denda = "Denda telat Periode Oktober 2026 {$total_menit}m (Kuota 60m)";
    $conn->query("UPDATE denda SET jumlah = $denda_akhir, keterangan = '$ket_denda' WHERE (nip = '$nip' OR nip = '$nik') AND MONTH(tanggal) = 10 AND YEAR(tanggal) = 2026 AND ket1 = 'Denda'");
    $conn->query("UPDATE rincian_gaji SET denda = $denda_akhir WHERE (nip = '$nip' OR nip = '$nik') AND MONTH(tanggal) = 10 AND YEAR(tanggal) = 2026");

    $conn->commit();
    $sukses = true;
} catch (Exception $e) {
    $conn->rollback();
    $pesan_error = $e->getMessage();
}

if ($is_cli) {
    if ($sukses) {
        echo "[SUKSES] Sinkronisasi Kuota Telat 60 Menit Wahyu Utomo Berhasil!\n";
        echo "Karyawan      : $nama (NIK: $nik, NIP: $nip)\n";
        echo "Total Telat   : $total_menit Menit\n";
        echo "Kuota Gratis  : 60 Menit\n";
        echo "Denda Akhir   : Rp " . number_format($denda_akhir, 0, ',', '.') . "\n";
    } else {
        echo "[ERROR] Gagal memperbarui denda: $pesan_error\n";
    }
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi Kuota Telat 60 Menit - Wahyu Utomo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .main-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            max-width: 650px;
            width: 100%;
            overflow: hidden;
            border: none;
        }
        .header-box {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 24px 28px;
        }
        .metric-badge {
            font-size: 1.1rem;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="main-card">
    <div class="header-box">
        <h4 class="fw-bold mb-1"><i class="fas fa-check-circle me-2 text-warning"></i>Sinkronisasi Kuota Telat 60 Menit</h4>
        <small class="text-white-50">Khusus Karyawan: <?php echo htmlspecialchars($nama); ?> (NIK: <?php echo htmlspecialchars($nik); ?>)</small>
    </div>
    <div class="p-4">
        <?php if ($sukses): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="fas fa-badge-check fa-2x me-3 text-success"></i>
                <div>
                    <strong class="d-block">Database Berhasil Disinkronkan!</strong>
                    <span>Kuota toleransi keterlambatan telah ditetapkan menjadi <strong>60 menit</strong> sebulan.</span>
                </div>
            </div>

            <div class="row g-3 mb-4 text-center">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block fw-semibold">Total Keterlambatan</small>
                        <span class="fs-4 fw-bold text-dark"><?php echo $total_menit; ?> <small class="fs-6">menit</small></span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block fw-semibold">Denda Keterlambatan Baru</small>
                        <span class="fs-4 fw-bold text-primary">Rp <?php echo number_format($denda_akhir, 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-light mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-calculator text-primary me-2"></i>Rincian Skema Tier Kuota 60 Menit</h6>
                    <ul class="list-group list-group-flush bg-transparent" style="font-size: 0.9rem;">
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-1">
                            <span>1. Menit 1 - 60 (0 - 60m): <strong><?php echo min($total_menit, 60); ?>m</strong></span>
                            <span class="badge bg-success">Gratis (Rp 0)</span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-1">
                            <span>2. Menit 61 - 120 (@ Rp 300): <strong><?php echo ($total_menit > 60 ? min($total_menit - 60, 60) : 0); ?>m</strong></span>
                            <span class="fw-semibold">Rp <?php echo number_format(($total_menit > 60 ? min($total_menit - 60, 60) * 300 : 0), 0, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-1">
                            <span>3. Menit 121 - 180 (@ Rp 600): <strong><?php echo ($total_menit > 120 ? min($total_menit - 120, 60) : 0); ?>m</strong></span>
                            <span class="fw-semibold">Rp <?php echo number_format(($total_menit > 120 ? min($total_menit - 120, 60) * 600 : 0), 0, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-1">
                            <span>4. Di atas 180 Menit (@ Rp 2.000): <strong><?php echo ($total_menit > 180 ? ($total_menit - 180) : 0); ?>m</strong></span>
                            <span class="fw-semibold">Rp <?php echo number_format(($total_menit > 180 ? ($total_menit - 180) * 2000 : 0), 0, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2 border-top fw-bold text-primary">
                            <span>Total Denda Akhir</span>
                            <span>Rp <?php echo number_format($denda_akhir, 0, ',', '.'); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="staff/detail-absen.php?nik=577&bulan=10&tahun=2026" class="btn btn-primary flex-fill py-2 rounded-3">
                    <i class="fas fa-eye me-1"></i>Lihat Detail Absen Staff
                </a>
                <a href="karyawan/absen.php" class="btn btn-outline-secondary flex-fill py-2 rounded-3">
                    <i class="fas fa-user me-1"></i>Halaman Absen Karyawan
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>Gagal:</strong> <?php echo htmlspecialchars($pesan_error); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
