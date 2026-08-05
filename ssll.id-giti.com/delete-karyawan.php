<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil id_rincian_gaji dari parameter POST
    $nip = $_POST['nip'];

    include 'conn.php';

    $query = "DELETE FROM karyawan, rincian_gaji WHERE nip = '$nip'";
    $result = $conn->query($query);

    if ($result) {
        $response = array("status" => "success");
        echo json_encode($response);
    } else {
        $response = array("status" => "failed");
        echo json_encode($response);
    }
} else {
    // Jika metode request bukan POST, arahkan ke halaman lainnya
    header('Location: data-karyawan.php');
    exit();
}
