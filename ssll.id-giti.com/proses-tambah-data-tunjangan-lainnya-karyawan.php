<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

// Periksa apakah data yang dibutuhkan dikirimkan melalui metode POST
if (isset($_POST['nip_tunjangan']) && isset($_POST['tanggal_tunjangan']) && isset($_POST['jumlah_tunjangan']) && isset($_POST['keterangan_tunjangan'])) {
    $nipTunjangan = $_POST['nip_tunjangan'];
    $tanggalTunjangan = $_POST['tanggal_tunjangan'];
    $jumlahTunjangan = $_POST['jumlah_tunjangan'];
    $keteranganTunjangan = $_POST['keterangan_tunjangan'];

    // Lakukan pemrosesan tambah data tunjangan ke database
    include 'conn.php';

    // Query untuk menambahkan data tunjangan ke tabel tunjangan_lainnya
    $query = "INSERT INTO tunjangan_lainnya (nip, tanggal, jumlah, keterangan, ket1) VALUES ('$nipTunjangan', '$tanggalTunjangan', '$jumlahTunjangan', '$keteranganTunjangan', 'ganti')";

    if ($conn->query($query) === TRUE) {
        // Lakukan pengecekan apakah data dengan NIP dan tanggal yang sama sudah ada di tabel rincian_gaji
        $queryCheck = "SELECT * FROM rincian_gaji WHERE nip='$nipTunjangan' AND MONTH(tanggal)='".date('m', strtotime($tanggalTunjangan))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalTunjangan))."'";
        $resultCheck = $conn->query($queryCheck);

        if ($resultCheck->num_rows > 0) {
            // Jika data dengan NIP dan tanggal yang sama sudah ada, maka lakukan update jumlah tunjangan lainnya
            $queryUpdate = "UPDATE rincian_gaji SET tunjangan_lainnya = tunjangan_lainnya + '$jumlahTunjangan' WHERE nip='$nipTunjangan' AND MONTH(tanggal)='".date('m', strtotime($tanggalTunjangan))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalTunjangan))."'";
            $resultUpdate = $conn->query($queryUpdate);

            if (!$resultUpdate) {
                echo "Error: " . $queryUpdate . "<br>" . $conn->error;
            }
        } else {
            // Jika data dengan NIP dan tanggal yang sama belum ada, maka tambahkan data baru ke tabel rincian_gaji
            $queryInsert = "INSERT INTO rincian_gaji (nip, tanggal, tunjangan_lainnya) VALUES ('$nipTunjangan', '$tanggalTunjangan', '$jumlahTunjangan')";
            $resultInsert = $conn->query($queryInsert);

            if (!$resultInsert) {
                echo "Error: " . $queryInsert . "<br>" . $conn->error;
            }
        }

        // Redirect ke halaman tunjangan-karyawan.php jika berhasil ditambahkan
        $message = "Success!";
        echo "<script>alert('$message'); window.location.href = 'staff/tunjangan-karyawan.php';</script>";
        exit();
    } else {
        // Tampilkan pesan error jika gagal menambahkan data
        echo "Error: " . $query . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
