<!DOCTYPE html>
<html>
<head>
    <title>Print Denda Karyawan</title>
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
        $query = "SELECT karyawan.nip, karyawan.nik, karyawan.nama, GROUP_CONCAT(DISTINCT CONCAT(denda.keterangan, ' : Rp. ', FORMAT(denda.jumlah, 0)) ORDER BY denda.tanggal ASC SEPARATOR '<br>') as keterangan, SUM(denda.jumlah) as total_denda
        FROM denda 
        JOIN karyawan ON karyawan.nip = denda.nip
        WHERE MONTH(denda.tanggal) = ? AND YEAR(denda.tanggal) = ? AND denda.ket1 = 'Denda'
        GROUP BY karyawan.nip, karyawan.nama
        ORDER BY karyawan.nama ASC";


        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $bulan, $tahun);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        echo "<h2>Invalid Request</h2>";
        exit();
    }
    ?>

    <h2>Data Denda Karyawan Bulan <?php echo date('F', mktime(0, 0, 0, $bulan, 1)) . ' ' . $tahun; ?></h2>

    <table>
        <tr>
            <th style="width:5%">NIK</th>
            <th style="width:25%">Nama</th>
            <th style="width:55%">Keterangan</th>
            <th style="width:15%">Jumlah Denda</th>
        </tr>
        <?php
        $totalDenda = 0;
        while ($data = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $data['nik'] . "</td>";
            echo "<td style='text-align:left;'>" . $data['nama'] . "</td>";
            echo "<td style='text-align:left;'>" . $data['keterangan'] . "</td>";
            echo "<td>Rp " . number_format($data['total_denda'], 0, ',', '.') . "</td>";
            echo "</tr>";
                // Accumulate the total denda
                $totalDenda += $data['total_denda'];
            }

            // Display the TOTAL row
            echo "<tr>";
            echo "<td colspan='3' style='text-align: right; font-weight: bold;'>TOTAL</td>";
            echo "<td style='font-weight: bold;'>Rp " . number_format($totalDenda, 0, ',', '.') . "</td>";
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
