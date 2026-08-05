<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan karyawan, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

$nip = $_SESSION['nip'];

include 'conn.php';

// ...

// Memeriksa apakah file foto telah diunggah
if (isset($_FILES['newPhoto'])) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES['newPhoto']['name']);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Memeriksa apakah file adalah gambar
    $check = getimagesize($_FILES['newPhoto']['tmp_name']);
    if ($check !== false) {
        // Memeriksa dan membatasi jenis file yang diizinkan (misalnya, hanya JPEG, PNG)
        if ($imageFileType == "jpg" || $imageFileType == "jpeg" || $imageFileType == "png") {
            // Memindahkan file ke folder tujuan
            if (move_uploaded_file($_FILES['newPhoto']['tmp_name'], $targetFile)) {
                // Mengupdate atribut pas_photo pada tabel karyawan
                $filename = basename($_FILES['newPhoto']['name']);
                $query = "UPDATE karyawan SET pas_photo = '$filename' WHERE nip = '$nip'";
                $result = $conn->query($query);

                if ($result) {
                    // Atribut pas_photo berhasil diperbarui
                    header('Location: admin-profile.php');
                    exit();
                } else {
                    // Gagal memperbarui atribut pas_photo
                    header('Location: error.html');
                    exit();
                }
            } else {
                // Gagal memindahkan file
                header('Location: error.html');
                exit();
            }
        } else {
            // Jenis file tidak diizinkan
            header('Location: error.html');
            exit();
        }
    } else {
        // File bukan gambar
        header('Location: error.html');
        exit();
    }
} else {
    // File foto tidak ditemukan
    header('Location: error.html');
    exit();
}

// ...

?>
