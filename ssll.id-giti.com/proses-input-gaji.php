<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

// Memeriksa apakah form telah dikirimkan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form
    $nip = $_POST['nip'];
    $tanggal = $_POST['tanggal'];
    $gajiPokok = $_POST['gaji_pokok'];
    $tunjanganJabatan = $_POST['tunjangan_jabatan'];
    $tunjanganMasaKerja = $_POST['tunjangan_masa_kerja'];

    // Ambil bulan dan tahun saat ini
    $bulanIni = date('m');
    $tahunIni = date('Y');

    // Menghitung total tunjangan lainnya
    $queryTunjanganLainnya = "SELECT SUM(jumlah) AS total_tunjangan_lainnya FROM tunjangan_lainnya WHERE nip = '$nip' AND MONTH(tanggal) = $bulanIni AND YEAR(tanggal) = $tahunIni";
    $resultTunjanganLainnya = $conn->query($queryTunjanganLainnya);
    $rowTunjanganLainnya = $resultTunjanganLainnya->fetch_assoc();
    $tunjanganLainnya = $rowTunjanganLainnya['total_tunjangan_lainnya'];

    // Menghitung total denda
    $queryDenda = "SELECT SUM(jumlah) AS total_denda FROM denda WHERE nip = '$nip' AND MONTH(tanggal) = $bulanIni AND YEAR(tanggal) = $tahunIni";
    $resultDenda = $conn->query($queryDenda);
    $rowDenda = $resultDenda->fetch_assoc();
    $denda = $rowDenda['total_denda'];

    // Melakukan operasi INSERT ke tabel rincian_gaji
    $query = "INSERT INTO rincian_gaji (nip, tanggal, gaji_pokok, tunjangan_jabatan, tunjangan_masa_kerja, tunjangan_lainnya, denda)
          VALUES ('$nip', '$tanggal', '$gajiPokok', '$tunjanganJabatan', '$tunjanganMasaKerja', '$tunjanganLainnya', '$denda')";

    if ($conn->query($query) === TRUE) {
        // Redirect ke halaman gaji-karyawan.php dengan pesan pop-up
        $message = "Employee salary has been added.";
        echo "<script>alert('$message'); window.location.href = 'gaji-karyawan.php';</script>";
        exit();
    } else {
        // Jika terjadi error saat menambahkan data ke database
        echo "Error: " . $query . "<br>" . $conn->error;
    }

    $conn->close();

}
?>
