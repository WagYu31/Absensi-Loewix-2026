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

$karyawan = $result->fetch_assoc();

// Check if employee data exists
if (!$karyawan) {
    die("Employee data not found.");
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $updatedNama = $_POST['nama'];
    $updatedNik = $_POST['nik'];
    $updatedJabatan = $_POST['jabatan'];
    $updatedGaji = $_POST['gaji_pokok'];
    $updatedTunjangan = $_POST['tunjangan'];
    $updatedTempatLahir = $_POST['tempat_lahir'];
    $updatedTanggalLahir = $_POST['tanggal_lahir'];
    $updatedAlamat = $_POST['alamat'];
    $updatedNomorHandphone = $_POST['nomor_handphone'];
    $updatedNomorTelepon = $_POST['nomor_telepon'];
    $updatedEmail = $_POST['email'];
    $updatedTanggalMasuk = $_POST['tanggal_masuk'];
    $updatedJenisGaji = $_POST['jenis_gaji'];
    $updatedMinggu = $_POST['minggu'];
    $updatedNomorKTP = $_POST['nomor_ktp'];
    $updatedNamaBank = $_POST['nama_bank'];
    $updatedNomorRekening = $_POST['nomor_rekening'];
    $updatedNamaPemilikRekening = $_POST['nama_pemilik_rekening'];
// Cek apakah gambar KTP diunggah
    if (isset($_FILES['gambar_ktp']) && $_FILES['gambar_ktp']['error'] === UPLOAD_ERR_OK) {
        $gambar_ktp = $_FILES['gambar_ktp']['name'];
        $tmp_name = $_FILES['gambar_ktp']['tmp_name'];
    
        // Tentukan lokasi folder penyimpanan gambar KTP
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($gambar_ktp);
    
        // Pindahkan gambar ke folder penyimpanan
        if (move_uploaded_file($tmp_name, $target_file)) {
            // Gambar berhasil diunggah, lakukan penyimpanan data profil karyawan ke database
            $query = "UPDATE karyawan SET nama='$updatedNama', nik='$updatedNik', jabatan='$updatedJabatan', gaji_pokok='$updatedGaji', jenis_gaji='$updatedJenisGaji', minggu='$updatedMinggu', tunjangan='$updatedTunjangan', tempat_lahir='$updatedTempatLahir', tanggal_lahir='$updatedTanggalLahir', alamat='$updatedAlamat', tanggal_masuk='$updatedTanggalMasuk', email='$updatedEmail', nomor_handphone='$updatedNomorHandphone', nomor_telepon='$updatedNomorTelepon', nomor_ktp='$updatedNomorKTP', gambar_ktp='$gambar_ktp', nama_bank='$updatedNamaBank', nomor_rekening='$updatedNomorRekening', nama_pemilik_rekening='$updatedNamaPemilikRekening' WHERE nip='$nip'";
    
            if ($conn->query($query) === TRUE) {
                // Data profil karyawan berhasil disimpan
                $message = "Success";
                echo "<script>alert('$message'); window.location.href = 'sa-view-profile.php?nip=$nip';</script>";
                exit();
        
            } else {
                $message = "Update Failed!";
                echo "<script>alert('$message'); window.location.href = 'sa-view-profile.php?nip=$nip';</script>";
                exit();
            }
    
        } else {
            $message = "Update Failed!";
            echo "<script>alert('$message'); window.location.href = 'sa-view-profile.php?nip=$nip';</script>";
            exit();
        }
    } else {
        // Gambar KTP tidak diunggah, lakukan penyimpanan data profil karyawan ke database tanpa perubahan pada gambar KTP
        $query = "UPDATE karyawan SET nama='$updatedNama', nik='$updatedNik', jabatan='$updatedJabatan', gaji_pokok='$updatedGaji', jenis_gaji='$updatedJenisGaji', gaji_1='$updatedMinggu', tunjangan='$updatedTunjangan', tempat_lahir='$updatedTempatLahir', tanggal_lahir='$updatedTanggalLahir', alamat='$updatedAlamat', tanggal_masuk='$updatedTanggalMasuk', email='$updatedEmail', nomor_handphone='$updatedNomorHandphone', nomor_telepon='$updatedNomorTelepon', nomor_ktp='$updatedNomorKTP', nama_bank='$updatedNamaBank', nomor_rekening='$updatedNomorRekening', nama_pemilik_rekening='$updatedNamaPemilikRekening' WHERE nip='$nip'";
    
        if ($conn->query($query) === TRUE) {
            // Data profil karyawan berhasil disimpan
            $message = "Success";
            echo "<script>alert('$message'); window.location.href = 'sa-view-profile.php?nip=$nip';</script>";
            exit();
    
        } else {
            $message = "Update Failed!";
            echo "<script>alert('$message'); window.location.href = 'sa-view-profile.php?nip=$nip';</script>";
            exit();
        }
    }
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
    <link rel="stylesheet" type="text/css" href="css/style-sa-edit-profile.css?rev=<?php echo time();?>"> 
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>?rev=<?php echo time();?>">
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
    <div class="content">
        <form action="" method="POST" enctype="multipart/form-data">
            <h2><?php echo $karyawan['nama']; ?></h2>
        <table>
        <div class="hide-a">
            <tr>
                <th colspan="2">Profil</th>
            </tr>
            <tr>
                <td width="20%">NIK</td>
                <td width="30%"><input type="text" name="nik" value="<?php echo $karyawan['nik']; ?>"></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td><input type="text" name="nama" value="<?php echo $karyawan['nama']; ?>"></td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td><input type="text" name="jabatan" value="<?php echo $karyawan['jabatan']; ?>"></td>
            </tr>
            <tr>
                <td>Tempat Lahir</td>
                <td><input type="text" name="tempat_lahir" value="<?php echo $karyawan['tempat_lahir']; ?>"></td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td>
                <td><input type="date" name="tanggal_lahir" value="<?php echo $karyawan['tanggal_lahir']; ?>"></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td><textarea name="alamat"><?php echo $karyawan['alamat']; ?></textarea></td>
            </tr>
                </div>

            <!-- Dari sini -->
            <div  class="hide-b">
            <tr>
                <th colspan="2">Kontak</th>
            </tr>
            <tr>
                <td>Nomor Handphone</td>
                <td><input type="text" name="nomor_handphone" value="<?php echo $karyawan['nomor_handphone']; ?>"></td>
           </tr>
            <tr>
                <td>Nomor Telepon</td>
                <td><input type="text" name="nomor_telepon" value="<?php echo $karyawan['nomor_telepon']; ?>"></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><input type="text" name="email" value="<?php echo $karyawan['email']; ?>"></td>
            </tr>
            </div>

            <!-- Sampai sini -->
        <div class="hide-c">

            <tr>
                <th colspan="2">Informasi Tambahan</th>
            </tr>
            <tr>
                <td>Gaji Pokok</td>
                <td><input type="text" name="gaji_pokok" value="<?php echo $karyawan['gaji_pokok']; ?>"></td>
            </tr>
            <tr>
                <td>Pembayaran</td>
                <td>
                    
                <select id="pembayaran" name="jenis_gaji">
                    <option value="bulanan" <?php if ($karyawan['jenis_gaji'] === 'bulanan') echo 'selected'; ?>>Bulanan</option>
                    <option value="mingguan" <?php if ($karyawan['jenis_gaji'] === 'mingguan') echo 'selected'; ?>>Mingguan</option>
                    <!-- Add more bank options here -->
                </select>

                </td>
            </tr>
                <td></td>
                <td><input type="text" name="minggu" style="width:50%; margin-right: 5px;" value="<?php echo $karyawan['gaji_1']; ?>"> di minggu ke-2</td>
            </tr>
            <tr>
                <td></td>
                <td class="note">* Isi dengan  angka 0 jika gaji dibayar bulanan</td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td><input type="text" name="tunjangan" value="<?php echo $karyawan['tunjangan']; ?>"></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td><input type="date" name="tanggal_masuk" value="<?php echo $karyawan['tanggal_masuk']; ?>"></td>
            </tr>
            <tr>
                <td>Nomor KTP</td>
                <td><input type="text" name="nomor_ktp" value="<?php echo $karyawan['nomor_ktp']; ?>"></td>
            </tr>
            <tr>
                <td>Gambar KTP</td>
                <td><input type="file" name="gambar_ktp"></td>
            </tr>
                </div>
            
            <!-- Dari sini -->
            <div  class="hide-d">
            <tr>
                <th colspan="2">Akun Bank</th>
            </tr>
            <tr>
                <td>Nama Bank</td>
                <td>
                    
                <select id="namaBank" name="nama_bank">
                    <option value="bca" <?php if ($karyawan['nama_bank'] === 'bca') echo 'selected'; ?>>Bank Central Asia (BCA)</option>
                    <option value="mandiri" <?php if ($karyawan['nama_bank'] === 'mandiri') echo 'selected'; ?>>Bank Mandiri</option>
                    <option value="bri" <?php if ($karyawan['nama_bank'] === 'bri') echo 'selected'; ?>>Bank Rakyat Indonesia (BRI)</option>
                    <option value="bni" <?php if ($karyawan['nama_bank'] === 'bni') echo 'selected'; ?>>Bank Negara Indonesia (BNI)</option>
                    <option value="btn" <?php if ($karyawan['nama_bank'] === 'btn') echo 'selected'; ?>>Bank Tabungan Negara (BTN)</option>
                    <option value="cimb" <?php if ($karyawan['nama_bank'] === 'cimb') echo 'selected'; ?>>CIMB Niaga</option>
                    <option value="bsi" <?php if ($karyawan['nama_bank'] === 'bsi') echo 'selected'; ?>>Bank Syariah Indonesia (BSI)</option>
                    <option value="ocbc" <?php if ($karyawan['nama_bank'] === 'ocbc') echo 'selected'; ?>>OCBC NISP</option>
                    <option value="panin" <?php if ($karyawan['nama_bank'] === 'panin') echo 'selected'; ?>>Bank Panin</option>
                    <option value="danamon" <?php if ($karyawan['nama_bank'] === 'danamon') echo 'selected'; ?>>Bank Danamon</option>
                    <option value="bcablue" <?php if ($karyawan['nama_bank'] === 'bcablue') echo 'selected'; ?>>Blu by BCA</option>
                    <option value="gopay" <?php if ($karyawan['nama_bank'] === 'gopay') echo 'selected'; ?>>Gopay</option>
                    <option value="ovo" <?php if ($karyawan['nama_bank'] === 'ovo') echo 'selected'; ?>>OVO</option>
                    <option value="link" <?php if ($karyawan['nama_bank'] === 'link') echo 'selected'; ?>>Link Aja</option>
                    <option value="dana" <?php if ($karyawan['nama_bank'] === 'dana') echo 'selected'; ?>>Dana</option>
                    <!-- Add more bank options here -->
                </select>

                </td>
            </tr>
            <tr>
                <td>Nomor Rekening</td>
                <td><input type="text" name="nomor_rekening" value="<?php echo $karyawan['nomor_rekening']; ?>"></td>
            </tr>
            <tr>
                <td>Atas Nama</td>
                <td><input type="text" name="nama_pemilik_rekening" value="<?php echo $karyawan['nama_pemilik_rekening']; ?>"></td>
            </tr>
                </div>

            <!-- Sampai sini -->

            <tr>
                <td colspan="4" style="text-align:center; padding-top:30px;">
                    <input type="hidden" name="nip" value="<?php echo $karyawan['nip']; ?>">
                    <input type="submit" name="submit" value="Simpan Perubahan">
                </td>
            </tr>
        </table>
        </form>
    </div><div class="footer">
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
