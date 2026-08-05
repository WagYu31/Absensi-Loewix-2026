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
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
		<title>Grav-Tech Salary</title>
		<meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
		<meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech" />
		<meta name="author" content="Irviani" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js" defer></script>
    <script type="text/javascript" src="tableExport.js"></script>
    <script type="text/javascript" src="jquery.base64.js"></script>
    <script type="text/javascript" src="html2canvas.js"></script>
    <script type="text/javascript" src="jspdf/libs/sprintf.js"></script>
    <script type="text/javascript" src="jspdf/jspdf.js"></script>
    <script type="text/javascript" src="jspdf/libs/base64.js"></script>
    <script type="text/javascript" src="js/script-download-cb.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/style-laporan-cashbon.css" />
    <link rel="stylesheet" type="text/css" href="css/foot.css">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>

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
    // $query = "SELECT bayar_cashbon.*, cashbon.nip, karyawan.nama, karyawan.nik
    //         FROM bayar_cashbon 
    //         JOIN karyawan ON karyawan.nip = cashbon.nip
    //         JOIN bayar_cashbon ON cashbon.id_cashbon = bayar_cashbon.id_cashbon";

    $query = "SELECT cashbon.*, bayar_cashbon.id_cashbon, bayar_cashbon.bayar, bayar_cashbon.tanggal AS tgl, bayar_cashbon.cicilan, karyawan.nip, karyawan.nama, karyawan.nik
            FROM cashbon
            JOIN karyawan ON karyawan.nip = cashbon.nip
            JOIN bayar_cashbon ON cashbon.id_cashbon = bayar_cashbon.id_cashbon";

    // Tambahkan kondisi filter berdasarkan bulan dan tahun jika telah dipilih
    if (!empty($bulan) && !empty($tahun)) {
        $query .= " WHERE MONTH(bayar_cashbon.tanggal) = '$bulan' AND YEAR(bayar_cashbon.tanggal) = '$tahun'";
    }

    $result = $conn->query($query);

    if (!$result) {
        die("Query execution failed: " . $conn->error);
    }
    
    
    ?>
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
                                    <li><a href="tambah-data-karyawan.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Tambah Data Karyawan</a></li>
                                    <li><a href="sa-tambah-data.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Akun Karyawan</a></li>
                                    <!-- <li><a class="gn-icon gn-icon-photoshop">Photoshop files</a></li> -->
                                </ul>
                            </li>
                            <!-- <li><a class="gn-icon gn-icon-cog">Settings</a></li> -->
                            <li><a href="sa-bonus.php"><i class="fa-solid fa-trophy" id="mnn"></i>Bonus</a></li>
                            
                            <li>
                                <a href="denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a>
                                <ul class="gn-submenu">
                                    <li><a href="absensi/add-shifting.php"><i class="fa-solid fa-caret-right" id="mn2"></i>1. Data Shifting</a></li>
                                    <li><a href="absensi/index.php"><i class="fa-solid fa-caret-right" id="mn2"></i>2. Upload Absensi</a></li>
                                    <li><a href="absensi/req_shift.php"><i class="fa-solid fa-caret-right" id="mn2"></i>3. Request Shifting</a></li>
                                    <li><a href="absensi/data-absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>4. Validasi Data Absen</a></li>
                                    <!--<li><a href="absensi/req_jam_kerja.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Request Jam Kerja</a></li>-->
                                </ul>
                            </li>
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
        <label for="tahun" class="tahun">Tahun :</label>
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
                    <ul class="dropdown-menu" id="pr" aria-labelledby="dropdownMenu1">
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
                    <th colspan="5" class="judul">Laporan Pembayaran Cashbon Bulan <?php echo $bulan . "/" . $tahun; ?></th>
                </tr>
                <tr>
                    <th width="3%" class="hide-m">NIK</th>
                    <th width="8%">Nama</th>
                    <th width="11%">Tanggal</th>
                    <th width="11%">Dibayar</th>
                    <th width="11%">Cicilan Ke-</th>
                </tr>
                <?php
                $sum = 0;
                $totalSUM = 0;
                while ($data = $result->fetch_assoc()) : 
                $totalCB = $data['bayar'];
                $sum += (int)$totalCB;

                $totalSUM = "Rp " . number_format($sum, 0, ',', '.');
                ?>
                <tr>
                    <td class="hide-m" style="text-align:center;"><?php echo $data['nik']; ?></td>
                    <td><?php echo $data['nama']; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($data['tgl'])); ?></td>
                    <td><?php echo "Rp " . number_format($data['bayar'], 0, ',', '.'); ?></td>
                    <td><?php echo $data['cicilan']; ?></td>
                </tr>
            <?php endwhile; ?>
                <tr>
                    <td style="font-weight:bold;">TOTAL</td>
                    <td class="hide-m"></td>
                    <td></td>
                    <td class="total" style="text-align:center;">
                    <?php echo $totalSUM; ?></td>
                    <td class="hide-m"></td>
                </tr>
        </table>
    </div>
</div><div class="footer">
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
        window.location.href = "laporan-cashbon.php?bulan=" + bulan + "&tahun=" + tahun;
    }
    </script>
</body>
</html>
