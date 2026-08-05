<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <script type="text/javascript" src="tableExport.js"></script>
    <script type="text/javascript" src="jquery.base64.js"></script>
    <script type="text/javascript" src="html2canvas.js"></script>
    <script type="text/javascript" src="jspdf/libs/sprintf.js"></script>
    <script type="text/javascript" src="jspdf/jspdf.js"></script>
    <script type="text/javascript" src="jspdf/libs/base64.js"></script>
    <script type="text/javascript" src="js/script-download-denda.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/style-menu-bar.css">

    <?php
    include 'conn.php';

    // Memeriksa apakah form telah disubmit dengan memeriksa method POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $bulan = $_POST["bulan"];
        $tahun = $_POST["tahun"];
    } else {
        // Jika form belum disubmit, gunakan bulan dan tahun saat ini
        $bulan = date('m');
        $tahun = date('Y');
    }

    // Query untuk mengambil data rincian gaji berdasarkan bulan dan tahun yang dipilih
    $query = "SELECT denda.*, karyawan.nama
            FROM denda 
            JOIN karyawan ON karyawan.nip = denda.nip";

    // Tambahkan kondisi filter berdasarkan bulan dan tahun jika telah dipilih
    if (!empty($bulan) && !empty($tahun)) {
        $query .= " WHERE MONTH(denda.tanggal) = '$bulan' AND YEAR(denda.tanggal) = '$tahun' AND denda.ket1 = 'Denda'";
    }

    $result = $conn->query($query);

    if (!$result) {
        die("Query execution failed: " . $conn->error);
    }
    
    ?>

    <style>
        .container{
            margin-top:5%;
            margin-left:16%;
            width:80%;
        }
        table{
            border: 2px solid #ddd;
        }
        table tr{
            vertical-align: middle;
        }
        table tr th.judul{
            font-size:18px;
            text-transform: uppercase;
            background-color:#333;
            color:white;
            text-align:center;
            padding: 10px;
        }
        table tr th{
            text-align: center;
        }
        table tr td{
            font-size:14px;
            text-align: center;
        }
        table tr td.total{
            text-align:right;
            padding-right:30px;
            background-color:yellow;
            font-size:15px;
            font-weight:bold;
        }
        i {
            border:none;
            background:none;
            text-decoration:none;
            font-size:15px;
            padding:10px;
            text-align:center;
            color:#0073e6;
        }
        i:hover{
            color:#006080;
            cursor:pointer;
        }
        @media print {
            .no-print {
                display: none;
            }
            .container{
                margin-left:0;
                width:100%;
            }
        }
    </style>
</head>
<body>

<div class="sidebar no-print">
        <ul class="z">
            <li><a href="sa-data-karyawan.php">Data Karyawan</a></li>
            <li><a href="sa-tambah-data.php">Akun Pegawai</a></li>
            <li><a href="sa-bonus.php">Bonus</a></li>
            <li><a href="sa-cashbon.php">Cashbon</a></li>
            <li>
                <a href="#">Laporan</a>
                <ul class="dropdown-menu">
                    <li><a href="laporan-gaji.php">Gaji</a></li>
                    <li><a href="laporan-cashbon.php">Cashbon</a></li>
                    <li><a href="laporan-denda.php">Denda</a></li>
                    <li><a href="laporan-pengganti.php">Uang Pengganti</a></li>
                </ul>
            </li>
            <li><a href="logout.php">Keluar</a></li>

        </ul>
    </div>

<div class="container">
<div class="pilih no-print">
    <!-- Tambahkan form untuk mengelompokkan input select -->
    <form method="post">
        <!-- Tambahkan input select untuk memilih nama bulan -->
        <label for="bulan">Bulan :</label>
        <select id="bulan" name="bulan">
            <?php
            $bulanNames = array(
                '01' => 'Januari',
                '02' => 'Februari',
                '03' => 'Maret',
                '04' => 'April',
                '05' => 'Mei',
                '06' => 'Juni',
                '07' => 'Juli',
                '08' => 'Agustus',
                '09' => 'September',
                '10' => 'Oktober',
                '11' => 'November',
                '12' => 'Desember'
            );

            foreach ($bulanNames as $bulanNum => $bulanName) {
                $selected = ($bulanNum == $bulan) ? 'selected' : '';
                echo "<option value='$bulanNum' $selected>$bulanName</option>";
            }
            ?>
        </select>

        <!-- Tambahkan input select untuk memilih tahun -->
        <label for="tahun" style="margin-left:10px;">Tahun :</label>
        <select id="tahun" name="tahun">
            <?php
            $tahunSekarang = date('Y');
            for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                $selected = ($i == $tahun) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            }
            ?>
        </select>

        <!-- Tambahkan tombol "Tampilkan Data" -->
        <button type="submit" class="gaji">Tampilkan Data</button>
    </form>
</div>
        <div class="row">
            <div class="btn-group pull-right no-print" style="padding: 10px;">
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <i class="fas fa-download"></i>Simpan
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                        <li><a href="#" onclick="exportToExcel();"><i class="fas fa-file-excel"></i>XLS</a></li>
                        <li><a href="#" onclick="exportToCSV();"><i class="fas fa-file-csv"></i>CSV</a></li>
                        <li><a href="#" onclick="exportToTXT();"><i class="fas fa-file-lines"></i>TXT</a></li>
                        <li><a href="#" onclick="printPage();"><i class="fas fa-print"></i>PRINT</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row" style="height: auto !important;">
            <table id="employees" class="table table-striped">
                <tr>
                    <?php
                    $currentMonthYear = date('F Y');
                    $lastMonthYear = date('F Y', strtotime('-1 month'));
                    ?>
                    <th colspan="5" class="judul">Laporan Denda</th>
                </tr>
                <tr>
                    <th width="3%">NIP</th>
                    <th width="8%">Nama</th>
                    <th width="11%">Tanggal</th>
                    <th width="11%">Jumlah</th>
                    <th width="6%" class="th no-print">Aksi</th>
                </tr>
                <?php
                    $sum = 0;
                    $totalSUM = 0;
                    while ($data = $result->fetch_assoc()) : 
                    $denda = $data['jumlah'];
                    $sum += (int)$denda;

                    $totalSUM = "Rp " . number_format($sum, 0, ',', '.');
                ?>
                <tr>
                    <td style="text-align:center;"><?php echo $data['nip']; ?></td>
                    <td><?php echo $data['nama']; ?></td>
                    <td><?php echo $data['tanggal']; ?></td>
                    <td><?php echo "Rp " . number_format($denda, 0, ',', '.'); ?></td>
                    <td class="td no-print">
                    <button onclick="deleteDenda('<?php echo $data['id_denda']; ?>')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endwhile; ?>
                <tr>
                    <td colspan="3" style="font-weight:bold;">TOTAL</td>
                    <td class="total" colspan="2" style="text-align:center;"><?php echo $totalSUM; ?></td>
                </tr>
        </table>
    </div>
</div>

<script>

function deleteDenda(id_denda) {
        if (confirm("Are you sure you want to delete this data??")) {
            // Redirect ke halaman proses-hapus-data-denda-karyawan.php dengan mengirimkan id data yang akan dihapus melalui parameter GET
            window.location.href = "sa-proses-hapus-denda.php?id_denda=" + id_denda; // Change 'id' to 'id_denda'
        }
      }

    function tampilkanData() {
        var bulan = document.getElementById("bulan").value;
        var tahun = document.getElementById("tahun").value;
        window.location.href = "laporan-denda.php?bulan=" + bulan + "&tahun=" + tahun;
    }
    </script>

</body>
</html>
