<?php
include '../conn.php';

if (isset($_GET['bulan']) && isset($_GET['tahun'])) {
    $bulan = $_GET['bulan']; // Ambil bulan dari URL
    $tahun = $_GET['tahun']; // Ambil tahun dari URL

    $queryKunci = "SELECT * FROM kunci_gaji WHERE bulan = '$bulan' AND tahun = '$tahun' AND kunci = 'Lock'";
    $resultKunci = $conn->query($queryKunci);
    
    $kunci = "Lock";

    if (!$resultKunci) {
        die("Query execution failed for kunci_gaji: " . $conn->error);
    }

    if ($resultKunci->num_rows > 0) {
        echo "Data sudah terkunci.";
        http_response_code(400); // Bad Request
        header("Location: penggajian.php");
    } 
    else {
        // $kncQuery = "INSERT INTO kunci_gaji (bulan, tahun, kunci) VALUES ('$bulan', '$tahun', '$kunci')";
        // if (!mysqli_query($conn, $kncQuery)) {
        //     die("Insert into kunci_gaji failed: " . mysqli_error($conn));
        // }

        $query = "SELECT * FROM karyawan 
                WHERE karyawan.nip != '001' 
                AND karyawan.nip != '70326'
                AND karyawan.status_karyawan != 'tidak aktif'
                AND DATE_FORMAT(karyawan.tanggal_masuk, '%Y-%m') <= '$tahun-$bulan'";

        $result = $conn->query($query);

        if (!$result) {
            die("Query execution failed for fetching karyawan data: " . $conn->error);
        }

        // Loop melalui hasil query dan perbarui atau masukkan data ke dalam rincian_gaji
        while ($data = mysqli_fetch_assoc($result)) {
            $nip = $data['nip'];
            $gaji = $data['gaji_pokok'];
            $tunjangan = $data['tunjangan'];
            $date = date('Y-m-t', strtotime($tahun . '-' . $bulan . '-01'));

            
            // $tanggal_masuk = $data['tanggal_masuk'];
            
            // // Ambil bulan dan tahun dari tanggal masuk karyawan
            // $bulan_masuk = date('m', strtotime($tanggal_masuk));
            // $tahun_masuk = date('Y', strtotime($tanggal_masuk));
            // $tgl_masuk = date('t', strtotime($tgl_masuk));

            // if($bulan_masuk == $bulan && $tahun_masuk == $tahun){
            //     $day = cal_days_in_month(CAL_GREGORIAN, $bulan, $year);
            //     $sisa = $day - $tgl_masuk;
            //     $calculatedGaji = $gaji / 4 * ceil($sisa / 7);
            // }

            

            // Periksa apakah data sudah ada di rincian_gaji
            $checkQuery = "SELECT * FROM rincian_gaji WHERE nip = '$nip' AND MONTH(rincian_gaji.tanggal) = '$bulan' AND YEAR(rincian_gaji.tanggal) = '$tahun'";
            $checkResult = mysqli_query($conn, $checkQuery);

            if (!$checkResult) {
                die("Query execution failed for checking existing data in rincian_gaji: " . $conn->error);
            }

            if (mysqli_num_rows($checkResult) > 0) {
                // Data sudah ada, lakukan update
                // $data = mysqli_fetch_assoc($checkResult);
                // $id_cashbon = $data['id_cashbon'];
                // include "generate-cb.php";
                $updateQuery = "UPDATE rincian_gaji SET gaji = $gaji, tunjangan_jabatan = $tunjangan WHERE nip = '$nip' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'";
                        if (!mysqli_query($conn, $updateQuery)) {
                            die("Update existing data in rincian_gaji failed: " . mysqli_error($conn));
                        }
            } else {
                // Data belum ada, lakukan insert
                $insertQuery = "INSERT INTO rincian_gaji (nip, tanggal, gaji, tunjangan_jabatan) VALUES ('$nip', '$date', $gaji, $tunjangan)";
                if (!mysqli_query($conn, $insertQuery)) {
                    die("Insert new data into rincian_gaji failed: " . mysqli_error($conn));
                }
            }
        }

        // Jalankan sinkronisasi dan generate potongan cicilan cashbon untuk periode ini
        include_once "generate-cb.php";

        http_response_code(200); // OK
        header("Location: penggajian.php?bulan=" . urlencode($bulan) . "&tahun=" . urlencode($tahun));
        exit();
    }
} else {
    http_response_code(400); // Bad Request
}
?>
