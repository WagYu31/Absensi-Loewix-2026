<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

$nip = $_GET['nip'];

$query = "SELECT * FROM karyawan WHERE nip = '$nip'";
$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

if ($result->num_rows === 0) {
    die("Employee data not found.");
}

$data = $result->fetch_assoc();
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
            <img src="uploads/<?php echo $data['pas_photo']; ?>" alt="Pas Photo" width="120px">
            <h2><?php echo $data['nama']?></h2>
            <h4><?php echo $data['jabatan']; ?></h4>
        </div>
        <div class="edit-profile">
            <a href="sa-edit-profile.php?nip=<?php echo $nip; ?>">Edit Profil</a>
        </div>
        <table>
            <tr>
                <th colspan="2">Profil</th>
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
                <td>Nomor KTP</td>
                <td>
                    <a href="#" class="view-ktp-link" data-img-src="uploads/<?php echo $data['gambar_ktp']; ?>">
                        <?php echo $data['nomor_ktp']; ?>
                    </a>
                </td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td><?php echo $data['alamat']; ?></td>
            </tr>
            <tr>
                <th colspan="2">Kontak</th>
            </tr>
            <tr>
                <td>Nomor Handphone</td>
                <td>                            
                <?php
                    $nomorHandphone = $data['nomor_handphone'];
                            
                    // Cek apakah nomor handphone dimulai dengan angka 0
                    if (substr($nomorHandphone, 0, 1) === '0') {
                        // Ganti angka 0 dengan 62
                        $nomorHandphone = '62' . substr($nomorHandphone, 1);
                    }
                    ?>
                    <a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank">
                    <?php echo $data['nomor_handphone']; ?>
                </td>
            </tr>
            <tr>
                <td>Nomor Telepon</td>
                <td><?php echo $data['nomor_telepon']; ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td>
                    <a href="mailto:<?php echo $data['email']; ?>?subject=Subject%20Here&to=<?php echo $data['email']; ?>">
                        <?php echo $data['email']; ?>
                    </a>
                </td>

            </tr>
            <tr>
                <th colspan="2">Informasi Tambahan</th>
            </tr>
            <tr>
                <td>Gaji Pokok</td>
                <td><?php echo "Rp " . number_format($data['gaji_pokok'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Pembayaran</td>
                <td style="text-transform:capitalize;"><?php 
                $jenis = $data['jenis_gaji'];
                if($jenis === "mingguan"){
                    echo $jenis . " - Gaji 1 Rp " . number_format($data['gaji_1'], 0, ',', '.');
                }
                else{
                    echo $jenis;
                }
                ?></td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td><?php echo "Rp " . number_format($data['tunjangan'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td><?php echo date('d-m-Y', strtotime($data['tanggal_masuk'])); ?></td>
            </tr>
            <tr>
                <td>Tunjangan Masa Kerja</td>
                <td><?php 
                include 'get-tunjangan-masa-kerja.php';
                echo "Rp " . number_format($dataTMK['tunjangan_masa_kerja'], 0, ',', '.'); ?></td>
            </tr>

            <tr>
                <th colspan="2">Akun Bank</th>
            </tr>
            <tr>
                <td>Nama Bank</td>
                <td><?php
                $namaBank = $data['nama_bank']; 
                include 'get-nama-bank.php';
                echo $nmbank; ?></td>
            </tr>
            <tr>
                <td>Nomor Rekening</td>
                <td><?php echo $data['nomor_rekening']; ?></td>
            </tr>
            <tr>
                <td>Atas Nama</td>
                <td><?php echo $data['nama_pemilik_rekening']; ?></td>
            </tr>
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
</script>

</body>
</html>
