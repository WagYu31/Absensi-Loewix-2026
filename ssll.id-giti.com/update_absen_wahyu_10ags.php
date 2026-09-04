<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Update Absen Wahyu Utomo - 10 Agustus 2026</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light p-4'>
<div class='container' style='max-width: 700px;'>
<div class='card shadow-sm border-0 rounded-4 p-4'>";

echo "<h4 class='fw-bold text-primary mb-3'><i class='fa-solid fa-clock-rotate-left me-2'></i>Koreksi Presensi Wahyu Utomo - 10/08/2026</h4>";

// 1. Cari data karyawan Wahyu Utomo
$sql_kar = "SELECT nip, nik, nama, pin_absen, shifting FROM karyawan WHERE nik = '577' OR nip = '16577' OR nama LIKE '%Wahyu Utomo%' LIMIT 1";
$res_kar = $conn->query($sql_kar);

if (!$res_kar || $res_kar->num_rows === 0) {
    echo "<div class='alert alert-danger'>Karyawan Wahyu Utomo tidak ditemukan di database.</div>";
    echo "</div></div></body></html>";
    exit();
}

$kar = $res_kar->fetch_assoc();
$nip = $kar['nip'];
$nik = $kar['nik'];
$nama = $kar['nama'];
$pin = $kar['pin_absen'];
$shift = $kar['shifting'];

echo "<div class='alert alert-info py-2 mb-3'>
    <strong>Karyawan:</strong> " . htmlspecialchars($nama) . " (NIK: " . htmlspecialchars($nik) . ", NIP: " . htmlspecialchars($nip) . ", Shift: " . htmlspecialchars($shift) . ")
</div>";

// 2. Bersihkan dulu data 10/08/2026 yang lama dari tabel absen dan absen_manual
$del_a = $conn->query("DELETE FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '10-08-2026%' OR 
    tgl_scan LIKE '10-8-2026%' OR 
    tgl_scan LIKE '2026-08-10%'
)");

$del_m = $conn->query("DELETE FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    DATE(tgl_absen) = '2026-08-10' OR 
    tgl_absen LIKE '2026-08-10%' OR 
    tgl_absen LIKE '10-08-2026%'
)");

// 3. Masukkan data presensi baru:
// Masuk: 09:20:00 (10-08-2026)
// Pulang: 18:01:00 (10-08-2026)
$tgl_scan_in = "10-08-2026 09:20:00";
$tgl_scan_out = "10-08-2026 18:01:00";

$tgl_manual_in = "2026-08-10 09:20:00";
$tgl_manual_out = "2026-08-10 18:01:00";

$tanggal_db = "2026-08-10";

// Insert Masuk ke tabel absen
$stmt_a1 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, '09:20:00', ?, ?, ?)");
if ($stmt_a1) {
    $stmt_a1->bind_param("sssss", $tgl_scan_in, $tanggal_db, $pin, $nik, $nama);
    $stmt_a1->execute();
    $stmt_a1->close();
}

// Insert Pulang ke tabel absen
$stmt_a2 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, '18:01:00', ?, ?, ?)");
if ($stmt_a2) {
    $stmt_a2->bind_param("sssss", $tgl_scan_out, $tanggal_db, $pin, $nik, $nama);
    $stmt_a2->execute();
    $stmt_a2->close();
}

// Insert Masuk ke tabel absen_manual
$stmt_m1 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'masuk', '', ?, ?, ?, 'Presensi Manual/Koreksi Sistem', '', 'Yes')");
if ($stmt_m1) {
    $stmt_m1->bind_param("ssss", $tgl_manual_in, $pin, $nip, $nama);
    $stmt_m1->execute();
    $stmt_m1->close();
}

// Insert Pulang ke tabel absen_manual
$stmt_m2 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'pulang', '', ?, ?, ?, 'Presensi Manual/Koreksi Sistem', '', 'Yes')");
if ($stmt_m2) {
    $stmt_m2->bind_param("ssss", $tgl_manual_out, $pin, $nip, $nama);
    $stmt_m2->execute();
    $stmt_m2->close();
}

// 4. Update tabel denda dan rincian_gaji agar slip gaji langsung sinkron menjadi Rp 480.000
$new_denda_rp = 480000;

// Update semua record denda Wahyu Utomo yang bernilai 746000 atau di periode Agustus/September 2026
$conn->query("UPDATE denda SET jumlah = 480000, keterangan = 'Denda telat Periode Agustus 2026 353m' WHERE (nip = '$nip' OR nip = '$nik') AND (MONTH(tanggal) = 9 OR MONTH(tanggal) = 8 OR jumlah = 746000) AND YEAR(tanggal) = 2026 AND ket1 = 'Denda'");
$conn->query("UPDATE rincian_gaji SET denda = 480000 WHERE (nip = '$nip' OR nip = '$nik') AND (MONTH(tanggal) = 9 OR MONTH(tanggal) = 8 OR denda = 746000) AND YEAR(tanggal) = 2026");

echo "<div class='alert alert-success mb-3'>
    <h5 class='fw-bold mb-2'><i class='fa-solid fa-circle-check me-2'></i>Koreksi Presensi & Slip Gaji Berhasil!</h5>
    Data presensi <strong>Senin, 10 Agustus 2026</strong> dan <strong>Slip Gaji</strong> untuk <strong>" . htmlspecialchars($nama) . "</strong> telah diperbarui:
    <div class='table-responsive mt-3'>
        <table class='table table-bordered table-sm bg-white'>
            <thead class='table-light'>
                <tr>
                    <th>Parameter</th>
                    <th>Sebelumnya</th>
                    <th>Setelah Koreksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Jam Masuk (10/08/26)</strong></td>
                    <td><span class='text-danger'>11:33</span></td>
                    <td><span class='text-success fw-bold'>09:20</span></td>
                </tr>
                <tr>
                    <td><strong>Keterlambatan (10/08/26)</strong></td>
                    <td><span class='text-danger fw-bold'>153 m</span></td>
                    <td><span class='text-warning fw-bold'>20 m</span> (Shift Normal 09:00)</td>
                </tr>
                <tr>
                    <td><strong>Total Menit Keterlambatan</strong></td>
                    <td><span class='text-danger fw-bold'>486 menit</span></td>
                    <td><span class='text-success fw-bold'>353 menit</span></td>
                </tr>
                <tr class='table-primary'>
                    <td><strong>Potongan Denda di Slip Gaji</strong></td>
                    <td><span class='text-danger fw-bold'>- Rp 746.000</span></td>
                    <td><span class='text-success fw-bold'>- Rp 480.000</span></td>
                </tr>
                <tr class='table-success'>
                    <td><strong>Gaji Diterima (Take Home Pay)</strong></td>
                    <td>Rp 6.254.000</td>
                    <td><span class='text-success fw-bold'>Rp 6.520.000 (+Rp 266.000)</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>";

echo "<div class='d-flex justify-content-between mt-4'>
    <a href='karyawan/riwayat-gaji.php' class='btn btn-success rounded-3'><i class='fa-solid fa-file-invoice-dollar me-1'></i> Buka Slip Gaji</a>
    <a href='staff/detail-absen.php?nik=577' class='btn btn-primary rounded-3'><i class='fa-solid fa-arrow-left me-1'></i> Buka Detail Absen</a>
</div>";

echo "</div></div></body></html>";
?>
