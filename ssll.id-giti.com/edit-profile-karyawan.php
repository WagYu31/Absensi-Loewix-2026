<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "karyawan") {
    header("Location: edit-profile-karyawan.php");
    exit();
}

$nip = $_SESSION['nip'];

include 'conn.php';

// Contoh data profil karyawan
    $query = "SELECT * FROM karyawan
                     WHERE karyawan.nip = '$nip'";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();


    // Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $updatedNama = $_POST['nama'];
    $updatedNik = $_POST['nik'];
    $updatedJabatan = $_POST['jabatan'];
    $updatedTempatLahir = $_POST['tempat_lahir'];
    $updatedTanggalLahir = $_POST['tanggal_lahir'];
    $updatedAlamat = $_POST['alamat'];
    $updatedNomorHandphone = $_POST['nomor_handphone'];
    $updatedNomorTelepon = $_POST['nomor_telepon'];
    $updatedEmail = $_POST['email'];
    $updatedTanggalMasuk = $_POST['tanggal_masuk'];
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
            $query = "UPDATE karyawan SET nama='$updatedNama', nik='$updatedNik', jabatan='$updatedJabatan', tempat_lahir='$updatedTempatLahir', tanggal_lahir='$updatedTanggalLahir', alamat='$updatedAlamat', email='$updatedEmail', nomor_handphone='$updatedNomorHandphone', nomor_telepon='$updatedNomorTelepon', nomor_ktp='$updatedNomorKTP', gambar_ktp='$gambar_ktp', nama_bank='$updatedNamaBank', nomor_rekening='$updatedNomorRekening', nama_pemilik_rekening='$updatedNamaPemilikRekening' WHERE nip='$nip'";
    
            if ($conn->query($query) === TRUE) {
                // Data profil karyawan berhasil disimpan
                $message = "Success";
                echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php?nip=$nip';</script>";
                exit();
        
            } else {
                $message = "Update Failed!";
                echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php?nip=$nip';</script>";
                exit();
            }
    
        } else {
            $message = "Update Failed!";
            echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php?nip=$nip';</script>";
            exit();
        }
    } else {
        // Gambar KTP tidak diunggah, lakukan penyimpanan data profil karyawan ke database tanpa perubahan pada gambar KTP
        $query = "UPDATE karyawan SET nama='$updatedNama', nik='$updatedNik', jabatan='$updatedJabatan', tempat_lahir='$updatedTempatLahir', tanggal_lahir='$updatedTanggalLahir', alamat='$updatedAlamat', email='$updatedEmail', nomor_handphone='$updatedNomorHandphone', nomor_telepon='$updatedNomorTelepon', nomor_ktp='$updatedNomorKTP', nama_bank='$updatedNamaBank', nomor_rekening='$updatedNomorRekening', nama_pemilik_rekening='$updatedNamaPemilikRekening' WHERE nip='$nip'";
    
        if ($conn->query($query) === TRUE) {
            // Data profil karyawan berhasil disimpan
            $message = "Success";
            echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php?nip=$nip';</script>";
            exit();
    
        } else {
            $message = "Update Failed!";
            echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php?nip=$nip';</script>";
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="css/foot.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>
    <style>
        body {
            background-color: #e0e4ee;
            font-family: 'Lato', sans-serif;
        }
        .containt {
            padding: 20px;
            width: 60%;
            margin: 0 auto;
            margin-top: 8%;
            margin-left: 20%;
            margin-bottom:5%;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            text-transform: uppercase;
            margin-bottom:8%
        }
        .profile-form {
            margin-bottom: 20px;
        }
        .profile-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            margin-top:3%;
        }
        .profile-form input[type="text"],
        .profile-form input[type="email"],
        .profile-form select,
        .profile-form input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .profile-form input[type="submit"] {
            background-color: #4CAF50;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top:3%;
        }
        .profile-form input[type="submit"]:hover {
            background-color: #45a049;
        }

        @media screen and (max-width: 768px) {
            .containt{
                width:90%;
                margin-left:5%;
                margin-top:18%;
            }
            h2{
                font-size:20px;
            }
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
        <h2>Edit Profil Karyawan</h2>

        <form action="" method="POST" enctype="multipart/form-data" class="profile-form">
            <label for="nama">Nama</label>
            <input type="text" name="nama" id="nama" value="<?php echo $row['nama']; ?>">

            <label for="nip">NIK</label>
            <input type="text" name="nik" id="nik" value="<?php echo $row['nik']; ?>" disabled>

            <label for="jabatan">Position</label>
            <input type="text" name="jabatan" id="jabatan" value="<?php echo $row['jabatan']; ?>" disabled>

            <label for="tanggal_masuk">Start Date</label>
            <input type="text" name="tanggal_masuk" id="tanggal_masuk" value="<?php echo $row['tanggal_masuk']; ?>" disabled>

            <label for="tempat_lahir">Tempat Lahir</label>
            <input type="text" name="tempat_lahir" id="tempat_lahir" value="<?php echo $row['tempat_lahir']; ?>">

            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="text" name="tanggal_lahir" id="tanggal_lahir" value="<?php echo $row['tanggal_lahir']; ?>">

            <label for="alamat">Alamat</label>
            <input type="text" name="alamat" id="alamat" value="<?php echo $row['alamat']; ?>">

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo $row['email']; ?>">

            <label for="nomor_handphone">Nomor Handphone</label>
            <input type="text" name="nomor_handphone" id="nomor_handphone" value="<?php echo $row['nomor_handphone']; ?>">

            <label for="nomor_telepon">Nomor Telepon</label>
            <input type="text" name="nomor_telepon" id="nomor_telepon" value="<?php echo $row['nomor_telepon']; ?>">

            <label for="nomor_ktp">Nomor KTP</label>
            <input type="text" name="nomor_ktp" id="nomor_ktp" value="<?php echo $row['nomor_ktp']; ?>">

            <label for="gambar_ktp">Gambar KTP</label>
            <input type="file" name="gambar_ktp" id="gambar_ktp">

            <label for="nama_bank">Nama Bank</label>
                <select id="namaBank" name="nama_bank">
                    <option value="bca" <?php if ($row['nama_bank'] === 'bca') echo 'selected'; ?>>Bank Central Asia (BCA)</option>
                    <option value="mandiri" <?php if ($row['nama_bank'] === 'mandiri') echo 'selected'; ?>>Bank Mandiri</option>
                    <option value="bri" <?php if ($row['nama_bank'] === 'bri') echo 'selected'; ?>>Bank Rakyat Indonesia (BRI)</option>
                    <option value="bni" <?php if ($row['nama_bank'] === 'bni') echo 'selected'; ?>>Bank Negara Indonesia (BNI)</option>
                    <option value="btn" <?php if ($row['nama_bank'] === 'btn') echo 'selected'; ?>>Bank Tabungan Negara (BTN)</option>
                    <option value="cimb" <?php if ($row['nama_bank'] === 'cimb') echo 'selected'; ?>>CIMB Niaga</option>
                    <option value="bsi" <?php if ($row['nama_bank'] === 'bsi') echo 'selected'; ?>>Bank Syariah Indonesia (BSI)</option>
                    <option value="ocbc" <?php if ($row['nama_bank'] === 'ocbc') echo 'selected'; ?>>OCBC NISP</option>
                    <option value="panin" <?php if ($row['nama_bank'] === 'panin') echo 'selected'; ?>>Bank Panin</option>
                    <option value="danamon" <?php if ($row['nama_bank'] === 'danamon') echo 'selected'; ?>>Bank Danamon</option>
                    <option value="bcablue" <?php if ($row['nama_bank'] === 'bcablue') echo 'selected'; ?>>Blu by BCA</option>
                    <option value="gopay" <?php if ($row['nama_bank'] === 'gopay') echo 'selected'; ?>>Gopay</option>
                    <option value="ovo" <?php if ($row['nama_bank'] === 'ovo') echo 'selected'; ?>>OVO</option>
                    <option value="link" <?php if ($row['nama_bank'] === 'link') echo 'selected'; ?>>Link Aja</option>
                    <option value="dana" <?php if ($row['nama_bank'] === 'dana') echo 'selected'; ?>>Dana</option>
                    <!-- Add more bank options here -->
                </select>

            <label for="nomor_rekening">Nomor Rekening</label>
            <input type="text" name="nomor_rekening" id="nomor_rekening" value="<?php echo $row['nomor_rekening']; ?>">

            <label for="nama_rekening">Atas Nama</label>
            <input type="text" name="nama_pemilik_rekening" id="nama_pemilik_rekening" value="<?php echo $row['nama_pemilik_rekening']; ?>">

            <input type="submit" name="submit" value="Simpan">
        </form>
        <?php
    }
    else {
        echo "Not Found!";
    }
    ?>
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
