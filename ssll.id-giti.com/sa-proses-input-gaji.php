<?php
include 'conn.php';

$nip = $_POST['nip'];
$gaji_pokok = $_POST['gaji_pokok'];

for ($i = 0; $i < count($nip); $i++) {
    $nip_karyawan = $nip[$i];
    $gaji_str = $gaji_pokok[$i];
    $gaji = !empty($gaji_str) ? floatval(str_replace(',', '', $gaji_str)) : 0;

    $query = "UPDATE karyawan SET gaji_pokok = $gaji WHERE nip = '$nip_karyawan'";
    if ($conn->query($query) !== TRUE) {
        die("Query execution failed: " . $conn->error);
    }
}

$conn->close();
// Redirect kembali ke halaman sebelumnya atau halaman lainnya
header('Location: sa-data-karyawan.php');
exit();
?>
