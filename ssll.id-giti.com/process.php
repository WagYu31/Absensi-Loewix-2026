<?php
// Koneksi ke database
include 'conn.php';

// Mengambil data dari form dan menyimpannya ke database
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST["nama"];
    // Ambil data lainnya dari form

    // Query untuk menyimpan data ke database (misalnya menggunakan prepared statement)
    $stmt = $conn->prepare("INSERT INTO karyawan (nama, gaji, tunjangan) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $nama, $gaji, $tunjangan);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Employee data has been successfully saved.";
    } else {
        echo "Gagal menyimpan data karyawan.";
    }

    $stmt->close();
}

$conn->close();
?>
