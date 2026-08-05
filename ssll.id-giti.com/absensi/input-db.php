<?php
$host = "localhost";
$user = "u787866715_root";
$password = "Eddie@1819";
$database = "u787866715_salary";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection to the database failed. " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["bulan"]) && isset($_GET["tahun"])) {
    $bulan = $_GET["bulan"];
    $tahun = $_GET["tahun"];
} else {
    $bulan = "";
    $tahun = "";
}

date_default_timezone_set('Asia/Jakarta');
$tanggal_sekarang = date("Y-m-d");

// Query data absen
$sql = "SELECT * FROM karyawan WHERE nip != '001' AND nip != '70326' AND nik != '114' AND status_karyawan = 'aktif' ORDER BY nama ASC";
$result = $conn->query($sql);

$jumlah_telat = 0;
$errors = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nip = $row['nip'];
        $nik = $row['nik'];

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
            k.nik = ? AND 
            MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = ? AND 
            YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = ?
        GROUP BY 
            a.nip, DATE_FORMAT(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s'), '%m-%d')";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("sii", $nik, $bulan, $tahun);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result2->num_rows > 0) {
            $jumlah_tidak_absen_masuk = 0;
            $jumlah_tidak_absen_pulang = 0;
            $jumlah_izin_jam_kerja = 0;
            $ket_izin = "";
            while ($row2 = $result2->fetch_assoc()) {
                $tgl_scan = date('d-m-Y H:i:s', strtotime($row2['tgl_scan']));
                $waktu_scan = date('H:i', strtotime($row2['tgl_scan']));
                $tgl_only = date('d-m-Y', strtotime($tgl_scan));
                $tgl_only2 = date('M Y', strtotime($tgl_scan));
                $cek_tgl = date('Y-m-d', strtotime($tgl_scan));

                $query = "SELECT MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
                    FROM absen 
                    WHERE nip = ? AND DATE_FORMAT(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'), '%d-%m-%Y') = ?";
                $stmt3 = $conn->prepare($query);
                $stmt3->bind_param("ss", $row2['nip'], $tgl_only);
                $stmt3->execute();
                $res = $stmt3->get_result();
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
                }
                if ($waktu_scan != $waktu_out && strtotime($waktu_out) < strtotime("11:00")) {
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
            } elseif ($jumlah_terlambat > 140) {
                $jumlah_denda = (60 * 300) + (60 * 600) + (($jumlah_terlambat - 140) * 2000);
            }
            $jumlah_denda_rupiah = number_format($jumlah_denda, 0, ',', '.');

            $jumlah_tidak_absen = $jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang;

            $jumlah_tidak_absen_nominal = $jumlah_tidak_absen * 25000;
            $tidak_absen_rupiah = number_format($jumlah_tidak_absen_nominal, 0, ',', '.');
            if ($jumlah_tidak_absen == 0 || $jumlah_tidak_absen === null) {
                $ketTidakAbsen = "";
            } else {
                $ketTidakAbsen = " Tidak absen " . $jumlah_tidak_absen . " kali.";
            }

            $jumlah_izin_jam_kerja_nominal = $jumlah_izin_jam_kerja * 60000;
            $jumlah_izin_jam_kerja_rupiah = number_format($jumlah_izin_jam_kerja_nominal, 0, ',', '.');
            if ($jumlah_izin_jam_kerja == 0 || $jumlah_izin_jam_kerja === null) {
                $ketIzinJam = "";
            } else {
                $ketIzinJam = " Izin setengah hari " . $jumlah_izin_jam_kerja . " kali.";
            }

            $total = $jumlah_denda + $jumlah_tidak_absen_nominal;
            $total_rupiah = number_format($total, 0, ',', '.');

            $fullKet = "Denda terlambat periode " . $tgl_only2 . " dan " . $ketTidakAbsen;
            $ket1 = "Denda";

            $queryInput = "INSERT INTO denda (nip, tanggal, ket1, keterangan, jumlah) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($queryInput)) {
                $stmt->bind_param("ssssd", $nip, $tanggal_sekarang, $ket1, $fullKet, $total);
                if ($stmt->execute()) {
                    $month = date('m', strtotime($tanggal_sekarang));
                    $year = date('Y', strtotime($tanggal_sekarang));

                    $queryCheck = "SELECT * FROM rincian_gaji WHERE nip=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?";
                    $stmtCheck = $conn->prepare($queryCheck);
                    $stmtCheck->bind_param("sss", $nip, $month, $year);
                    $stmtCheck->execute();
                    $resultCheck = $stmtCheck->get_result();

                    if ($resultCheck->num_rows == 0) {
                        $queryInsert = "INSERT INTO rincian_gaji (nip, tanggal, denda) VALUES (?, ?, ?)";
                        $stmtInsert = $conn->prepare($queryInsert);
                        $stmtInsert->bind_param("ssd", $nip, $tanggal_sekarang, $total);
                        $stmtInsert->execute();
                    } else {
                        $queryUpdate = "UPDATE rincian_gaji SET denda = denda + ? WHERE nip=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?";
                        $stmtUpdate = $conn->prepare($queryUpdate);
                        $stmtUpdate->bind_param("dsss", $total, $nip, $month, $year);
                        $stmtUpdate->execute();
                    }
                } else {
                    $errors[] = "Terjadi kesalahan saat memasukkan data: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $errors[] = "Terjadi kesalahan dalam mempersiapkan statement: " . $conn->error;
            }
        } else {
            $errors[] = "Tidak ada data absen";
        }
    }
} else {
    $errors[] = "Tidak ada data absen";
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p>Error: $error</p>";
    }
}

$message = "Success!";
echo "<script>alert('$message'); window.location.href = '../denda-karyawan.php';</script>";
exit();
$conn->close();
?>