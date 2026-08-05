<?php
// Include koneksi ke database
include "../conn.php";

// Periksa apakah form telah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Periksa apakah data nik dan nip telah diterima dari form
    if (isset($_POST['nik']) && isset($_POST['nip'])) {
        // Loop melalui data yang diterima dari form
        foreach ($_POST['nik'] as $key => $nik) {
            // Ambil nilai shifting yang dipilih dari form
            $shift = $_POST['shift_' . $nik];

            // Ambil nilai nip yang sesuai dengan nik saat ini
            $nip = $_POST['nip'][$key];

            // Update shifting dalam tabel karyawan berdasarkan nik
            $sqlUpdate = "UPDATE karyawan SET shifting = '$shift' WHERE nik = '$nik'";

            // Eksekusi query update
            if ($conn->query($sqlUpdate) === TRUE) {
            } else {
                echo "Error: " . $sqlUpdate . "<br>" . $conn->error;
            }
        }
        header("Location: shifting.php");
        exit;
    } else {
        echo "Data tidak lengkap.";
    }
}

// Tutup koneksi database
$conn->close();
