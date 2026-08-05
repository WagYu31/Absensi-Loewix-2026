<?php
session_start();

// Cek apakah pengguna telah login dan peran pengguna adalah superadmin
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Gaji - Superadmin</title>
    <!-- Tambahkan CSS atau style lainnya di sini -->
</head>
<body>
    <h1>Laporan Gaji - Superadmin</h1>
    <div>
        <button onclick="window.location.href='laporan-gaji-bulanan.php'">Laporan Gaji Bulanan</button>
        <button onclick="window.location.href='laporan-penggantian-pembayaran.php'">Laporan Penggantian Pembayaran</button>
        <button onclick="window.location.href='laporan-cashbon.php'">Laporan Cashbon</button>
        <button onclick="window.location.href='laporan-denda.php'">Laporan Denda</button>
        <button onclick="window.location.href='laporan-gaji-tahunan.php'">Laporan Gaji Tahunan</button>
        <button onclick="window.location.href='laporan-gaji.php'">Laporan Gaji</button>
    </div>
    <!-- Tambahkan konten lainnya sesuai kebutuhan -->
</body>
</html>
