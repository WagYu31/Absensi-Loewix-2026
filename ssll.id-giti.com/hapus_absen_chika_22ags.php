<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Penghapusan Data Absen - 22 Agustus 2026</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-4'>
<div class='container' style='max-width: 700px;'>
<div class='card shadow-sm border-0 rounded-4 p-4'>";

echo "<h4 class='fw-bold text-primary mb-3'>Proses Hapus Data Absen 22/08/2026</h4>";

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

// 2. Cek & Hapus dari tabel 'absen'
$sql_check_absen = "SELECT * FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '22-08-2026%' OR 
    tgl_scan LIKE '22-8-2026%' OR 
    tgl_scan LIKE '2026-08-22%'
)";
$res_check_absen = $conn->query($sql_check_absen);
$count_absen = $res_check_absen ? $res_check_absen->num_rows : 0;

echo "<h6>1. Tabel <code>absen</code>: Ditemukan <strong>$count_absen</strong> data scan.</h6>";
if ($count_absen > 0) {
    while ($r = $res_check_absen->fetch_assoc()) {
        echo "<li class='small text-muted'>Tgl Scan: " . htmlspecialchars($r['tgl_scan']) . " (NIP: " . htmlspecialchars($r['nip']) . ")</li>";
    }
    
    $sql_del_absen = "DELETE FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
        tgl_scan LIKE '22-08-2026%' OR 
        tgl_scan LIKE '22-8-2026%' OR 
        tgl_scan LIKE '2026-08-22%'
    )";
    if ($conn->query($sql_del_absen)) {
        echo "<div class='alert alert-success py-1 mt-2 small'>Berhasil menghapus $count_absen baris dari tabel <code>absen</code>.</div>";
    } else {
        echo "<div class='alert alert-danger py-1 mt-2 small'>Gagal menghapus: " . $conn->error . "</div>";
    }
} else {
    echo "<p class='small text-muted'>Tidak ada data di tabel <code>absen</code>.</p>";
}

// 3. Cek & Hapus dari tabel 'absen_manual'
$sql_check_manual = "SELECT * FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    DATE(tgl_absen) = '2026-08-22' OR 
    tgl_absen LIKE '2026-08-22%' OR 
    tgl_absen LIKE '22-08-2026%'
)";
$res_check_manual = $conn->query($sql_check_manual);
$count_manual = $res_check_manual ? $res_check_manual->num_rows : 0;

echo "<h6 class='mt-3'>2. Tabel <code>absen_manual</code>: Ditemukan <strong>$count_manual</strong> data scan.</h6>";
if ($count_manual > 0) {
    while ($r = $res_check_manual->fetch_assoc()) {
        echo "<li class='small text-muted'>Tgl Absen: " . htmlspecialchars($r['tgl_absen']) . " | Tipe: " . htmlspecialchars($r['tipe_absen']) . "</li>";
    }
    
    $sql_del_manual = "DELETE FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
        DATE(tgl_absen) = '2026-08-22' OR 
        tgl_absen LIKE '2026-08-22%' OR 
        tgl_absen LIKE '22-08-2026%'
    )";
    if ($conn->query($sql_del_manual)) {
        echo "<div class='alert alert-success py-1 mt-2 small'>Berhasil menghapus $count_manual baris dari tabel <code>absen_manual</code>.</div>";
    } else {
        echo "<div class='alert alert-danger py-1 mt-2 small'>Gagal menghapus: " . $conn->error . "</div>";
    }
} else {
    echo "<p class='small text-muted'>Tidak ada data di tabel <code>absen_manual</code>.</p>";
}

echo "<hr class='my-4'>";
echo "<div class='alert alert-success'>
    <h5 class='fw-bold mb-1'><i class='fa-solid fa-circle-check me-2'></i>Selesai!</h5>
    Data absensi masuk & pulang tanggal <strong>Sabtu, 22 Agustus 2026</strong> untuk <strong>" . htmlspecialchars($nama) . "</strong> telah berhasil dihapus.
</div>";

echo "<div class='text-end mt-3'>
    <a href='staff/detail-absen.php?nik=558' class='btn btn-primary rounded-3'>Kembali ke Detail Absen Chika</a>
</div>";

echo "</div></div></body></html>";
?>
