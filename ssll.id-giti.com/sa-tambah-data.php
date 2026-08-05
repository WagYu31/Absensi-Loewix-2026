<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

    // Koneksi ke database
   include 'conn.php';
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
        <link rel="stylesheet" type="text/css" href="css/style-sa-tambah-data.css?rev=<?php echo time();?>?rev=<?php echo time();?>"> 
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>

    <title>Form Input</title>
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
<!-- Form Pertama: Tambah Akun Admin -->
<h2 style="margin-left:2%;">Tambah Admin</h2>

<form action="" method="POST">
    <table>
        <tr>
            <th class="hide-m">NIK</th>
            <th>Nama</th>
            <th>Nama Jabatan</th>
            <th class="hide-m">Username</th>
            <th class="hide-m">Email</th>
            <th>Role</th>
            <th>Aksi</th>
            <th>Hapus</th>
        </tr>
        <?php
        // Query untuk mengambil data dari tabel users, karyawan, dan jabatan
        $query = "SELECT users.nip, users.role, users.username, karyawan.nama, karyawan.nik, karyawan.jabatan, karyawan.email FROM users
                  JOIN karyawan WHERE users.nip = karyawan.nip";
        $result = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $nip = $row['nip'];
            $nik = $row['nik'];
            $nama = $row['nama'];
            $nama_jabatan = $row['jabatan'];
            $username = $row['username'];
            $email = $row['email'];
            $role = $row['role'];
            if($nip != '70326'):
            ?>
            <tr>
                <td class="hide-m"><?php echo $nik; ?></td>
                <td><?php echo $nama; ?></td>
                <td><?php echo $nama_jabatan; ?></td>
                <td class="hide-m"><?php echo $username; ?></td>
                <td class="hide-m"><?php echo $email; ?></td>
                <td style="text-transform:capitalize;"><?php echo $role; ?></td>
                <td>
                    <?php if ($role === 'superadmin') { ?>
                         <button class="ijo" type="button" onclick="changePasswordPrompt('<?php echo $nip; ?>')"><i class="fa-solid fa-key"></i> Ubah Password</button>
                    <?php } else if ($role === 'admin') { ?>
                        <button class="ijo" type="submit" name="aksi" value="<?php echo $nip; ?>"><i class="fa-solid fa-retweet"></i> Karyawan</button>
                    <?php } else { ?>
                        <button class="ijo" type="submit" name="aksi" value="<?php echo $nip; ?>"><i class="fa-solid fa-retweet"></i> Admin</button>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($role === 'superadmin') { ?>
                        <button type="submit" name="hapus" value="<?php echo $nip; ?>" class="hps" disabled><i class="fa-regular fa-trash-can"></i></button>
                    <?php } else { ?>
                        <button type="submit" name="hapus" value="<?php echo $nip; ?>" class="hps"><i class="fa-regular fa-trash-can"></i></button>
                    <?php } ?>
                </td>
            </tr>
            <?php
            endif;
        }
        ?>
    </table>
</form>


<?php
// Fungsi untuk mengubah role user menjadi admin
if (isset($_POST['aksi'])) {
    $nip = $_POST['aksi'];
    
    // Query untuk mengambil role user saat ini
    $query = "SELECT role FROM users WHERE nip = '$nip'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $currentRole = $row['role'];
        
        // Mengubah role user sesuai kondisi
        if ($currentRole === 'admin') {
            $newRole = 'karyawan';
        } else {
            $newRole = 'admin';
        }
        
        // Query untuk mengubah role user
        $updateQuery = "UPDATE users SET role = '$newRole' WHERE nip = '$nip'";
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if ($updateResult) {
            $message = "Role telah diubah menjadi $newRole.";
            echo "<script>alert('$message'); window.location.href = 'sa-tambah-data.php';</script>";
            exit();
        } else {
            echo "Gagal mengubah role user dengan NIP $nip.<br>";
        }
    } else {
        echo "Gagal mengambil data user dengan NIP $nip.<br>";
    }
}

if(isset($_POST['hapus'])){
    $nip = $_POST['hapus'];

    $querydel = "DELETE FROM users WHERE nip = '$nip'";
    $resultdel = $conn->query($querydel);

    if (!$resultdel) {
        die("Query execution failed: " . $conn->error);
    }
    else{
        $message = "Akun berhasil di hapus.";
        echo "<script>alert('$message'); window.location.href = 'sa-tambah-data.php';</script>";
        exit();
    }
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
        url: "validate_old_password.php",
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
                    url: "change_password_script.php",
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
