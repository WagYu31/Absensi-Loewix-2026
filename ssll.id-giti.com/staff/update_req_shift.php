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
                echo "<script>alert('Permintaan shifting berhasil ditambahkan!'); window.location.href = 'shift-req.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error: " . addslashes($stmt->error) . "'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error database: " . addslashes($conn->error) . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Semua field form shifting harus diisi.'); window.history.back();</script>";
    }

    $conn->close();
} else {
    header("Location: shift-req.php");
    exit();
}
?>
