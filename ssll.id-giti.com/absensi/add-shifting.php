<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai admin
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin atau superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];
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
        .label-shift{
            margin-left:5px;
        }
    </style>

</head>

<body>
    <div class="container no-print">
        
    <?php include "gn-menu.php"; ?>
    </div><!-- /container -->

    <div class="container mt-5 form-container">
        <h2>Data Absen</h2>
        <form method="post" action="update_shift.php">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:10%;">NIK</th>
                        <th style="width:10%;">PIN</th>
                        <th style="width:30%;">Nama Karyawan</th>
                        <th style="width:50%;">Shifting</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include "../conn.php";

                    // Query data absen
                    $sql = "SELECT * FROM karyawan WHERE 
                            pin_absen IS NOT NULL 
                            AND pin_absen <> 0 
                            AND status_karyawan = 'aktif'
                            ORDER BY nama ASC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td style='font-size:16px;'>" . $row["nik"] . "</td>";
                            echo "<td style='font-size:16px;'>" . $row["pin_absen"] . "</td>";
                            echo "<td style='font-size:16px; text-align:left;'>" . $row["nama"] . "</td>";
                            echo "<td style='text-align:left;'>";
                            echo "<input type='hidden' name='nik[]' value='" . $row['nik'] . "'>";
                            echo "<input type='hidden' name='nip[]' value='" . $row['nip'] . "'>";
                            echo "<div class='form-check form-check-inline'>";
                            if ($row['shifting'] == 'P') {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftP_" . $row['nik'] . "' value='P' checked>";
                            } else {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftP_" . $row['nik'] . "' value='P'>";
                            }
                            echo "<label class='form-check-label text-dark label-shift' for='shiftP_" . $row['nik'] . "'>Pagi (07:00 - 16:00)</label>";
                            echo "</div>";
                            // Menambahkan radio button untuk shift tengah
                            echo "<div class='form-check form-check-inline'>";
                            if ($row['shifting'] == 'M') {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftM_" . $row['nik'] . "' value='M' checked>";
                            } else {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftM_" . $row['nik'] . "' value='M'>";
                            }
                            echo "<label class='form-check-label text-dark label-shift' for='shiftM_" . $row['nik'] . "'>Tengah (08:30 - 17:30)</label>";
                            echo "</div>";
                            // Menambahkan radio button untuk shift siang
                            echo "<div class='form-check form-check-inline'>";
                            if ($row['shifting'] == 'S') {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftS_" . $row['nik'] . "' value='S' checked>";
                            } else {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftS_" . $row['nik'] . "' value='S'>";
                            }
                            echo "<label class='form-check-label text-dark label-shift' for='shiftS_" . $row['nik'] . "'>Siang (09:30 - 18:30)</label>";
                            echo "</div>";
                            // Menambahkan radio button untuk shift tengah
                            echo "<div class='form-check form-check-inline'>";
                            if ($row['shifting'] == 'T') {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftT_" . $row['nik'] . "' value='T' checked>";
                            } else {
                                echo "<input class='form-check-input input-shift' type='radio' name='shift_" . $row['nik'] . "' id='shiftT_" . $row['nik'] . "' value='T'>";
                            }
                            echo "<label class='form-check-label text-dark label-shift' for='shiftM_" . $row['nik'] . "'>Toko Harco (09:10 - 18:00)</label>";
                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Tidak ada data absen</td></tr>";
                    }
                    $conn->close();
                    ?>


                </tbody>
            </table>
            <div class="row">
                <div class="col-12" style="margin-left:20px;">
                    <button type="submit" class="btn btn-primary">Update Shifting</button>
                </div>
            </div>
        </form>
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