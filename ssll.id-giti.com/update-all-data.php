<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

// Ambil data bulan dan tahun dari URL
if (isset($_GET['bulan']) && isset($_GET['tahun'])) {
    $bulan = $_GET['bulan'];
    $tahun = $_GET['tahun'];
} else {
    // Jika data bulan dan tahun tidak ada di URL, kembalikan ke halaman sebelumnya
    header("Location: laporan-gaji.php");
    exit();
}

// Query untuk mengambil data karyawan
$query = "SELECT karyawan.*, 
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = $bulan AND YEAR(tunjangan_lainnya.tanggal) = $tahun) AS total_tunjangan_lainnya,
                (SELECT SUM(jumlah) FROM denda WHERE denda.nip = karyawan.nip AND MONTH(denda.tanggal) = $bulan AND YEAR(denda.tanggal) = $tahun) AS total_denda
        FROM karyawan";

$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

$sum = 0;

while ($data = $result->fetch_assoc()) : 
    include 'get-tunjangan-masa-kerja.php';
    $nipK = $data['nip'];
    $tanggal = date('Y-m-d', strtotime("$tahun-$bulan-01"));
    $tunjanganMasaKerja = $dataTMK['tunjangan_masa_kerja'];
    $tunjanganLainnya = $data['total_tunjangan_lainnya'];
    $denda = $data['total_denda'];

    // Jika data sudah ada, lakukan update
    $queryUpdate = "UPDATE rincian_gaji SET tunjangan_masa_kerja='$tunjanganMasaKerja', tunjangan_lainnya='$tunjanganLainnya', denda='$denda' WHERE nip='$nipK' AND MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'";
    $resultUpdate = $conn->query($queryUpdate);

    if (!$resultUpdate) {
        die("Update execution failed: " . $conn->error);
    }
endwhile;

$conn->close();

// Kembali ke halaman gaji setelah proses update selesai
header("Location: laporan-gaji.php");
exit();
?>
