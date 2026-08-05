<?php



// =============================================================================

// AWAL BLOK PERHITUNGAN DENDA CUTI

// =============================================================================



$total_denda_cuti = 0; // Inisialisasi variabel hasil akhir



try {

// LANGKAH 1: AMBIL SEMUA DATA YANG DIPERLUKAN DARI DATABASE

// =========================================================================



// 1a. Ambil jatah cuti tahunan untuk tahun yang ditentukan

$jatah_cuti_tahunan = 0;

$stmt_jatah = $conn->prepare("SELECT jumlah FROM jatah_cuti_tahunan WHERE tahun = ?");

$stmt_jatah->bind_param("s", $tahun);

$stmt_jatah->execute();

$result_jatah = $stmt_jatah->get_result();

if ($row_jatah = $result_jatah->fetch_assoc()) {

$jatah_cuti_tahunan = (int) $row_jatah['jumlah'];

}

$stmt_jatah->close();



// 1b. Ambil semua tanggal libur dari kalender kerja untuk tahun yang ditentukan

// Ini lebih efisien daripada query berulang kali di dalam loop.

$hari_libur_nasional = [];

$stmt_libur = $conn->prepare("SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND YEAR(tanggal_merah) = ? AND deleted_at IS NULL");

$stmt_libur->bind_param("s", $tahun);

$stmt_libur->execute();

$result_libur = $stmt_libur->get_result();

while ($row_libur = $result_libur->fetch_assoc()) {

$hari_libur_nasional[] = $row_libur['tanggal_merah']; // Format 'YYYY-MM-DD'

}

$stmt_libur->close();



// 1c. Ambil semua riwayat cuti karyawan yang disetujui dan potong gaji

$riwayat_cuti_karyawan = [];

$stmt_cuti = $conn->prepare("SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip = ? AND verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL");

$stmt_cuti->bind_param("s", $nip);

$stmt_cuti->execute();

$result_cuti = $stmt_cuti->get_result();

$riwayat_cuti_karyawan = $result_cuti->fetch_all(MYSQLI_ASSOC);

$stmt_cuti->close();





// LANGKAH 2: HITUNG TOTAL HARI CUTI YANG VALID SESUAI ATURAN

// =========================================================================



$total_durasi_cuti_valid = 0;

// Tentukan batas akhir periode perhitungan (akhir dari bulan yang ditentukan)

$periode_berakhir = new DateTime("$tahun-$bulan-01");

$periode_berakhir->modify('last day of this month');



// Loop melalui setiap data cuti yang dimiliki karyawan

foreach ($riwayat_cuti_karyawan as $cuti) {

$tgl_mulai = new DateTime($cuti['tgl_mulai']);

$tgl_selesai = new DateTime($cuti['tgl_selesai']);



// Tambahkan 1 hari ke tanggal selesai agar ikut terhitung dalam periode iterasi

$tgl_selesai->modify('+1 day');

$interval = new DateInterval('P1D'); // Interval 1 hari

$rentang_tanggal = new DatePeriod($tgl_mulai, $interval, $tgl_selesai);



// Loop setiap HARI di dalam rentang cuti

foreach ($rentang_tanggal as $hari) {

// Terapkan semua kondisi filter:

// 1. Cek apakah hari ini masih dalam tahun yang dihitung

if ($hari->format('Y') != $tahun) {

continue; // Lanjut ke hari berikutnya

}

// 2. Cek apakah hari ini melebihi batas bulan yang ditentukan

if ($hari > $periode_berakhir) {

continue; // Lanjut ke hari berikutnya

}

// 3. Cek apakah hari ini adalah hari Minggu (format 'N' -> 7 adalah Minggu)

if ($hari->format('N') == 7) {

continue; // Lanjut ke hari berikutnya

}

// 4. Cek apakah hari ini ada di dalam daftar libur nasional

if (in_array($hari->format('Y-m-d'), $hari_libur_nasional)) {

continue; // Lanjut ke hari berikutnya

}



// Jika semua filter lolos, maka hari ini dihitung sebagai hari cuti yang valid

$total_durasi_cuti_valid++;

}

}



// LANGKAH 3: HITUNG KELEBIHAN HARI CUTI

// =========================================================================



// Gunakan max(0, ...) untuk memastikan hasilnya tidak pernah negatif

$hari_diluar_jatah = max(0, $total_durasi_cuti_valid - $jatah_cuti_tahunan);



// LANGKAH 4: KALKULASI FINAL DENDA CUTI

// =========================================================================



$gaji_per_hari_kerja = $total_gapok / 24; // Sesuai formula Anda

$total_denda_cuti = $gaji_per_hari_kerja * $hari_diluar_jatah;





} catch (Exception $e) {

// Handle jika ada error, misalnya pada pembuatan objek DateTime

// Anda bisa mencatat error ini ke log untuk debugging

// error_log("Error pada kalkulasi denda cuti: " . $e->getMessage());

$total_denda_cuti = 0; // Set denda ke 0 jika terjadi error

}



// =============================================================================

// AKHIR BLOK PERHITUNGAN DENDA CUTI

// Variabel $total_denda_cuti kini siap digunakan.

// =============================================================================



?>