<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

$jam_pulang_input = isset($_GET['jam_pulang']) ? trim($_GET['jam_pulang']) : (isset($_POST['jam_pulang']) ? trim($_POST['jam_pulang']) : '18:01:00');
if (strlen($jam_pulang_input) === 5) {
    $jam_pulang_input .= ':00';
}

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Koreksi Absen Pulang Wahyu Utomo - 24 September 2026</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-light p-4'>
<div class='container' style='max-width: 720px;'>
<div class='card shadow-sm border-0 rounded-4 p-4'>";

echo "<h4 class='fw-bold text-primary mb-3'><i class='fa-solid fa-clock-rotate-left me-2'></i>Koreksi Absen Pulang Wahyu Utomo - 24/09/2026</h4>";

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

// 2. Cek apakah ada record masuk pada 24/09/2026
$res_masuk = $conn->query("SELECT * FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '24-09-2026%' OR 
    tgl_scan LIKE '24-9-2026%' OR 
    tgl_scan LIKE '2026-09-24%'
) ORDER BY jam ASC LIMIT 1");

$jam_masuk_exist = "09:34:00";
if ($res_masuk && $res_masuk->num_rows > 0) {
    $row_m = $res_masuk->fetch_assoc();
    if (!empty($row_m['jam'])) {
        $jam_masuk_exist = $row_m['jam'];
    }
}

// 3. Bersihkan data 24/09/2026 dari tabel absen dan absen_manual untuk mencegah duplikat
$conn->query("DELETE FROM absen WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    tgl_scan LIKE '24-09-2026%' OR 
    tgl_scan LIKE '24-9-2026%' OR 
    tgl_scan LIKE '2026-09-24%'
)");

$conn->query("DELETE FROM absen_manual WHERE (nip = '$nik' OR nip = '$nip' OR pin = '$pin') AND (
    DATE(tgl_absen) = '2026-09-24' OR 
    tgl_absen LIKE '2026-09-24%' OR 
    tgl_absen LIKE '24-09-2026%'
)");

// 4. Masukkan data presensi lengkap:
// Masuk: $jam_masuk_exist (09:34:00)
// Pulang: $jam_pulang_input (18:01:00)
$tgl_scan_in = "24-09-2026 " . $jam_masuk_exist;
$tgl_scan_out = "24-09-2026 " . $jam_pulang_input;

$tgl_manual_in = "2026-09-24 " . $jam_masuk_exist;
$tgl_manual_out = "2026-09-24 " . $jam_pulang_input;

$tanggal_db = "2026-09-24";

// Insert Masuk ke tabel absen
$stmt_a1 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt_a1) {
    $stmt_a1->bind_param("ssssss", $tgl_scan_in, $tanggal_db, $jam_masuk_exist, $pin, $nik, $nama);
    $stmt_a1->execute();
    $stmt_a1->close();
}

// Insert Pulang ke tabel absen
$stmt_a2 = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt_a2) {
    $stmt_a2->bind_param("ssssss", $tgl_scan_out, $tanggal_db, $jam_pulang_input, $pin, $nik, $nama);
    $stmt_a2->execute();
    $stmt_a2->close();
}

// Insert Masuk ke tabel absen_manual
$stmt_m1 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'masuk', '', ?, ?, ?, 'Presensi Scan/Sistem', '', 'Yes')");
if ($stmt_m1) {
    $stmt_m1->bind_param("ssss", $tgl_manual_in, $pin, $nip, $nama);
    $stmt_m1->execute();
    $stmt_m1->close();
}

// Insert Pulang ke tabel absen_manual
$stmt_m2 = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, 'pulang', '', ?, ?, ?, 'Presensi Manual / Koreksi Pulang', '', 'Yes')");
if ($stmt_m2) {
    $stmt_m2->bind_param("ssss", $tgl_manual_out, $pin, $nip, $nama);
    $stmt_m2->execute();
    $stmt_m2->close();
}

echo "<div class='alert alert-success mb-3'>
    <h5 class='fw-bold mb-2'><i class='fa-solid fa-circle-check me-2'></i>Absen Pulang Berhasil Disesuaikan!</h5>
    Data presensi <strong>Kamis, 24 September 2026</strong> untuk <strong>" . htmlspecialchars($nama) . "</strong> telah berhasil diperbarui:
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
                    <td><strong>Jam Masuk (24/09/26)</strong></td>
                    <td>" . substr($jam_masuk_exist, 0, 5) . " WIB</td>
                    <td><span class='text-dark fw-bold'>" . substr($jam_masuk_exist, 0, 5) . " WIB</span></td>
                </tr>
                <tr>
                    <td><strong>Jam Pulang (24/09/26)</strong></td>
                    <td><span class='text-danger fw-bold'>Tidak Absen Pulang</span></td>
                    <td><span class='text-success fw-bold'>" . substr($jam_pulang_input, 0, 5) . " WIB</span></td>
                </tr>
                <tr>
                    <td><strong>Denda Tidak Absen Pulang</strong></td>
                    <td><span class='text-danger fw-bold'>+Rp 25.000</span></td>
                    <td><span class='text-success fw-bold'>Rp 0 (Dihapus)</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>";

echo "<div class='card p-3 mb-3 bg-white border'>
    <h6 class='fw-bold mb-2'>Ubah Jam Pulang (Opsional):</h6>
    <form method='GET' action='' class='row g-2 align-items-center'>
        <div class='col-auto'>
            <label class='col-form-label small fw-bold'>Jam Pulang:</label>
        </div>
        <div class='col-auto'>
            <input type='time' name='jam_pulang' class='form-control form-control-sm' value='" . substr($jam_pulang_input, 0, 5) . "' required>
        </div>
        <div class='col-auto'>
            <button type='submit' class='btn btn-sm btn-primary'><i class='fa-solid fa-arrows-rotate me-1'></i> Perbarui Jam</button>
        </div>
    </form>
</div>";

echo "<div class='d-flex justify-content-between mt-4'>
    <a href='staff/detail-absen.php?nik=577' class='btn btn-primary rounded-3'><i class='fa-solid fa-calendar-days me-1'></i> Buka Detail Absen Wahyu</a>
    <a href='karyawan/absen.php' class='btn btn-info text-white rounded-3'><i class='fa-solid fa-calendar-check me-1'></i> Buka Rekap Absen Karyawan</a>
    <a href='karyawan/riwayat-gaji.php' class='btn btn-success rounded-3'><i class='fa-solid fa-file-invoice-dollar me-1'></i> Buka Slip Gaji</a>
</div>";

echo "</div></div></body></html>";
?>
