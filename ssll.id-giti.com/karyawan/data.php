<?php
// session_start(); // Diasumsikan sudah ada di atas
// include '../conn.php'; // Diasumsikan sudah ada di atas
// include 'get-kar-login-data.php'; // Diasumsikan sudah ada, menyediakan $nip, $nik, $nama_lengkap, dll.

// --- PENGATURAN PERIODE SAAT INI UNTUK DASHBOARD ---
$current_year_dash = date('Y');
$current_month_dash = date('m'); // Format '01', '02', ..., '12'
$current_month_name_full_dash = date('F', mktime(0, 0, 0, (int)$current_month_dash, 1, (int)$current_year_dash)); // Nama bulan lengkap
$periode_display_dash = $current_month_name_full_dash . " " . $current_year_dash;

// --- PENGATURAN PERIODE UNTUK RINGKASAN ABSENSI (SATU BULAN LALU) ---
$date_for_last_month_absensi = new DateTime("first day of last month");
$last_month_year_absensi = $date_for_last_month_absensi->format('Y');
$last_month_num_absensi = $date_for_last_month_absensi->format('m'); // Format '01', '02', ..., '12'
$last_month_name_full_absensi = $date_for_last_month_absensi->format('F');
$periode_absensi_display_dash = $last_month_name_full_absensi . " " . $last_month_year_absensi;

// --- INISIALISASI VARIABEL UNTUK TAMPILAN DASHBOARD ---
$total_hari_kerja_dash = 0;
$jumlah_hadir_dash = 0;
$jumlah_izin_sakit_dash = 0;
$total_menit_terlambat_dash = 0;
$link_ke_detail_absen_dash = "absen.php?nik=" . htmlspecialchars($nik) . "&bulan=" . $last_month_num_absensi . "&tahun=" . $last_month_year_absensi;

// Info Slip Gaji
$info_gaji_bulan_ini_dash = [
    'periode' => $periode_display_dash,
    'gaji_bersih_rp' => "Rp 0",
    'tanggal_bayar' => ""
];
$link_ke_riwayat_gaji_dash = "riwayat-gaji.php?bulan=" . $current_month_dash . "&tahun=" . $current_year_dash;


// --- 1. LOGIKA UNTUK RINGKASAN ABSENSI (BULAN LALU) ---
// a. Hitung Total Hari Kerja Efektif untuk BULAN LALU
$total_days_in_last_month = (int)$date_for_last_month_absensi->format('t');
$public_holidays_last_month_dates = [];

// Query tanggal merah dari kalender_kerja untuk bulan lalu
// Menggunakan nama kolom `tanggal_merah` yang berisi tanggalnya
$stmt_holidays = $conn->prepare("SELECT tanggal_merah FROM kalender_kerja WHERE YEAR(tanggal_merah) = ? AND MONTH(tanggal_merah) = ? AND libur = 'Yes'");
if ($stmt_holidays) {
    $stmt_holidays->bind_param("ss", $last_month_year_absensi, $last_month_num_absensi);
    $stmt_holidays->execute();
    $result_holidays = $stmt_holidays->get_result();
    while ($holiday_row = $result_holidays->fetch_assoc()) {
        if (!empty($holiday_row['tanggal_merah'])) {
            try {
                // Pastikan format YYYY-MM-DD untuk konsistensi in_array
                $public_holidays_last_month_dates[] = (new DateTime($holiday_row['tanggal_merah']))->format('Y-m-d');
            } catch (Exception $e) {
                error_log("Dashboard - Invalid date format in kalender_kerja: " . $holiday_row['tanggal_merah'] . " | Error: " . $e->getMessage());
            }
        }
    }
    $stmt_holidays->close();
} else {
    error_log("Dashboard - Gagal prepare query kalender_kerja: " . $conn->error);
}

