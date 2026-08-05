<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

// Memeriksa apakah parameter NIP untuk melihat profil telah diterima
if (isset($_GET['nip'])) {
    $nip = $_GET['nip'];

    // Get the selected bulan and tahun from URL parameters
    $bulan = $_GET['bulan'];
    $tahun = $_GET['tahun'];

    // Query to get employee details based on the NIP and the selected bulan and tahun
    $query = "SELECT karyawan.*, 
                    (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = $bulan AND YEAR(tunjangan_lainnya.tanggal) = $tahun) AS total_tunjangan_lainnya,
                    (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = $bulan AND YEAR(tunjangan_lainnya.tanggal) = $tahun AND tunjangan_lainnya.ket1 = 'ganti') AS total_tunjangan_lainnya_ganti,
                    (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = $bulan AND YEAR(tunjangan_lainnya.tanggal) = $tahun AND tunjangan_lainnya.ket1 = 'bonus') AS total_tunjangan_lainnya_bonus,
                    (SELECT SUM(jumlah) FROM denda WHERE denda.nip = karyawan.nip AND MONTH(denda.tanggal) = $bulan AND YEAR(denda.tanggal) = $tahun) AS total_denda,
                    -- (SELECT SUM(jumlah) FROM denda WHERE denda.nip = karyawan.nip AND MONTH(denda.tanggal) = $bulan AND YEAR(denda.tanggal) = $tahun AND denda.ket1 = 'Denda') AS total_denda2,
                    (SELECT SUM(bayar) FROM bayar_cashbon WHERE bayar_cashbon.nip = karyawan.nip AND MONTH(bayar_cashbon.tanggal) = $bulan AND YEAR(bayar_cashbon.tanggal) = $tahun) AS total_cashbon
            FROM karyawan
            WHERE karyawan.nip = '$nip'";

    $result = $conn->query($query);
    if (!$result) {
        die("Query execution failed: " . $conn->error);
    }

    $query2 = "SELECT tunjangan_lainnya.*, karyawan.nip FROM karyawan
                JOIN tunjangan_lainnya ON tunjangan_lainnya.nip = karyawan.nip
                WHERE karyawan.nip = '$nip' AND MONTH(tunjangan_lainnya.tanggal) = $bulan AND YEAR(tunjangan_lainnya.tanggal) = $tahun";
    
    $result2 = $conn->query($query2);
    if (!$result2) {
        die("Query execution failed: " . $conn->error);
    }

    include 'get-query-4.php';
    include 'get-query-5.php';
    include 'get-query-6.php';
          
    $query3 = "SELECT denda.*, karyawan.nip FROM karyawan
                JOIN denda ON denda.nip = karyawan.nip
                WHERE karyawan.nip = '$nip' AND MONTH(denda.tanggal) = $bulan AND YEAR(denda.tanggal) = $tahun";
    
    $result3 = $conn->query($query3);
    if (!$result3) {
        die("Query execution failed: " . $conn->error);
    }

    $employee = $result->fetch_assoc();
    include 'sa-get-tunjangan-masa-kerja.php';
    $tunjangan_masa_kerja = $dataTMK['tunjangan_masa_kerja'];

    $totalGaji = $employee['gaji_pokok'] + $employee['tunjangan'] + $tunjangan_masa_kerja + $employee['total_tunjangan_lainnya_ganti'] + $employee['total_tunjangan_lainnya_bonus'] - $employee['total_denda'] - $employee['total_cashbon'];
    $nik = $employee['nik'];
}
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
        <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" type="text/css" href="css/style-sa-slip-gaji.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>
