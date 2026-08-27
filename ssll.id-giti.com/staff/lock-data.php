<?php
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
header("Location: proses-lock-gaji.php?bulan=" . urlencode($bulan) . "&tahun=" . urlencode($tahun) . "&action=lock");
exit();
?>