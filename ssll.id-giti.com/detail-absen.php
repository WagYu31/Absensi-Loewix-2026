<?php
session_start();

// Cek apakah pengguna telah login dan memiliki peran sebagai admin
if (!isset($_SESSION['nip'])) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin atau superadmin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

$nip = $_SESSION['nip'];
$role = $_SESSION['role'];

// Gunakan prepared statement untuk mencegah SQL Injection
$getNik = $conn->prepare("SELECT nik FROM karyawan WHERE nip = ?");
$getNik->bind_param("s", $nip);
$getNik->execute();
$resNik = $getNik->get_result();
$rowNik = $resNik->fetch_assoc();
$nik = $rowNik['nik'];

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
            padding: 20px;
            padding-top: 10px;
            border-radius: 8px;
            /*box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);*/
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
            padding: 20px;
            border-radius: 5px;
            /*box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);*/
            margin-top: 0px;
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
    <div class="container">
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
								<li><a href="riwayat-gaji.php"><i class="fa-solid fa-hand-holding-dollar" id="mnn"></i>Slip Gaji</a></li>
								<li><a href="detail-absen.php"><i class="fa-solid fa-receipt" id="mnn"></i>Rincian Absensi</a></li>

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
                    <form method="post" class="bt">
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

    <div class="container mt-5">
        <div class="table-responsive">
            <table class="table table-bordered" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>No</th>
                    <!-- <th>PIN</th>
                    <th>NIP</th>
                    <th>Nama</th> -->
                    <th>Hari / Tanggal</th>
                    <th>Shifting</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Terlambat</th>
                    <th>Jam Kerja</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php

                $sql = "SELECT 
                            MIN(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_scan, 
                            a.nip, 
                            a.pin, 
                            k.nik, 
                            k.nama AS nama_karyawan, 
                            k.shifting
                        FROM 
                            absen a
                        JOIN 
                            karyawan k ON a.nip = k.nik
                        WHERE
                            k.nik = '$nik' AND
                            MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = $bulan AND
                            YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = $tahun
                        GROUP BY 
                            a.nip, DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%m-%d')";
                $result = $conn->query($sql);

                $jumlah_telat = 0;
                $no = 1;
                    $denda_absen = 0;
                    $jumlah_terlambat = 0;
                    $jumlah_tidak_absen_masuk = 0;
                    $jumlah_tidak_absen_pulang = 0;
                    $jumlah_izin_jam_kerja = 0;
                    $jam_scan = "";
                    $jam_out = "";
                    $shifting = "";
                    $hari_scan1 = "";
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $tgl_scan = date('d-m-Y H:i:s', strtotime($row['tgl_scan']));
                        $waktu_scan = date('H:i', strtotime($row['tgl_scan']));
                        $jam_scan = date('H:i', strtotime($row['tgl_scan']));
                        $tgl_only = date('d-m-Y', strtotime($tgl_scan));
                        $cek_tgl = date('Y-m-d', strtotime($tgl_scan));
                        $query = "SELECT MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
                            FROM absen 
                            WHERE nip = '" . $row['nip'] . "' AND DATE_FORMAT(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'), '%d-%m-%Y') = '" . $tgl_only . "'";
                        $res = $conn->query($query);
                        $data = $res->fetch_assoc();
                        $tgl_out = date('d-m-Y H:i:s', strtotime($data['tgl_out']));
                        $waktu_out = date('H:i', strtotime($data['tgl_out']));
                        $jam_out = date('H:i', strtotime($data['tgl_out']));
                        $shifting = $row["shifting"];

                        $hari_scan = date('l', strtotime($tgl_scan));
                        setlocale(LC_TIME, 'id_ID.UTF-8');

                        if($hari_scan == "Monday"){
                            $hari_scan1 = "Senin";
                        }
                        elseif($hari_scan == "Tuesday"){
                            $hari_scan1 = "Selasa";
                        }
                        elseif($hari_scan == "Wednesday"){
                            $hari_scan1 = "Rabu";
                        }
                        elseif($hari_scan == "Thursday"){
                            $hari_scan1 = "Kamis";
                        }
                        elseif($hari_scan == "Friday"){
                            $hari_scan1 = "Jumat";
                        }
                        elseif($hari_scan == "Saturday"){
                            $hari_scan1 = "Sabtu";
                        }
                        elseif($hari_scan == "Sunday"){
                            $hari_scan1 = "Minggu";
                        }
                        else{
                            $hari_scan1 = $hari_scan;
                        }

                        $shifting = $row["shifting"];
                        $pinAbsen = $row["pin"];

                        include "req_shift_db.php";

                        if ($hari_scan == "Saturday" && $shifting != "T") {
                            $shifting = "W";
                        } elseif ($hari_scan == "Saturday" && $shifting == "T") {
                            $shifting = "TW";
                        }
                        
                        if ($waktu_scan == $waktu_out && strtotime($waktu_scan) > strtotime("12:00")) {
                            $jam_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $tgl_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $jumlah_tidak_absen_masuk++;
                        } elseif ($waktu_scan != $waktu_out && strtotime($waktu_scan) > strtotime("12:00")) {
                            $jam_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $tgl_scan = "<span class='text-danger'>Tidak Absen Masuk</span>";
                            $jumlah_tidak_absen_masuk++;
                        }

                        if ($waktu_scan == $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
                            $jam_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $tgl_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $jumlah_tidak_absen_pulang++;
                        } elseif ($waktu_scan != $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
                            $jam_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $tgl_out = "<span class='text-danger'>Tidak Absen Pulang</span>";
                            $jumlah_tidak_absen_pulang++;
                        }
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        echo "<td style='text-align:left;'>" . $hari_scan1 . ", " . $tgl_only . "</td>";

                        if ($shifting == "P") {
                            $shifting_1 = "Shift 1 (07.00 s/d 16.00)";
                        } elseif ($shifting == "M") {
                            $shifting_1 = "Shift 2 (08.30 s/d 17.30)";
                        } elseif ($shifting == "S") {
                            $shifting_1 = "Shift 3 (09.30 s/d 18.30)";
                        } elseif ($shifting == "T") {
                            $shifting_1 = "Harco (09.10 s/d 18.00)";
                        } elseif ($shifting == "W") {
                            $shifting_1 = "Sabtu (8.30 s/d 13.00)";
                        } elseif ($shifting == "TW") {
                            $shifting_1 = "Harco Sabtu (9.10 s/d 14.00)";
                        } else {
                            $shifting_1 = $shifting;
                        }

                        echo "<td style='text-align:left;'>" . $shifting_1 . "</td>";

                        echo "<td>" . $jam_scan . "</td>";
                        echo "<td>" . $jam_out . "</td>";

                        $waktu_masuk_unix = "";
                        if ($shifting == "P") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 07:00:00");
                        } elseif ($shifting == "M") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 08:30:00");
                        } elseif ($shifting == "S") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 09:30:00");
                        } elseif ($shifting == "T") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 09:10:00");
                        } elseif ($shifting == "W") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 08:30:00");
                        } elseif ($shifting == "TW") {
                            $waktu_masuk_unix = strtotime(date('d-m-Y') . " 09:10:00");
                        }

                        $tgl_scan_unix = strtotime(date('d-m-Y') . " " . $waktu_scan);

                        $keterlambatan_detik = $tgl_scan_unix - $waktu_masuk_unix;

                        $keterlambatan_menit = floor($keterlambatan_detik / 60);
                        if ($keterlambatan_menit < 0) {
                            $keterlambatan_menit = 0;
                        } elseif ($tgl_scan == "<span class='text-danger'>Tidak Absen Masuk</span>") {
                            $keterlambatan_menit = 0;
                        }

                        $ket_izin = "";
                        include "izin_jam_kerja.php";

                        echo "<td>" . $keterlambatan_menit . " menit" . "</td>";

                        $jumlah_terlambat += $keterlambatan_menit;
                        $tgl_scan_unix = strtotime($tgl_scan);
                        $tgl_out_unix = strtotime($tgl_out);
                        $selisih_detik = $tgl_out_unix - $tgl_scan_unix;

                        $jam = floor($selisih_detik / (60 * 60));
                        $menit = floor(($selisih_detik - ($jam * 60 * 60)) / 60);
                        $detik = $selisih_detik - ($jam * 60 * 60) - ($menit * 60);

                        if ($tgl_scan == "<span class='text-danger'>Tidak Absen Masuk</span>" || $tgl_out == "<span class='text-danger'>Tidak Absen Pulang</span>") {
                            echo "<td>-</td>";
                        } else {
                            echo "<td>" . $jam . " jam " . $menit . " menit " . $detik . " detik" . "</td>";
                        }

                        echo "<td>" . $ket_izin . "</td>";

                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='4'>Tidak ada data absen</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
        </div>
        </div>
        <?php
        echo "<div class='row mb-5'>";
        
        echo "<div class='col-md-6'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom d-flex flex-column justify-content-center align-items-center text-center'>";
        echo "<p class='card-text card-text-custom'>Total Terlambat</p><p>" . $jumlah_terlambat . " menit</p>";
        $jumlah_denda = "";
        if ($jumlah_terlambat <= 20) {
            $jumlah_denda = 0;
        } elseif ($jumlah_terlambat > 20 && $jumlah_terlambat <= 80) {
            $jumlah_denda = ($jumlah_terlambat - 20) * 300;
        } elseif ($jumlah_terlambat > 80 && $jumlah_terlambat <= 140) {
            $jumlah_denda = (60 * 300) + (($jumlah_terlambat - 80) * 600);
        }elseif ($jumlah_terlambat > 140) {
            $jumlah_denda = (60 * 300) + (60 * 600) + (($jumlah_terlambat - 140) * 2000);
        }
        $jumlah_denda_rupiah = number_format($jumlah_denda, 0, ',', '.');
        echo "<p class='font-weight-bold'>Rp " . $jumlah_denda_rupiah . "</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "<div class='col-md-6'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom text-center d-flex flex-column justify-content-center align-items-center'>";
        $jumlah_tidak_absen = $jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang;
        echo "<p class='card-text card-text-custom'>Total Tidak Absen</p><p>" . $jumlah_tidak_absen . " x Rp 25,000</p>";
        $jumlah_tidak_absen_nominal = $jumlah_tidak_absen * 25000;
        $tidak_absen_rupiah = number_format($jumlah_tidak_absen_nominal, 0, ',', '.');
        echo "<p class='font-weight-bold'>" . $tidak_absen_rupiah . " </p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "</div>";

        // $total_all = $jumlah_denda + $jumlah_tidak_absen_nominal + $jumlah_izin_jam_kerja_nominal;
        $total_all = $jumlah_denda + $jumlah_tidak_absen_nominal;
        $total_all_total = number_format($total_all, 0, ',', '.');

        echo "<div class='row mb-5' style='margin-top: 4vh;'>";

        // echo "<div class='col-md-5'>";
        // echo "<div class='card card-custom shadow-sm'>";
        // echo "<div class='card-body card-body-custom d-flex flex-column justify-content-center align-items-start text-start'>";
        // echo "<p class='card-text font-weight-bold'>Keterangan Perhitungan Denda Terlambat</p>";
        // echo "<p>20 Menit pertama = Rp 0,- (Free)<br>";
        // echo "60 Menit selanjutnya = Rp 300,-/menit<br>";
        // echo "60 Menit selanjutnya = Rp 600,-/menit<br>";
        // echo "Selanjutnya = Rp 2,000,-/menit</p>";
        // echo "</div>";
        // echo "</div>";
        // echo "</div>";

        echo "<div class='col-md-7'>";
        echo "<div class='card card-custom shadow-sm'>";
        echo "<div class='card-body card-body-custom d-flex flex-column justify-content-center align-items-center text-center'>";
        echo "<p class='card-text font-weight-bold'>TOTAL DENDA</p>";
        echo "<p class='card-text font-weight-bold total-denda'>Rp " . $total_all_total . "</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "</div>";

        ?>
    </div>
    <div class="footer">
        Copyrights © Gravitti Technology 2023<br>All Rights Reserved
    </div>
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