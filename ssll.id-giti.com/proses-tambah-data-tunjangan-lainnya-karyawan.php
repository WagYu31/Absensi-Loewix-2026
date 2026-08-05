<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.html');
    exit();
}

if (isset($_POST['nip_tunjangan']) && isset($_POST['tanggal_tunjangan']) && isset($_POST['jumlah_tunjangan']) && isset($_POST['keterangan_tunjangan'])) {
    $nipTunjangan = $_POST['nip_tunjangan'];
    $tanggalTunjangan = $_POST['tanggal_tunjangan'];
    $jumlahTunjangan = $_POST['jumlah_tunjangan'];
    $keteranganTunjangan = $_POST['keterangan_tunjangan'];

    include 'conn.php';

    $query = "INSERT INTO tunjangan_lainnya (nip, tanggal, jumlah, keterangan, ket1) VALUES ('$nipTunjangan', '$tanggalTunjangan', '$jumlahTunjangan', '$keteranganTunjangan', 'ganti')";

    if ($conn->query($query) === TRUE) {
        $queryCheck = "SELECT * FROM rincian_gaji WHERE nip='$nipTunjangan' AND MONTH(tanggal)='".date('m', strtotime($tanggalTunjangan))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalTunjangan))."'";
        $resultCheck = $conn->query($queryCheck);

        if ($resultCheck && $resultCheck->num_rows > 0) {
            $queryUpdate = "UPDATE rincian_gaji SET tunjangan_lainnya = tunjangan_lainnya + '$jumlahTunjangan' WHERE nip='$nipTunjangan' AND MONTH(tanggal)='".date('m', strtotime($tanggalTunjangan))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggalTunjangan))."'";
            $conn->query($queryUpdate);
        } else {
            $queryInsert = "INSERT INTO rincian_gaji (nip, tanggal, tunjangan_lainnya) VALUES ('$nipTunjangan', '$tanggalTunjangan', '$jumlahTunjangan')";
            $conn->query($queryInsert);
        }

        $conn->close();
        header('Location: staff/tunjangan-karyawan.php?msg=added');
        exit();
    } else {
        echo "Error: " . $query . "<br>" . $conn->error;
    }

    $conn->close();
} else {
    header('Location: staff/tunjangan-karyawan.php');
    exit();
}
?>
