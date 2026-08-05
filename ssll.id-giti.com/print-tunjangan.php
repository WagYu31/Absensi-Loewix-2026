<!DOCTYPE html>
<html>
<head>
    <title>Print Biaya Pengganti Karyawan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h2 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <?php
    // Koneksi ke database
    include 'conn.php';

    // Memeriksa apakah form telah disubmit dengan memeriksa method GET
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['bulan']) && isset($_GET['tahun'])) {
        $bulan = $_GET["bulan"];
        $tahun = $_GET["tahun"];

        // Query untuk mengambil data rincian gaji berdasarkan bulan dan tahun yang dipilih
        $query = "SELECT karyawan.nip, karyawan.nik, karyawan.nama, GROUP_CONCAT(DISTINCT CONCAT(tunjangan_lainnya.keterangan, ' : Rp. ', FORMAT(tunjangan_lainnya.jumlah, 0)) ORDER BY tunjangan_lainnya.tanggal ASC SEPARATOR '<br>') as keterangan, SUM(tunjangan_lainnya.jumlah) as total_tunjangan_lainnya
        FROM tunjangan_lainnya 
        JOIN karyawan ON karyawan.nip = tunjangan_lainnya.nip
        WHERE MONTH(tunjangan_lainnya.tanggal) = ? AND YEAR(tunjangan_lainnya.tanggal) = ? AND tunjangan_lainnya.ket1 = 'ganti'
        GROUP BY karyawan.nip, karyawan.nama";


        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $bulan, $tahun);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        echo "<h2>Invalid Request</h2>";
        exit();
    }
    ?>

    <h2>Data Biaya Pengganti Karyawan Bulan <?php echo date('F', mktime(0, 0, 0, $bulan, 1)) . ' ' . $tahun; ?></h2>

    <table>
        <tr>
            <th>NIK</th>
            <th>Nama</th>
            <th>Keterangan</th>
            <th>Jumlah</th>
        </tr>
        <?php
        $totaltunjangan_lainnya = 0;
        while ($data = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='width:5%'>" . $data['nik'] . "</td>";
            echo "<td style='width:25%'>" . $data['nama'] . "</td>";
            echo "<td style='text-align:left;width:15%;'>" . $data['keterangan'] . "</td>";
            echo "<td style='width:15%'>Rp " . number_format($data['total_tunjangan_lainnya'], 0, ',', '.') . "</td>";
            echo "</tr>";
                // Accumulate the total tunjangan_lainnya
                $totaltunjangan_lainnya += $data['total_tunjangan_lainnya'];
            }

            // Display the TOTAL row
            echo "<tr>";
            echo "<td colspan='3' style='text-align: right; font-weight: bold;'>TOTAL</td>";
            echo "<td style='font-weight: bold;'>Rp " . number_format($totaltunjangan_lainnya, 0, ',', '.') . "</td>";
            echo "</tr>";
            ?>
    </table>
    <script>
        // Wait for the document to fully load
        window.addEventListener('load', function() {
            // Function to trigger print
            function triggerPrint() {
                window.print();
            }

            // Delay the print trigger to ensure the content is fully rendered
            setTimeout(triggerPrint, 1000);
        });
    </script>
</body>
</html>
