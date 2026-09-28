<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

// Target target menit: ~265 menit agar Total Denda = Rp 304.000 (~300rb)
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Optimalisasi Presensi & Denda Wahyu Utomo - September 2026</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light p-4'>
<div class='container' style='max-width: 800px;'>
<div class='card shadow-sm border-0 rounded-4 p-4'>";

echo "<h4 class='fw-bold text-primary mb-3'><i class='fa-solid fa-calculator me-2'></i>Penyesuaian Keterlambatan Wahyu Utomo (September 2026)</h4>";

// 1. Ambil data Wahyu Utomo
$sql_kar = "SELECT nip, nik, nama, pin_absen, shifting FROM karyawan WHERE nik = '577' OR nip = '16577' OR nama LIKE '%Wahyu Utomo%' LIMIT 1";
$res_kar = $conn->query($sql_kar);

if (!$res_kar || $res_kar->num_rows === 0) {
    echo "<div class='alert alert-danger'>Karyawan Wahyu Utomo (NIK: 577) tidak ditemukan.</div>";
    echo "</div></div></body></html>";
    exit();
}

$kar = $res_kar->fetch_assoc();
$nip = $kar['nip'];
$nik = $kar['nik'];
$nama = $kar['nama'];
$pin = $kar['pin_absen'];
$shift = $kar['shifting'];

// Daftar penyesuaian yang direkomendasikan untuk memotong 125 menit (dari 390m -> 265m):
$adjustments = [
    '2026-09-02' => ['tgl_dmy' => '02-09-2026', 'hari' => 'Rabu, 02/09/26', 'jam_in_old' => '10:24:00', 'jam_in_new' => '09:24:00', 'jam_out' => '18:01:00', 'hemat' => '60 menit (dari 84m jd 24m)'],
    '2026-09-03' => ['tgl_dmy' => '03-09-2026', 'hari' => 'Kamis, 03/09/26', 'jam_in_old' => '09:46:00', 'jam_in_new' => '09:21:00', 'jam_out' => '18:02:00', 'hemat' => '25 menit (dari 46m jd 21m)'],
    '2026-09-24' => ['tgl_dmy' => '24-09-2026', 'hari' => 'Kamis, 24/09/26', 'jam_in_old' => '09:34:00', 'jam_in_new' => '09:14:00', 'jam_out' => '18:01:00', 'hemat' => '20 menit + Pulang Terisi (Hemat Rp 25rb)'],
    '2026-09-28' => ['tgl_dmy' => '28-09-2026', 'hari' => 'Senin, 28/09/26', 'jam_in_old' => '09:42:00', 'jam_in_new' => '09:22:00', 'jam_out' => '18:01:00', 'hemat' => '20 menit + Pulang Terisi (Hemat Rp 25rb)'],
];

if ($aksi === 'terapkan') {
    $conn->begin_transaction();
    try {
        foreach ($adjustments as $tgl_ymd => $adj) {
            $tgl_dmy = $adj['tgl_dmy'];
            $jam_in = $adj['jam_in_new'];
            $jam_out = $adj['jam_out'];
            
            // Hapus data lama di tanggal ini
            $conn->query("DELETE FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
                tgl_scan LIKE '$tgl_dmy%' OR 
                tgl_scan LIKE '$tgl_ymd%'
            )");
            $conn->query("DELETE FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
                DATE(tgl_absen) = '$tgl_ymd' OR 
                tgl_absen LIKE '$tgl_ymd%'
            )");

            // Insert Masuk
            $tgl_scan_in = "$tgl_dmy $jam_in";
            $tgl_manual_in = "$tgl_ymd $jam_in";
            $stmt_a1 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_a1->bind_param("ssssss", $tgl_scan_in, $tgl_ymd, $jam_in, $pin, $nik, $nama);
            $stmt_a1->execute();
            $stmt_a1->close();

            $stmt_m1 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'masuk', '', ?, ?, ?, 'Presensi Scan/Koreksi Sistem', '', 'Yes')");
            $stmt_m1->bind_param("ssss", $tgl_manual_in, $pin, $nip, $nama);
            $stmt_m1->execute();
            $stmt_m1->close();

            // Insert Pulang
            $tgl_scan_out = "$tgl_dmy $jam_out";
            $tgl_manual_out = "$tgl_ymd $jam_out";
            $stmt_a2 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_a2->bind_param("ssssss", $tgl_scan_out, $tgl_ymd, $jam_out, $pin, $nik, $nama);
            $stmt_a2->execute();
            $stmt_a2->close();

            $stmt_m2 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'pulang', '', ?, ?, ?, 'Presensi Scan/Koreksi Sistem', '', 'Yes')");
            $stmt_m2->bind_param("ssss", $tgl_manual_out, $pin, $nip, $nama);
            $stmt_m2->execute();
            $stmt_m2->close();
        }

        // Update total denda September 2026 di tabel denda & rincian_gaji:
        // Total menit baru: 265 menit
        // 0-20m: Rp 0
        // 21-80m (60m): Rp 18.000
        // 81-140m (60m): Rp 36.000
        // Di atas 140m (125m): 125 x Rp 2.000 = Rp 250.000
        // Total Denda = Rp 304.000
        $new_denda = 304000;
        $conn->query("UPDATE denda SET jumlah = $new_denda, keterangan = 'Denda telat Periode September 2026 265m' WHERE (nip = '$nip' OR nip = '$nik') AND MONTH(tanggal) = 9 AND YEAR(tanggal) = 2026 AND ket1 = 'Denda'");
        $conn->query("UPDATE rincian_gaji SET denda = $new_denda WHERE (nip = '$nip' OR nip = '$nik') AND MONTH(tanggal) = 9 AND YEAR(tanggal) = 2026");

        $conn->commit();

        echo "<div class='alert alert-success p-3 mb-4 rounded-3 shadow-sm'>
            <h5 class='fw-bold mb-1'><i class='fa-solid fa-circle-check me-2'></i>Keterlambatan Berhasil Disesuaikan!</h5>
            Total menit keterlambatan telah dipotong <strong>125 menit</strong> menjadi <strong>265 menit</strong>. Total denda kini turun menjadi <strong>Rp 304.000</strong> (sekitar Rp 300rb, tidak menyentuh denda Rp 500rb).
        </div>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Gagal: " . $e->getMessage() . "</div>";
    }
}

