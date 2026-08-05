<?php
// Ambil NIP dari parameter GET
$nip = $_GET['nip'];

// Ambil bulan dan tahun saat ini
$bulanIni = date('m');
$tahunIni = date('Y');

// Koneksi ke database
include 'conn.php';

// Query untuk mengambil data tunjangan lainnya sesuai dengan NIP, bulan, dan tahun saat ini
$query = "SELECT * FROM tunjangan_lainnya WHERE nip = '$nip' AND MONTH(tanggal) = $bulanIni AND YEAR(tanggal) = $tahunIni";
$result = $conn->query($query);

$tunjanganLainnya = array();
while ($row = $result->fetch_assoc()) {
    $tunjanganLainnya[] = $row;
}

$conn->close();

// Mengembalikan hasil dalam format JSON
header('Content-Type: application/json');
echo json_encode($tunjanganLainnya);
?>
