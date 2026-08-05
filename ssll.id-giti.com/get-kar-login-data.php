<?php
$nip = $_SESSION['nip'];

$query = "SELECT *
        FROM karyawan
        WHERE karyawan.nip = '$nip'";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nama = $row['nama'];
    $nik = $row['nik'];
    $jabatan = $row['jabatan'];
    $gajiPokok = "Rp " . number_format($row['gaji_pokok'], 0, ',', '.');
    $tunjangan = "Rp " . number_format($row['tunjangan'], 0, ',', '.');
    $tempatLahir = $row['tempat_lahir'];
    $tanggalLahir = $row['tanggal_lahir'];
    $alamat = $row['alamat'];
    $nomorHP = $row['nomor_handphone'];
    $nomorTelepon = $row['nomor_telepon'];
    $email = $row['email'];
    $photo = $row['pas_photo'];
    $nomorKTP = $row['nomor_ktp'];
    $gambarKTP = $row['gambar_ktp'];
    $tanggalMasuk = $row['tanggal_masuk'];
    $namaBank = $row['nama_bank'];
    $nomorRekening = $row['nomor_rekening'];
    $namaPemilikRekening = $row['nama_pemilik_rekening'];
    $statusKaryawan = $row['status_karyawan'];
} else {
    // Jika data karyawan tidak ditemukan, arahkan ke halaman lain atau berikan pesan error
    header('Location: error.html');
    exit();
}
?>