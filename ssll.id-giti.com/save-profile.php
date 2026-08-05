<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "karyawan") {
    header("Location: save-profile.php");
    exit();
}

// Pastikan Anda mengatur permission yang sesuai pada folder tujuan penyimpanan gambar

// Ambil data profil karyawan dari form
$nama = $_POST['nama'];
$nip = $_POST['nip'];
$tempat_lahir = $_POST['tempat_lahir'];
$tanggal_lahir = $_POST['tanggal_lahir'];
$alamat = $_POST['alamat'];
$email = $_POST['email'];
$nomor_handphone = $_POST['nomor_handphone'];
$nomor_telepon = $_POST['nomor_telepon'];
$nomor_ktp = $_POST['nomor_ktp'];
$nama_bank = $_POST['nama_bank'];
$nomor_rekening = $_POST['nomor_rekening'];
$nama_pemilik_rekening = $_POST['nama_pemilik_rekening'];

include 'conn.php';

// Cek apakah gambar KTP diunggah
if (isset($_FILES['gambar_ktp']) && $_FILES['gambar_ktp']['error'] === UPLOAD_ERR_OK) {
    $gambar_ktp = $_FILES['gambar_ktp']['name'];
    $tmp_name = $_FILES['gambar_ktp']['tmp_name'];

    // Tentukan lokasi folder penyimpanan gambar KTP
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($gambar_ktp);

    // Pindahkan gambar ke folder penyimpanan
    if (move_uploaded_file($tmp_name, $target_file)) {
        // Gambar berhasil diunggah, lakukan penyimpanan data profil karyawan ke database
        $query = "UPDATE karyawan SET nama='$nama', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', alamat='$alamat', email='$email', nomor_handphone='$nomor_handphone', nomor_telepon='$nomor_telepon', nomor_ktp='$nomor_ktp', gambar_ktp='$gambar_ktp', nama_bank='$nama_bank', nomor_rekening='$nomor_rekening', nama_pemilik_rekening='$nama_pemilik_rekening' WHERE nip='$nip'";

        if ($conn->query($query) === TRUE) {
            // Data profil karyawan berhasil disimpan
            $message = "Success";
            echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php';</script>";
            exit();
    
        } else {
            $message = "Update Failed!";
            echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php';</script>";
            exit();
        }

    } else {
        $message = "Update Failed!";
        echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php';</script>";
        exit();
    }
} else {
    // Gambar KTP tidak diunggah, lakukan penyimpanan data profil karyawan ke database tanpa perubahan pada gambar KTP
    $query = "UPDATE karyawan SET nama='$nama', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', alamat='$alamat', email='$email', nomor_handphone='$nomor_handphone', nomor_telepon='$nomor_telepon', nomor_ktp='$nomor_ktp', nama_bank='$nama_bank', nomor_rekening='$nomor_rekening', nama_pemilik_rekening='$nama_pemilik_rekening' WHERE nip='$nip'";

    if ($conn->query($query) === TRUE) {
        // Data profil karyawan berhasil disimpan
        $message = "Success";
        echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php';</script>";
        exit();

    } else {
        $message = "Update Failed!";
        echo "<script>alert('$message'); window.location.href = 'profile-karyawan.php';</script>";
        exit();
    }
}

// Tutup koneksi ke database
$conn->close();
?>
