   <?php $query4 = "SELECT tunjangan_lainnya.*, karyawan.nip FROM karyawan
                JOIN tunjangan_lainnya ON tunjangan_lainnya.nip = karyawan.nip
                WHERE karyawan.nip = '$nip' AND MONTH(tunjangan_lainnya.tanggal) = $bulan AND YEAR(tunjangan_lainnya.tanggal) = $tahun";
    
    $result4 = $conn->query($query4);
    if (!$result4) {
        die("Query execution failed: " . $conn->error);
    }?>