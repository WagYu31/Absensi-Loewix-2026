<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'superadmin') {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: index.php');
    exit();
}

// Periksa apakah data yang dibutuhkan dikirimkan melalui metode POST
if (isset($_POST['nip_denda']) && isset($_POST['tanggal_denda']) && isset($_POST['tanggal_mulai']) && isset($_POST['jumlah_denda']) && isset($_POST['keterangan_denda']) && isset($_POST['bayar'])) {
    $nipDenda = $_POST['nip_denda'];
    $tanggalDenda = $_POST['tanggal_denda'];
    $tanggalMulai = $_POST['tanggal_mulai'];
    $jumlahDenda = $_POST['jumlah_denda'];
    $keteranganDenda = $_POST['keterangan_denda'];
    $bayar = $_POST['bayar'];
    $id_cashbon = rand(10000, 99999);

    // Lakukan pemrosesan tambah data denda ke database
    include 'conn.php';

    // Query untuk menambahkan data denda ke tabel denda
    $query = "INSERT INTO cashbon (id_cashbon, nip, tanggal, jumlah, keterangan, mulai, cicil, lunas) VALUES ('$id_cashbon', '$nipDenda', '$tanggalDenda', '$jumlahDenda', '$keteranganDenda', '$tanggalMulai', '$bayar', 'N')";

if ($conn->query($query) === TRUE) {
    // Check if the same NIP and tanggal (bulan dan tahun) exists in rincian_gaji
    $checkQuery = "SELECT * FROM rincian_gaji WHERE nip = '$nipDenda' AND MONTH(tanggal) = MONTH('$tanggalDenda') AND YEAR(tanggal) = YEAR('$tanggalDenda')";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (!$checkResult) {
        echo "Error checking existing data in rincian_gaji: " . mysqli_error($conn);
    } else {
        if (mysqli_num_rows($checkResult) > 0) {
            // Data already exists, update id_cashbon
            $data = mysqli_fetch_assoc($checkResult);
            $existingIdCashbon = $data['id_cashbon'];
            $updateQuery = "UPDATE rincian_gaji SET id_cashbon = '$id_cashbon' WHERE nip = '$nipDenda' AND MONTH(tanggal) = MONTH('$tanggalDenda') AND YEAR(tanggal) = YEAR('$tanggalDenda')";
            if (!mysqli_query($conn, $updateQuery)) {
                echo "Error updating id_cashbon in rincian_gaji: " . mysqli_error($conn);
            }
        } else {
            // Data doesn't exist, insert into rincian_gaji
            $insertQuery = "INSERT INTO rincian_gaji (nip, tanggal, id_cashbon) VALUES ('$nipDenda', '$tanggalDenda', '$id_cashbon')";
            if (!mysqli_query($conn, $insertQuery)) {
                echo "Error inserting data into rincian_gaji: " . mysqli_error($conn);
            }
        }
    }

    // Redirect ke halaman denda-karyawan.php jika berhasil ditambahkan
    $message = "Success!";
    echo "<script>alert('$message'); window.location.href = 'sa-cashbon.php';</script>";
    exit();
} else {
    // Tampilkan pesan error jika gagal menambahkan data
    echo "Error: " . $query . "<br>" . $conn->error;
}


    $conn->close();
}
?>
