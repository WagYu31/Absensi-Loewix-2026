<?php
session_start();
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST["bulan"];
    $tahun = $_POST["tahun"];
} else {
    $currentMonth = date('m');
    $currentYear = date('Y');

    if ($currentMonth == '01') {
        $bulan = '12';
        $tahun = $currentYear - 1;
    } else {
        $bulan = str_pad($currentMonth - 1, 2, '0', STR_PAD_LEFT);
        $tahun = $currentYear;
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <script src="../js/script.js" defer></script>
    <script type="text/javascript" src="../tableExport.js"></script>
    <script type="text/javascript" src="../jquery.base64.js"></script>
    <script type="text/javascript" src="../html2canvas.js"></script>
    <script type="text/javascript" src="../jspdf/libs/sprintf.js"></script>
    <script type="text/javascript" src="../jspdf/jspdf.js"></script>
    <script type="text/javascript" src="../jspdf/libs/base64.js"></script>
    <script type="text/javascript" src="../js/script-download-all.js?rev=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- <link rel="stylesheet" type="text/css" href="css/style-menu-bar.css"> -->
    <link rel="stylesheet" type="text/css" href="../css/style-data-karyawan.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="../css/foot.css?rev=<?php echo time(); ?>">


    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="stylesheet" type="text/css" href="../css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="../css/demo.css" />
    <link rel="stylesheet" type="text/css" href="../css/component.css" />
    <script src="../js/modernizr.custom.js"></script>

    <style>
        .highlight td {
            background-color: #ffff66;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 15vh;
            margin-bottom: 5vh;
        }

        a.bt-data {
            padding: 7px;
            font-size: 1.3rem;
            border-radius: 3px;
        }

        a.bt-data:hover {
            text-decoration: none;
        }

        .formDat,
        .form-control,
        .btn {
            font-size: 1.4rem;
        }
    </style>

</head>

<body>
    <div class="container no-print">
        <?php include "gn-menu.php"; ?>
    </div><!-- /container -->

    <div class="container form-container">
        <div class="row mb-3">
            <div class="col-12 mb-4">
                <div class="pilih no-print">
                    <form method="post" class="p-4 bg-white formDat">
                        <div class="form-group mb-3">
                            <label for="bulan" class="form-label">Bulan:</label>
                            <select id="bulan" name="bulan" class="form-control">
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
                        </div>

                        <div class="form-group mb-3">
                            <label for="tahun" class="form-label">Tahun:</label>
                            <select id="tahun" name="tahun" class="form-control">
                                <?php
                                $tahunSekarang = date('Y');
                                for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                                    $selected = ($i == $tahun) ? 'selected' : '';
                                    echo "<option class='thn' value='$i' $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" class="btn btn-primary" id="bs">Tampilkan Data</button>
                        </div>
                    </form>


                </div>
            </div>
            <div class="col-9">
                <h2>Data Absen</h2>
            </div>
            <div class="col-3 text-right">
                <a href="input-to-database.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="btn-primary bt-data">Data Ini Sudah Benar</a>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center align-middle">NIK</th>
                    <th class="text-center align-middle">Nama</th>
                    <th>Tanggal</th>
                    <th>Tidak Absen</th>
                    <th class="text-center align-middle" style="background:#ddd;">Total Denda</th>
                    <th>Keterangan</th>
                    <th>Cek</th>
                </tr>
            </thead>
            <tbody>
                <?php

                // Query data absen
                $sql = "SELECT * FROM karyawan WHERE nip != '001' AND nip != '70326' AND nik != '114' AND status_karyawan = 'aktif' ORDER BY nama ASC";
                $result = $conn->query($sql);

                $jumlah_telat = 0;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $nik = $row['nik'];
                        $karNip = $row['nip'];
                        echo "<tr>";
                        
                        
                        include "validasi-terlambat.php";

                        echo "</tr>";
                    }
            // Tambahkan script untuk alert dan redirect setelah tabel selesai ditampilkan
            echo "<script>
                    alert('Data berhasil disimpan');
                    window.location.href = 'data-absen.php';
                  </script>";
                } else {
                    echo "<tr><td colspan='3'>Tidak ada data absen</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    <div class="footer">
        Copyrights © Gravitti Technology 2023<br>All Rights Reserved
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/classie.js"></script>
    <script src="../js/gnmenu.js"></script>
    <script>
        new gnMenu(document.getElementById('gn-menu'));
    </script>
    <script>
        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>

</html>