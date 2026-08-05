<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

// Fungsi untuk menghapus data karyawan berdasarkan NIP
function deleteGaji($conn, $id_rincian_gaji)
{
    $queryDel = "DELETE FROM rincian_gaji WHERE id_rincian_gaji = '$id_rincian_gaji';";
    if ($conn->query($queryDel) === TRUE) {
    } else {
        echo "Error occurred while deleting employee data. " . $conn->error;
    }
}

// Memeriksa apakah parameter NIP untuk penghapusan data karyawan telah diterima
if (isset($_GET['deleteID'])) {
    $deleteID = $_GET['deleteID'];
    deleteGaji($conn, $deleteID);
}

// Memeriksa apakah form telah disubmit dengan memeriksa method POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    // Jika form belum disubmit, gunakan bulan dan tahun saat ini
    $bulan = date('m');
    $tahun = date('Y');
}

$query = "SELECT * FROM karyawan WHERE karyawan.nip != '001' AND karyawan.nip != '70326' AND karyawan.status_karyawan != 'tidak aktif' ORDER BY nama ASC";

$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}


?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--<meta http-equiv='cache-control' content='no-cache'>-->
    <!--<meta http-equiv='expires' content='0'>-->
    <!--<meta http-equiv='pragma' content='no-cache'>-->
    <title>Grav-Tech Salary</title>
    <meta name="description" content="Website Penghitung Gaji Karyawan Grav-Tech" />
    <meta name="keywords" content="salary, gaji, gravitti technology, gravitti, grav-tech" />
    <meta name="author" content="Irviani" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <script src="js/script.js" defer></script>
    <script type="text/javascript" src="tableExport.js"></script>
    <script type="text/javascript" src="jquery.base64.js"></script>
    <script type="text/javascript" src="html2canvas.js"></script>
    <script type="text/javascript" src="jspdf/libs/sprintf.js"></script>
    <script type="text/javascript" src="jspdf/jspdf.js"></script>
    <script type="text/javascript" src="jspdf/libs/base64.js"></script>
    <script type="text/javascript" src="js/script-download-all.js?rev=<?php echo time();?>"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- <link rel="stylesheet" type="text/css" href="css/style-menu-bar.css"> -->
    <link rel="stylesheet" type="text/css" href="css/style-laporan-gaji.css?rev=<?php echo time();?>" />
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">


    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="stylesheet" type="text/css" href="css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="css/demo.css" />
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <script src="js/modernizr.custom.js"></script>
</head>

