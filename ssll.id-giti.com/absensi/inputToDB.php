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
<?php include "head-input.php";?>

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
            <div class="col-9">
                <h2>Data Absen</h2>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center align-middle">NIK</th>
                    <th class="text-center align-middle">Nama</th>
                    <th class="text-center align-middle">Tidak Absen</th>
                    <th class="text-center align-middle" style="background:#ddd;">Total Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php

                include "../conn.php";

                // Query data absen
                $sql = "SELECT * FROM karyawan WHERE nip != '001' AND nip != '70326' AND nik != '114' AND status_karyawan = 'aktif' ORDER BY nama ASC";
                $result = $conn->query($sql);

                $jumlah_telat = 0;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $nik = $row['nik'];
                        echo "<tr>";
                        echo "<td>" . $nik . "</td>";
                        echo "<td><a href='detail-absen.php?nik=" . $nik . "'>" . $row["nama"] . "</a></td>"; // Perbaikan pada bagian href
                        $jumlah_terlambat = 0;

                        $sql2 = "SELECT 
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
                                    MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan' AND 
                                    YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun'
                                GROUP BY 
                                    a.nip, DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%m-%d')";
                        $result2 = $conn->query($sql2);
                        
                        if ($result2->num_rows > 0) {
                            $jumlah_tidak_absen_masuk = 0;
                            $jumlah_tidak_absen_pulang = 0;
                            $jumlah_izin_jam_kerja = 0;
                            $ket_izin = "";
                            while ($row2 = $result2->fetch_assoc()) {
                                $tgl_scan = date('d-m-Y H:i:s', strtotime($row2['tgl_scan']));
                                $waktu_scan = date('H:i', strtotime($row2['tgl_scan']));
                                $tgl_only = date('d-m-Y', strtotime($tgl_scan));
                                $cek_tgl = date('Y-m-d', strtotime($tgl_scan));
                                $query = "SELECT MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
                                            FROM absen 
                                            WHERE nip = '" . $row2['nip'] . "' AND DATE_FORMAT(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'), '%d-%m-%Y') = '" . $tgl_only . "'";
                                $res = $conn->query($query);
                                $data = $res->fetch_assoc();
                                $tgl_out = date('d-m-Y H:i:s', strtotime($data['tgl_out']));
                                $waktu_out = date('H:i', strtotime($data['tgl_out']));
                                $shifting = $row2["shifting"];
                        
                                $hari_scan = date('l', strtotime($tgl_scan));
                                $shifting = $row2["shifting"];
                                $pinAbsen = $row["pin_absen"];
                                                
                                include "req_shift_db.php";
                        
                                if ($hari_scan == "Saturday" && $shifting != "T") {
                                    $shifting = "W";
                                } elseif ($hari_scan == "Saturday" && $shifting == "T") {
                                    $shifting = "TW";
                                }
                                if ($waktu_scan == $waktu_out && strtotime($waktu_scan) > strtotime("12:00")) {
                                    $tgl_scan = "Tidak Absen Masuk";
                                    $jumlah_tidak_absen_masuk++;
                                } elseif ($waktu_scan != $waktu_out && strtotime($waktu_scan) > strtotime("12:00")) {
                                    $tgl_scan = "Tidak Absen Masuk";
                                    $jumlah_tidak_absen_masuk++;
                                }
                        
                                if ($waktu_scan == $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
                                    $tgl_out = "Tidak Absen Pulang";
                                    $jumlah_tidak_absen_pulang++;
                                } elseif ($waktu_scan != $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
                                    $tgl_out = "Tidak Absen Pulang";
                                    $jumlah_tidak_absen_pulang++;
                                }
                        
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
                                if ($keterlambatan_menit < 0 || $tgl_scan == "Tidak Absen Masuk") {
                                    $keterlambatan_menit = 0;
                                }
                                
                                include "izin_jam_kerja.php";
                        
                                $jumlah_terlambat += $keterlambatan_menit;
                                $tgl_scan_unix = strtotime($tgl_scan);
                                $tgl_out_unix = strtotime($tgl_out);
                                $selisih_detik = $tgl_out_unix - $tgl_scan_unix;
                        
                                $jam = floor($selisih_detik / (60 * 60));
                                $menit = floor(($selisih_detik - ($jam * 60 * 60)) / 60);
                                $detik = $selisih_detik - ($jam * 60 * 60) - ($menit * 60);
                            }
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
                        
                            $jumlah_tidak_absen = $jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang;
                            echo "<td class='text-center'>" . $jumlah_tidak_absen . "</td>";
                        
                            $jumlah_tidak_absen_nominal = $jumlah_tidak_absen * 25000;
                            $tidak_absen_rupiah = number_format($jumlah_tidak_absen_nominal, 0, ',', '.');
                        
                            $total = $jumlah_denda + $jumlah_tidak_absen_nominal;
                            $total_rupiah = number_format($total, 0, ',', '.');
                            echo "<td style='background:#eeee;'><b>Rp " . $total_rupiah . "</b></td>";
                        } else {
                            echo "<td colspan='7'>Tidak ada data absen</td>";
                        }

                        echo "</tr>";
                    }
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