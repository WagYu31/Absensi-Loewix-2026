<?php
session_start();

if (!isset($_SESSION["nip"]) || $_SESSION["role"] !== "superadmin") {
    header("Location: index.php");
    exit();
}

include 'conn.php';

// Mengambil bulan dan tahun saat ini
$currentMonth = date('m');
$currentYear = date('Y');

// Query untuk mengambil data karyawan
$query = "SELECT karyawan.*, 
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = $currentMonth AND YEAR(tunjangan_lainnya.tanggal) = $currentYear) AS total_tunjangan_lainnya,
                (SELECT SUM(jumlah) FROM denda WHERE denda.nip = karyawan.nip AND MONTH(denda.tanggal) = $currentMonth AND YEAR(denda.tanggal) = $currentYear) AS total_denda
        FROM karyawan";

$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

$sum = 0;
$existData = false; // Inisialisasi variabel untuk mengecek apakah data dengan kombinasi 'nip' dan tanggal sudah ada

while ($data = $result->fetch_assoc()) : 
    include 'get-tunjangan-masa-kerja.php';
    $nipK = $data['nip'];
    $bulan = date('m'); // Ubah sesuai dengan bulan yang sesuai dengan data gaji saat ini
    $tahun = date('Y'); // Ubah sesuai dengan tahun yang sesuai dengan data gaji saat ini
    $tanggal = date('Y-m-d');
    $tunjanganMasaKerja = $dataTMK['tunjangan_masa_kerja'];
    $tunjanganLainnya = $data['total_tunjangan_lainnya'];
    $denda = $data['total_denda'];

    // Periksa apakah data dengan kombinasi 'nip' dan tanggal sudah ada
    $queryCheck = "SELECT * FROM rincian_gaji WHERE nip='$nipK' AND MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'";
    $resultCheck = $conn->query($queryCheck);

    if ($resultCheck->num_rows > 0) {
        $existData = true; // Set variabel existData menjadi true jika data sudah ada
    } else {
        // Jika data tidak ada, lakukan INSERT
        $queryInsert = "INSERT INTO rincian_gaji (nip, tanggal, tunjangan_masa_kerja, tunjangan_lainnya, denda) VALUES ('$nipK', '$tanggal', '$tunjanganMasaKerja', '$tunjanganLainnya', '$denda')";
        $resultInsert = $conn->query($queryInsert);

        if (!$resultInsert) {
            die("Insert execution failed: " . $conn->error);
        }
    }
endwhile;

$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Gaji</title>
</head>
<body>

<?php
// Tampilkan peringatan dan dua buah link jika data sudah ada
if ($existData) {
    echo "Data dengan NIP dan Bulan ini sudah ada. Anda dapat melakukan update semua data dengan mengklik tautan berikut:<br>";
    echo "<a href='update-all-data.php?bulan=$currentMonth&tahun=$currentYear'>Update Semua Data</a><br>";
    echo "<a href='laporan-gaji.php'>Kembali ke Halaman Gaji</a>";
}
?>

</body>
</html>