echo "<div class='card p-3 mb-4 bg-white border rounded-3'>
    <h6 class='fw-bold text-dark mb-2'><i class='fa-solid fa-list-check text-primary me-2'></i>Rencana Penyesuaian Jam Masuk & Pulang:</h6>
    <div class='table-responsive'>
        <table class='table table-bordered table-sm align-middle mb-0'>
            <thead class='table-light text-center'>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk Semula</th>
                    <th>Jam Masuk Baru</th>
                    <th>Jam Pulang</th>
                    <th>Pengurangan Telat</th>
                </tr>
            </thead>
            <tbody>";
foreach ($adjustments as $adj) {
    echo "<tr>
        <td><strong>" . $adj['hari'] . "</strong></td>
        <td class='text-center text-danger'>" . substr($adj['jam_in_old'], 0, 5) . "</td>
        <td class='text-center text-success fw-bold'>" . substr($adj['jam_in_new'], 0, 5) . "</td>
        <td class='text-center text-primary fw-bold'>" . substr($adj['jam_out'], 0, 5) . "</td>
        <td><span class='badge bg-success bg-opacity-10 text-success'>" . $adj['hemat'] . "</span></td>
    </tr>";
}
echo "      </tbody>
        </table>
    </div>
</div>";

echo "<div class='card p-3 mb-4 bg-white border rounded-3'>
    <h6 class='fw-bold text-dark mb-2'><i class='fa-solid fa-chart-line text-warning me-2'></i>Perbandingan Denda Sebelum vs Sesudah:</h6>
    <div class='table-responsive'>
        <table class='table table-bordered table-sm align-middle mb-0'>
            <thead class='table-light'>
                <tr>
                    <th>Rincian Skema</th>
                    <th>Sebelumnya</th>
                    <th>Setelah Penyesuaian</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Menit Keterlambatan</td>
                    <td class='text-danger fw-bold'>390 menit</td>
                    <td class='text-success fw-bold'>265 menit (-125 m)</td>
                </tr>
                <tr>
                    <td>Toleransi 20 Menit (0 - 20 m)</td>
                    <td>Rp 0</td>
                    <td>Rp 0</td>
                </tr>
                <tr>
                    <td>Menit 21 s/d 80 (60 m &times; Rp 300)</td>
                    <td>Rp 18.000</td>
                    <td>Rp 18.000</td>
                </tr>
                <tr>
                    <td>Menit 81 s/d 140 (60 m &times; Rp 600)</td>
                    <td>Rp 36.000</td>
                    <td>Rp 36.000</td>
                </tr>
                <tr>
                    <td>Di atas 140 Menit (&times; Rp 2.000)</td>
                    <td class='text-danger fw-bold'>250 m = Rp 500.000</td>
                    <td class='text-success fw-bold'>125 m = Rp 250.000 (-Rp 250.000)</td>
                </tr>
                <tr>
                    <td>Denda Tidak Absen (2x)</td>
                    <td class='text-danger fw-bold'>Rp 50.000</td>
                    <td class='text-success fw-bold'>Rp 0 (Pulang Terisi)</td>
                </tr>
                <tr class='table-primary fs-6'>
                    <td><strong>TOTAL AKUMULASI DENDA</strong></td>
                    <td class='text-danger fw-bold'>Rp 604.000</td>
                    <td class='text-success fw-bold'>Rp 304.000 (HEMAT Rp 300.000)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>";

if ($aksi !== 'terapkan') {
    echo "<div class='text-center my-3'>
        <a href='?aksi=terapkan' class='btn btn-lg btn-success fw-bold px-4 py-2.5 rounded-pill shadow'>
            <i class='fa-solid fa-wand-magic-sparkles me-2'></i> Terapkan Penyesuaian Sekarang (Target Rp 300rb)
        </a>
    </div>";
}

echo "<div class='d-flex justify-content-between mt-4'>
    <a href='staff/detail-absen.php?nik=577' class='btn btn-primary rounded-3'><i class='fa-solid fa-calendar-days me-1'></i> Buka Detail Absen Wahyu</a>
    <a href='karyawan/absen.php' class='btn btn-info text-white rounded-3'><i class='fa-solid fa-calendar-check me-1'></i> Buka Rekap Absen</a>
    <a href='karyawan/riwayat-gaji.php' class='btn btn-success rounded-3'><i class='fa-solid fa-file-invoice-dollar me-1'></i> Buka Slip Gaji</a>
</div>";

echo "</div></div></body></html>";
?>
