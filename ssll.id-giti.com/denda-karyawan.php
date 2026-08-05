<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai admin
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin atau superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.php');
    exit();
}

include 'conn.php';

$role = $_SESSION['role'];

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
$query = "SELECT denda.*, karyawan.nama, karyawan.nip AS nipk, karyawan.status_karyawan, karyawan.nik
        FROM denda 
        JOIN karyawan ON karyawan.nip = denda.nip";

// Tambahkan kondisi filter berdasarkan bulan dan tahun jika telah dipilih
if (!empty($bulan) && !empty($tahun)) {
    $query .= " WHERE MONTH(denda.tanggal) = ? AND YEAR(denda.tanggal) = ? AND denda.ket1 = 'Denda'";
    $query .= " ORDER BY karyawan.nama ASC";
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js" defer></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style-tambah-data-denda-karyawan.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time(); ?>">
    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="stylesheet" type="text/css" href="css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="css/demo.css" />
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <script src="js/modernizr.custom.js"></script>
</head>

<body>
    <?php
    if ($role === 'admin') {
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
                                    <li><a href="absensi/req_absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>4. Absen Manual</a></li>
                                    <li><a href="absensi/data-absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>5. Validasi Data Absen</a></li>
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
    } else if ($role === 'superadmin') {
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
                                    <li><a href="absensi/req_absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>4. Absen Manual</a></li>
                                    <li><a href="absensi/data-absen.php"><i class="fa-solid fa-caret-right" id="mn2"></i>5. Validasi Data Absen</a></li>
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

    <div class="containt">
        <form action="proses-tambah-data-denda-karyawan.php" method="POST" enctype="multipart/form-data">
            <h2>Denda</h2>

            <label for="nip-denda">Nama:</label>
            <select id="nip-denda" name="nip_denda" required>
                <?php
                // Menampilkan pilihan nama karyawan dengan status aktif dalam dropdown
                foreach ($karyawan as $data) {
                    if ($data['status_karyawan'] === 'aktif' and $data['nip'] != '001' and $data['nip'] != '70326') {
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

            <input type="submit" value="Tambah Denda">
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
                    <select id="tahun" name="tahun" class="thn">
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
                    <th onclick="sortTable(0)" width="5%">No</th>
                    <th class="hide-m" onclick="sortTable(1)" width="5%">NIK</th>
                    <th onclick="sortTable(2)" width="20%">Nama</th>
                    <th onclick="sortTable(3)" width="15%">Tanggal</th>
                    <th class="hide-m" onclick="sortTable(4)" width="35%">Keterangan</th>
                    <th onclick="sortTable(5)" width="10%">Jumlah</th>
                    <th width="10%">Action</th> <!-- Kolom baru untuk tombol delete -->
                </tr>
                <?php
                $nomor_urut = 1;
                foreach ($dataa as $data) {
                    if ($data['ket1'] === 'Denda') {
                        $jumlah = "Rp " . number_format($data['jumlah'], 0, ',', '.');
                        echo "<tr>";
                        echo "<td>" . $nomor_urut . "</td>";
                        echo "<td class='hide-m'>" . $data['nik'] . "</td>";
                        echo "<td style='text-align:left;'>" . $data['nama'] . "</td>";
                        setlocale(LC_TIME, 'id_ID'); // Setel lokal ke Bahasa Indonesia

                        $tanggal = $data['tanggal'];
                        $tanggal_diubah_format = strftime('%d %b %Y', strtotime($tanggal));

                        echo "<td>" . $tanggal_diubah_format . "</td>";
                        echo "<td class='hide-m' style='text-align:left;'>" . $data['keterangan'] . "</td>";
                        echo "<td>" . $jumlah . "</td>";
                        echo "<td>";
                        // echo "<button onclick=\"confirmDelete('" . $data['id_denda'] . "')\">Delete</button>"; // Change 'id' to 'id_denda'

                        // Query untuk mendapatkan bulan dan tahun yang terkunci dari tabel kunci_gaji
                        $queryLockedDates2 = "SELECT DISTINCT bulan, tahun FROM kunci_gaji WHERE kunci = 'Lock'";
                        $resultLockedDates2 = $conn->query($queryLockedDates2);

                        $lockedDates2 = array();

                        // if ($resultLockedDates2->num_rows > 0) {
                        //     echo "<button onclick=\"confirmDelete('" . $data['id_denda'] . "')\" disabled>Lock</button>";
                        // }
                        // else{
                        echo "<button onclick=\"confirmDelete('" . $data['id_denda'] . "')\">Delete</button>";
                        // }


                        echo "</td>";
                        echo "</tr>";
                    }
                    $nomor_urut++;
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
        new gnMenu(document.getElementById('gn-menu'));
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
                            shouldSwitch = true;
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
                    switchcount++;
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

        function confirmDelete(id_denda) {
            if (confirm("Are you sure you want to delete this data?")) {
                // Redirect ke halaman proses-hapus-data-denda-karyawan.php dengan mengirimkan id data yang akan dihapus melalui parameter GET
                window.location.href = "proses-hapus-data-denda-karyawan.php?id_denda=" + id_denda; // Change 'id' to 'id_denda'
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

        function printData() {
            var bulan = document.getElementById("bulan").value;
            var tahun = document.getElementById("tahun").value;
            var url = "print-denda.php?bulan=" + bulan + "&tahun=" + tahun;
            window.open(url, "_blank");
        }
    </script>
</body>

</html>