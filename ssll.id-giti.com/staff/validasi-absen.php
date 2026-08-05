<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Akses ditolak.'];
    header('Location: absen.php');
    exit();
}

include '../conn.php';

if (!isset($_GET['bulan']) || !ctype_digit($_GET['bulan']) || !isset($_GET['tahun']) || !ctype_digit($_GET['tahun'])) {
    die("Error: Periode bulan dan tahun tidak valid.");
}

$bulan = $_GET['bulan'];
$tahun = $_GET['tahun'];
$bulan_filter = sprintf("%02d", $bulan);
$tahun_filter = $tahun;

function calculateAbsensiLengkap($conn, $row_karyawan, $bulan_filter, $tahun_filter) {
    $nik = $row_karyawan['nik'];
    $nip_karyawan = $row_karyawan['nip'];
    $pin_karyawan = $row_karyawan['pin_absen'];
    $default_shifting = $row_karyawan['shifting'];

    $total_menit_terlambat = 0;
    $total_tidak_absen_masuk = 0;
    $total_tidak_absen_pulang = 0;

    $jumlah_hari = date('t', mktime(0, 0, 0, $bulan_filter, 1, $tahun_filter));
    $hari_ini_str = date('Y-m-d');

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
    while ($rowA = $resAbsen->fetch_assoc()) {
        $dateKey = date('Y-m-d', strtotime($rowA['tgl_in']));
        $attendanceData[$dateKey] = $rowA;
    }

    for ($d = 1; $d <= $jumlah_hari; $d++) {
        $currentDateStr = $tahun_filter . '-' . $bulan_filter . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        if ($currentDateStr > $hari_ini_str) break;

        if (isset($attendanceData[$currentDateStr])) {
            $dataRow = $attendanceData[$currentDateStr];
            $tgl_scan_dt = new DateTime($dataRow['tgl_in']);
            $tgl_out_dt = new DateTime($dataRow['tgl_out']);
            $jam_masuk_str = $tgl_scan_dt->format('H:i');
            $jam_pulang_str = ($tgl_out_dt != $tgl_scan_dt) ? $tgl_out_dt->format('H:i') : "-";
            $is_error_masuk = false;

            if ($tgl_out_dt == $tgl_scan_dt) {
                if (strtotime($jam_masuk_str) >= strtotime("12:00")) {
                    $total_tidak_absen_masuk++;
                    $is_error_masuk = true;
                } else {
                    $total_tidak_absen_pulang++;
                }
            } else {
                if (strtotime($jam_masuk_str) > strtotime("13:00")) {
                    $total_tidak_absen_masuk++;
                    $is_error_masuk = true;
                }
                if (strtotime($jam_pulang_str) < strtotime("11:00")) {
                    $total_tidak_absen_pulang++;
                }
            }

            if (!$is_error_masuk) {
                $current_shifting = $default_shifting;
                $stmt_req = $conn->prepare("SELECT shifting FROM shift_req WHERE nip = ? AND ? BETWEEN tgl_mulai AND tgl_selesai LIMIT 1");
                $stmt_req->bind_param("ss", $pin_karyawan, $currentDateStr);
                $stmt_req->execute();
                $res_req = $stmt_req->get_result();
                if ($r_req = $res_req->fetch_assoc()) { $current_shifting = $r_req['shifting']; }
                $stmt_req->close();

                $dayNameEng = date('l', strtotime($currentDateStr));
                if ($dayNameEng == "Saturday") {
                    $current_shifting = ($current_shifting == "T") ? "TW" : "W";
                }

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
                    $total_menit_terlambat += floor(($tgl_scan_dt->getTimestamp() - $waktu_masuk_seharusnya_unix) / 60);
                }
            }
        }
    }

    $denda_terlambat_rp = 0;
    if ($total_menit_terlambat > 20) {
        if ($total_menit_terlambat <= 80) $denda_terlambat_rp = ($total_menit_terlambat - 20) * 300;
        elseif ($total_menit_terlambat <= 140) $denda_terlambat_rp = (60 * 300) + (($total_menit_terlambat - 80) * 600);
        else $denda_terlambat_rp = (60 * 300) + (60 * 600) + (($total_menit_terlambat - 140) * 2000);
    }

    $jumlah_kejadian_tidak_absen = $total_tidak_absen_masuk + $total_tidak_absen_pulang;
    $denda_tidak_absen_rp = $jumlah_kejadian_tidak_absen * 25000;

    return [
        'total_denda' => $denda_terlambat_rp + $denda_tidak_absen_rp,
        'menit_telat' => $total_menit_terlambat,
        'tidak_absen' => $jumlah_kejadian_tidak_absen
    ];
}

$sql_karyawan = "SELECT nip, nik, pin_absen, shifting FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND nip NOT IN ('001', '70326')";
$result_karyawan = $conn->query($sql_karyawan);

$conn->begin_transaction();
try {
    $stmt_denda_insert = $conn->prepare("INSERT INTO denda (nip, tanggal, ket1, keterangan, jumlah) VALUES (?, ?, 'Denda', ?, ?)");
    $stmt_gaji_select = $conn->prepare("SELECT id_rincian_gaji, denda FROM rincian_gaji WHERE nip = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $stmt_gaji_update = $conn->prepare("UPDATE rincian_gaji SET denda = ? WHERE id_rincian_gaji = ?");
    $stmt_gaji_insert = $conn->prepare("INSERT INTO rincian_gaji (nip, tanggal, denda) VALUES (?, ?, ?)");

    date_default_timezone_set('Asia/Jakarta');
    $tgl_skrg = date('Y-m-d');
    $bln_skrg = date('m');
    $thn_skrg = date('Y');
    $bulanNames = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

    while ($karyawan = $result_karyawan->fetch_assoc()) {
        $res = calculateAbsensiLengkap($conn, $karyawan, $bulan_filter, $tahun_filter);
        
        if ($res['total_denda'] > 0) {
            $ket = "Denda telat Periode " . $bulanNames[$bulan_filter] . " " . $tahun_filter . " ";
            if ($res['menit_telat'] > 0) $ket .= $res['menit_telat'] . "m ";
            if ($res['tidak_absen'] > 0) $ket .= "dan Tidak Absen " . $res['tidak_absen'] . " kali";

            $stmt_denda_insert->bind_param("sssi", $karyawan['nip'], $tgl_skrg, $ket, $res['total_denda']);
            $stmt_denda_insert->execute();

            $stmt_gaji_select->bind_param("sss", $karyawan['nip'], $bln_skrg, $thn_skrg);
            $stmt_gaji_select->execute();
            $result_gaji = $stmt_gaji_select->get_result();

            if ($row_gaji = $result_gaji->fetch_assoc()) {
                $denda_baru = $row_gaji['denda'] + $res['total_denda'];
                $stmt_gaji_update->bind_param("ii", $denda_baru, $row_gaji['id_rincian_gaji']);
                $stmt_gaji_update->execute();
            } else {
                $stmt_gaji_insert->bind_param("ssi", $karyawan['nip'], $tgl_skrg, $res['total_denda']);
                $stmt_gaji_insert->execute();
            }
        }
    }
    $conn->commit();
    $_SESSION['pesan_flash'] = ['tipe' => 'success', 'pesan' => 'Validasi Berhasil!'];
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal: ' . $e->getMessage()];
}

header('Location: absen.php');
exit();