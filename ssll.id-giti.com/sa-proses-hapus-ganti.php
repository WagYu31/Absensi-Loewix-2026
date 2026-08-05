<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

if (isset($_GET['id_tunjangan_lain'])) {
    $id = $_GET['id_tunjangan_lain'];

    // Lakukan pemrosesan hapus data denda dari database
    include 'conn.php';

    // Query untuk mengambil data denda berdasarkan ID
    $queryGetDenda = "SELECT * FROM tunjangan_lainnya WHERE id_tunjangan_lain = '$id'";
    $resultGetDenda = $conn->query($queryGetDenda);

    if ($resultGetDenda->num_rows > 0) {
        // Data ditemukan, lanjutkan proses penghapusan
        $dendaData = $resultGetDenda->fetch_assoc();
        $nipDenda = $dendaData['nip'];
        $tanggalDenda = $dendaData['tanggal'];
        $jumlahDenda = $dendaData['jumlah'];

        // Query untuk menghapus data denda berdasarkan ID
        $queryDelete = "DELETE FROM tunjangan_lainnya WHERE id_tunjangan_lain = '$id'";

        if ($conn->query($queryDelete) === TRUE) {
            // Lakukan pengecekan apakah data dengan NIP dan tanggal yang sama sudah ada di tabel rincian_gaji
            $queryCheck = "SELECT * FROM rincian_gaji WHERE nip='$nipDenda' AND MONTH(tanggal)='".date('m', strtotime($tanggalDenda))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalDenda))."'";
            $resultCheck = $conn->query($queryCheck);

            if ($resultCheck->num_rows > 0) {
                // Jika data dengan NIP dan tanggal yang sama sudah ada, maka lakukan update jumlah denda
                $queryUpdate = "UPDATE rincian_gaji SET tunjangan_lainnya = tunjangan_lainnya - '$jumlahDenda' WHERE nip='$nipDenda' AND MONTH(tanggal)='".date('m', strtotime($tanggalDenda))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalDenda))."'";
                $resultUpdate = $conn->query($queryUpdate);

                if (!$resultUpdate) {
                    echo "Error: " . $queryUpdate . "<br>" . $conn->error;
                }
            } 

            // Redirect ke halaman denda-karyawan.php jika berhasil dihapus
            $message = "Success!";
            echo "<script>alert('$message'); window.location.href = 'laporan-pengganti.php';</script>";
            exit();
        } else {
            // Tampilkan pesan error jika gagal menghapus data
            echo "Error: " . $queryDelete . "<br>" . $conn->error;
        }
    } else {
        // Data tidak ditemukan, arahkan kembali ke halaman denda-karyawan.php
        header('Location: laporan-pengganti.php');
        exit();
    }

    $conn->close();
} else {
    // Jika parameter id tidak ditemukan, arahkan kembali ke halaman denda-karyawan.php
    header('Location: laporan-pengganti.php');
    exit();
}
?>
