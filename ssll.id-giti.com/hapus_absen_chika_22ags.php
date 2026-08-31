<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Set Absen Pulang Saja - 22 Agustus 2026</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-4'>
<div class='container' style='max-width: 700px;'>
<div class='card shadow-sm border-0 rounded-4 p-4'>";

echo "<h4 class='fw-bold text-primary mb-3'>Pengaturan Absen Chika - 22/08/2026</h4>";

// 1. Cari data karyawan Chika Retno Astriani
$sql_kar = "SELECT nip, nik, nama, pin_absen FROM karyawan WHERE nik = '558' OR nama LIKE '%Chika Retno%' LIMIT 1";
$res_kar = $conn->query($sql_kar);

if (!$res_kar || $res_kar->num_rows === 0) {
    echo "<div class='alert alert-danger'>Karyawan Chika Retno Astriani tidak ditemukan di database.</div>";
    echo "</div></div></body></html>";
    exit();
}

$kar = $res_kar->fetch_assoc();
$nip = $kar['nip'];
$nik = $kar['nik'];
$nama = $kar['nama'];
$pin = $kar['pin_absen'];

echo "<div class='alert alert-info py-2 mb-3'>
    <strong>Karyawan:</strong> " . htmlspecialchars($nama) . " (NIK: " . htmlspecialchars($nik) . ", PIN: " . htmlspecialchars($pin) . ")
</div>";

// 2. Bersihkan dulu data 22/08/2026 yang lama
$conn->query("DELETE FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '22-08-2026%' OR 
    tgl_scan LIKE '22-8-2026%' OR 
    tgl_scan LIKE '2026-08-22%'
)");

$conn->query("DELETE FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    DATE(tgl_absen) = '2026-08-22' OR 
    tgl_absen LIKE '2026-08-22%' OR 
    tgl_absen LIKE '22-08-2026%'
)");

// 3. Masukkan HANYA data ABSEN PULANG (13:00:16)
$tgl_scan_absen = "22-08-2026 13:00:16";
$tgl_absen_manual = "2026-08-22 13:00:16";

// Insert ke tabel absen
$stmt_a = $conn->prepare("INSERT INTO absen (nip, pin, tgl_scan) VALUES (?, ?, ?)");
if ($stmt_a) {
    $stmt_a->bind_param("sss", $nik, $pin, $tgl_scan_absen);
    $stmt_a->execute();
    $stmt_a->close();
}

// Insert ke tabel absen_manual
$stmt_m = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'pulang', '', ?, ?, ?, 'Presensi Manual/Sistem', '', 'Yes')");
if ($stmt_m) {
    $stmt_m->bind_param("sssss", $tgl_absen_manual, $pin, $nip, $nama);
    $stmt_m->execute();
    $stmt_m->close();
}

echo "<div class='alert alert-success mb-3'>
    <h5 class='fw-bold mb-2'><i class='fa-solid fa-circle-check me-2'></i>Berhasil Diperbarui!</h5>
    Status kehadiran tanggal <strong>Sabtu, 22 Agustus 2026</strong> untuk <strong>" . htmlspecialchars($nama) . "</strong> telah diatur:
    <ul class='mb-0 mt-2'>
        <li><strong>Masuk:</strong> <span class='text-danger fw-bold'>Tidak Absen Masuk</span> (Dihapus / Tidak absen)</li>
        <li><strong>Pulang:</strong> <span class='text-success fw-bold'>13:00:16</span> (Tercatat Absen Pulang)</li>
        <li><strong>Keterlambatan:</strong> <span class='text-muted fw-bold'>- (0 Menit / Tidak ada denda telat)</span></li>
    </ul>
</div>";

echo "<div class='text-end mt-4'>
    <a href='staff/detail-absen.php?nik=558' class='btn btn-primary rounded-3'>Buka Detail Absen Chika</a>
</div>";

echo "</div></div></body></html>";
?>
