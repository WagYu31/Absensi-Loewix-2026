<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

$jam_input = isset($_GET['jam']) ? trim($_GET['jam']) : '09:00:00';
if (strlen($jam_input) === 5) {
    $jam_input .= ':00';
}

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Koreksi Presensi Wahyu Utomo - 11 September 2026</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light p-4'>
<div class='container' style='max-width: 720px;'>
<div class='card shadow-sm border-0 rounded-4 p-4'>";

echo "<h4 class='fw-bold text-primary mb-3'><i class='fa-solid fa-clock-rotate-left me-2'></i>Koreksi Presensi Wahyu Utomo - 11/09/2026</h4>";

// 1. Cari data karyawan Wahyu Utomo
$sql_kar = "SELECT nip, nik, nama, pin_absen, shifting FROM karyawan WHERE nik = '577' OR nip = '16577' OR nama LIKE '%Wahyu Utomo%' LIMIT 1";
$res_kar = $conn->query($sql_kar);

if (!$res_kar || $res_kar->num_rows === 0) {
    echo "<div class='alert alert-danger'>Karyawan Wahyu Utomo (NIK: 577) tidak ditemukan di database.</div>";
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

// 2. Cek apakah ada record pulang pada 11/09/2026
$res_pulang = $conn->query("SELECT * FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '11-09-2026%' OR 
    tgl_scan LIKE '11-9-2026%' OR 
    tgl_scan LIKE '2026-09-11%'
) ORDER BY jam DESC LIMIT 1");

$jam_pulang_exist = "18:01:00";
if ($res_pulang && $res_pulang->num_rows > 0) {
    $row_p = $res_pulang->fetch_assoc();
    if (!empty($row_p['jam'])) {
        $jam_pulang_exist = $row_p['jam'];
    }
}

// 3. Bersihkan data 11/09/2026 yang lama dari tabel absen dan absen_manual
$conn->query("DELETE FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '11-09-2026%' OR 
    tgl_scan LIKE '11-9-2026%' OR 
    tgl_scan LIKE '2026-09-11%'
)");

$conn->query("DELETE FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    DATE(tgl_absen) = '2026-09-11' OR 
    tgl_absen LIKE '2026-09-11%' OR 
    tgl_absen LIKE '11-09-2026%'
)");

// 4. Masukkan data presensi baru:
// Masuk: $jam_input (11-09-2026) -> Default 09:00:00
// Pulang: $jam_pulang_exist (11-09-2026) -> Default 18:01:00
$tgl_scan_in = "11-09-2026 " . $jam_input;
$tgl_scan_out = "11-09-2026 " . $jam_pulang_exist;

$tgl_manual_in = "2026-09-11 " . $jam_input;
$tgl_manual_out = "2026-09-11 " . $jam_pulang_exist;

$tanggal_db = "2026-09-11";

// Insert Masuk ke tabel absen
$stmt_a1 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt_a1) {
    $stmt_a1->bind_param("ssssss", $tgl_scan_in, $tanggal_db, $jam_input, $pin, $nik, $nama);
    $stmt_a1->execute();
    $stmt_a1->close();
}

// Insert Pulang ke tabel absen
$stmt_a2 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt_a2) {
    $stmt_a2->bind_param("ssssss", $tgl_scan_out, $tanggal_db, $jam_pulang_exist, $pin, $nik, $nama);
    $stmt_a2->execute();
    $stmt_a2->close();
}

// Insert Masuk ke tabel absen_manual
$stmt_m1 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'masuk', '', ?, ?, ?, 'Presensi Manual / Kendala Jaringan', '', 'Yes')");
if ($stmt_m1) {
    $stmt_m1->bind_param("ssss", $tgl_manual_in, $pin, $nip, $nama);
    $stmt_m1->execute();
    $stmt_m1->close();
}

// Insert Pulang ke tabel absen_manual
$stmt_m2 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'pulang', '', ?, ?, ?, 'Presensi Manual / Kendala Jaringan', '', 'Yes')");
if ($stmt_m2) {
    $stmt_m2->bind_param("ssss", $tgl_manual_out, $pin, $nip, $nama);
    $stmt_m2->execute();
    $stmt_m2->close();
}

// 5. Update tabel denda dan rincian_gaji untuk periode September 2026
// Denda telat 281m = Rp 336.000, Denda tidak absen = 0 (turun dari Rp 361.000 menjadi Rp 336.000)
$new_total_denda = 336000;

$conn->query("UPDATE denda SET jumlah = $new_total_denda, keterangan = 'Denda telat Periode September 2026 281m' WHERE (nip = '$nip' OR nip = '$nik') AND MONTH(tanggal) = 9 AND YEAR(tanggal) = 2026 AND ket1 = 'Denda'");
$conn->query("UPDATE rincian_gaji SET denda = $new_total_denda WHERE (nip = '$nip' OR nip = '$nik') AND MONTH(tanggal) = 9 AND YEAR(tanggal) = 2026");

echo "<div class='alert alert-success mb-3'>
    <h5 class='fw-bold mb-2'><i class='fa-solid fa-circle-check me-2'></i>Presensi Berhasil Disesuaikan!</h5>
    Data presensi <strong>Jumat, 11 September 2026</strong> untuk <strong>" . htmlspecialchars($nama) . "</strong> telah berhasil diperbarui:
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
                    <td><strong>Jam Masuk (11/09/26)</strong></td>
                    <td><span class='text-danger fw-bold'>Tidak Absen Masuk</span></td>
                    <td><span class='text-success fw-bold'>" . substr($jam_input, 0, 5) . " WIB</span></td>
                </tr>
                <tr>
                    <td><strong>Jam Pulang (11/09/26)</strong></td>
                    <td>" . substr($jam_pulang_exist, 0, 5) . " WIB</td>
                    <td><span class='text-dark fw-bold'>" . substr($jam_pulang_exist, 0, 5) . " WIB</span></td>
                </tr>
                <tr>
                    <td><strong>Denda Tidak Absen</strong></td>
                    <td><span class='text-danger fw-bold'>1x (Rp 25.000)</span></td>
                    <td><span class='text-success fw-bold'>0x (Rp 0)</span></td>
                </tr>
                <tr>
                    <td><strong>Total Menit Keterlambatan</strong></td>
                    <td>281 menit</td>
                    <td><span class='text-primary fw-bold'>281 menit</span> (Denda: Rp 336.000)</td>
                </tr>
                <tr class='table-primary'>
                    <td><strong>Total Akumulasi Denda</strong></td>
                    <td><span class='text-danger fw-bold'>Rp 361.000</span></td>
                    <td><span class='text-success fw-bold'>Rp 336.000 (-Rp 25.000)</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>";

echo "<div class='d-flex justify-content-between mt-4'>
    <a href='staff/detail-absen.php?nik=577' class='btn btn-primary rounded-3'><i class='fa-solid fa-calendar-days me-1'></i> Buka Detail Absen Wahyu</a>
    <a href='karyawan/riwayat-gaji.php' class='btn btn-success rounded-3'><i class='fa-solid fa-file-invoice-dollar me-1'></i> Buka Slip Gaji</a>
</div>";

echo "</div></div></body></html>";
?>
