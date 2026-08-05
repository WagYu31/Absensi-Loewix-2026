<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip'])) {
    // Jika tidak ada sesi pengguna, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

// Memeriksa apakah parameter NIP untuk melihat profil telah diterima
if (isset($_SESSION['nip'])) {
    $nip = $_SESSION['nip'];

    // Get the selected bulan and tahun from URL parameters
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    // Jika form belum disubmit, gunakan bulan dan tahun saat ini
    $bulan = date('m');
    $tahun = date('Y');
}

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
    
    $query9 = "SELECT rincian_gaji.*, karyawan.nip FROM karyawan
                JOIN rincian_gaji ON rincian_gaji.nip = karyawan.nip
                WHERE karyawan.nip = '$nip' AND MONTH(rincian_gaji.tanggal) = $bulan AND YEAR(rincian_gaji.tanggal) = $tahun";
    
    $result9 = $conn->query($query9);
    if (!$result9) {
        die("Query execution failed: " . $conn->error);
    }
    
    $emp = mysqli_fetch_assoc($result9);
    $gajiThis = $emp['gaji'];
    $gajiIt = 0;
    if($gajiThis == 0){
        $gajiIt = $employee['gaji_pokok'];
    }
    else{
        $gajiIt = $emp['gaji'];
    }

    $totalGaji = $gajiIt + $employee['tunjangan'] + $tunjangan_masa_kerja + $employee['total_tunjangan_lainnya_ganti'] + $employee['total_tunjangan_lainnya_bonus'] - $employee['total_denda'] - $employee['total_cashbon'];
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="css/style-riwayat-gaji.css?rev=<?php echo time();?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">
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
								<li>
									<a href="profile-karyawan.php"><i class="fa-solid fa-users" id="mn"></i>Profile</a>
								</li>
								<li>
									<a href="absensi/detail-absen-kar.php?nik=<?php echo $nik;?>"><i class="fa-solid fa-thumbs-up" id="mnn"></i>Data Absen</a>
								</li>
								<li><a href="view-riwayat-gaji.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Slip Gaji</a></li>
								<!--<li><a href="detail-absen.php"><i class="fa-solid fa-receipt" id="mnn"></i>Rincian Absensi</a></li>-->

							</ul>
						</div><!-- /gn-scroller -->
					</nav>
				</li>
				<li><a class="codrops-icon" href="#"><i class="fa-solid fa-g" id="gt"></i><span>ravitti Technology</span></a></li>
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
                <label class="tahun" for="tahun">Tahun :</label>
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
            <div class="line no-print">
                <a href="javascript:window.print();" class="print-button"><i class="fas fa-print"></i>Print</a>
            </div>
            <!-- Display the employee details in a slip-like format -->
            <div class="slip-gaji">
                <h2>Slip Gaji</h2>
                <?php
                $blnNama = array(
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
                
                $namaBulan = $blnNama[$bulan];
                ?>
                <h5>Periode : <?php echo $namaBulan . " "; echo $tahun ?></h4>
                <table>
                    <tr>
                        <th style="text-align:left">Nama</th>
                        <td colspan="2"><?php echo $employee['nama']; ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left">NIP</th>
                        <td colspan="2"><?php echo $employee['nip']; ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left">Jabatan</th>
                        <td colspan="2"><?php echo $employee['jabatan']; ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left">Gaji Pokok</th>
                        <td colspan="2"><?php echo "Rp " . number_format($gajiIt, 0, ',', '.'); ?></td>
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
                        <th style="text-align:left">Tunjangan Jabatan</th>
                        <td colspan="2"><?php echo "Rp " . number_format($employee['tunjangan'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left">Tunjangan Masa Kerja</th>
                        <td colspan="2"><?php echo "Rp " . number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Bonus</th>
                        <?php
                         include 'get-bonus.php';
                        ?>
                        <td colspan="2" style="text-align:left;"><?php echo "Rp " . number_format($employee['total_tunjangan_lainnya_bonus'], 0, ',', '.'); ?></td>
                    </tr>

                    <tr>
                        <th style="text-align:left;">Bayar Cashbon</th>
                        <td colspan="2" style="text-align:left;"><?php echo "Rp " . number_format($employee['total_cashbon'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left">Rincian Bayar Cashbon</th>
                        <?php
                         include 'get-cb.php';
                        ?>
                    </tr>

                    <tr>
                        <th style="text-align:left;">Biaya Pengganti</th>
                        <?php
                         include 'get-ganti.php';
                        ?>
                        <td colspan="2" style="text-align:left;"><?php echo "Rp " . number_format($employee['total_tunjangan_lainnya_ganti'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Denda</th>
                        <td colspan="2" style="text-align:left;"><?php echo "Rp " . number_format($employee['total_denda'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left">Rincian Denda</th>
                        <?php
                         include 'get-denda-2.php';
                        ?>
                    </tr>
                    <tr>
                        <th style="background-color:#0073e6; color:white">Total Gaji</th>
                        <td colspan="2" style="text-align:left"><?php echo "Rp " . number_format($totalGaji-$gaji1, 0, ',', '.'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
</div>
<div class="footer">
    Copyrights © Gravitti Technology 2023<br>All Rights Reserved
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/classie.js"></script>
<script src="js/gnmenu.js"></script>
<script>
	new gnMenu( document.getElementById( 'gn-menu' ) );
</script>
<script>
    
        function tampilkanData() {
            var bulan = document.getElementById("bulan").value;
            var tahun = document.getElementById("tahun").value;
            window.location.href = "riwayat-gaji.php?bulan=" + bulan + "&tahun=" + tahun;
        }
</script>
</body>
</html>

<?php
$conn->close();
?>
