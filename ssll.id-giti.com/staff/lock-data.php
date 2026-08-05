<?php
include '../conn.php';

if (isset($_GET['bulan']) && isset($_GET['tahun'])) {
    $bulan = $_GET['bulan']; // Ambil bulan dari URL
    $tahun = $_GET['tahun']; // Ambil tahun dari URL

    $queryKunci = "SELECT * FROM kunci_gaji WHERE bulan = '$bulan' AND tahun = '$tahun' AND kunci = 'Lock'";
    $resultKunci = $conn->query($queryKunci);
    
    $kunci = "Lock";

    if (!$resultKunci) {
        die("Query execution failed for kunci_gaji: " . $conn->error);
    }

    if ($resultKunci->num_rows > 0) {
        echo "Data sudah terkunci.";
        http_response_code(400); // Bad Request
        header("Location: penggajian.php");
    } 
    else {
        $kncQuery = "INSERT INTO kunci_gaji (bulan, tahun, kunci) VALUES ('$bulan', '$tahun', '$kunci')";
        if (!mysqli_query($conn, $kncQuery)) {
            http_response_code(200); // OK
            header("Location: penggajian.php");
        }
    } 
} 
else {
    http_response_code(400); // Bad Request
    header("Location: penggajian.php");
}
?>