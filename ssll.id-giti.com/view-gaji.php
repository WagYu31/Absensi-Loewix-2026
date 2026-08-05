<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

// Ambil parameter nip dan tanggal dari URL
if (isset($_GET['nip']) && isset($_GET['tanggal'])) {
    $nip = $_GET['nip'];
    $tanggal = $_GET['tanggal'];
} else {
    // Jika parameter nip atau tanggal tidak tersedia, arahkan kembali ke halaman sebelumnya
    header('Location: data-gaji.php');
    exit();
}

// Ambil data karyawan dan rincian gaji dari database
include 'conn.php';

// Query untuk mengambil data rincian gaji karyawan berdasarkan nip dan tanggal
$query = "SELECT karyawan.nama, rincian_gaji.gaji_pokok, rincian_gaji.tunjangan_jabatan, rincian_gaji.tunjangan_masa_kerja, rincian_gaji.tunjangan_lainnya, rincian_gaji.denda, (rincian_gaji.gaji_pokok + rincian_gaji.tunjangan_jabatan + rincian_gaji.tunjangan_masa_kerja + rincian_gaji.tunjangan_lainnya - rincian_gaji.denda) AS total_gaji
          FROM karyawan
          INNER JOIN rincian_gaji ON karyawan.nip = rincian_gaji.nip
          WHERE karyawan.nip = '$nip' AND rincian_gaji.tanggal = '$tanggal'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nama = $row['nama'];
    $gajiPokok = "Rp " . number_format($row['gaji_pokok'], 0, ',', '.');
    $tunjanganJabatan = "Rp " . number_format($row['tunjangan_jabatan'], 0, ',', '.');
    $tunjanganMasaKerja = "Rp " . number_format($row['tunjangan_masa_kerja'], 0, ',', '.');
    $tunjanganLainnya = "Rp " . number_format($row['tunjangan_lainnya'], 0, ',', '.');
    $denda = "Rp " . number_format($row['denda'], 0, ',', '.');
    $totalGaji = "Rp " . number_format($row['total_gaji'], 0, ',', '.');
} else {
    // Jika data tidak ditemukan, arahkan kembali ke halaman sebelumnya
    header('Location: data-gaji.php');
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Gaji Karyawan - Admin</title>
    <link rel="stylesheet" type="text/css" href="css/style-sidebar-menu.css">
    <link rel="stylesheet" type="text/css" href="css/style-view-gaji-adm.css">
</head>
<body>    <div class="container">
        <h1>Detail Gaji</h1>
        <h2><?php echo $nama;?></h2>
        <table id="view-gaji">
            <tr>
                <th>Nama</th>
                <th>NIP</th>
                <th>Gaji Pokok</th>
            </tr>
            <tr>
                <td><?php echo $nama; ?></td>
                <td><?php echo $nip; ?></td>
                <td><?php echo $gajiPokok; ?></td>
            </tr>
            <tr>
                <td></td>
            </tr>
            <tr>
                <th>Tunjangan Jabatan</th>
                <td colspan="2"><?php echo $tunjanganJabatan; ?></td>
            </tr>
            <tr>
                <th>Tunjangan Masa Kerja</th>
                <td colspan="2"><?php echo $tunjanganMasaKerja; ?></td>
            </tr>
            <tr>
                <th>Pembayaran Pengganti</th>
                <td colspan="2"><?php echo $tunjanganLainnya; ?></td>
            </tr>
            <tr>
                <th>Denda / Cashbon</th>
                <td colspan="2"><?php echo $denda; ?></td>
            </tr>
            <tr>
                <th>Total</th>
                <td colspan="2"><?php echo $totalGaji; ?></td>
            </tr>
        </table>
            <!-- Tambahkan tombol cetak -->
        <a href="javascript:window.print();" class="print-button">Print</a>
        <a href="data-gaji.php" class="back">Kembali</a>
    </div>
</body>
</html>
