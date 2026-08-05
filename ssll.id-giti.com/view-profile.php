<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

$nip = $_GET['nip'];

$query = "SELECT * FROM karyawan WHERE karyawan.nip = '$nip'";
$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

if ($result->num_rows === 0) {
    die("Employee data not found.");
}

$karyawan = $result->fetch_assoc();
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style-view-profile.css">
    <link rel="stylesheet" type="text/css" href="css/foot.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>
    
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
									<a href="admin-profile.php"><i class="fa-solid fa-users" id="mn"></i>Profile</a>
									<ul class="gn-submenu">
										<li><a href="adm-riwayat-gaji.php"><i class="fa-solid fa-caret-right" id="mn2"></i>Slip Gaji</a></li>
									</ul>
								</li>
								<li><a href="data-karyawan.php"><i class="fa-solid fa-archive" id="mnn"></i>Data Karyawan</a></li>
								<li><a href="tunjangan-karyawan.php"><i class="fa-solid fa-hand-holding-dollar" id="mnnn"></i>Biaya Pengganti</a></li>
								<li><a href="denda-karyawan.php"><i class="fa-solid fa-receipt" id="mnnn"></i>Denda</a></li>

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
            <img src="uploads/<?php echo $karyawan['pas_photo']; ?>" alt="Pas Photo" width="120px">
            <h2><?php echo $karyawan['nama']?></h2>
            <h4><?php echo $karyawan['jabatan']; ?></h4>
        </div>
        <div class="edit-profile">
            <a href="adm-edit-profile-karyawan.php?nip=<?php echo $nip; ?>">Edit Profil</a>
        </div>
        <table>
            <tr>
                <th colspan="2">Profil</th>
            </tr>
            <tr>
                <td width="30%">NIK</td>
                <td><?php echo $karyawan['nik']; ?></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td><?php echo $karyawan['nama']; ?></td>
            </tr>
            <tr>
                <td>Tempat Lahir</td>
                <td><?php echo $karyawan['tempat_lahir']; ?></td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td>
                <td><?php echo date('d M Y', strtotime($karyawan['tanggal_lahir'])); ?></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td><?php echo $karyawan['alamat']; ?></td>
            </tr>
            <tr>
                <th colspan="2">Kontak</th>
            </tr>
            <tr>
                <td>Nomor Handphone</td>
                <td>
                            <?php
                            $nomorHandphone = $karyawan['nomor_handphone'];
                            
                            // Cek apakah nomor handphone dimulai dengan angka 0
                            if (substr($nomorHandphone, 0, 1) === '0') {
                                // Ganti angka 0 dengan 62
                                $nomorHandphone = '62' . substr($nomorHandphone, 1);
                            }
                            ?>
                            <a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank">
                                <?php echo $karyawan['nomor_handphone']; ?>
                            </a>
                        </td>
            </tr>
            <tr>
                <td>Nomor Telepon</td>
                <td><?php echo $karyawan['nomor_telepon']; ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><a href="mailto:<?php echo $karyawan['email']; ?>?subject=Subject%20Here&to=<?php echo $karyawan['email']; ?>">
                        <?php echo $karyawan['email']; ?>
                    </a></td>
            </tr>
            <tr>
                <th colspan="2">Informasi Tambahan</th>
            </tr>
            <tr>
                <td>Gaji Pokok</td>
                <td>Rp *******</td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td>Rp ******</td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td><?php echo date('d M Y', strtotime($karyawan['tanggal_masuk'])); ?></td>
            </tr>
            <tr>
                <td>Nomor KTP</td>
                <td><?php echo $karyawan['nomor_ktp']; ?></td>
            </tr>
            <tr>
                <td>Gambar KTP</td>
                <td><img src="uploads/<?php echo $karyawan['gambar_ktp']; ?>" width="200px" alt="KTP"></td>
            </tr>
            <tr>
                <th colspan="2">Akun Bank</th>
            </tr>
            <tr>
                <td>Nama Bank</td>
                <td><?php
                $namaBank = $karyawan['nama_bank']; 
                include 'get-nama-bank.php';
                echo $nmbank; ?></td>
            </tr>
            <tr>
                <td>Nomor Rekening</td>
                <td><?php echo $karyawan['nomor_rekening']; ?></td>
            </tr>
            <tr>
                <td>Atas Nama</td>
                <td><?php echo $karyawan['nama_pemilik_rekening']; ?></td>
            </tr>
        </table>
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
</body>
</html>