$workdays_count_last_month = 0;
for ($day_lm = 1; $day_lm <= $total_days_in_last_month; $day_lm++) {
    $current_date_str_lm = $last_month_year_absensi . "-" . $last_month_num_absensi . "-" . str_pad($day_lm, 2, '0', STR_PAD_LEFT);
    try {
        $date_obj_lm = new DateTime($current_date_str_lm);
        $day_of_week_num_lm = $date_obj_lm->format('N'); // 1 (Senin) - 7 (Minggu)

        if ($day_of_week_num_lm == 7) { // Jika hari Minggu, bukan hari kerja
            continue;
        }
        if (in_array($current_date_str_lm, $public_holidays_last_month_dates)) { // Jika tanggal merah
            continue;
        }
        // Jika perusahaan Anda juga libur pada hari Sabtu (selain yang sudah masuk tanggal merah), tambahkan:
        // if ($day_of_week_num_lm == 6) { continue; } 
        $workdays_count_last_month++;
    } catch (Exception $e) {
        error_log("Dashboard - Error creating DateTime for workday calculation: " . $current_date_str_lm . " | Error: " . $e->getMessage());
    }
}
$total_hari_kerja_dash = $workdays_count_last_month;

// b. Hitung Kehadiran dan Keterlambatan dari tabel 'absen' untuk BULAN LALU
//    Menggunakan $nik (NIK database karyawan dari get-kar-login-data.php)
$sql_kehadiran_lm = "SELECT 
                        DATE(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) as tanggal_scan_date,
                        MIN(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS jam_masuk_aktual_scan,
                        MAX(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) AS jam_pulang_aktual_scan, -- Untuk cek validitas scan
                        k.shifting 
                   FROM absen a
                   JOIN karyawan k ON a.nip = k.nik 
                   WHERE k.nik = ? 
                     AND MONTH(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = ?
                     AND YEAR(STR_TO_DATE(a.tgl_scan, '%d-%m-%Y %H:%i:%s')) = ?
                   GROUP BY tanggal_scan_date, k.shifting
                   ORDER BY tanggal_scan_date ASC";
$stmt_kehadiran_lm = $conn->prepare($sql_kehadiran_lm);
if ($stmt_kehadiran_lm) {
    $stmt_kehadiran_lm->bind_param("sss", $nik, $last_month_num_absensi, $last_month_year_absensi);
    $stmt_kehadiran_lm->execute();
    $result_kehadiran_lm = $stmt_kehadiran_lm->get_result();
    $jumlah_hadir_dash = 0; // Inisialisasi ulang
    $processed_hadir_dates = []; // Untuk memastikan hari hadir dihitung sekali

    while ($rec_abs_lm = $result_kehadiran_lm->fetch_assoc()) {
        // Validasi dasar untuk jam_masuk_aktual_scan
        if (empty($rec_abs_lm['jam_masuk_aktual_scan']) || $rec_abs_lm['jam_masuk_aktual_scan'] === '-' || $rec_abs_lm['jam_masuk_aktual_scan'] === null) {
            error_log("Dashboard - Invalid jam_masuk_aktual_scan (value: " . ($rec_abs_lm['jam_masuk_aktual_scan'] ?? 'NULL') . ") for NIK {$nik} on date (approx) {$rec_abs_lm['tanggal_scan_date']}");
            continue;
        }
        // Validasi juga jam_pulang_aktual_scan jika akan digunakan untuk menentukan validitas hari hadir
        $valid_scan_masuk = false;
        $valid_scan_pulang = false;
        $jam_masuk_dt = null;
        $jam_pulang_dt = null;

        try {
            $jam_masuk_dt = new DateTime($rec_abs_lm['jam_masuk_aktual_scan']);
            $valid_scan_masuk = true;
        } catch (Exception $e) {
            error_log("Dashboard - Error parsing jam_masuk_aktual_scan (NIK: {$nik}, Data: " . $rec_abs_lm['jam_masuk_aktual_scan'] . "): " . $e->getMessage());
        }

        if (!empty($rec_abs_lm['jam_pulang_aktual_scan']) && $rec_abs_lm['jam_pulang_aktual_scan'] !== '-' && $rec_abs_lm['jam_pulang_aktual_scan'] !== null) {
            try {
                $jam_pulang_dt = new DateTime($rec_abs_lm['jam_pulang_aktual_scan']);
                if ($jam_pulang_dt > $jam_masuk_dt) { // Pastikan jam pulang setelah jam masuk
                    $valid_scan_pulang = true;
                }
            } catch (Exception $e) {
                error_log("Dashboard - Error parsing jam_pulang_aktual_scan (NIK: {$nik}, Data: " . $rec_abs_lm['jam_pulang_aktual_scan'] . "): " . $e->getMessage());
            }
        }

        // Hitung hadir jika ada minimal scan masuk yang valid ATAU scan pulang yang valid (sesuaikan aturan perusahaan)
        // Untuk sederhana, kita anggap hadir jika ada scan masuk yang valid pada hari itu
        if ($valid_scan_masuk && !isset($processed_hadir_dates[$rec_abs_lm['tanggal_scan_date']])) {
            $jumlah_hadir_dash++;
            $processed_hadir_dates[$rec_abs_lm['tanggal_scan_date']] = true;
        }
        
        $tgl_only_db = $jam_masuk_dt->format('Y-m-d');

        // Hitung keterlambatan hanya jika scan masuk valid
        if ($valid_scan_masuk) {
            $current_shifting_abs_lm = $rec_abs_lm["shifting"];
            
            $query_req_shift = "SELECT shifting FROM shift_req WHERE nip = ? AND ? BETWEEN tgl_mulai AND tgl_selesai LIMIT 1";
            $stmt_req = $conn->prepare($query_req_shift);
                if ($stmt_req) {
                    $stmt_req->bind_param("ss", $pinAbsen, $tgl_only_db);
                    $stmt_req->execute();
                    $result_req = $stmt_req->get_result();
                if ($result_req->num_rows > 0) {
                    $row_req = $result_req->fetch_assoc();
                    $current_shifting_abs_lm = $row_req['shifting'];
                }
                    $stmt_req->close();
                }
            
            $nama_hari_eng_abs_lm = $jam_masuk_dt->format('l');

            if ($nama_hari_eng_abs_lm == "Saturday") {
                $current_shifting_abs_lm = ($current_shifting_abs_lm == "T") ? "TW" : "W";
            }
            $jam_masuk_seharusnya_str_abs_lm = match ($current_shifting_abs_lm) {
                "P" => "07:00:00",
                "M" => "08:30:00",
                "S", "T", "TW" => "09:30:00",
                "W" => "08:30:00",
                default => "09:00:00"
            };
            $waktu_masuk_seharusnya_unix_abs_lm = strtotime($rec_abs_lm['tanggal_scan_date'] . " " . $jam_masuk_seharusnya_str_abs_lm);
            $waktu_scan_masuk_unix_abs_lm = $jam_masuk_dt->getTimestamp();

            if ($waktu_scan_masuk_unix_abs_lm > $waktu_masuk_seharusnya_unix_abs_lm) {
                $keterlambatan_hari_ini_menit_abs_lm = floor(($waktu_scan_masuk_unix_abs_lm - $waktu_masuk_seharusnya_unix_abs_lm) / 60);
                $total_menit_terlambat_dash += $keterlambatan_hari_ini_menit_abs_lm;
            }
        }
    }
    $stmt_kehadiran_lm->close();
} else {
    error_log("Dashboard - Gagal prepare query kehadiran bulan lalu: " . $conn->error);
}

// Pastikan variabel untuk batas bulan lalu terdefinisi dengan benar SEBELUM digunakan
$first_day_last_month_str = $last_month_year_absensi . "-" . $last_month_num_absensi . "-01";
try {
    $lm_start_dt_obj = new DateTime($first_day_last_month_str); // Objek DateTime untuk hari pertama bulan lalu
    $lm_end_dt_obj = (new DateTime($first_day_last_month_str))->modify('last day of this month'); // Objek DateTime untuk hari terakhir bulan lalu
} catch (Exception $e) {
    error_log("Dashboard - Fatal error creating date objects for last month boundaries: " . $e->getMessage());
    // Handle error, mungkin set default atau hentikan eksekusi bagian ini
    // Untuk sekarang, kita akan set default agar tidak ada error fatal berikutnya, tapi data mungkin tidak akurat
    $lm_start_dt_obj = new DateTime("first day of last month"); // Fallback
    $lm_end_dt_obj = (new DateTime("first day of last month"))->modify('last day of this month'); // Fallback
}


// c. Hitung Total Hari Kerja Efektif dari Cuti yang Disetujui yang DIMULAI pada BULAN LALU (semua jenis)
$jumlah_izin_sakit_dash = 0;

$sql_cuti_bulan_lalu = "SELECT tgl_mulai, tgl_selesai 
                        FROM cuti 
                        WHERE nip = ? 
                          AND verif = 'Disetujui' 
                          AND YEAR(tgl_mulai) = ? 
                          AND MONTH(tgl_mulai) = ?
                          AND deleted_at IS NULL";

$stmt_cuti_lm = $conn->prepare($sql_cuti_bulan_lalu);

if ($stmt_cuti_lm) {
    $stmt_cuti_lm->bind_param("sss", $nip, $last_month_year_absensi, $last_month_num_absensi);
    $stmt_cuti_lm->execute();
    $result_cuti_lm = $stmt_cuti_lm->get_result();

    while ($cuti_row = $result_cuti_lm->fetch_assoc()) {
        if (
            empty($cuti_row['tgl_mulai']) || empty($cuti_row['tgl_selesai']) ||
            !strtotime($cuti_row['tgl_mulai']) || !strtotime($cuti_row['tgl_selesai'])
        ) {
            error_log("Dashboard - Invalid tgl_mulai or tgl_selesai from cuti table for NIP {$nip}: " . print_r($cuti_row, true));
            continue;
        }

        try {
            $cuti_start_dt = new DateTime($cuti_row['tgl_mulai']);
            $cuti_end_dt = new DateTime($cuti_row['tgl_selesai']);

            // Durasi cuti dihitung dari tgl_mulai (yang pasti di bulan lalu)
            // hingga tgl_selesai atau akhir bulan lalu, mana yang lebih dulu.
            $hitung_sampai_tanggal = min($cuti_end_dt, $lm_end_dt_obj); // Menggunakan $lm_end_dt_obj yang sudah didefinisikan

            $temp_date_iterator = clone $cuti_start_dt;

            while ($temp_date_iterator <= $hitung_sampai_tanggal) {
                $day_of_week_num = $temp_date_iterator->format('N');
                $current_date_str_for_check = $temp_date_iterator->format('Y-m-d');

                if ($day_of_week_num != 7 && !in_array($current_date_str_for_check, $public_holidays_last_month_dates)) {
                    $jumlah_izin_sakit_dash++;
                }
                $temp_date_iterator->modify('+1 day');
            }
        } catch (Exception $e) {
            error_log("Dashboard - Error processing cuti dates (NIP: {$nip}, Mulai: " . $cuti_row['tgl_mulai'] . ", Selesai: " . $cuti_row['tgl_selesai'] . "): " . $e->getMessage());
        }
    }
    $stmt_cuti_lm->close();
} else {
    error_log("Dashboard - Gagal prepare query cuti bulan lalu: " . $conn->error);
}

$query_gaji_dash = "SELECT karyawan.*, 
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = '$current_month_dash' AND YEAR(tunjangan_lainnya.tanggal) = '$current_year_dash' AND tunjangan_lainnya.ket1 = 'ganti') AS total_tunjangan_lainnya_ganti,
                (SELECT SUM(jumlah) FROM tunjangan_lainnya WHERE tunjangan_lainnya.nip = karyawan.nip AND MONTH(tunjangan_lainnya.tanggal) = '$current_month_dash' AND YEAR(tunjangan_lainnya.tanggal) = '$current_year_dash' AND tunjangan_lainnya.ket1 = 'bonus') AS total_tunjangan_lainnya_bonus,
                (SELECT SUM(jumlah) FROM denda WHERE denda.nip = karyawan.nip AND MONTH(denda.tanggal) = '$current_month_dash' AND YEAR(denda.tanggal) = '$current_year_dash') AS total_denda,
                (SELECT SUM(bayar) FROM bayar_cashbon WHERE bayar_cashbon.nip = karyawan.nip AND MONTH(bayar_cashbon.tanggal) = '$current_month_dash' AND YEAR(bayar_cashbon.tanggal) = '$current_year_dash') AS total_cashbon
            FROM karyawan
            WHERE karyawan.nip = '$nip'";
$result_gaji_dash_main = $conn->query($query_gaji_dash);
$employee_dash = null;
if ($result_gaji_dash_main && $result_gaji_dash_main->num_rows > 0) {
    $employee_dash = $result_gaji_dash_main->fetch_assoc();
} else {
    // Handle jika data karyawan utama tidak ditemukan
    error_log("Dashboard - Data karyawan utama tidak ditemukan untuk NIP: " . $nip);
    // Set default atau tampilkan pesan error jika perlu di dashboard
}

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

if ($lamaKerja < 12) {
    $dataTMK['tunjangan_masa_kerja'] = 0;
} else if ($lamaKerja >= 12 && $lamaKerja < 24) {
    $dataTMK['tunjangan_masa_kerja'] = 100000;
} else if ($lamaKerja >= 24 && $lamaKerja < 36) {
    $dataTMK['tunjangan_masa_kerja'] = 200000;
} else if ($lamaKerja >= 36 && $lamaKerja < 48) {
    $dataTMK['tunjangan_masa_kerja'] = 300000;
} else if ($lamaKerja >= 48 && $lamaKerja < 60) {
    $dataTMK['tunjangan_masa_kerja'] = 400000;
} else if ($lamaKerja >= 60 && $lamaKerja < 120) {
    $dataTMK['tunjangan_masa_kerja'] = 500000;
} else if ($lamaKerja >= 120 && $lamaKerja < 180) {
    $dataTMK['tunjangan_masa_kerja'] = 1000000;
} else if ($lamaKerja >= 180) {
    $dataTMK['tunjangan_masa_kerja'] = 1500000;
} else {
    $dataTMK['tunjangan_masa_kerja'] = 0;
}


// include '../sa-get-tunjangan-masa-kerja.php';
$tunjangan_masa_kerja_dash = $dataTMK['tunjangan_masa_kerja'] ?? 0; // Default ke 0 jika tidak ada
// $tunjangan_masa_kerja_dash = 0; 

if ($employee_dash) {
    $query_rincian_gaji_dash = "SELECT gaji FROM rincian_gaji 
                                WHERE nip = '$nip' AND MONTH(tanggal) = '$current_month_dash' AND YEAR(tanggal) = '$current_year_dash'";
    $result_rincian_gaji_dash = $conn->query($query_rincian_gaji_dash);
    $emp_gaji_dash = $result_rincian_gaji_dash ? $result_rincian_gaji_dash->fetch_assoc() : null;

    $gajiIt_dash = ($emp_gaji_dash && isset($emp_gaji_dash['gaji']) && $emp_gaji_dash['gaji'] != 0) ? (float)$emp_gaji_dash['gaji'] : (float)($employee_dash['gaji_pokok'] ?? 0);
    $gaji1_dash = ($employee_dash['jenis_gaji'] == 'mingguan') ? (float)($employee_dash['gaji_1'] ?? 0) : 0;

    $totalGaji_dash = $gajiIt_dash +
        ($employee_dash['tunjangan'] ?? 0) +
        ($tunjangan_masa_kerja_dash) + // Pastikan ini numerik
        ($employee_dash['total_tunjangan_lainnya_ganti'] ?? 0) +
        ($employee_dash['total_tunjangan_lainnya_bonus'] ?? 0) -
        ($employee_dash['total_denda'] ?? 0) -
        ($employee_dash['total_cashbon'] ?? 0);

    $gajiBersihFinal_dash = $totalGaji_dash - $gaji1_dash;
    $info_gaji_bulan_ini_dash['gaji_bersih_rp'] = "Rp " . number_format($gajiBersihFinal_dash, 0, ',', '.');
}


// f. Tanggal Pembayaran (Sabtu terakhir bulan ini)
$date_tf_gaji_dash = new DateTime();
$date_tf_gaji_dash->setDate((int)$current_year_dash, (int)$current_month_dash, 1);
$date_tf_gaji_dash->modify('last day of this month');
while ($date_tf_gaji_dash->format('N') != 6) { // Sabtu adalah 6
    $date_tf_gaji_dash->modify('-1 day');
}
$info_gaji_bulan_ini_dash['tanggal_bayar'] = $date_tf_gaji_dash->format('d F Y');
