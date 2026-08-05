<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai admin
if (!isset($_SESSION['nip'])) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin atau superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

$role = $_SESSION['role'];
if (isset($_GET['nik'])) {
    $nik = $_GET['nik'];
} else {
    echo "NIK tidak terdaftar!";
}

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
            /*padding: 20px;*/
            padding-bottom:20px;
            padding-top: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 15vh;
            margin-bottom: 5vh;
        }

        .card-custom {
            border: none;
            border-radius: 10px;
            /* box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); */
            border: 1px solid #444;
            transition: transform 0.2s ease-in-out;
        }

        .card-custom:hover {
            transform: translateY(-5px);
        }

        .card-body-custom {
            padding: 20px;
        }

        .card-text-custom {
            font-size: 1.1rem;
            color: #555;
        }

        .card-text-custom:hover {
            font-size: 1.3rem;
        }

        .font-weight-bold {
            font-weight: 700;
            color: #333;
        }

        .card-custom p {
            margin: 0;
        }

        .total-denda {
            font-size: 1.5rem;
            color: #e74c3c;
        }

        .total-denda:hover {
            font-size: 1.7rem;
        }

        button.tampilData {
            padding: 5px;
        }

        /* Custom styles for the form */
        form.bt {
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 0px;
            width:90%;
        }

        form.bt .form-group label {
            margin-left: 20px;
        }

        form.bt .tampilData {
            margin-right: 10px;
        }

        form.bt .btn-secondary {
            margin-left: 10px;
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
								<li>
									<a href="../profile-karyawan.php"><i class="fa-solid fa-users" id="mn"></i>Profile</a>
								</li>
								<li>
									<a href="detail-absen-kar.php?nik=<?php echo $nik;?>"><i class="fa-solid fa-thumbs-up" id="mnn"></i>Data Absen</a>
								</li>
								<li><a href="../riwayat-gaji.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Slip Gaji</a></li>
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
				<li><a class="codrops-icon" href="../logout.php"><i class="fa-solid fa-right-to-bracket" id="mnn"></i><span>Log Out</span></a></li>
				<!-- <li><a class="codrops-icon codrops-icon-drop" href="http://tympanus.net/codrops/?p=16030"><span>Back to the Codrops Article</span></a></li> -->
			</ul>
    </div><!-- /container -->

    <div class="container mt-5 form-container">
        <div class="row">
            <div class="col-12" style="margin-left:25px; margin-top: 4vh;">
                <?php
                $getNama = "SELECT 
                a.nip, 
                a.pin, 
                k.nik, 
                k.pin_absen,
                k.nama AS nama_karyawan
            FROM 
                absen a
            JOIN 
                karyawan k ON a.nip = k.nik
            WHERE
                k.nik = '$nik'";
                $resultNama = $conn->query($getNama);
                $dataNama = $resultNama->fetch_assoc();
                $namaData = $dataNama['nama_karyawan'];
                ?>
                <h2><?php echo $namaData . " (NIK : " . $nik . ")"; ?></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="pilih no-print">

                    <!-- Tambahkan form untuk mengelompokkan input select -->
                    <form method="post" class="bt" style="border:none;box-shadow:none;">
                        <!-- Input select for month -->
                        <div class="form-group row">
                            <label for="bulan" class="col-sm-2 col-form-label">Bulan :</label>
                            <div class="col-sm-10">
                                <select id="bulan" name="bulan" class="form-control" style="margin-left:18px;">
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
                            </div>
                        </div>

                        <!-- Input select for year -->
                        <div class="form-group row">
                            <label for="tahun" class="col-sm-2 col-form-label">Tahun :</label>
                            <div class="col-sm-10">
                                <select id="tahun" name="tahun" class="form-control" style="margin-left:18px;">
                                    <?php
                                    $tahunSekarang = date('Y');
                                    for ($i = $tahunSekarang; $i >= $tahunSekarang - 15; $i--) {
                                        $selected = ($i == $tahun) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Button to submit and print -->
                        <div class="form-group row">
                            <div class="col-sm-10 offset-sm-2">
                                <button type="submit" class="btn btn-primary tampilData" id="bs" style="margin-left:18px;">Tampilkan Data</button>
                                <a href="#" onclick="printData()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> PRINT</a>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

                                    <?php
                                    $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']);

                                    // Include the appropriate menu file based on device size
                                    if ($isMobile) {
                                        include "detail-absen-kar-mobile.php";
                                    } else {
                                        include "detail-absen-kar-dekstop.php";
                                    }
                                    ?>
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