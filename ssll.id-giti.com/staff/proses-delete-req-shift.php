<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || ($_SESSION['role'] == 'admin' && $_SESSION['role'] == 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Lakukan pemrosesan hapus data denda dari database
    include '../conn.php';

    // Query untuk mengambil data denda berdasarkan ID
    $queryGetDenda = "SELECT * FROM shift_req WHERE id = '$id'";
    $resultGetDenda = $conn->query($queryGetDenda);

    if ($resultGetDenda->num_rows > 0) {
        // Data ditemukan, lanjutkan proses penghapusan
        $dendaData = $resultGetDenda->fetch_assoc();

        // Query untuk menghapus data denda berdasarkan ID
        $queryDelete = "DELETE FROM shift_req WHERE id = '$id'";

        if ($conn->query($queryDelete) === TRUE) {
            // Redirect ke halaman denda-karyawan.php jika berhasil dihapus
            $message = "Success!";
            echo "<script>alert('$message'); window.location.href = 'shit-req.php';</script>";
            exit();
        } else {
            // Tampilkan pesan error jika gagal menghapus data
            echo "Error: " . $queryDelete . "<br>" . $conn->error;
        }
    } else {
        // Data tidak ditemukan, arahkan kembali ke halaman denda-karyawan.php
        header('Location: shit-req.php');
        exit();
    }

    $conn->close();
} else {
    // Jika parameter id tidak ditemukan, arahkan kembali ke halaman denda-karyawan.php
    header('Location: shit-req.php');
    exit();
}
?>
