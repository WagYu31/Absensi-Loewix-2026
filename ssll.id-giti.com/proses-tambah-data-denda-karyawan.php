<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

// Periksa apakah data yang dibutuhkan dikirimkan melalui metode POST
if (isset($_POST['nip_denda']) && isset($_POST['tanggal_denda']) && isset($_POST['jumlah_denda']) && isset($_POST['keterangan_denda'])) {
    $nipDenda = $_POST['nip_denda'];
    $tanggalDenda = $_POST['tanggal_denda'];
    $jumlahDenda = $_POST['jumlah_denda'];
    $keteranganDenda = $_POST['keterangan_denda'];

    // Lakukan pemrosesan tambah data denda ke database
    include 'conn.php';

    // Query untuk menambahkan data denda ke tabel denda
    $query = "INSERT INTO denda (nip, tanggal, jumlah, keterangan) VALUES ('$nipDenda', '$tanggalDenda', '$jumlahDenda', '$keteranganDenda')";

    if ($conn->query($query) === TRUE) {
        // Lakukan pengecekan apakah data dengan NIP dan tanggal yang sama sudah ada di tabel rincian_gaji
        $queryCheck = "SELECT * FROM rincian_gaji WHERE nip='$nipDenda' AND MONTH(tanggal)='".date('m', strtotime($tanggalDenda))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalDenda))."'";
        $resultCheck = $conn->query($queryCheck);

        if ($resultCheck->num_rows > 0) {
            // Jika data dengan NIP dan tanggal yang sama sudah ada, maka lakukan update jumlah denda
            $queryUpdate = "UPDATE rincian_gaji SET denda = denda + '$jumlahDenda' WHERE nip='$nipDenda' AND MONTH(tanggal)='".date('m', strtotime($tanggalDenda))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalDenda))."'";
            $resultUpdate = $conn->query($queryUpdate);

            if (!$resultUpdate) {
                echo "Error: " . $queryUpdate . "<br>" . $conn->error;
            }
        } else {
            // Jika data dengan NIP dan tanggal yang sama belum ada, maka tambahkan data baru ke tabel rincian_gaji
            $queryInsert = "INSERT INTO rincian_gaji (nip, tanggal, denda) VALUES ('$nipDenda', '$tanggalDenda', '$jumlahDenda')";
            $resultInsert = $conn->query($queryInsert);

            if (!$resultInsert) {
                echo "Error: " . $queryInsert . "<br>" . $conn->error;
            }
        }

        // Redirect ke halaman denda-karyawan.php jika berhasil ditambahkan
        $message = "Success!";
        echo "<script>alert('$message'); window.location.href = 'denda-karyawan.php';</script>";
        exit();
    } else {
        // Tampilkan pesan error jika gagal menambahkan data
        echo "Error: " . $query . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
