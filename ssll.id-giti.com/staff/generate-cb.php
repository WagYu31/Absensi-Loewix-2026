<?php
include_once "../conn.php";

if ((isset($_GET['bulan']) && isset($_GET['tahun'])) || (isset($bulan) && isset($tahun))) {
    $bulan_num = isset($_GET['bulan']) ? $_GET['bulan'] : $bulan;
    $tahun_num = isset($_GET['tahun']) ? $_GET['tahun'] : $tahun;
    
    $bulan_pad = str_pad((int)$bulan_num, 2, '0', STR_PAD_LEFT);
    $target_periode = "$tahun_num-$bulan_pad";
    $dateInPeriod = sprintf('%04d-%02d-28', $tahun_num, $bulan_num);

    $lunas = "Y";
    // Cari semua cashbon yang belum lunas dan tanggal mulainya <= periode ini
    $queryCB = "SELECT * FROM cashbon WHERE lunas != '$lunas' AND DATE_FORMAT(mulai, '%Y-%m') <= '$target_periode'";
    $check_queryCB = $conn->query($queryCB);

    if ($check_queryCB && $check_queryCB->num_rows > 0) {
        while ($dtCB = $check_queryCB->fetch_assoc()) {
            $id_cashbon = $dtCB['id_cashbon'];
            $jumlah = (float)$dtCB['jumlah'];
            $cicil = (int)$dtCB['cicil'];
            $nipcb = $dtCB['nip'];
            
            $bayar = ($cicil > 0) ? round($jumlah / $cicil) : $jumlah;

            // Cek apakah sudah pernah ada record potongan di bulan/tahun target ini
            $qCheckExist = "SELECT id_bayar_cashbon, cicilan FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon' AND DATE_FORMAT(tanggal, '%Y-%m') = '$target_periode' LIMIT 1";
            $resultExist = mysqli_query($conn, $qCheckExist);

            if ($resultExist && mysqli_num_rows($resultExist) > 0) {
                // Update record yang sudah ada
                $rowExist = mysqli_fetch_assoc($resultExist);
                $id_bayar = $rowExist['id_bayar_cashbon'];
                $updt = "UPDATE bayar_cashbon SET bayar = '$bayar', tanggal = '$dateInPeriod' WHERE id_bayar_cashbon = '$id_bayar'";
                mysqli_query($conn, $updt);
            } else {
                // Cari nomor cicilan berikutnya
                $qGetLastCicilan = "SELECT MAX(cicilan) as max_cicilan FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon'";
                $resultLastCicilan = mysqli_query($conn, $qGetLastCicilan);
                $rowLastCicilan = mysqli_fetch_assoc($resultLastCicilan);
                $cicilan = (int)($rowLastCicilan['max_cicilan'] ?? 0) + 1;

                $qUp = "INSERT INTO bayar_cashbon (id_cashbon, nip, tanggal, cicilan, bayar) VALUES ('$id_cashbon', '$nipcb', '$dateInPeriod', '$cicilan', '$bayar')";
                mysqli_query($conn, $qUp);
            }

            // Cek apakah total pembayaran sudah melunasi pinjaman
            $qSum = "SELECT SUM(bayar) as total_sudah_bayar, COUNT(*) as total_cicilan_tercatat FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon'";
            $resSum = mysqli_query($conn, $qSum);
            if ($resSum && $rowSum = mysqli_fetch_assoc($resSum)) {
                $total_sudah_bayar = (float)($rowSum['total_sudah_bayar'] ?? 0);
                $total_cicilan = (int)($rowSum['total_cicilan_tercatat'] ?? 0);
                if ($total_sudah_bayar >= $jumlah || ($cicil > 0 && $total_cicilan >= $cicil)) {
                    $queque = "UPDATE cashbon SET lunas = '$lunas' WHERE id_cashbon = '$id_cashbon'";
                    mysqli_query($conn, $queque);
                }
            }
        }
    }
}
?>
