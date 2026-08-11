<?php
session_start();
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    die("Akses tidak diizinkan.");
}

include '../conn.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Sync Presensi Foto ke Database</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head>";
echo "<body class='bg-light p-4'><div class='container bg-white p-4 rounded-4 shadow-sm' style='max-width:700px;'>";
echo "<h3 class='fw-bold text-primary mb-3'>🔄 Auto Sync Presensi Foto ke Database</h3>";

$attendance_dir = '../uploads/attendance/';
if (!is_dir($attendance_dir)) {
    die("<div class='alert alert-danger'>Folder uploads/attendance/ tidak ditemukan!</div>");
}

$files = scandir($attendance_dir);
$total_files = 0;
$total_inserted = 0;
$total_skipped = 0;

foreach ($files as $fname) {
    if (strpos($fname, 'presensi_') !== 0) continue;
    $total_files++;

    // Format: presensi_{nip}_{YYYYMMDD}_{HHMMSS}.jpg
    $parts = explode('_', $fname);
    if (count($parts) >= 4) {
        $nip = $parts[1];
        $date_str = $parts[2];
        $time_raw = explode('.', $parts[3])[0];

        if (strlen($date_str) === 8 && strlen($time_raw) === 6 && is_numeric($date_str) && is_numeric($time_raw)) {
            $formatted_datetime = substr($date_str,0,4).'-'.substr($date_str,4,2).'-'.substr($date_str,6,2).' '.substr($time_raw,0,2).':'.substr($time_raw,2,2).':'.substr($time_raw,4,2);
            $hour = (int)substr($time_raw, 0, 2);
            $tipe_absen = ($hour < 13) ? 'masuk' : 'pulang';

            // Check if record already exists in absen_manual
            $stmt_chk = $conn->prepare("SELECT id FROM absen_manual WHERE image = ? LIMIT 1");
            $stmt_chk->bind_param("s", $fname);
            $stmt_chk->execute();
            $res_chk = $stmt_chk->get_result();

            if ($res_chk->num_rows === 0) {
                // Fetch employee name
                $nama_kar = 'Karyawan';
                $stmt_k = $conn->prepare("SELECT nama FROM karyawan WHERE nip = ? OR nik = ? OR pin_absen = ? LIMIT 1");
                $stmt_k->bind_param("sss", $nip, $nip, $nip);
                $stmt_k->execute();
                $res_k = $stmt_k->get_result();
                if ($r_k = $res_k->fetch_assoc()) {
                    $nama_kar = $r_k['nama'];
                }
                $stmt_k->close();

                $stmt_ins = $conn->prepare("INSERT INTO absen_manual (tgl_absen, tipe_absen, pin, nip, image, nama, lokasi_absen, lokasi_koordinat, verif) VALUES (?, ?, ?, ?, ?, ?, 'Lokasi Kantor', '-6.130189784035325,106.75142085117402', 'Yes')");
                $stmt_ins->bind_param("ssssss", $formatted_datetime, $tipe_absen, $nip, $nip, $fname, $nama_kar);
                if ($stmt_ins->execute()) {
                    $total_inserted++;
                }
                $stmt_ins->close();
            } else {
                $total_skipped++;
            }
            $stmt_chk->close();
        }
    }
}

echo "<div class='alert alert-success'>";
echo "<h5>✅ Proses Sinkronisasi Selesai!</h5>";
echo "<ul class='mb-0'>";
echo "<li>Total File Foto Ditemukan: <b>$total_files file</b></li>";
echo "<li>Presensi Baru Berhasil Dibereskan / Disinkron: <b class='text-success'>$total_inserted record</b></li>";
echo "<li>Presensi Sudah Ada Sebelumnya: <b>$total_skipped record</b></li>";
echo "</ul></div>";

echo "<a href='data-absensi.php' class='btn btn-primary fw-bold mt-2'>← Kembali ke Validasi Absensi Manual</a>";
echo "</div></body></html>";
?>
