<?php
session_start();
include '../conn.php';

if (isset($_GET['bulan']) && isset($_GET['tahun']) && isset($_GET['action'])) {
    $bulan_num = (int)$_GET['bulan'];
    $tahun_num = (int)$_GET['tahun'];
    $bulan_pad = str_pad($bulan_num, 2, '0', STR_PAD_LEFT);
    $action = $_GET['action'];

    if ($action === 'lock') {
        // Cek apakah sudah terkunci (baik format '8' maupun '08')
        $stmt_check = $conn->prepare("SELECT * FROM kunci_gaji WHERE (bulan = ? OR bulan = ?) AND tahun = ? AND kunci = 'Lock'");
        $stmt_check->bind_param("sss", $bulan_num, $bulan_pad, $tahun_num);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check->num_rows == 0) {
            $stmt_lock = $conn->prepare("INSERT INTO kunci_gaji (bulan, tahun, kunci) VALUES (?, ?, 'Lock')");
            $stmt_lock->bind_param("ss", $bulan_pad, $tahun_num);
            $stmt_lock->execute();
            $stmt_lock->close();
            $_SESSION['pesan_flash'] = ['tipe' => 'success', 'pesan' => 'Data gaji periode berhasil dikunci (Locked).'];
        } else {
            $_SESSION['pesan_flash'] = ['tipe' => 'info', 'pesan' => 'Data gaji periode ini sudah terkunci.'];
        }
        $stmt_check->close();
    } elseif ($action === 'unlock') {
        $stmt_unlock = $conn->prepare("DELETE FROM kunci_gaji WHERE (bulan = ? OR bulan = ?) AND tahun = ? AND kunci = 'Lock'");
        $stmt_unlock->bind_param("sss", $bulan_num, $bulan_pad, $tahun_num);
        $stmt_unlock->execute();
        $stmt_unlock->close();
        $_SESSION['pesan_flash'] = ['tipe' => 'success', 'pesan' => 'Kunci data gaji berhasil dibuka (Unlocked). Data sekarang dapat di-generate ulang atau diedit.'];
    }

    header("Location: penggajian.php?bulan=" . urlencode($bulan_pad) . "&tahun=" . urlencode($tahun_num));
    exit();
} else {
    header("Location: penggajian.php");
    exit();
}
?>
