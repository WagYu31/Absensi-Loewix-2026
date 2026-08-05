<?php

include 'conn.php';

$nip = $_GET['nip'];

$query = "SELECT karyawan.*
        FROM karyawan
        WHERE karyawan.nip = '$nip'";
$result = $conn->query($query);
$data = $result->fetch_assoc();

$masuk = $data["tanggal_masuk"];
$tanggalSekarang = date("Y-m-d");
$selisih = date_diff(date_create($masuk), date_create($tanggalSekarang));

// Hitung selisih dalam bulan
$lamaKerja = ($selisih->y * 12) + $selisih->m;

$tunjanganMasaKerja = 0;

if($lamaKerja < 12){
        $data['tunjangan_masa_kerja'] = 0;
}
else if($lamaKerja >= 12 && $lamaKerja < 24){
        $data['tunjangan_masa_kerja'] = 100000;
}
else if($lamaKerja >= 24 && $lamaKerja < 36){
        $data['tunjangan_masa_kerja'] = 200000;
}
else if($lamaKerja >= 36 && $lamaKerja < 48){
        $data['tunjangan_masa_kerja'] = 300000;
}
else if($lamaKerja >= 48 && $lamaKerja < 60){
        $data['tunjangan_masa_kerja'] = 400000;
}
else if($lamaKerja >= 60 && $lamaKerja < 132){
        $data['tunjangan_masa_kerja'] = 500000;
}
else if($lamaKerja >= 132 && $lamaKerja < 180){
        $data['tunjangan_masa_kerja'] = 1000000;
}
else if($lamaKerja >= 180){
        $data['tunjangan_masa_kerja'] = 1500000;
}
else{
        $data['tunjangan_masa_kerja'] = 0;
}

$conn->close();

echo json_encode($data);

?>