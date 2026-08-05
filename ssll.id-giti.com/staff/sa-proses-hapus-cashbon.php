<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

if (isset($_GET['id_denda'])) {
    $id = $_GET['id_denda'];

    // Lakukan pemrosesan hapus data denda dari database
    include '../conn.php';

    // Query untuk mengambil data denda berdasarkan ID
    $queryGetDenda = "SELECT * FROM cashbon WHERE id_cashbon = '$id'";
    $resultGetDenda = $conn->query($queryGetDenda);

    if ($resultGetDenda->num_rows > 0) {
        // Data ditemukan, lanjutkan proses penghapusan
        $dendaData = $resultGetDenda->fetch_assoc();
        $nipDenda = $dendaData['nip'];
        $tanggalDenda = $dendaData['tanggal'];
        $jumlahDenda = $dendaData['jumlah'];
        
        // Hapus data dari tabel bayar_cashbon yang memiliki id_cashbon dan nip yang sama
        $queryDeleteBayar = "DELETE FROM bayar_cashbon WHERE id_cashbon = '$id' AND nip = '$nipDenda'";
        if ($conn->query($queryDeleteBayar) !== TRUE) {
            // Jika terjadi kesalahan saat menghapus data, Anda dapat menampilkan pesan error
            // echo "Error: " . $queryDeleteBayar . "<br>" . $conn->error;
        }

        // Query untuk menghapus data denda berdasarkan ID
        $queryDelete = "DELETE FROM cashbon WHERE id_cashbon = '$id'";

        if ($conn->query($queryDelete) === TRUE) {
            // Redirect ke halaman denda-karyawan.php jika berhasil dihapus
            $message = "Success!";
            echo "<script>alert('$message'); window.location.href = 'cashbon.php';</script>";
            exit();
        } else {
            // Tampilkan pesan error jika gagal menghapus data
            echo "Error: " . $queryDelete . "<br>" . $conn->error;
        }
    } else {
        // Data tidak ditemukan, arahkan kembali ke halaman denda-karyawan.php
        header('Location: cashbon.php');
        exit();
    }

    $conn->close();
} else {
    // Jika parameter id tidak ditemukan, arahkan kembali ke halaman denda-karyawan.php
    header('Location: cashbon.php');
    exit();
}
?>
