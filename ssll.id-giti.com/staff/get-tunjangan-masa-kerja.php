<?php

include '../conn.php';

$nip = $data['nip'];

$queryTMK = "SELECT karyawan.*
        FROM karyawan
        WHERE karyawan.nip = '$nip'";
$resultTMK = $conn->query($queryTMK);
$dataTMK = $resultTMK->fetch_assoc();

$masuk = $dataTMK["tanggal_masuk"];
$tanggalSekarang = date("Y-m-d");
$selisih = date_diff(date_create($masuk), date_create($tanggalSekarang));

// Hitung selisih dalam bulan
$lamaKerja = ($selisih->y * 12) + $selisih->m;

$tunjanganMasaKerja = 0;

if($lamaKerja < 12){
        $dataTMK['tunjangan_masa_kerja'] = 0;
}
else if($lamaKerja >= 12 && $lamaKerja < 24){
        $dataTMK['tunjangan_masa_kerja'] = 100000;
}
else if($lamaKerja >= 24 && $lamaKerja < 36){
        $dataTMK['tunjangan_masa_kerja'] = 200000;
}
else if($lamaKerja >= 36 && $lamaKerja < 48){
        $dataTMK['tunjangan_masa_kerja'] = 300000;
}
else if($lamaKerja >= 48 && $lamaKerja < 60){
        $dataTMK['tunjangan_masa_kerja'] = 400000;
}
else if($lamaKerja >= 60 && $lamaKerja < 120){
        $dataTMK['tunjangan_masa_kerja'] = 500000;
}
else if($lamaKerja >= 120 && $lamaKerja < 180){
        $dataTMK['tunjangan_masa_kerja'] = 1000000;
}
else if($lamaKerja >= 180){
        $dataTMK['tunjangan_masa_kerja'] = 1500000;
}
else{
        $dataTMK['tunjangan_masa_kerja'] = 0;
}



?>