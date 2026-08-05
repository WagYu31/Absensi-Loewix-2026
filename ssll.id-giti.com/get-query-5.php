    <?php $query5 = "SELECT denda.*, karyawan.nip FROM karyawan
                JOIN denda ON denda.nip = karyawan.nip
                WHERE karyawan.nip = '$nip' AND MONTH(denda.tanggal) = $bulan AND YEAR(denda.tanggal) = $tahun";
    
    $result5 = $conn->query($query5);
    if (!$result5) {
        die("Query execution failed: " . $conn->error);
    } ?>
