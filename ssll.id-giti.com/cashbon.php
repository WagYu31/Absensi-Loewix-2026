<?php
session_start();
if (!isset($_SESSION['nip'])) {
    header('Location: index.php');
    exit();
}

include 'conn.php';
include 'get-kar-login-data.php';
$nip = "63456";
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
    <link rel="stylesheet" type="text/css" href="css/style-tambah-cashbon.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time(); ?>">
    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="stylesheet" type="text/css" href="css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="css/demo.css" />
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <script src="js/modernizr.custom.js"></script>

</head>

<body>

    <?php 
        include 'gn-menu.php';
        include 'conn.php';
    ?>

    <div class="containt">
        
        <form action="proses-cashbon.php" method="POST" enctype="multipart/form-data">
            <h2 class="cb">Cashbon</h2>
        
            <label for="nip-denda">Nama:</label>
            <input type="text" value="<?php echo htmlspecialchars($nama); ?>" disabled>
            <input type="hidden" value="<?php echo htmlspecialchars($nip); ?>" id="nip-denda" name="nip_denda">
        
            <label for="jumlah-denda">Jumlah:</label>
            <input type="number" id="jumlah-denda" name="jumlah_denda" required>
        
            <label for="pembayaran">Pembayaran:</label>
            <input type="number" id="bayar" name="bayar" placeholder="Dicicil   . . .   bulan" required>
        
            <label for="keterangan-denda">Keterangan:</label>
            <textarea id="keterangan-denda" name="keterangan_denda" required></textarea>
        
            <input type="submit" value="Tambah Cashbon">
        </form>
        
        <div class="tab">
            <div class="filter-buttons">
                <button id="btn-pengajuan">Pengajuan</button>
                <button id="btn-belum-lunas" class="active">Belum Lunas</button>
                <button id="btn-lunas">Lunas</button>
            </div>

            <div class="tgl">
                <?php
                $date = date('d-m-Y');
                echo "Tanggal : " . $date;
                ?>
            </div>
            
        <table id='pengajuan'>
            <thead>
                <tr>
                    <!--<th>No</th>-->
                    <th>No</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Cicilan</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $qcb = "SELECT * FROM pengajuan_cashbon WHERE nip = '$nip'";
                    $rescbon = mysqli_query($conn, $qcb);
                    $no = 1;
                    while($rowbon = mysqli_fetch_assoc($rescbon)){
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        echo "<td style='text-align:left;'>" . htmlspecialchars($rowbon['keterangan']) . "</td>";
                        echo "<td>Rp " . number_format($rowbon['jumlah'], 0, ',', '.') . "</td>";
                        echo "<td>" . htmlspecialchars($rowbon['cicil']) . " kali</td>";
                        echo "<td style='text-transform:capitalize;'>" . htmlspecialchars($rowbon['status']) . "</td>";
                        echo "<td>
                                <button onclick=\"viewDetails('" . htmlspecialchars($rowbon['id_pc']) . "')\">
                                    <i class='fas fa-magnifying-glass' id='b'></i>
                                </button>
                                <button onclick=\"confirmDelete('" . htmlspecialchars($rowbon['id_pc']) . "')\">
                                    <i class='fas fa-trash' id='c'></i>
                                </button>
                              </td>";
                        echo "</tr>";
                        $no++;
                    }
                ?>
            </tbody>
        </table>
        
        
        <table id='belum-lunas'>
            <thead>
                <tr>
                    <!--<th>No</th>-->
                    <th>No</th>
                    <th>Nama</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Cicilan</th>
                    <th>Sisa Cicilan</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $qcbbl = "SELECT cashbon.*, karyawan.nama, karyawan.nip FROM cashbon JOIN karyawan ON cashbon.nip = karyawan.nip WHERE cashbon.nip = '$nip' AND lunas != 'Y'";
                    $rescbonbl = mysqli_query($conn, $qcbbl);
                    $nobl = 1;
                    while($rowbonbl = mysqli_fetch_assoc($rescbonbl)){
                    $idCB = $rowbonbl['id_cashbon'];
                    $cicilan = $rowbonbl['jumlah'] / $rowbonbl['cicil'];
                    $queque = "SELECT  SUM(bayar) AS total_bayar FROM bayar_cashbon WHERE id_cashbon = $idCB";
                    $result_queque = $conn->query($queque);
                    $row2 = $result_queque->fetch_assoc();

                    // Mendapatkan akumulasi nilai bayar
                    $akumulasiBayar = $row2['total_bayar'];

                    $sisa = $rowbonbl['jumlah'] - $akumulasiBayar;
                        echo "<tr>";
                        echo "<td>" . $nobl . "</td>";
                        echo "<td style='text-align:left;'>" . htmlspecialchars($rowbonbl['nama']) . "</td>";
                        echo "<td style='text-align:left;'>" . htmlspecialchars($rowbonbl['keterangan']) . "</td>";
                        echo "<td>Rp " . number_format($rowbonbl['jumlah'], 0, ',', '.') . "</td>";
                        echo "<td>" . htmlspecialchars($rowbonbl['cicil']) . " kali</td>";
                        echo "<td>Rp " . number_format($sisa, 0, ',', '.') . "</td>";
                        echo "<td><a href='sa-view-cashbon.php?id_cashbon=" . htmlspecialchars($rowbonbl['id_cashbon']) . "'>
                                <i class='fas fa-magnifying-glass' id='b'></i>
                              </a></td>";
                        echo "</tr>";
                        $nobl++;
                    }
                ?>
            </tbody>
        </table>
        
        
        <table id='lunas'>
            <thead>
                <tr>
                    <!--<th>No</th>-->
                    <th>No</th>
                    <th>Nama</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Cicilan</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $qcbl = "SELECT cashbon.*, karyawan.nama, karyawan.nip FROM cashbon JOIN karyawan ON cashbon.nip = karyawan.nip WHERE cashbon.nip = '$nip' AND lunas = 'Y'";
                    $rescbonl = mysqli_query($conn, $qcbl);
                    $nol = 1;
                    while($rowbonl = mysqli_fetch_assoc($rescbonl)){
                        echo "<tr>";
                        echo "<td>" . $nol . "</td>";
                        echo "<td style='text-align:left;'>" . htmlspecialchars($rowbonl['nama']) . "</td>";
                        echo "<td style='text-align:left;'>" . htmlspecialchars($rowbonl['keterangan']) . "</td>";
                        echo "<td>Rp " . number_format($rowbonl['jumlah'], 0, ',', '.') . "</td>";
                        echo "<td>" . htmlspecialchars($rowbonl['cicil']) . " kali</td>";
                        echo "<td><a href='sa-view-cashbon.php?id_cashbon=" . htmlspecialchars($rowbonbl['id_cashbon']) . "'>
                                <i class='fas fa-magnifying-glass' id='b'></i>
                              </a></td>";
                        echo "</tr>";
                        $no++;
                    }
                ?>
            </tbody>
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
</body>

</html>