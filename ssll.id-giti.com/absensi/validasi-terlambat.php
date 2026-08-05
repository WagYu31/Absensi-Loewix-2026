<?php
include "../conn.php";
date_default_timezone_set('Asia/Jakarta');
$tanggal_sekarang = date("Y-m-d");

    echo "<td>" . $karNip . "</td>";
    echo "<td><a href='detail-absen.php?nik=" . $nik . "'>" . $row["nama"] . "</a></td>"; 
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
                $tgl_only2 = date('M Y', strtotime($tgl_scan));
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
        
        $ket1 = "Denda";
                        
        $query_shift_req = "SELECT * FROM shift_req WHERE nip = ?";
        
        if ($stmt = $conn->prepare($query_shift_req)) {
            $stmt->bind_param('s', $pinAbsen);
        
            if ($stmt->execute()) {
                $result_shift_req = $stmt->get_result();
        
                if ($result_shift_req->num_rows > 0) {
                    while ($row_shift_req = $result_shift_req->fetch_assoc()) {
        
                        $tgl_mulai_shift_req = date('Y-m-d', strtotime($row_shift_req['tgl_mulai']));
                        $tgl_selesai_shift_req = date('Y-m-d', strtotime($row_shift_req['tgl_selesai']));
        
                        if ($cek_tgl >= $tgl_mulai_shift_req && $cek_tgl <= $tgl_selesai_shift_req) {
                            $shifting = $row_shift_req['shifting'];
                        }
                    }
                } else {
                }
            } else {
                echo "Error: " . $stmt->error;
            }
        
            $stmt->close();
        } else {
            echo "Error: " . $conn->error;
        }
        

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
        
        
        $query_izin_jam = "SELECT * FROM izin_jam_kerja WHERE nip = ?";
        
        if ($stmt = $conn->prepare($query_izin_jam)) {
            // Bind parameter
            $stmt->bind_param('s', $pinAbsen);
        
            if ($stmt->execute()) {
                $result_izin_jam = $stmt->get_result();
        
                if ($result_izin_jam->num_rows > 0) {
                    while ($row_izin_jam = $result_izin_jam->fetch_assoc()) {
        
                        $tgl_req_izin_jam = date('Y-m-d', strtotime($row_izin_jam['tgl_izin']));
                        $pada = $row_izin_jam['pada'];
        
        
                        if ($cek_tgl == $tgl_req_izin_jam) {
                            $keterlambatan_menit = 0;
                            $jumlah_izin_jam_kerja++;
                            if ($pada == "1") {
                                $pada = "Masuk";
                            } else {
                                $pada = "Keluar";
                            }
                            $ket_izin = "Izin Setengah Hari (Pada Jam " . $pada . " Kerja)";
                        }
                    }
                } else {
                }
            } else {
                echo "Error: " . $stmt->error;
            }
        
            $stmt->close();
        } else {
            echo "Error: " . $conn->error;
        }


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
    echo "<td class='text-center'>". $cek_tgl ."</td>";


    $jumlah_tidak_absen = $jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang;
    echo "<td class='text-center'>" . $jumlah_tidak_absen . "</td>";

    $jumlah_tidak_absen_nominal = $jumlah_tidak_absen * 25000;
    $tidak_absen_rupiah = number_format($jumlah_tidak_absen_nominal, 0, ',', '.');


    $total = $jumlah_denda + $jumlah_tidak_absen_nominal;
    $total_rupiah = number_format($total, 0, ',', '.');
    $fullKet = "Denda terlambat periode " . $tgl_only2 . " dan Tidak Absen " . $jumlah_tidak_absen . " kali";
    
    echo "<td style='background:#eeee;'><b>Rp " . $total_rupiah . "</b></td>";
    echo "<td class='text-center'>". $fullKet ."</td>";
    
        
            $queryInput = "INSERT INTO denda (nip, tanggal, ket1, keterangan, jumlah) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($queryInput)) {
                $stmt->bind_param("ssssd", $karNip, $tanggal_sekarang, $ket1, $fullKet, $total);
                if ($stmt->execute()) {
                    
                    $month = date('m', strtotime($tanggal_sekarang));
                    $year = date('Y', strtotime($tanggal_sekarang));
                
                    $queryCheck = "SELECT * FROM rincian_gaji WHERE nip=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?";
                        $stmtCheck = $conn->prepare($queryCheck);
                        $stmtCheck->bind_param("sss", $karNip, $month, $year);
                        $stmtCheck->execute();
                        $resultCheck = $stmtCheck->get_result();
            
                        if ($resultCheck->num_rows == 0) {
                            // echo "<td class='text-center'></td>";
                            $queryInsert = "INSERT INTO rincian_gaji (nip, tanggal, denda) VALUES (?, ?, ?)";
                            $stmtInsert = $conn->prepare($queryInsert);
                            $stmtInsert->bind_param("ssd", $karNip, $tanggal_sekarang, $total);
                            $stmtInsert->execute();
                        }
                        else{
                            // echo "<td class='text-center'>v</td>";
                            $queryUpdate = "UPDATE rincian_gaji SET denda = denda + ? WHERE nip=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?";
                            $stmtUpdate = $conn->prepare($queryUpdate);
                            $stmtUpdate->bind_param("dsss", $total, $karNip, $month, $year);
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
    echo "<td colspan='7'>Tidak ada data absen</td>";
}
