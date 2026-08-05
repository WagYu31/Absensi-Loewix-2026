<?php

include "../conn.php";

if (isset($_GET['bulan']) && isset($_GET['tahun'])) {
    $bulan = $_GET['bulan']; // Ambil bulan dari URL
    $tahun = $_GET['tahun']; // Ambil tahun dari URL

    $lunas = "Y";
    $queryCB = "SELECT * FROM cashbon WHERE lunas != '$lunas' AND DATE_FORMAT(mulai, '%Y-%m') <= '$tahun-$bulan'";
    $check_queryCB = $conn->query($queryCB);

    $successFlag = false;

    while ($dtCB = $check_queryCB->fetch_assoc()) {
        $id_cashbon = $dtCB['id_cashbon'];
        $jumlah = $dtCB['jumlah'];
        $tgl = $dtCB['tgl'];
        $cicil = $dtCB['cicil'];
        $mulai = $dtCB['mulai'];
        $nipcb = $dtCB['nip'];
        $dateNow = date('Y-m-d');
        
        // Get the last cicilan value from bayar_cashbon
        $qGetLastCicilan = "SELECT MAX(cicilan) as max_cicilan FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon'";
        $resultLastCicilan = mysqli_query($conn, $qGetLastCicilan);
        $rowLastCicilan = mysqli_fetch_assoc($resultLastCicilan);
        
        $cicilan = ($rowLastCicilan['max_cicilan'] ?? 0) + 1;
        
        $bayar = $jumlah / $cicil;

        $qCheckExist = "SELECT COUNT(*) as count FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon' AND DATE_FORMAT(tanggal, '%Y-%m') = '$tahun-$bulan'";
        $result = mysqli_query($conn, $qCheckExist);
        $row = mysqli_fetch_assoc($result);
        $count = $row['count'];

        if ($count == 0) {
            $qUp = "INSERT INTO bayar_cashbon (id_cashbon, nip, tanggal, cicilan, bayar) VALUES ('$id_cashbon', '$nipcb', '$dateNow', $cicilan, $bayar)";
            if (mysqli_query($conn, $qUp)) {
                $successFlag = true;
            } else {
                echo "Error inserting data: " . mysqli_error($conn);
            }
        } else {
            $updt = "UPDATE bayar_cashbon SET bayar = '$bayar', tanggal = '$dateNow' WHERE nip = '$nipcb' AND id_cashbon = '$id_cashbon' AND DATE_FORMAT(tanggal, '%Y-%m') = '$tahun-$bulan'";
            if (mysqli_query($conn, $updt)) {
                $successFlag = true;
            } else {
                echo "Error inserting data: " . mysqli_error($conn);
            }
        }
        
        // Update status 'lunas' in cashbon if necessary
        if ($cicilan == $cicil) {
            $queque = "UPDATE cashbon SET lunas = '$lunas' WHERE nip = '$nipcb' AND id_cashbon = '$id_cashbon'";
            if (mysqli_query($conn, $queque)) {
                echo "Status 'lunas' updated successfully.";
            } else {
                echo "Error updating status 'lunas': " . mysqli_error($conn);
            }
        }
    }

    if ($successFlag) {
        echo "All data inserted successfully.";
    }
}
else {
    http_response_code(400); // Bad Request
}
?>
