<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

// Fungsi untuk menghapus data karyawan berdasarkan NIP
function deleteKaryawan($conn, $nip) {
    $query = "DELETE FROM rincian_gaji WHERE nip = '$nip';";
    if ($conn->query($query) === TRUE) {
        
        $query = "DELETE FROM denda WHERE nip = '$nip';";
        if ($conn->query($query) === TRUE) {
            
            $query = "DELETE FROM tunjangan_lainnya WHERE nip = '$nip';";
            if ($conn->query($query) === TRUE) {
                
                $query = "DELETE FROM users WHERE nip = '$nip';";
                if ($conn->query($query) === TRUE) {
                    
                    $query = "DELETE FROM karyawan WHERE nip = '$nip';";
                    if ($conn->query($query) === TRUE) {
                        $message = "Employee data with NIP $nip has been successfully deleted.";
                        echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
                    } else {
                        $message = "Error occurred while deleting employee data. " . $conn->error;
                        echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
                    }
                } else {
                    $message = "Error occurred while deleting employee data. " . $conn->error;
                    echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
                }
            } else {
                $message = "Error occurred while deleting employee data. " . $conn->error;
                echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
            }

        } else {
            $message = "Error occurred while deleting employee data. " . $conn->error;
            echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
        }
    } else {
        $message = "Error occurred while deleting employee data. " . $conn->error;
        echo "<script>alert('$message'); window.location.href = 'sa-data-karyawan.php';</script>";
    }
}

// Memeriksa apakah parameter NIP untuk penghapusan data karyawan telah diterima
if (isset($_GET['deleteNIP'])) {
    $deleteNIP = $_GET['deleteNIP'];
    deleteKaryawan($conn, $deleteNIP);
}

$query = "SELECT * FROM karyawan ORDER BY nama ASC";
$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

$karyawanData = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $karyawanData[] = $row;
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style-data-karyawan.css?rev=<?php echo time();?>">
    
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="css/normalize.css" />
		<link rel="stylesheet" type="text/css" href="css/demo.css" />
		<link rel="stylesheet" type="text/css" href="css/component.css" />
		<script src="js/modernizr.custom.js"></script>

    <style>
    .highlight td {
        background-color: #ffff66;
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

    <div class="content">
        <div class="header">
          <h2>Data Karyawan</h2>
              <?php
                // Check if there are any records with gaji_pokok value of 0
                $query_check_zero_gaji = "SELECT COUNT(*) AS count FROM karyawan WHERE gaji_pokok = 0";
                $result_check_zero_gaji = $conn->query($query_check_zero_gaji);
                
                if ($result_check_zero_gaji) {
                    $zero_gaji_count = $result_check_zero_gaji->fetch_assoc()['count'];
                    
                    // Display the link only if there are records with gaji_pokok value of 0
                    if ($zero_gaji_count > 0) {
                        // echo '<a href="sa-input-gaji.php" class="disini">Input Gaji</a>';
                        ?>
                          <div class="header-container">
                            <p>Karyawan belum diberi gaji!</p>
                            <a href="sa-input-gaji.php" class="disini">Input Gaji</a>
                          </div>
                        <?php
                    }
                }
                ?>
        </div>
        <table id="karyawan">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">NIK</th>
                    <th onclick="sortTable(1)">Nama</th>
                    <th onclick="sortTable(2)">Jabatan</th>
                    <th onclick="sortTable(3)" class="hide-m">Tanggal Masuk</th>
                    <th onclick="sortTable(4)" class="hide-m">Nomor Handphone</th>
                    <th onclick="sortTable(5)" class="hide-m">Alamat</th>
                    <th onclick="sortTable(6)" colspan="2">Status</th>
                    <th onclick="sortTable(7)">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($karyawanData as $karyawan) :
                    if($karyawan['nip'] != '001'  AND $karyawan['nip'] != '70326') :
                    // Tambahkan class "highlight" jika gaji_pokok adalah 0.00
                    $highlightClass = $karyawan['gaji_pokok'] == '0.00' ? 'highlight' : '';
                ?>
                    <tr class="<?php echo $highlightClass; ?>">
                        <td><?php echo $karyawan['nik']; ?></td>
                        <td id="kiri" style="text-transform:capitalize;"><?php echo $karyawan['nama']; ?></td>
                        <td id="kiri"><?php echo $karyawan['jabatan']; ?></td>
                        <td class="hide-m"><?php echo date('d-m-Y', strtotime($karyawan['tanggal_masuk'])); ?></td>
                        <td class="hide-m">
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

                        <td id="kiri" class="hide-m" style="text-transform:capitalize;"><?php echo $karyawan['alamat']; ?></td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" onchange="updateStatus('<?php echo $karyawan['nip']; ?>', this)" <?php if ($karyawan['status_karyawan'] === 'aktif') echo 'checked'; ?>>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td style="text-transform:capitalize;"><?php echo $karyawan['status_karyawan']; ?></td>
                        <td>
                            <a href="sa-view-profile.php?nip=<?php echo $karyawan['nip']; ?>" class="button2"><i class="fas fa-magnifying-glass"></i></a>
                            <button onclick="deleteKaryawan('<?php echo $karyawan['nip']; ?>')"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php 
                endif;
                endforeach; ?>
            </tbody>
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

    <script>
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("karyawan");
            switching = true;
            //Set the sorting direction to ascending:
            dir = "asc"; 
            /*Make a loop that will continue until
            no switching has been done:*/
            while (switching) {
                //start by saying: no switching is done:
                switching = false;
                rows = table.rows;
                /*Loop through all table rows (except the
                first, which contains table headers):*/
                for (i = 1; i < (rows.length - 1); i++) {
                    //start by saying there should be no switching:
                    shouldSwitch = false;
                    /*Get the two elements you want to compare,
                    one from current row and one from the next:*/
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];
                    /*check if the two rows should switch place,
                    based on the direction, asc or desc:*/
                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            //if so, mark as a switch and break the loop:
                            shouldSwitch= true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            //if so, mark as a switch and break the loop:
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    /*If a switch has been marked, make the switch
                    and mark that a switch has been done:*/
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    //Each time a switch is done, increase this count by 1:
                    switchcount ++;      
                } else {
                    /*If no switching has been done AND the direction is "asc",
                    set the direction to "desc" and run the while loop again.*/
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
        
        function deleteKaryawan(nip) {
            if (confirm("Are you sure you want to delete this data?")) {
                window.location.href = "sa-data-karyawan.php?deleteNIP=" + nip;
            }
        }

        
        function updateStatus(nip, checkbox) {
            var status = checkbox.checked ? 'aktif' : 'tidak aktif';
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log("");
                    location.reload(); // Refresh the page after status update
                }
            };
            xhttp.open("GET", "sa-update-status-karyawan.php?nip=" + nip + "&status=" + status, true);
            xhttp.send();
        }

        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>
