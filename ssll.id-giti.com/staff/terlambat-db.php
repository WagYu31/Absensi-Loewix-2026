<?php
$total_menit_terlambat = 0;
$total_tidak_absen_masuk = 0;
$total_tidak_absen_pulang = 0;
$total_hadir = 0;
$total_cuti = 0;
$total_alfa = 0;

$bulan_filter = sprintf("%02d", $bulan);
$tahun_filter = $tahun;
$jumlah_hari_dalam_bulan = date('t', mktime(0, 0, 0, $bulan_filter, 1, $tahun_filter));
$hari_ini_str = date('Y-m-d'); 

$default_shifting = $row['shifting']; 
$pin_karyawan = $row['pin_absen']; 

$attendanceData = [];
$sqlAbsen = "SELECT 
        MIN(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_in, 
        MAX(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) AS tgl_out
    FROM absen 
    WHERE nip = '$nik' AND
    MONTH(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$bulan_filter' AND
    YEAR(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s')) = '$tahun_filter'
    GROUP BY DATE(STR_TO_DATE(tgl_scan, '%d-%m-%Y %H:%i:%s'))";

$resAbsen = $conn->query($sqlAbsen);
if ($resAbsen) {
    while ($rowA = $resAbsen->fetch_assoc()) {
        $dateKey = date('Y-m-d', strtotime($rowA['tgl_in']));
        $attendanceData[$dateKey] = $rowA;
    }
}

$holidays = [];
$queryHolidays = "SELECT tanggal_merah, libur FROM kalender_kerja 
                  WHERE MONTH(tanggal_merah) = '$bulan_filter' 
                  AND YEAR(tanggal_merah) = '$tahun_filter' 
                  AND deleted_at IS NULL";
$resHolidays = $conn->query($queryHolidays);
if ($resHolidays) {
    while ($h = $resHolidays->fetch_assoc()) {
        $holidays[$h['tanggal_merah']] = $h;
    }
}

$approvedLeaves = [];
$nip_karyawan = $row['nip']; 
$queryCuti = "SELECT tgl_mulai, tgl_selesai FROM cuti 
              WHERE nip = '$nip_karyawan' 
              AND verif LIKE 'Disetujui%' 
              AND deleted_at IS NULL";
$resCuti = $conn->query($queryCuti);
if ($resCuti) {
    while ($c = $resCuti->fetch_assoc()) {
        $start = new DateTime($c['tgl_mulai']);
        $end = new DateTime($c['tgl_selesai']);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        foreach ($period as $date) {
            $approvedLeaves[$date->format('Y-m-d')] = true;
        }
    }
}

for ($d = 1; $d <= $jumlah_hari_dalam_bulan; $d++) {
    $currentDateStr = $tahun_filter . '-' . $bulan_filter . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
    
    if ($currentDateStr > $hari_ini_str) {
        break; 
    }

    $dayNameEng = date('l', strtotime($currentDateStr));
    $isSunday = ($dayNameEng == 'Sunday');
    $isHoliday = isset($holidays[$currentDateStr]) && $holidays[$currentDateStr]['libur'] == 'yes';
    $isWorkDay = !$isSunday && !$isHoliday;

    if (isset($attendanceData[$currentDateStr])) {
        $total_hadir++;
        $dataRow = $attendanceData[$currentDateStr];
        
        $tgl_scan_dt = new DateTime($dataRow['tgl_in']);
        $tgl_out_dt = new DateTime($dataRow['tgl_out']);
        
        $jam_masuk_str = $tgl_scan_dt->format('H:i');
        $jam_pulang_str = ($tgl_out_dt != $tgl_scan_dt) ? $tgl_out_dt->format('H:i') : "-";
        
        $is_error_masuk = false; 

        if ($tgl_out_dt == $tgl_scan_dt) {
            if (strtotime($jam_masuk_str) >= strtotime("12:00")) {
                if ($currentDateStr != $hari_ini_str) {
                    $total_tidak_absen_masuk++;
                }
                $is_error_masuk = true; 
            } else {
                if ($currentDateStr != $hari_ini_str) {
                    $total_tidak_absen_pulang++;
                }
            }
        } else {
            if (strtotime($jam_masuk_str) > strtotime("13:00")) { 
                if ($currentDateStr != $hari_ini_str) {
                    $total_tidak_absen_masuk++;
                }
                $is_error_masuk = true;
            }
            if (strtotime($jam_pulang_str) < strtotime("11:00")) { 
                if ($currentDateStr != $hari_ini_str) {
                    $total_tidak_absen_pulang++;
                }
            }
        }

        if (!$is_error_masuk) {
            $current_shifting = $default_shifting;
            $stmt_req = $conn->prepare("SELECT shifting FROM shift_req WHERE nip = ? AND ? BETWEEN tgl_mulai AND tgl_selesai LIMIT 1");
            if ($stmt_req) {
                $param_pin = $row['pin_absen'] ?? $row['nip']; 
                $stmt_req->bind_param("ss", $param_pin, $currentDateStr);
                $stmt_req->execute();
                $res_req = $stmt_req->get_result();
                if ($res_req->num_rows > 0) {
                    $r_req = $res_req->fetch_assoc();
                    $current_shifting = $r_req['shifting'];
                }
                $stmt_req->close();
            }

            if ($dayNameEng == "Saturday") {
                $current_shifting = ($current_shifting == "T") ? "TW" : "W";
            }

            $waktu_masuk_seharusnya_unix = 0;
            $base_time_str = $currentDateStr;
            
            switch ($current_shifting) {
                case "P": $base_time_str .= " 07:00:00"; break;
                case "M": $base_time_str .= " 08:30:00"; break;
                case "N": $base_time_str .= " 09:00:00"; break;
                case "S": $base_time_str .= " 09:30:00"; break;
                case "T": $base_time_str .= " 09:10:00"; break;
                case "W": $base_time_str .= " 08:30:00"; break;
                case "TW": $base_time_str .= " 09:10:00"; break;
                default:  $base_time_str .= " 09:00:00"; break;
            }
            $waktu_masuk_seharusnya_unix = strtotime($base_time_str);

            if ($tgl_scan_dt->getTimestamp() > $waktu_masuk_seharusnya_unix) {
                $menit = floor(($tgl_scan_dt->getTimestamp() - $waktu_masuk_seharusnya_unix) / 60);
                $total_menit_terlambat += $menit;
            }
        }

    } else {
        if ($isWorkDay && $currentDateStr != $hari_ini_str) {
            if (isset($approvedLeaves[$currentDateStr])) {
                $total_cuti++;
            } else {
                $total_alfa++;
            }
        }
    }
}

$getdnd = "SELECT * FROM dnd WHERE id = 1";
$resdnd = $conn->query($getdnd);
if ($resdnd) {
    while ($dnd = $resdnd->fetch_assoc()) {
        $gabsen = $dnd['gabsen'];
        $telatst = $dnd['telatst'];
        $telatd = $dnd['telatd'];
        $telattg = $dnd['telattg'];
    }
}

$denda_terlambat_rp = 0;
if ($total_menit_terlambat > 20) {
    if ($total_menit_terlambat <= 80) {
        $denda_terlambat_rp = ($total_menit_terlambat - 20) * $telatst;
    } elseif ($total_menit_terlambat <= 140) {
        $denda_terlambat_rp = (60 * $telatst) + (($total_menit_terlambat - 80) * $telatd);
    } else {
        $denda_terlambat_rp = (60 * $telatst) + (60 * $telatd) + (($total_menit_terlambat - 140) * $telattg);
    }
}

$jumlah_kejadian_tidak_absen = $total_tidak_absen_masuk + $total_tidak_absen_pulang;
$denda_tidak_absen_rp = $jumlah_kejadian_tidak_absen * $gabsen;
$grand_total_rp = $denda_terlambat_rp + $denda_tidak_absen_rp;

// Modern Badges Output
$menit_badge = ($total_menit_terlambat > 0) 
    ? "<span class='badge bg-warning-subtle text-dark border border-warning fw-bold px-2 py-1'>" . $total_menit_terlambat . " m</span>" 
    : "<span class='text-muted'>0</span>";

$tidak_absen_badge = ($jumlah_kejadian_tidak_absen > 0) 
    ? "<span class='badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1'>" . $jumlah_kejadian_tidak_absen . "</span>" 
    : "<span class='text-muted'>0</span>";

$hadir_badge = "<span class='badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1'>" . $total_hadir . "</span>";

$cuti_badge = ($total_cuti > 0) 
    ? "<span class='badge bg-info-subtle text-info border border-info-subtle fw-bold px-2 py-1'>" . $total_cuti . "</span>" 
    : "<span class='text-muted'>0</span>";

$alfa_badge = ($total_alfa > 0) 
    ? "<span class='badge bg-danger text-white fw-bold px-2 py-1'>" . $total_alfa . "</span>" 
    : "<span class='text-muted'>0</span>";

$total_denda_display = ($grand_total_rp > 0) 
    ? "<span class='badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2.5 py-1 fs-6'>Rp " . number_format($grand_total_rp, 0, ',', '.') . "</span>" 
    : "<span class='text-muted font-monospace'>Rp 0</span>";

echo "<td>" . $menit_badge . "</td>";
echo "<td class='fw-medium text-secondary'>Rp " . number_format($denda_terlambat_rp, 0, ',', '.') . "</td>";
echo "<td>" . $tidak_absen_badge . "</td>";
echo "<td class='fw-medium text-secondary'>Rp " . number_format($denda_tidak_absen_rp, 0, ',', '.') . "</td>";
echo "<td>" . $hadir_badge . "</td>";
echo "<td>" . $cuti_badge . "</td>";
echo "<td>" . $alfa_badge . "</td>";
echo "<td style='background: rgba(241, 245, 249, 0.7);'>" . $total_denda_display . "</td>";

unset($attendanceData, $holidays, $approvedLeaves);
?>