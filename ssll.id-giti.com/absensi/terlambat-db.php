<?php
include "../conn.php";

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
    echo "<td class='text-center'>" . $jumlah_terlambat . "</td>";

    echo "<td>RP " . $jumlah_denda_rupiah . "</td>";

    $jumlah_tidak_absen = $jumlah_tidak_absen_masuk + $jumlah_tidak_absen_pulang;
    echo "<td class='text-center'>" . $jumlah_tidak_absen . "</td>";

    $jumlah_tidak_absen_nominal = $jumlah_tidak_absen * 25000;
    $tidak_absen_rupiah = number_format($jumlah_tidak_absen_nominal, 0, ',', '.');
    echo "<td>RP " . $tidak_absen_rupiah . "</td>";

    // echo "<td class='text-center'>" . $jumlah_izin_jam_kerja . "</td>";

    // $jumlah_izin_jam_kerja_nominal = $jumlah_izin_jam_kerja * 60000;
    // $jumlah_izin_jam_kerja_rupiah = number_format($jumlah_izin_jam_kerja_nominal, 0, ',', '.');
    // echo "<td>RP " . $jumlah_izin_jam_kerja_rupiah . "</td>";

    // $total = $jumlah_denda + $jumlah_tidak_absen_nominal + $jumlah_izin_jam_kerja_nominal;
    $total = $jumlah_denda + $jumlah_tidak_absen_nominal;
    $total_rupiah = number_format($total, 0, ',', '.');
    echo "<td style='background:#eeee;'><b>Rp " . $total_rupiah . "</b></td>";
} else {
    echo "<td colspan='7'>Tidak ada data absen</td>";
}
