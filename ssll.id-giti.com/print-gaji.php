<?php
// Koneksi ke database
include 'conn.php';

// Mengecek parameter yang diterima
if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
    // Cetak berdasarkan tanggal mulai dan berakhir
    $startDate = $conn->real_escape_string($_GET['startDate']);
    $endDate = $conn->real_escape_string($_GET['endDate']);

    $query = "SELECT karyawan.nip, karyawan.nama, rincian_gaji.tanggal, rincian_gaji.gaji_pokok, rincian_gaji.tunjangan_jabatan, rincian_gaji.tunjangan_masa_kerja, rincian_gaji.tunjangan_lainnya, rincian_gaji.denda, (rincian_gaji.gaji_pokok + rincian_gaji.tunjangan_jabatan + rincian_gaji.tunjangan_masa_kerja + rincian_gaji.tunjangan_lainnya - rincian_gaji.denda) AS total_gaji
              FROM karyawan
              INNER JOIN rincian_gaji ON karyawan.nip = rincian_gaji.nip
              WHERE rincian_gaji.tanggal BETWEEN '$startDate' AND '$endDate'
              ORDER BY rincian_gaji.tanggal DESC";
} else {
    // Jika tidak ada parameter yang valid, kembali ke halaman sebelumnya atau lakukan tindakan lain
    header('Location: data-gaji.php');
    exit();
}

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Riwayat Gaji Karyawan</title>
    <style>
        /* Gaya cetakan untuk tabel */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Riwayat Gaji Karyawan</h1>
    <table>
        <tr>
            <th>Tanggal</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Gaji Pokok</th>
            <th>Tunjangan Jabatan</th>
            <th>Tunjangan Masa Kerja</th>
            <th>Pembayaran Pengganti</th>
            <th>Denda / Cashbon</th>
            <th>Total</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {

            $sum = 0;
            while ($row = $result->fetch_assoc()) {
                
                $tanggal = $row['tanggal'];
                $nip = $row['nip'];
                $nama = $row['nama'];
                $gajiPokok = "Rp " . number_format($row['gaji_pokok'], 0, ',', '.');
                $tunjanganJabatan = "Rp " . number_format($row['tunjangan_jabatan'], 0, ',', '.');
                $tunjanganMasaKerja = "Rp " . number_format($row['tunjangan_masa_kerja'], 0, ',', '.');
                $tunjanganLainnya = "Rp " . number_format($row['tunjangan_lainnya'], 0, ',', '.');
                $denda = "Rp " . number_format($row['denda'], 0, ',', '.');
                $total = $row['gaji_pokok'] + $row['tunjangan_jabatan'] + $row['tunjangan_masa_kerja'] + $row['tunjangan_lainnya'] - $row['denda'];
                
                $sum += (int)$total;

                $totalGaji = "Rp " . number_format($total, 0, ',', '.');
                $totalSUM = "Rp " . number_format($sum, 0, ',', '.');
                echo "<tr>";
                echo "<td>" . $tanggal . "</td>";
                echo "<td>" . $nip . "</td>";
                echo "<td>" . $nama . "</td>";
                echo "<td>" . $gajiPokok . "</td>";
                echo "<td>" . $tunjanganJabatan . "</td>";
                echo "<td>" . $tunjanganMasaKerja . "</td>";
                echo "<td>" . $tunjanganLainnya . "</td>";
                echo "<td>" . $denda . "</td>";
                echo "<td>" . $totalGaji . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='9'>No salary data found.</td></tr>";
        }
        
        echo "<tr>";
        echo "<td colspan='8'> </td>";
        echo "<td align='center' style='background-color:yellow; font-weight:bold; padding-right:10px; font-size:18px;'>" . $totalSUM . "</td>";
        echo "</tr>";
        ?>
    </table>

    <script>
        // Otomatis memanggil fungsi print saat halaman selesai diload
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

<?php
// Tutup koneksi ke database
$conn->close();
?>
