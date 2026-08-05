<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip'])) {
    // Jika tidak ada sesi pengguna, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

// Ambil data karyawan dari database
$host = "localhost";
$user = "root";
$password = "";
$database = "salary";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection to the database failed. " . $conn->connect_error);
}

// Ambil ID rincian gaji dari parameter URL
if (isset($_GET['id'])) {
    $idRincianGaji = $_GET['id'];

    // Query untuk mengambil rincian gaji berdasarkan ID
    $query = "SELECT * FROM rincian_gaji WHERE id_rincian_gaji = '$idRincianGaji'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        // Jika ID rincian gaji tidak ditemukan, arahkan ke halaman riwayat-gaji.php
        header('Location: riwayat-gaji.php');
        exit();
    }
} else {
    // Jika tidak ada parameter ID, arahkan ke halaman riwayat-gaji.php
    header('Location: riwayat-gaji.php');
    exit();
}

// Ambil NIP dari sesi pengguna yang login
$nip = $_SESSION['nip'];

// Query untuk mengambil riwayat gaji karyawan berdasarkan NIP
$queryKar = "SELECT * FROM karyawan WHERE nip = '$nip'";
$resultKar = $conn->query($queryKar);

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Salary History</title>
    <link rel="stylesheet" type="text/css" href="css/style-view-gaji.css">
</head>
<body>
<div class="container">

    <?php
        $gaji = "Rp " . number_format($row['gaji_pokok'], 0, ',', '.');
        $tj = "Rp " . number_format($row['tunjangan_jabatan'], 0, ',', '.');
        $tl = "Rp " . number_format($row['tunjangan_lainnya'], 0, ',', '.');
        // $ket_tl = $row['ket_tl'];
        $tmk = "Rp " . number_format($row['tunjangan_masa_kerja'], 0, ',', '.');
        $denda = "Rp " . number_format($row['denda'], 0, ',', '.');
        // $ket_denda = $row['ket_denda'];
        $total = $row['gaji_pokok'] + $row['tunjangan_jabatan'] + $row['tunjangan_masa_kerja'] + $row['tunjangan_lainnya'] - $row['denda'];
        $totalFormatted = "Rp " . number_format($total, 0, ',', '.');
    ?>

    <div class="slip-gaji">
    <h2>Salary</h2>

    <table>
        <tr>
            <?php
                if ($resultKar->num_rows > 0) {
                    $rowKar = $resultKar->fetch_assoc();
                    $nama = $rowKar['nama'];
                    $nipKar = $rowKar['nip'];
            ?>
            <th>Name</th>
            <th>NIP</th>
            <th>Date</th>
        </tr>
        <tr>
            <td><?php echo $nama;?></td>
            <td><?php echo $nipKar; ?></td>
            <?php
                }
            ?>
            <td><?php echo $row['tanggal']; ?></td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <th>Salary</th>
            <td colspan="2"><?php echo $gaji; ?></td>
        </tr>
        <tr>
            <th>Allowance</th>
            <td colspan="2"><?php echo $tj; ?></td>
        </tr>
        <tr>
            <th>Service Length Allowance</th>
            <td colspan="2"><?php echo $tmk; ?></td>
        </tr>
        <tr>
            <th>Reimburse</th>
            <td colspan="2"><?php echo $tl; ?></td>
        </tr>
<!--         <tr>
            <th>Keterangan</th>
            <td><?php echo $ket_tl; ?></td>
        </tr> -->
        <tr>
            <th>Penalty</th>
            <td colspan="2"><?php echo $denda; ?></td>
        </tr>
<!--         <tr>
            <th>Keterangan</th>
            <td><?php echo $ket_denda; ?></td>
        </tr> -->
        <tr>
            <th>Total</th>
            <th colspan="2" style="background-color: yellow;"><?php echo $totalFormatted; ?></th>
        </tr>
        <tr>
            <td></td>
        </tr>
    </table>

    <a href="cetak.php" class="back">Print</a>
    <a href="riwayat-gaji.php" class="back">Back</a>

</div>
</div>
</body>
</html>

<?php
$conn->close();
?>
