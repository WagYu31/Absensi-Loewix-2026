<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.html');
    exit();
}

include 'conn.php';

if (isset($_GET['id_tunjangan_lain'])) {
    $idTunjangan = $_GET['id_tunjangan_lain'];

    $query = "SELECT * FROM tunjangan_lainnya WHERE id_tunjangan_lain = '$idTunjangan'";
    $resultGetTunjangan = $conn->query($query);

    if ($resultGetTunjangan && $resultGetTunjangan->num_rows > 0) {
        $tunjanganData = $resultGetTunjangan->fetch_assoc();
        $nip = $tunjanganData['nip'];
        $tanggal = $tunjanganData['tanggal'];
        $jumlah = $tunjanganData['jumlah'];

        $queryDelete = "DELETE FROM tunjangan_lainnya WHERE id_tunjangan_lain = '$idTunjangan'";
        
        if ($conn->query($queryDelete) === TRUE) {
            $queryCheck = "SELECT * FROM rincian_gaji WHERE nip = '$nip' AND MONTH(tanggal)='".date('m', strtotime($tanggal))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggal))."'";
            $resultCheck = $conn->query($queryCheck);

            if ($resultCheck && $resultCheck->num_rows > 0) {
                $queryUpdate = "UPDATE rincian_gaji SET tunjangan_lainnya = tunjangan_lainnya - $jumlah WHERE nip = '$nip' AND MONTH(tanggal)='".date('m', strtotime($tanggal))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggal))."'";
                $conn->query($queryUpdate);
            }
            
            $conn->close();
            header('Location: staff/tunjangan-karyawan.php?msg=deleted');
            exit();
        } else {
            echo "Error: " . $queryDelete . "<br>" . $conn->error;
        }
    } else {
        header('Location: staff/tunjangan-karyawan.php');
        exit();
    }

    $conn->close();
} else {
    header('Location: staff/tunjangan-karyawan.php');
    exit();
}
?>