<body>

    <?php error_reporting(E_ALL ^ E_NOTICE); ?>

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

        <div class="pilih no-print">
            <!-- Tambahkan form untuk mengelompokkan input select -->
            <form method="post">
                <!-- Tambahkan input select untuk memilih nama bulan -->
                <label for="bulan">Bulan :</label>
                <select id="bulan" name="bulan">
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
                        echo "<option value='$bulanNum' $selected>$bulanName</option>";
                    }
                    ?>
                </select>

                <!-- Tambahkan input select untuk memilih tahun -->
                <label class="tahun" for="tahun">Tahun :</label>
                <select id="tahun" name="tahun">
                    <?php
                    $tahunSekarang = date('Y');
                    for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                        $selected = ($i == $tahun) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>

                <!-- Tambahkan tombol "Tampilkan Data" -->
                <button type="submit" class="gaji">Tampilkan Data</button>
            </form>
        </div>


        <div class="row">
            <div class="btn-group pull-right no-print" style="padding: 10px;">
                <div class="dropdown">
                    <?php
                    function isLocked($bulan, $tahun)
                    {
                        include 'conn.php';

                        $queryKunci = "SELECT * FROM kunci_gaji WHERE bulan = '$bulan' AND tahun = '$tahun' AND kunci = 'Lock'";
                        $resultKunci = $conn->query($queryKunci);

                        if (!$resultKunci) {
                            die("Query execution failed: " . $conn->error);
                        }

                        return $resultKunci->num_rows > 0;
                    }
                    function isDataGenerated($bulan, $tahun)
                    {
                        include 'conn.php';

                        $queryGenerate = "SELECT * FROM kunci_gaji WHERE bulan = '$bulan' AND tahun = '$tahun' AND kunci = 'Lock'";
                        $resultGenerate = $conn->query($queryGenerate);

                        if (!$resultGenerate) {
                            die("Query execution failed: " . $conn->error);
                        }

                        return $resultGenerate->num_rows > 0;
                    }
                    ?>

                    <button id="generate" class="btn btn-default" onclick="generateDataAndCashbon()" <?php echo isDataGenerated($bulan, $tahun); ?>><i class="fa-solid fa-list-check"></i>Generate Data</button>
                    <button id="lock" class="btn btn-default" onclick="lockData()" <?php echo isLocked($bulan, $tahun); ?>><i class="fas fa-lock"></i>Lock Data</button>
                    <button id="unlock" class="btn btn-default" onclick="unlockData()" <?php echo isLocked($bulan, $tahun); ?>><i class="fas fa-lock-open"></i>Unlock Data</button>
                    <button id="lp3" class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <i class="fas fa-download"></i>Simpan
                    </button>
                    <ul class="dropdown-menu" id="pr" aria-labelledby="dropdownMenu1">
                        <li><a href="#" onclick="exportToExcel();"><i class="fas fa-file-excel"></i>XLS</a></li>
                        <li><a href="#" onclick="exportToCSV();"><i class="fas fa-file-csv"></i>CSV</a></li>
                        <li><a href="#" onclick="exportToTXT();"><i class="fas fa-file-lines"></i>TXT</a></li>
                        <li><a href="#" onclick="printPage();"><i class="fas fa-print"></i>PRINT</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row" style="height: auto !important;">
            <table id="employees" class="table table-striped">
                <tr>
                    <th colspan="13" class="judul">Laporan Gaji Bulanan <?php echo $bulan . "/" . $tahun; ?></th>
                </tr>
                <tr>
                    <th>No</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Gaji Pokok</th>
                    <th class="hide-m">Gaji Mingguan</th>
                    <th class="hide-m">Total Tunjangan</th>
                    <th class="hide-m">Total Denda</th>
                    <th class="hide-m">Bayar Cashbon</th>
                    <th class="hide-m">Nama Bank</th>
                    <th class="hide-m">Atas Nama</th>
                    <th class="hide-m">Nomor Rekening</th>
                    <th>Total Gaji</th>
                    <th class="no-print" id="act">Action</th>
                </tr>
                <?php

                $sum = 0;
                $totalSUM = 0;
                $nomor_urut = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    $nip = $row['nip'];
                    $gaji = $row['gaji_pokok'];
                    $tunjangan = $row['tunjangan'];
                    $namaBank = $row['nama_bank'];
                    $nik = $row['nik'];


                    // Periksa apakah data sudah ada di rincian_gaji
                    $checkQuery = "SELECT * FROM rincian_gaji WHERE nip = '$nip' AND MONTH(rincian_gaji.tanggal) = '$bulan' AND YEAR(rincian_gaji.tanggal) = '$tahun'";
                    $checkResult = mysqli_query($conn, $checkQuery);


                    while ($data = $checkResult->fetch_assoc()) {
                        include 'get-tunjangan-masa-kerja.php';
                        $totalTunjangan = $tunjangan + $dataTMK['tunjangan_masa_kerja'] + $data['tunjangan_lainnya'];

                ?>
                        <tr>
                            <td style="text-align:center;"><?php echo $nomor_urut;?></td>
                            <td style="text-align:center;"><?php echo $nik; ?></td>
                            <td><?php echo $row['nama']; ?></td>
                            <td style="text-align:center;"><?php echo "Rp " . number_format($data['gaji'], 0, ',', '.');?></td>
                                <?php
                                    $m1 = 0;
                                    if($row['jenis_gaji'] === 'mingguan'){
                                        $m1 = $data['m1'];
                                ?>
                                    <td class="hide-m" style="text-align:center;"><?php echo "Rp " . number_format($m1, 0, ',', '.');?></td>
                                <?php
                                    }
                                    else {
                                        echo '<td class="hide-m" style="text-align:center;">-</td>';
                                    }
                                ?>

                            <td class="hide-m"><?php echo "Rp " . number_format($totalTunjangan, 0, ',', '.'); ?></td>
                            <td class="hide-m"><?php echo "Rp " . number_format($data['denda'], 0, ',', '.'); ?></td>
                            <!-- <td class="hide-m"><?php echo "Rp " . number_format($data['cashbon'], 0, ',', '.'); ?></td> -->
                            <td class="hide-m"><?php
                                $idCB = $data['id_cashbon'];
                                $gaji_rg = $data['gaji'];
                                if ($gaji_rg == 0.00) {
                                    $gaji = $gaji;
                                } else {
                                    $gaji = $gaji_rg;
                                }
                                $que = "SELECT * FROM bayar_cashbon WHERE nip = '$nip' AND MONTH(bayar_cashbon.tanggal) = '$bulan' AND YEAR(bayar_cashbon.tanggal) = '$tahun'";
                                $res = mysqli_query($conn, $que);
                                $bayar = 0;
                                if($dataCB = $res->fetch_assoc()) {
                                    $bayar = $dataCB['bayar'];
                                    echo "Rp ". number_format($bayar, 0, ',', '.'); 
                                }
                                else{
                                    echo "Rp " . $bayar;
                                }
                                $totalGaji = $gaji - $m1 + $totalTunjangan - $data['denda'] - $bayar;
                                $totalSUM += (int)$totalGaji;
                            ?></td>
   
                            <td style="text-align:center;" class="hide-m"><?php include 'get-nama-bank.php';
                                                echo $nb; ?></td>
                            <td class="hide-m"><?php echo $row['nama_pemilik_rekening']; ?></td>
                            <td class="hide-m" style="text-align:center;"><?php echo $row['nomor_rekening']; ?></td>
                            <td style="text-align:center; font-weight:bold;"><?php echo "Rp " . number_format($totalGaji, 0, ',', '.'); ?></td>
                            <td style="text-align:center;" class="no-print">
                                <a href="sa-data-view-profile.php?nip=<?php echo $row['nip']; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="button2"><i class="fas fa-magnifying-glass" id="b"></i></a>
                                <button onclick="deleteGaji('<?php echo $data['id_rincian_gaji']; ?>')"><i class="fas fa-trash" id="c"></i></button>
                            </td>
                        </tr>

                 <?php
                    }
                    $sum += $totalSUM;
                    // $totalSUM = "Rp " . number_format($sum, 0, ',', '.');
                    $nomor_urut++;
                }
                ?>
                <tr>
                    <td style="font-weight:bold;">TOTAL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="hide-m"></td>
                    <td class="hide-m"></td>
                    <td class="hide-m"></td>
                    <td class="hide-m"></td>
                    <td class="hide-m"></td>
                    <td class="hide-m"></td>
                    <td class="hide-m"></td>
                    <td class="total" style="text-align:center;"><?php echo "Rp " . number_format($totalSUM, 0, ',', '.'); ?></td>
                    <td class="hide-m"></td>
                </tr>
            </table>
        </div>
    </div><div class="footer">
    Copyrights © Gravitti Technology 2023<br>All Rights Reserved
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/classie.js"></script>
    <script src="js/gnmenu.js"></script>
    <script>
        new gnMenu(document.getElementById('gn-menu'));
    </script>
    <script>
        function deleteGaji(id_rincian_gaji) {
            if (confirm("Are you sure you want to delete this data?")) {
                window.location.href = "laporan-gaji.php?deleteID=" + id_rincian_gaji;
            }
        }

        function tampilkanData() {
            var bulan = document.getElementById("bulan").value;
            var tahun = document.getElementById("tahun").value;
            window.location.href = "laporan-gaji.php?bulan=" + bulan + "&tahun=" + tahun;
        }

        function updateButtonStatus() {
            var buttonGenerate = document.getElementById("generate");
            var buttonLock = document.getElementById("lock");
            var buttonUnlock = document.getElementById("unlock");

            var isGenerated = <?php echo isDataGenerated($bulan, $tahun) ? 'true' : 'false'; ?>;
            var isButtonLocked = <?php echo isLocked($bulan, $tahun) ? 'true' : 'false'; ?>;

            if (isGenerated) {
                buttonGenerate.style.display = "none";
                buttonLock.style.display = isButtonLocked ? "none" : "inline";
                buttonUnlock.style.display = isButtonLocked ? "inline" : "none";
            } else {
                buttonGenerate.style.display = "inline";
                buttonLock.style.display = "inline";
                buttonUnlock.style.display = "none";
            }
        }

        // Panggil fungsi saat halaman dimuat
        updateButtonStatus();

        // function generateData() {
        //     var bulan = document.getElementById("bulan").value;
        //     var tahun = document.getElementById("tahun").value;

        //     // Kirim permintaan AJAX
        //     var xhr = new XMLHttpRequest();
        //     xhr.open("GET", "generate-data.php?bulan=" + bulan + "&tahun=" + tahun, true);
        //     xhr.open("GET", "generate-cb.php?bulan=" + bulan + "&tahun=" + tahun, true);
        //     xhr.onreadystatechange = function() {
        //         if (xhr.readyState === XMLHttpRequest.DONE) {
        //             if (xhr.status === 200) {
        //                 // Berhasil, lakukan sesuatu jika perlu
        //                 // alert("Data gaji telah dikunci dan diperbarui.");
        //                 location.reload();
        //             } else {
        //                 // Ada kesalahan, beri tahu pengguna
        //                 alert("Terjadi kesalahan saat mengunci data gaji.");
        //                 location.reload();
        //             }
        //         }
        //     };
        //     xhr.send();
        // }

