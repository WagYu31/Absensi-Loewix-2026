<?php
include '../conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $pin_absen = $_POST['pin'];
    $tgl_req = $_POST['tgl_req'];
    $pada = $_POST['pada'];

    // Validasi data
    if (!empty($pin_absen) && !empty($tgl_req) && !empty($pada)) {
        // Query untuk memasukkan data ke dalam tabel shift_req
        $sql = "INSERT INTO izin_jam_kerja (nip, tgl_izin, pada) VALUES (?, ?, ?)";

        // Prepare statement
        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param('sss', $pin_absen, $tgl_req, $pada);

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
