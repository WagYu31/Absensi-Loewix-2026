    <?php $query6 = "SELECT bayar_cashbon.*, karyawan.nip FROM karyawan
                JOIN bayar_cashbon ON bayar_cashbon.nip = karyawan.nip
                WHERE karyawan.nip = '$nip' AND MONTH(bayar_cashbon.tanggal) = $bulan AND YEAR(bayar_cashbon.tanggal) = $tahun";
    
    $result6 = $conn->query($query6);
    if (!$result6) {
        die("Query execution failed: " . $conn->error);
    } ?>
