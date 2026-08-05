<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pin_absen = $_POST['pin'] ?? '';
    $tgl_mulai = $_POST['tanggal_mulai'] ?? '';
    $tgl_selesai = $_POST['tanggal_selesai'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $valid = "W";

    if (!empty($pin_absen) && !empty($tgl_mulai) && !empty($tgl_selesai) && !empty($shift)) {
        $sql = "INSERT INTO shift_req (nip, tgl_mulai, tgl_selesai, shifting, valid) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('sssss', $pin_absen, $tgl_mulai, $tgl_selesai, $shift, $valid);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                $bulan = date('m', strtotime($tgl_mulai));
                $tahun = date('Y', strtotime($tgl_mulai));
                header("Location: shift-req.php?bulan=$bulan&tahun=$tahun&msg=success");
                exit();
            } else {
                $_SESSION['flash_error'] = "Error: " . $stmt->error;
                header("Location: shift-req.php");
                exit();
            }
        } else {
            $_SESSION['flash_error'] = "Error database: " . $conn->error;
            header("Location: shift-req.php");
            exit();
        }
    } else {
        $_SESSION['flash_error'] = "Semua field form shifting harus diisi.";
        header("Location: shift-req.php");
        exit();
    }
} else {
    header("Location: shift-req.php");
    exit();
}
?>
