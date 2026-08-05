<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip'])) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

// Periksa apakah data yang dibutuhkan dikirimkan melalui metode POST
if (isset($_POST['nip_denda']) && isset($_POST['jumlah_denda']) && isset($_POST['keterangan_denda']) && isset($_POST['bayar'])) {
    $nipDenda = $_POST['nip_denda'];
    $tanggalDenda = date("Y-m-d");
    $jumlahDenda = $_POST['jumlah_denda'];
    $keteranganDenda = $_POST['keterangan_denda'];
    $bayar = $_POST['bayar'];

    // Lakukan pemrosesan tambah data denda ke database
    include 'conn.php';

    // Query untuk menambahkan data denda ke tabel denda
    $query = "INSERT INTO pengajuan_cashbon (nip, tanggal, jumlah, keterangan, cicil, status) VALUES ('$nipDenda', '$tanggalDenda', '$jumlahDenda', '$keteranganDenda', '$bayar', 'pengajuan')";

if ($conn->query($query) === TRUE) {
    // Redirect ke halaman denda-karyawan.php jika berhasil ditambahkan
    $message = "Success!";
    echo "<script>alert('$message'); window.location.href = 'cashbon.php';</script>";
    exit();
} else {
    // Tampilkan pesan error jika gagal menambahkan data
    echo "Error: " . $query . "<br>" . $conn->error;
}


    $conn->close();
}
?>
