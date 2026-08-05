<?php
// Sisipkan file koneksi ke database
include 'conn.php';

// Periksa apakah tanggal saat ini adalah tanggal 14
if (date('d') === '13') {
    // Ambil tahun dan bulan saat ini
    $tahun = date('Y');
    $bulan = date('m');

    // Query untuk mengambil data karyawan dengan jenis_gaji 'mingguan'
    $query = "SELECT * FROM karyawan WHERE jenis_gaji = 'mingguan'";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $nip = $row['nip'];

            // Periksa apakah data rincian_gaji sudah ada untuk karyawan ini pada bulan dan tahun saat ini
            $checkQuery = "SELECT * FROM rincian_gaji WHERE nip = '$nip' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'";
            $checkResult = $conn->query($checkQuery);

            if ($checkResult->num_rows > 0) {
                // Data rincian_gaji sudah ada, lakukan update
                $data = $checkResult->fetch_assoc();
                $idRincianGaji = $data['id_rincian_gaji'];
                $gaji1 = $row['gaji_1'];
                $tgl1 = date('Y-m-d'); // Tanggal saat ini

                // Query untuk melakukan update pada rincian_gaji
                $updateQuery = "UPDATE rincian_gaji SET m1 = $gaji1, tgl1 = '$tgl1' WHERE id_rincian_gaji = '$idRincianGaji'";
                $conn->query($updateQuery);
            } else {
                // Data rincian_gaji belum ada, lakukan insert baru
                $insertQuery = "INSERT INTO rincian_gaji (nip, tanggal, m1, tgl1) VALUES ('$nip', '$tahun-$bulan-14', '$gaji1', '$tgl1')";
                $conn->query($insertQuery);
            }
        }
    } else {
        echo "Error executing query: " . $conn->error;
    }
}

// Tutup koneksi database
$conn->close();
?>
