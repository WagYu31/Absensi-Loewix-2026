<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan karyawan, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

// Ambil data profil karyawan dari sesi login
include 'get-kar-login-data.php';

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
  <link rel="stylesheet" type="text/css" href="css/style-view-profile-kar.css">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>
    <script>
        $(document).ready(function() {
            // Menampilkan pop-up saat tombol pas photo di klik
            $(".edit-photo-button").click(function() {
                $("#uploadPopup").css("display", "block");
            });

            // Menyembunyikan pop-up saat tombol Cancel di klik
            $("#cancelBtn").click(function() {
                $("#uploadPopup").css("display", "none");
            });
        });
    </script>
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
								<li>
									<a href="absensi/detail-absen-kar.php?nik=<?php echo $nik;?>"><i class="fa-solid fa-thumbs-up" id="mnn"></i>Data Absen</a>
								</li>
								<li><a href="riwayat-gaji.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Slip Gaji</a></li>
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

    <div class="content">
        <div class="header">
            <button class="edit-photo-button" id="photoButton">
                <img src="uploads/<?php echo $photo; ?>" alt="Pas Photo" width="120px">
            </button>
            <h2><?php echo $nama; ?></h2>
            <h4><?php echo $jabatan; ?></h4>
        </div>
        
        <div class="edit-profile">
            <button type="button" onclick="changePasswordPrompt('<?php echo $nip; ?>')">Ganti Password</button>
            <a href="edit-profile-karyawan.php">Edit Profil</a>
        </div>

        <table>
            <tr>
                <th colspan="2">Profil</th>
            </tr>
            <tr>
                <td width="30%">NIK</td>
                <td><?php echo $nik; ?></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td><?php echo $nama; ?></td>
            </tr>
            <tr>
                <td>Tempat Lahir</td>
                <td><?php echo $tempatLahir; ?></td>
            </tr>
            <tr>
                <td>Tanggal Lahir</td>
                <td><?php echo date('d M Y', strtotime($tanggalLahir)); ?></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td><?php echo $alamat; ?></td>
            </tr>
            <tr>
                <th colspan="2">Kontak</th>
            </tr>
            <tr>
                <td>Nomor Handphone</td>
                <td><?php echo $nomorHP; ?></td>
            </tr>
            <tr>
                <td>Nomor Telepon</td>
                <td><?php echo $nomorTelepon; ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><?php echo $email; ?></td>
            </tr>
            <tr>
                <th colspan="2">Informasi Tambahan</th>
            </tr>
            <tr>
                <td>Gaji Pokok</td>
                <td><?php echo $gajiPokok; ?></td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td><?php echo $tunjangan; ?></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td><?php echo date('d M Y', strtotime($tanggalMasuk)); ?></td>
            </tr>
            <tr>
                <td>Nomor KTP</td>
                <td><?php echo $nomorKTP; ?></td>
            </tr>
            <tr>
                <td>Gambar KTP</td>
                <td><img src="uploads/<?php echo $gambarKTP; ?>" alt="KTP"></td>
            </tr>
            <tr>
                <th colspan="2">Akun Bank</th>
            </tr>
            <tr>
                <td>Nama Bank</td>
                <td><?php include 'get-nama-bank.php'; echo $nmbank; ?></td>
            </tr>
            <tr>
                <td>Nomor Rekening</td>
                <td><?php echo $nomorRekening; ?></td>
            </tr>
            <tr>
                <td>Atas Nama</td>
                <td><?php echo $namaPemilikRekening; ?></td>
            </tr>
        </table>
    </div>
        <!-- Upload Photo Popup -->
        <div id="uploadPopup" class="popup">
        <div class="popup-content">
            <span class="close" id="cancelBtn">&times;</span>
            <h2>Upload Photo Profil Baru</h2>
            <form action="upload-photo-kar.php" method="post" enctype="multipart/form-data">
            <input type="file" name="newPhoto" accept="image/jpeg, image/png">
            <button class="upload-button">Upload Photo Profil Baru</button>
            </form>
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
function changePasswordPrompt(nip) {
    var oldPassword = prompt("Masukkan Password Lama:");
    if (oldPassword === null) {
        return; // User canceled the prompt
    }

    var newPassword = prompt("Masukkan Password Baru:");
    if (newPassword === null) {
        return; // User canceled the prompt
    }

    var confirmPassword = prompt("Konfirmasi Password Baru:");
    if (confirmPassword === null) {
        return; // User canceled the prompt
    }

    // Send the data to the server for validation and processing
    $.ajax({
        type: "POST",
        url: "kar-validate_old_password.php",
        data: {
            nip: nip,
            oldPassword: oldPassword
        },
        success: function(response) {
            if (response === "success") {
                if (newPassword !== confirmPassword) {
                    alert("Konfirmasi Password Baru tidak sesuai dengan Password Baru.");
                    return;
                }

                // Hash the new password before sending to the server
                var hashedNewPassword = btoa(newPassword); // Basic base64 encoding

                $.ajax({
                    type: "POST",
                    url: "kar-change_password_script.php",
                    data: {
                        nip: nip,
                        newPassword: hashedNewPassword
                    },
                    success: function(response) {
                        alert(response);
                    },
                    error: function() {
                        alert("An error occurred while processing the request.");
                    }
                });
            } else {
                alert("Password Lama yang dimasukkan salah.");
            }
        },
        error: function() {
            alert("An error occurred while processing the request.");
        }
    });
}
</script>
</body>
</html>
<script>
        // Ambil button profil dari sidebar
        var profileButton = document.querySelector('a[href="edit-profile-karyawan.php"]');
        
        // Tambahkan event listener untuk tombol profil
        profileButton.addEventListener('click', function(event) {
            // Cek apakah pengguna sudah login (misalnya, dengan memeriksa sesi)
            var isLoggedIn = true; // Ganti dengan logika sesi yang sesuai
            
            // Jika pengguna belum login, batalkan event click dan arahkan ke halaman login
            if (!isLoggedIn) {
                event.preventDefault();
                window.location.href = 'edit-profile-karyawan.php'; // Ganti dengan halaman login yang sesuai
            }
        });
    </script>