</head>
<body>
<div class="container no-print">
        <ul id="gn-menu" class="gn-menu-main">
            <li class="gn-trigger">
                <a class="gn-icon gn-icon-menu"><span>Menu</span></a>
                <nav class="gn-menu-wrapper">
                    <div class="gn-scroller">
                        <ul class="gn-menu">
                            <!-- <li class="gn-search-item">
									<input placeholder="Search" type="search" class="gn-search">
									<a class="gn-icon gn-icon-search"><span>Search</span></a>
								</li> -->
                            <li>
                                <a href="sa-data-karyawan.php"><i class="fa-solid fa-users" id="mn"></i>Data Karyawan</a>
                                <ul class="gn-submenu">
                                    <li><a href="sa-tambah-data.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Akun Karyawan</a></li>
                                    <!-- <li><a class="gn-icon gn-icon-photoshop">Photoshop files</a></li> -->
                                </ul>
                            </li>
                            <!-- <li><a class="gn-icon gn-icon-cog">Settings</a></li> -->
                            <li><a href="sa-bonus.php"><i class="fa-solid fa-trophy" id="mnn"></i>Bonus</a></li>
                            <li><a href="denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a></li>
                            <li><a href="sa-cashbon.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Cashbon</a></li>
                            <li><a href="tunjangan-karyawan.php"><i class="fa-solid fa-file-invoice" id="mnnn"></i>Biaya Pengganti</a></li>
                            <!-- <li><a class="gn-icon gn-icon-help" href="sa-cashbon.php">Cashbon</a></li> -->
                            <li>
                                <a class="gn-icon gn-icon-archive">Laporan</a>
                                <ul class="gn-submenu">
                                    <li><a href="laporan-gaji.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Gaji</a></li>
                                    <!-- <li><a href="laporan-gaji-mingguan.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Gaji Mingguan</a></li> -->
                                    <li><a href="laporan-cashbon.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Cashbon</a></li>
                                    <li><a href="laporan-denda.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Denda</a></li>
                                    <li><a href="laporan-pengganti.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Biaya Pengganti</a></li>
                                </ul>
                            </li>

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
        <div class="row">
            <div class="line no-print">
                <a href="laporan-gaji.php" class="back">Back</a>
                <a href="javascript:window.print();" class="print-button"><i class="fas fa-print"></i>Print</a>
            </div>
            <!-- Display the employee details in a slip-like format -->
            <div class="slip-gaji">
                <h2>Slip Gaji</h2>
                <h5>Periode : <?php echo $bulan . "/"; echo $tahun ?></h4>
                <table>
                    <tr>
                        <th style="text-align:left;">Nama</th>
                        <td colspan="2"><?php echo $employee['nama']; ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">NIK</th>
                        <td colspan="2"><?php echo $employee['nik']; ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Jabatan</th>
                        <td colspan="2"><?php echo $employee['jabatan']; ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Gaji Pokok</th>
                        <td colspan="2"><?php echo "Rp " . number_format($employee['gaji_pokok'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php
                        if($employee['jenis_gaji'] == 'mingguan'){
                            $gaji1 = $employee['gaji_1'];
                    ?>
                    <tr>
                        <th style="text-align:left;">Gaji Sudah Dibayar</th>
                        <td colspan="2"><?php echo "Rp " . number_format($gaji1, 0, ',', '.'); ?></td>
                    </tr>
                    <?php
                        }
                        else{
                            $gaji1 = 0;
                        } 
                    ?>
                    <tr>
                        <th style="text-align:left;">Tunjangan Jabatan</th>
                        <td colspan="2"><?php echo "Rp " . number_format($employee['tunjangan'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Tunjangan Masa Kerja</th>
                        <td colspan="2"><?php echo "Rp " . number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th colspan="3" style="background-color:#ffff66;">Bonus</th>
                    </tr>
                        <?php
                         include 'get-bonus.php';
                        ?>
                    <tr>
                        <th colspan="3"><?php echo "TOTAL : Rp " . number_format($employee['total_tunjangan_lainnya_bonus'], 0, ',', '.'); ?></th></tr>
                    </tr>

                    <tr>
                        <th colspan="3" style="background-color:#ffff66;">Bayar Cashbon</th>
                    </tr>
                    <tr>
                        <?php
                         include 'get-cb.php';
                        ?>
                    <tr>
                        <th colspan="3"><?php echo "TOTAL : Rp " . number_format($employee['total_cashbon'], 0, ',', '.'); ?></th></tr>
                    </tr>

                    <tr>
                        <th colspan="3" style="background-color:#ffff66;">Biaya Pengganti</th>
                        <?php
                         include 'get-ganti.php';
                        ?>
                    <tr>
                        <th colspan="3"><?php echo "TOTAL : Rp " . number_format($employee['total_tunjangan_lainnya_ganti'], 0, ',', '.'); ?></th></tr>
                    </tr>
                    <tr>
                        <th colspan="3" style="background-color:#ffff66;">Denda</th>
                        <?php
                         include 'get-denda-3.php';
                        ?>
                    <tr>
                        <th>Total</th>
                        <th colspan="2"><?php echo "Rp " . number_format($employee['total_denda'], 0, ',', '.'); ?></th></tr>
                    </tr>
                    <tr>
                        <th style="text-align:center; background-color:#0073e6; color:white">Total Gaji</th>
                        <td colspan="3"><?php echo "Rp " . number_format($totalGaji-$gaji1, 0, ',', '.'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/classie.js"></script>
<script src="js/gnmenu.js"></script>
<script>
	new gnMenu( document.getElementById( 'gn-menu' ) );
</script>

    <!-- Include necessary JavaScript code (similar to laporan-gaji.php) -->
    <!-- ... Add your JavaScript code here ... -->

</body>
</html>
