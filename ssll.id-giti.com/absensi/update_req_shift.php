<?php
include '../conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $pin_absen = $_POST['pin'];
    $tgl_mulai = $_POST['tanggal_mulai'];
    $tgl_selesai = $_POST['tanggal_selesai'];
    $shift = $_POST['shift'];
    $valid = "W";

    // Validasi data
    if (!empty($pin_absen) && !empty($tgl_mulai) && !empty($tgl_selesai) && !empty($shift)) {
        // Query untuk memasukkan data ke dalam tabel shift_req
        $sql = "INSERT INTO shift_req (nip, tgl_mulai, tgl_selesai, shifting, valid) VALUES (?, ?, ?, ?, ?)";

        // Prepare statement
        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param('sssss', $pin_absen, $tgl_mulai, $tgl_selesai, $shift, $valid);

            // Eksekusi statement
            if ($stmt->execute()) {
                header("Location: data-absen.php");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }

            // Tutup statement
            $stmt->close();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Semua field harus diisi.";
    }

    // Tutup koneksi
    $conn->close();
} else {
    echo "Metode request tidak valid.";
}
