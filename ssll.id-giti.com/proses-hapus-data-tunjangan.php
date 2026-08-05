<?php
session_start();

// Cek apakah pengguna telah login
if (!isset($_SESSION['nip']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    // Jika tidak ada sesi pengguna atau peran pengguna bukan admin, arahkan ke halaman login atau halaman lainnya
    header('Location: login.html');
    exit();
}

include 'conn.php';

if (isset($_GET['id_tunjangan_lain'])) {
    $idTunjangan = $_GET['id_tunjangan_lain'];

    // Ambil data tunjangan sebelum dihapus
    $query = "SELECT * FROM tunjangan_lainnya WHERE id_tunjangan_lain = '$idTunjangan'";
    $resultGetTunjangan = $conn->query($query);

    if ($resultGetTunjangan->num_rows > 0) {
        $tunjanganData = $resultGetTunjangan->fetch_assoc();
        $nip = $tunjanganData['nip'];
        $tanggal = $tunjanganData['tanggal'];
        $jumlah = $tunjanganData['jumlah'];

        // Hapus data tunjangan dari tabel tunjangan_lainnya
        $queryDelete = "DELETE FROM tunjangan_lainnya WHERE id_tunjangan_lain = '$idTunjangan'";
        
        if ($conn->query($queryDelete) === TRUE) {
            // Lakukan pengecekan apakah data dengan NIP dan tanggal yang sama sudah ada di tabel rincian_gaji
            $queryCheck = "SELECT * FROM rincian_gaji WHERE nip = '$nip' AND MONTH(tanggal)='".date('m', strtotime($tanggal))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggal))."'";
            $resultCheck = $conn->query($queryCheck);

            if ($resultCheck->num_rows > 0) {
                // Jika data dengan NIP dan tanggal yang sama sudah ada, maka lakukan update jumlah tunjangan
                $queryUpdate = "UPDATE rincian_gaji SET tunjangan_lainnya = tunjangan_lainnya - $jumlah WHERE nip = '$nip' AND MONTH(tanggal)='".date('m', strtotime($tanggal))."' AND YEAR(tanggal)='".date('Y', strtotime($tanggal))."'";
                $resultUpdate = $conn->query($queryUpdate);

                if (!$resultUpdate) {
                    echo "Error: " . $queryUpdate . "<br>" . $conn->error;
                }
            }
            
            // Redirect ke halaman tunjangan-karyawan.php jika berhasil dihapus
            $message = "Success!";
            echo "<script>alert('$message'); window.location.href = 'tunjangan-karyawan.php';</script>";
            exit();
        } else {
            // Tampilkan pesan error jika gagal menghapus data
            echo "Error: " . $queryDelete . "<br>" . $conn->error;
        }
    } else {
        // Data tidak ditemukan, arahkan kembali ke halaman tunjangan-karyawan.php
        header('Location: tunjangan-karyawan.php');
        exit();
    }

    $conn->close();
} else {
    // Jika parameter id tidak ditemukan, arahkan kembali ke halaman tunjangan-karyawan.php
    header('Location: tunjangan-karyawan.php');
    exit();
}
?>
