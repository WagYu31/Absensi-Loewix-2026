<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

$id = $_GET['id_cashbon'];

$query = "SELECT cashbon.*, karyawan.nip, karyawan.nik, karyawan.nama, karyawan.jabatan
        FROM cashbon
        JOIN karyawan ON cashbon.nip = karyawan.nip
        WHERE cashbon.id_cashbon = '$id'";
$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

if ($result->num_rows === 0) {
    die("Employee data not found.");
}

$data = $result->fetch_assoc();

$que = "SELECT * FROM bayar_cashbon WHERE bayar_cashbon.id_cashbon = '$id'";
$result_que = $conn->query($que);

if(!$result_que){
    die("Query execution failed: " . $conn->error);
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
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />   
        <link rel="stylesheet" type="text/css" href="css/style-view-profile.css?rev=<?php echo time();?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">  
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script> 
		<style>
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

    <div class="content">
        <div class="header">
            <h2><?php echo $data['nama']?></h2>
            <h4><?php echo $data['jabatan']; ?></h4>
        </div>
        <div class="edit-profile no-print">
            <a href="#" onclick="cetak()">Print</a>
        </div>
        <table>
            <tr>
                <th colspan="2">Rincian Cashbon</th>
            </tr>
            <tr>
                <td width="30%">NIK</td>
                <td><?php echo $data['nik']; ?></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td><?php echo $data['nama']; ?></td>
            </tr>
            <tr>
                <td>Jumlah Cashbon</td>
                <td><?php echo "Rp " . number_format($data['jumlah'], 0, ',', '.');?></td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td><?php echo $data['keterangan'];?></td>
            </tr>
            <tr>
                <td>Tanggal Ambil</td>
                <td><?php echo  date('d-m-Y', strtotime($data['tanggal']));?></td>
            </tr>
            <tr>
                <th colspan="2">Cicilan</th>
            </tr>
            <tr>
                <td>Dicicil</td>
                <td><?php echo $data['cicil'];?> kali</td>
            </tr>
            <tr>
                <td>Cicilan</td>
                <td><?php
                $cicilan = $data['jumlah'] / $data['cicil'];
                echo "Rp " . number_format($cicilan, 0, ',', '.');?></td>
            </tr>
            <tr>
                <td>Tanggal Mulai Pembayaran</td>
                <td><?php echo  date('d-m-Y', strtotime($data['mulai']));?></td>
            </tr>
            <tr>
                <th colspan="2">Rincian Pembayaran</th>
            </tr>
            <?php
            if ($result_que->num_rows > 0) {
                while ($cb = $result_que->fetch_assoc()) {
                    ?>
                    <tr>
                        <td>Cicilan ke <?php echo $cb['cicilan'];?> : <?php echo date('d-m-Y', strtotime($cb['tanggal']));?></td>
                        <td><?php echo "Rp " . number_format($cb['bayar'], 0, ',', '.');?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            

            
        </table>
    </div>
    <div id="ktp-modal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="ktp-img">
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
    // Get the modal
    var modal = document.getElementById("ktp-modal");

    // Get the image and insert it inside the modal
    var img = document.getElementById("ktp-img");
    var linkElements = document.querySelectorAll(".view-ktp-link");

    linkElements.forEach(function(link) {
        link.addEventListener("click", function(event) {
            event.preventDefault();
            var imgSrc = link.getAttribute("data-img-src");
            img.src = imgSrc;
            modal.style.display = "block";
        });
    });

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks on <span> (x), close the modal
    span.addEventListener("click", function() {
        modal.style.display = "none";
    });

    // When the user clicks anywhere outside of the modal, close it
    window.addEventListener("click", function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
    
    function cetak() {
                window.print();
            }
</script>

</body>
</html>
