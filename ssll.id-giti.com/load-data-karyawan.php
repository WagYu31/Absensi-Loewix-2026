<?php
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nip'])) {
  $nip = $_POST['nip'];

  $query = "SELECT k.*, j.gaji_pokok, tj.jumlah AS tunjangan_jabatan, tl.jumlah AS tunjangan_lainnya, SUM(d.jumlah) AS denda
            FROM karyawan k
            LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            LEFT JOIN tunjangan_jabatan tj ON k.id_jabatan = tj.id_jabatan
            LEFT JOIN tunjangan_lainnya tl ON k.nip = tl.nip
            LEFT JOIN denda d ON k.nip = d.nip
            WHERE k.nip = '$nip'
            AND MONTH(d.bulan) = MONTH(NOW()) AND YEAR(d.bulan) = YEAR(NOW())
            GROUP BY k.nip";
  $result = $conn->query($query);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $employeeData = array(
      'gajiPokok' => $row['gaji_pokok'],
      'tunjanganJabatan' => $row['tunjangan_jabatan'],
      'tunjanganMasaKerja' => calculateTunjanganMasaKerja($row['tanggal_masuk']),
      'tunjanganLainnya' => $row['tunjangan_lainnya'],
      'denda' => $row['denda']
    );
    echo json_encode($employeeData);
  } else {
    echo json_encode(null);
  }
}

function calculateTunjanganMasaKerja($tanggalMasuk) {
  // Kode untuk menghitung tunjangan masa kerja berdasarkan tanggal masuk karyawan
  // ...

  return $tunjanganMasaKerja;
}

$conn->close();
?>
