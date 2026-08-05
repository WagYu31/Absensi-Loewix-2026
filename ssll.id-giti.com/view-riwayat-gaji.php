<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip'])) {
    // Jika tidak ada sesi pengguna, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

// Ambil data karyawan dari database
include 'conn.php';

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

// Ambil bulan dan tahun dari tanggal pada rincian gaji
$tanggalRincianGaji = $row['tanggal'];
$bulanRincianGaji = date('m', strtotime($tanggalRincianGaji));
$tahunRincianGaji = date('Y', strtotime($tanggalRincianGaji));

// Query untuk mengambil tunjangan lainnya pada bulan dan tahun yang sama
$queryTunjangan = "SELECT tanggal, jumlah, keterangan FROM tunjangan_lainnya WHERE nip = '$nip' AND MONTH(tanggal) = '$bulanRincianGaji' AND YEAR(tanggal) = '$tahunRincianGaji'";
$resultTunjangan = $conn->query($queryTunjangan);

// Query untuk mengambil denda pada bulan dan tahun yang sama
$queryDenda = "SELECT tanggal, jumlah, keterangan FROM denda WHERE nip = '$nip' AND MONTH(tanggal) = '$bulanRincianGaji' AND YEAR(tanggal) = '$tahunRincianGaji'";
$resultDenda = $conn->query($queryDenda);

?>

<!DOCTYPE html>
<html>
<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
		<title>Grav-Tech Salary</title>
		<meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
		<meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech" />
		<meta name="author" content="Irviani" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="css/style-view-gaji.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>
    <style>
        /* Tambahkan CSS untuk tombol cetak */
        .slip-gaji .print-button {
            display: inline-block;
            text-align: center;
            margin-top: 20px;
            padding: 10px 20px;
            text-decoration: none;
            background-color: #4CAF50;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            width: 20%;
            transition: background-color 0.3s;
        }

        .slip-gaji .print-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="container">
			<ul id="gn-menu" class="gn-menu-main">
				<li class="gn-trigger">
					<a class="gn-icon gn-icon-menu"><span>Menu</span></a>
					<nav class="gn-menu-wrapper">
						<div class="gn-scroller">
							<ul class="gn-menu">
								<li>
									<a href="profile-karyawan.php"><i class="fa-solid fa-users" id="mn"></i>Profile</a>
								</li>
								<li><a href="view-riwayat-gaji.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Slip Gaji</a></li>

							</ul>
						</div><!-- /gn-scroller -->
					</nav>
				</li>
				<li><a class="codrops-icon" href="laporan-gaji.php"><i class="fa-solid fa-g" id="gt"></i><span>ravitti Technology</span></a></li>
				<li>
                    <?php
                    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
                    if ($referer) {
                        echo '<a class="codrops-icon codrops-icon-prev" href="' . $referer . '"><span>Previous</span></a>';
                    } else {
                        echo '<a class="codrops-icon codrops-icon-prev disabled" href="#"><span>Previous</span></a>';
                    }
                    ?>
                </li>
				<li><a class="codrops-icon" href="logout.php"><i class="fa-solid fa-right-to-bracket" id="mnn"></i><span>Log Out</span></a></li>
				<!-- <li><a class="codrops-icon codrops-icon-drop" href="http://tympanus.net/codrops/?p=16030"><span>Back to the Codrops Article</span></a></li> -->
			</ul>
		</div><!-- /container -->
<div class="containt">
    <div class="slip-gaji">
    <h2>Slip Gaji</h2>

    <table>
        <tr>
            <?php
                if ($resultKar->num_rows > 0) {
                    $rowKar = $resultKar->fetch_assoc();
                    $nama = $rowKar['nama'];
                    $nipKar = $rowKar['nip'];
            ?>
            <th>Nama</th>
            <th>NIP</th>
            <th>Tanggal</th>
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
            <th>Gaji Pokok</th>
            <td colspan="2"><?php echo $gaji; ?></td>
        </tr>
        <tr>
            <th>Tunjangan Jabatan</th>
            <td colspan="2"><?php echo $tj; ?></td>
        </tr>
        <tr>
            <th>Tunjangan Masa Kerja</th>
            <td colspan="2"><?php echo $tmk; ?></td>
        </tr>
        <tr>
            <th>Pembayaran Pengganti</th>
            <td colspan="2"><?php echo $tl; ?></td>
        </tr>
        <?php
            // Tampilkan data tunjangan lainnya
            if ($resultTunjangan && $resultTunjangan->num_rows > 0) {
                while ($rowTunjangan = $resultTunjangan->fetch_assoc()) {
                    $tanggalTunjangan = $rowTunjangan['tanggal'];
                    $jumlahTunjangan = "Rp " . number_format($rowTunjangan['jumlah'], 0, ',', '.');
                    $keteranganTunjangan = $rowTunjangan['keterangan'];
        ?>
        <tr>
            <th>Keterangan</th>
            <td>
                    <?php echo $tanggalTunjangan; ?><br>
                    <b><?php echo $keteranganTunjangan; ?></b> - <?php echo $jumlahTunjangan; ?>
            </td>
        </tr>
        <?php
                }
            }
        ?>
        <tr>
            <th>Denda / Cashbon</th>
            <td colspan="2"><?php echo $denda; ?></td>
        </tr>
        <?php
            // Tampilkan data denda
            if ($resultDenda && $resultDenda->num_rows > 0) {
                while ($rowDenda = $resultDenda->fetch_assoc()) {
                    $tanggalDenda = $rowDenda['tanggal'];
                    $jumlahDenda = "Rp " . number_format($rowDenda['jumlah'], 0, ',', '.');
                    $keteranganDenda = $rowDenda['keterangan'];
                }
            }
        ?>
        <tr>
            <th>Keterangan</th>
            <td>
                    <?php echo $tanggalDenda; ?><br>
                    <b><?php echo $keteranganDenda; ?></b> - <?php echo $jumlahDenda; ?>
            </td>
        </tr>
        <tr>
            <th>Total</th>
            <th colspan="2" style="background-color: yellow;"><?php echo $totalFormatted; ?></th>
        </tr>
        <tr>
            <td></td>
        </tr>
    </table>

    <!-- Tambahkan tombol cetak -->
    <a href="javascript:window.print();" class="print-button">Print</a>
    
    <a href="riwayat-gaji.php" class="back">Kembali</a>

</div>
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/classie.js"></script>
<script src="js/gnmenu.js"></script>
<script>
	new gnMenu( document.getElementById( 'gn-menu' ) );
</script>
</body>
</html>

<?php
$conn->close();
?>
