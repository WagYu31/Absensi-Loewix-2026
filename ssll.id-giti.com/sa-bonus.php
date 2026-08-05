<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

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
$query = "SELECT tunjangan_lainnya.*, karyawan.nip AS nipk,  karyawan.nama, karyawan.status_karyawan, karyawan.nik 
        FROM tunjangan_lainnya 
        JOIN karyawan ON karyawan.nip = tunjangan_lainnya.nip";

// Tambahkan kondisi filter berdasarkan bulan dan tahun jika telah dipilih
if (!empty($bulan) && !empty($tahun)) {
    $query .= " WHERE MONTH(tunjangan_lainnya.tanggal) = ? AND YEAR(tunjangan_lainnya.tanggal) = ? AND tunjangan_lainnya.ket1 = 'bonus'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $bulan, $tahun);
} else {
    $stmt = $conn->prepare($query);
}

$result = $stmt->execute();

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

$dataa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// $query = "SELECT tunjangan_lainnya.*, karyawan.nip AS nipk,  karyawan.nama, karyawan.status_karyawan, karyawan.nik 
//         FROM tunjangan_lainnya 
//         JOIN karyawan ON karyawan.nip = tunjangan_lainnya.nip";
// $result = $conn->query($query);
// $dendaKaryawan = array();
// while ($row = $result->fetch_assoc()) {
//     $dendaKaryawan[] = $row;
// }

$queryKar = "SELECT * FROM karyawan ORDER BY nama ASC";
$resultKar = $conn->query($queryKar);
$karyawan = array();
while ($rowKar = $resultKar->fetch_assoc()) {
    $karyawan[] = $rowKar;
}
// $conn->close();
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
        <link rel="stylesheet" type="text/css" href="css/style-tambah-data-denda-karyawan.css?rev=<?php echo time();?>">
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
        <form action="sa-proses-bonus.php" method="POST" enctype="multipart/form-data">
            <h2 class="bonus">Bonus</h2>

            <label for="nip-denda">Nama:</label>
            <select id="nip-denda" name="nip_denda" required>
            <?php
                // Menampilkan pilihan nama karyawan dengan status aktif dalam dropdown
                foreach ($karyawan as $data) {
                    if ($data['status_karyawan'] === 'aktif' AND $data['nip'] != '001' AND $data['nip'] != '70326'){
                        echo "<option value='" . $data['nip'] . "'>" . $data['nama'] . "</option>";
                    }
                }
                ?>
            </select>

            <label for="tanggal-denda">Tanggal:</label>
            <?php
                include 'conn.php';

                // Query untuk mendapatkan bulan dan tahun yang terkunci dari tabel kunci_gaji
                $queryLockedDates = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
                $resultLockedDates = $conn->query($queryLockedDates);
                
                $lockedDates = array();
                
                if ($resultLockedDates->num_rows > 0) {
                    while ($rowLockedDate = $resultLockedDates->fetch_assoc()) {
                        $lockedDates[] = $rowLockedDate['tahun'] . '-' . str_pad($rowLockedDate['bulan'], 2, '0', STR_PAD_LEFT);
                    }
                }

            ?>
            <input type="date" id="tanggal-denda" name="tanggal_denda" onchange="checkLockedDates()" required>

            <label for="jumlah-denda">Jumlah:</label>
            <input type="number" id="jumlah-denda" name="jumlah_denda" required>

            <label for="keterangan-denda">Keterangan:</label>
            <textarea id="keterangan-denda" name="keterangan_denda" required></textarea>

            <input type="submit" value="Tambah Bonus">
        </form>
    <div class="tab">
        <div class="pilih no-print">

    <!-- Tambahkan form untuk mengelompokkan input select -->
    <form method="post" class="bt">
        <!-- Tambahkan input select untuk memilih nama bulan -->
        <label for="bulan" class="bln" style="margin-left:20px">Bulan :</label>
        <select id="bulan" name="bulan" class="bln">
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
                echo "<option class='bln' value='$bulanNum' $selected>$bulanName</option>";
            }
            ?>
        </select>

        <!-- Tambahkan input select untuk memilih tahun -->
        <label for="tahun" class="thn">Tahun :</label>
        <select id="tahun" name="tahun"class="thn" >
            <?php
            $tahunSekarang = date('Y');
            for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                $selected = ($i == $tahun) ? 'selected' : '';
                echo "<option class='thn' value='$i' $selected>$i</option>";
            }
            ?>
        </select>

        <!-- Tambahkan tombol "Tampilkan Data" -->
        <button type="submit" class="gaji" id="bs">Tampilkan Data</button>
        <a href="#" onclick="printData()" class="bp"><i class="fa-solid fa-print"></i>PRINT</a>
    </form>

    </div>
    
        <table id="denda">
            <tr>
                <th onclick="sortTable(0)" class="hide-m">NIK</th>
                <th onclick="sortTable(1)">Nama</th>
                <th onclick="sortTable(2)">Tanggal</th>
                <th onclick="sortTable(3)">Keterangan</th>
                <th onclick="sortTable(4)">Jumlah</th>
                <th>Action</th> <!-- Kolom baru untuk tombol delete -->
            </tr>
            <?php
            foreach ($dataa as $denda) {
                if($denda['ket1'] === 'bonus'){
                    $jumlah = "Rp " . number_format($denda['jumlah'], 0, ',', '.');
                    echo "<tr>";
                    echo "<td class='hide-m'>" . $denda['nik'] . "</td>";
                    echo "<td>" . $denda['nama'] . "</td>";
                    echo "<td>" . date('d-m-Y', strtotime($denda['tanggal'])) . "</td>";
                    echo "<td>" . $denda['keterangan'] . "</td>";
                    echo "<td>" . $jumlah . "</td>";
                    echo "<td>";
                    echo "<button onclick=\"confirmDelete('" . $denda['id_tunjangan_lain'] . "')\">Delete</button>"; // Change 'id' to 'id_denda'
                    echo "</td>";
                    echo "</tr>";
            }
            }
            ?>
        </table>
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
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("denda");
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

function checkLockedDates() {
    var tanggalInput = document.getElementById("tanggal-denda");
    var selectedDate = tanggalInput.value;

    if (selectedDate !== "") {
        var selectedYearMonth = selectedDate.substr(0, 7);

        // Cek apakah tanggal yang dipilih ada dalam daftar tanggal terkunci
        if (<?php echo json_encode($lockedDates); ?>.includes(selectedYearMonth)) {
            alert("Tanggal pada bulan dan tahun yang terkunci tidak dapat dipilih.");
            tanggalInput.value = "";
        }
    }
}

      function confirmDelete(id_tunjangan_lain) {
        if (confirm("Are you sure you want to delete this data?")) {
            // Redirect ke halaman proses-hapus-data-denda-karyawan.php dengan mengirimkan id data yang akan dihapus melalui parameter GET
            window.location.href = "sa-proses-hapus-bonus.php?id_tunjangan_lain=" + id_tunjangan_lain; // Change 'id' to 'id_denda'
        }
      }
    </script>
</body>
</html>
