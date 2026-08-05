<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai admin
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin atau superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

include 'conn.php';

$role = $_SESSION['role'];
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
  <link rel="stylesheet" type="text/css" href="css/style-tambah-data-karyawan.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>
</head>
<body>
    <?php
    if($role === 'admin'){
        ?>
        <div class="container no-print">
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

        <?php
    }

    else if($role === 'superadmin'){
        ?>
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
    <?php
    }
    ?>

  <form action="proses-tambah-data-karyawan.php" method="POST" enctype="multipart/form-data" class="containt">
  <h2>Tambah Karyawan</h2>

    <label for="nik">NIK (Nomor Induk Karyawan) :</label>
    <input type="text" id="nik" name="nik" required>
    
    <label for="nik">PIN ABSEN (Cek Dari Mesin Absen) :</label>
    <input type="text" id="pin" name="pin" required>

    <label for="nama">Nama :</label>
    <input type="text" id="nama" name="nama" required>

    <label for="tempatLahir">Tempat Lahir :</label>
    <input type="text" id="tempatLahir" name="tempat_lahir">

    <label for="tanggalLahir">Tanggal Lahir :</label>
    <input type="date" id="tanggalLahir" name="tanggal_lahir">

    <label for="alamat">Alamat :</label>
    <textarea id="alamat" name="alamat"></textarea>

    <label for="nomorHP">Nomor Handphone :</label>
    <input type="text" id="nomorHP" name="nomor_handphone">

    <label for="nomorTelepon">Nomor Telepon :</label>
    <input type="text" id="nomorTelepon" name="nomor_telepon">

    <label for="email">Email :</label>
    <input type="email" id="email" name="email">

    <label for="nomorKTP">Nomor KTP :</label>
    <input type="text" id="nomorKTP" name="nomor_ktp">

    <label for="tanggalMasuk">Tanggal Masuk :</label>
    <input type="date" id="tanggalMasuk" name="tanggal_masuk">

    <label for="namaBank">Nama Bank :</label>
    <select id="namaBank" name="nama_bank">
        <option value="bca">Bank Central Asia (BCA)</option>
        <option value="mandiri">Bank Mandiri</option>
        <option value="bri">Bank Rakyat Indonesia (BRI)</option>
        <option value="bni">Bank Negara Indonesia (BNI)</option>
        <option value="btn">Bank Tabungan Negara (BTN)</option>
        <option value="cimb">CIMB Niaga</option>
        <option value="bsi">Bank Syariah Indonesia (BSI)</option>
        <option value="ocbc">OCBC NISP</option>
        <option value="panin">Bank Panin</option>
        <option value="danamon">Bank Danamon</option>
        <option value="bcablue">Blu by BCA</option>
        <option value="gopay">Gopay</option>
        <option value="ovo">OVO</option>
        <option value="link">Link Aja</option>
        <option value="dana">Dana</option>
        <!-- Add more bank options here -->
    </select>

    <label for="nomorRekening">Nomor Rekening :</label>
    <input type="text" id="nomorRekening" name="nomor_rekening">

    <label for="namaPemilikRekening">Atas Nama :</label>
    <input type="text" id="namaPemilikRekening" name="nama_pemilik_rekening">

    <label for="idJabatan">Jabatan :</label>
    <input type="text" id="idJabatan" name="id_jabatan">

    <label for="gambarKTP">Gambar KTP :</label>
    <input type="file" id="gambarKTP" name="gambar_ktp" accept="image/*">

    <input type="submit" value="Tambah Data">
  </form>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/classie.js"></script>
<script src="js/gnmenu.js"></script>
<script>
	new gnMenu( document.getElementById( 'gn-menu' ) );
</script>
</body>
</html>