function generateDataAndCashbon() {
    var bulan = document.getElementById("bulan").value;
    var tahun = document.getElementById("tahun").value;

    // Kirim permintaan AJAX untuk generate-data.php
    var xhr1 = new XMLHttpRequest();
    xhr1.open("GET", "generate-data.php?bulan=" + bulan + "&tahun=" + tahun, true);
    xhr1.onreadystatechange = function() {
        if (xhr1.readyState === XMLHttpRequest.DONE) {
            if (xhr1.status === 200) {
                // Berhasil, lakukan sesuatu jika perlu
                generateCashbon(bulan, tahun); // Panggil generateCashbon setelah generate-data.php berhasil
            } else {
                // Ada kesalahan, beri tahu pengguna
                alert("Terjadi kesalahan saat mengunci data gaji.");
                location.reload();
            }
        }
    };
    xhr1.send();
}

function generateCashbon(bulan, tahun) {
    // Kirim permintaan AJAX untuk generate-cb.php
    var xhr2 = new XMLHttpRequest();
    xhr2.open("GET", "generate-cb.php?bulan=" + bulan + "&tahun=" + tahun, true);
    xhr2.onreadystatechange = function() {
        if (xhr2.readyState === XMLHttpRequest.DONE) {
            if (xhr2.status === 200) {
                // Berhasil, lakukan sesuatu jika perlu
                alert("Data berhasil diupdate.");
                location.reload();
            } else {
                // Ada kesalahan, beri tahu pengguna
                alert("Terjadi kesalahan saat mengupdate data.");
                location.reload();
            }
        }
    };
    xhr2.send();
}


        // Fungsi untuk mengunci data gaji
        function lockData() {
            var bulan = document.getElementById("bulan").value;
            var tahun = document.getElementById("tahun").value;

            // Kirim permintaan AJAX
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "lock-data.php?bulan=" + bulan + "&tahun=" + tahun, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        // Berhasil, lakukan sesuatu jika perlu
                        // alert("Data gaji telah dikunci dan diperbarui.");
                        location.reload();
                    } else {
                        // Ada kesalahan, beri tahu pengguna
                        alert("Terjadi kesalahan saat mengunci data gaji.");
                        location.reload();
                    }
                }
            };
            xhr.send();
        }

        function unlockData() {
            var bulan = document.getElementById("bulan").value;
            var tahun = document.getElementById("tahun").value;

            // Kirim permintaan AJAX
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "unlock-data.php?bulan=" + bulan + "&tahun=" + tahun, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        // Berhasil, lakukan sesuatu jika perlu
                        // alert("Data gaji telah dikunci dan diperbarui.");
                        location.reload();
                    } else {
                        // Ada kesalahan, beri tahu pengguna
                        alert("Terjadi kesalahan saat mengunci data gaji.");
                        location.reload();
                    }
                }
            };
            xhr.send();
        }
    </script>

<script>
    $(document).ready(function() {
        $(".show-more-button").click(function() {
            var id = $(this).data("id");
            $(".hidden-row[data-id='" + id + "']").toggle();
        });
    });
</script>

</body>

</html>