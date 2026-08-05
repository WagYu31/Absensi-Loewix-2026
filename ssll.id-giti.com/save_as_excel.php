<?php
// Koneksi ke database
$host = "localhost";
$user = "root";
$password = "";
$database = "salary";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

$query = "SELECT rincian_gaji.*, karyawan.nama, karyawan.nama_bank, karyawan.nomor_rekening, karyawan.nama_pemilik_rekening FROM rincian_gaji JOIN karyawan ON rincian_gaji.nip = karyawan.nip";
$result = $conn->query($query);

if (!$result) {
    die("Query execution failed: " . $conn->error);
}

// Mengambil semua data rincian gaji dari database
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Mengubah data menjadi format JSON
$jsonData = json_encode($data);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Save as Excel</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="path/to/jquery-excel.js"></script>
</head>
<body>
    <table id="excel-table">
        <!-- Tabel dengan data rincian gaji -->
    </table>

    <button id="export-btn">Export to Excel</button>

    <script>
    $(document).ready(function() {
        var jsonData = <?php echo $jsonData; ?>; // Mendapatkan data JSON dari PHP

        // Membuat HTML tabel dari data rincian gaji
        var tableHtml = '<tr><th>NIP</th><th>Nama</th><th>Gaji Pokok</th><th>Tunjangan Jabatan</th><th>Tunjangan Masa Kerja</th><th>Tunjangan Lainnya</th><th>Denda</th><th>Total Gaji</th><th>Nama Bank</th><th>Nomor Rekening</th><th>Nama Pemilik Rekening</th></tr>';
        for (var i = 0; i < jsonData.length; i++) {
            tableHtml += '<tr>';
            tableHtml += '<td>' + jsonData[i].nip + '</td>';
            tableHtml += '<td>' + jsonData[i].nama + '</td>';
            tableHtml += '<td>' + jsonData[i].gaji_pokok + '</td>';
            tableHtml += '<td>' + jsonData[i].tunjangan_jabatan + '</td>';
            tableHtml += '<td>' + jsonData[i].tunjangan_masa_kerja + '</td>';
            tableHtml += '<td>' + jsonData[i].tunjangan_lainnya + '</td>';
            tableHtml += '<td>' + jsonData[i].denda + '</td>';
            tableHtml += '<td>' + jsonData[i].total_gaji + '</td>';
            tableHtml += '<td>' + jsonData[i].nama_bank + '</td>';
            tableHtml += '<td>' + jsonData[i].nomor_rekening + '</td>';
            tableHtml += '<td>' + jsonData[i].nama_pemilik_rekening + '</td>';
            tableHtml += '</tr>';
        }

        // Menambahkan tabel ke dalam elemen dengan ID "excel-table"
        $('#excel-table').html(tableHtml);

        // Menginisialisasi plugin jQuery-Excel
        $('#export-btn').click(function() {
            // Mengubah tabel menjadi file Excel
            $("#excel-table").excelExport({
                filename: "laporan_gaji_bulanan", // Nama file Excel
                sheetname: "Rincian Gaji Bulanan", // Nama sheet dalam file Excel
                fileext: ".xlsx" // Ekstensi file Excel
            });
        });
    });
    </script>
</body>
</html>
