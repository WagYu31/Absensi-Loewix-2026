<?php
include_once __DIR__ . '/../conn.php';

if (!function_exists('syncCashbonForPeriod')) {
    function syncCashbonForPeriod($conn, $bulan_num, $tahun_num) {
        $bulan_int = (int)$bulan_num;
        $tahun_int = (int)$tahun_num;
        $bulan_pad = str_pad($bulan_int, 2, '0', STR_PAD_LEFT);
        $target_periode = "$tahun_int-$bulan_pad";
        $dateInPeriod = sprintf('%04d-%02d-28', $tahun_int, $bulan_int);

        // Cari semua cashbon yang belum lunas (lunas != 'Y' atau lunas IS NULL)
        // Dan tanggal mulai atau tanggal ambil <= target periode
        $queryCB = "SELECT * FROM cashbon 
                    WHERE (lunas IS NULL OR lunas != 'Y') 
                    AND (
                        (mulai IS NOT NULL AND mulai != '0000-00-00' AND DATE_FORMAT(mulai, '%Y-%m') <= '$target_periode')
                        OR
                        ((mulai IS NULL OR mulai = '0000-00-00') AND tanggal IS NOT NULL AND DATE_FORMAT(tanggal, '%Y-%m') <= '$target_periode')
                    )";
        $check_queryCB = $conn->query($queryCB);

        if ($check_queryCB && $check_queryCB->num_rows > 0) {
            while ($dtCB = $check_queryCB->fetch_assoc()) {
                $id_cashbon = $dtCB['id_cashbon'];
                $jumlah = (float)$dtCB['jumlah'];
                $cicil = (int)($dtCB['cicil'] ?? 1);
                if ($cicil <= 0) $cicil = 1;
                $nipcb = $dtCB['nip'];
                
                $bayar = round($jumlah / $cicil);

                // Cek apakah sudah pernah ada record potongan di bulan/tahun target ini
                $qCheckExist = "SELECT id_bayar_cashbon, cicilan FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon' AND (DATE_FORMAT(tanggal, '%Y-%m') = '$target_periode' OR (MONTH(tanggal) = $bulan_int AND YEAR(tanggal) = $tahun_int)) LIMIT 1";
                $resultExist = mysqli_query($conn, $qCheckExist);

                if ($resultExist && mysqli_num_rows($resultExist) > 0) {
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

                    // Pastikan cicilan belum melebihi jumlah tenor
                    if ($cicilan <= $cicil) {
                        $qUp = "INSERT INTO bayar_cashbon (id_cashbon, nip, tanggal, cicilan, bayar) VALUES ('$id_cashbon', '$nipcb', '$dateInPeriod', '$cicilan', '$bayar')";
                        mysqli_query($conn, $qUp);
                    }
                }

                // Cek apakah total pembayaran sudah melunasi pinjaman
                $qSum = "SELECT SUM(bayar) as total_sudah_bayar, COUNT(*) as total_cicilan_tercatat FROM bayar_cashbon WHERE id_cashbon = '$id_cashbon'";
                $resSum = mysqli_query($conn, $qSum);
                if ($resSum && $rowSum = mysqli_fetch_assoc($resSum)) {
                    $total_sudah_bayar = (float)($rowSum['total_sudah_bayar'] ?? 0);
                    $total_cicilan = (int)($rowSum['total_cicilan_tercatat'] ?? 0);
                    if ($total_sudah_bayar >= $jumlah || $total_cicilan >= $cicil) {
                        $queque = "UPDATE cashbon SET lunas = 'Y' WHERE id_cashbon = '$id_cashbon'";
                        mysqli_query($conn, $queque);
                    }
                }
            }
        }
    }
}

// Jika dipanggil via include atau HTTP GET
$bulan_val = $_GET['bulan'] ?? ($bulan ?? date('m'));
$tahun_val = $_GET['tahun'] ?? ($tahun ?? date('Y'));
syncCashbonForPeriod($conn, $bulan_val, $tahun_val);
?>
