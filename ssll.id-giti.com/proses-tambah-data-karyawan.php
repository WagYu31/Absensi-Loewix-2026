<?php
session_start();

// Cek apakah pengguna telah login sebagai admin
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

include 'conn.php';

function generateNIP($conn) {
    // Fungsi untuk menghasilkan NIP dengan angka random berjumlah 5 digit
    do {
        $nip = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT); // Menghasilkan angka random berjumlah 5 digit dan di-left pad dengan '0'
        $query = "SELECT nip FROM karyawan WHERE nip = '$nip'";
        $result = $conn->query($query);
    } while ($result->num_rows > 0);

    return $nip;
}

// Memeriksa apakah form telah dikirimkan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = generateNIP($conn); // Panggil fungsi generateNIP untuk mendapatkan NIP yang unik
    $nik = $_POST['nik'];
    $pin = $_POST['pin'];
    $nama = $_POST['nama'];
    $tempatLahir = $_POST['tempat_lahir'];
    $tanggalLahir = $_POST['tanggal_lahir'];
    $alamat = $_POST['alamat'];
    $nomorHP = $_POST['nomor_handphone'];
    $nomorTelepon = $_POST['nomor_telepon'];
    $email = $_POST['email'];
    $nomorKTP = $_POST['nomor_ktp'];
    $tanggalMasuk = $_POST['tanggal_masuk'];
    $namaBank = $_POST['nama_bank'];
    $nomorRekening = $_POST['nomor_rekening'];
    $namaPemilikRekening = $_POST['nama_pemilik_rekening'];
    $statusKaryawan = "aktif";
    $idJabatan = $_POST['id_jabatan'];

    // Menghandle file gambar KTP yang diupload
    $targetDir = "uploads/"; // Direktori penyimpanan gambar
    $gambarKTP = basename($_FILES["gambar_ktp"]["name"]); // Nama file gambar KTP

    // Jika gambar KTP tidak diupload, gunakan gambar default
    if ($gambarKTP === "") {
        $gambarKTP = "template-ktp-kosong-52.png";
    } else {
        $targetFile = $targetDir . $gambarKTP; // Path file gambar KTP

        // Memindahkan file yang diupload ke direktori tujuan
        if (!move_uploaded_file($_FILES["gambar_ktp"]["tmp_name"], $targetFile)) {
            // Jika pemindahan file gagal
            echo "Error uploading file.";
            exit();
        }
    }

    $pasphoto = "default.png";
    $query = "INSERT INTO karyawan (nip, pin_absen, nik, nama, tempat_lahir, tanggal_lahir, alamat, nomor_handphone, nomor_telepon, email, nomor_ktp, tanggal_masuk, nama_bank, nomor_rekening, nama_pemilik_rekening, status_karyawan, jabatan, gambar_ktp, pas_photo) VALUES ('$nip', '$pin', '$nik', '$nama', '$tempatLahir', '$tanggalLahir', '$alamat', '$nomorHP', '$nomorTelepon', '$email', '$nomorKTP', '$tanggalMasuk', '$namaBank', '$nomorRekening', '$namaPemilikRekening', '$statusKaryawan', '$idJabatan', '$gambarKTP', '$pasphoto')";

    if ($conn->query($query) === TRUE) {
        // Jika data berhasil ditambahkan, arahkan kembali ke halaman data karyawan
        header('Location: data-karyawan.php');
        exit();
    } else {
        // Jika terjadi error saat menambahkan data ke database
        echo "Error: " . $query . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
