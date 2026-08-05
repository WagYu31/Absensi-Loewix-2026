<?php
session_start();

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Akses ditolak.'];
    header('Location: data-karyawan.php');
    exit();
}

include '../conn.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: data-karyawan-baru.php');
    exit();
}

function generateNIP($conn) {
    do {
        $nip = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $query = "SELECT nip FROM karyawan WHERE nip = ?";
        $stmt_check = $conn->prepare($query);
        $stmt_check->bind_param("s", $nip);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
    } while ($result->num_rows > 0);
    $stmt_check->close();
    return $nip;
}

function handleFileUpload($fileKey, $newNip, $prefix, $defaultFilename) {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileKey];
        $target_dir = "../uploads/";
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $prefix . "_" . $newNip . "_" . time() . "." . $file_ext;

        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
            return $new_filename;
        }
    }
    return $defaultFilename;
}

$nip_baru = generateNIP($conn);
$nik = trim($_POST['nik'] ?? '');
$pin = trim($_POST['pin'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = empty($_POST['tanggal_lahir']) ? null : $_POST['tanggal_lahir'];
$alamat = trim($_POST['alamat'] ?? '');
$nomor_handphone = trim($_POST['nomor_handphone'] ?? '');
$nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
$email = trim($_POST['email'] ?? '');
$nomor_ktp = trim($_POST['nomor_ktp'] ?? '');
$tanggal_masuk = empty($_POST['tanggal_masuk']) ? null : $_POST['tanggal_masuk'];
$nama_bank = $_POST['nama_bank'] ?? '';
$nomor_rekening = trim($_POST['nomor_rekening'] ?? '');
$nama_pemilik_rekening = trim($_POST['nama_pemilik_rekening'] ?? '');
$jabatan = trim($_POST['id_jabatan'] ?? '');
$status_karyawan = "aktif";

if (empty($nik) || empty($nama) || empty($pin) || empty($jabatan)) {
    $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Input Gagal! NIK, Nama, PIN Absen, dan Jabatan wajib diisi.'];
    header('Location: data-karyawan-baru.php');
    exit();
}

$pas_photo_filename = handleFileUpload('pas_photo', $nip_baru, 'photo', 'default.png');
$gambar_ktp_filename = handleFileUpload('gambar_ktp', $nip_baru, 'ktp', 'template-ktp-kosong-52.png');

try {
    $sql_karyawan = "INSERT INTO karyawan 
        (nip, pin_absen, nik, nama, tempat_lahir, tanggal_lahir, alamat, nomor_handphone, nomor_telepon, email, nomor_ktp, tanggal_masuk, nama_bank, nomor_rekening, nama_pemilik_rekening, status_karyawan, jabatan, gambar_ktp, pas_photo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_karyawan);
    if (!$stmt) {
        throw new Exception("Query Prepare Failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        "sssssssssssssssssss",
        $nip_baru, $pin, $nik, $nama, $tempat_lahir, $tanggal_lahir, $alamat, $nomor_handphone, $nomor_telepon, $email, $nomor_ktp, $tanggal_masuk, $nama_bank, $nomor_rekening, $nama_pemilik_rekening, $status_karyawan, $jabatan, $gambar_ktp_filename, $pas_photo_filename
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execute Failed: " . $stmt->error);
    }
    
    $_SESSION['pesan_flash'] = [
        'tipe' => 'success', 
        'pesan' => "Sukses! Karyawan baru '$nama' dengan NIP '$nip_baru' telah berhasil ditambahkan."
    ];
    header('Location: data-karyawan.php');
    exit();

} catch (Exception $exception) {
    if ($conn->errno == 1062) {
         $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Input Gagal! NIK atau PIN Absen sudah terdaftar.'];
    } else {
         $_SESSION['pesan_flash'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan saat menyimpan: ' . $exception->getMessage()];
    }
    header('Location: data-karyawan-baru.php');
    exit();
} finally {
    if (isset($stmt) && $stmt !== false) $stmt->close();
    $conn->close();
}
?>