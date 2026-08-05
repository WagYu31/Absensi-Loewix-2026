<?php
// =============================================================================
// AWAL BLOK PERHITUNGAN DENDA CUTI (VERSI AKUMULATIF FINAL)
// =============================================================================

// Inisialisasi variabel untuk perhitungan karyawan saat ini
$total_denda_cuti = 0;

try {
    // LANGKAH 1: PERSIAPAN DATA (Data ini sebaiknya diambil satu kali di luar loop untuk efisiensi)
    // Namun, untuk menjaga keutuhan logika sesuai permintaan, kita letakkan di sini.
    
    // 1a. Ambil jatah cuti tahunan
    $jatah_cuti_tahunan = 0;
    if (!isset($jatah_cuti_cache)) { // Caching sederhana agar tidak query berulang
        $stmt_jatah = $conn->prepare("SELECT jumlah FROM jatah_cuti_tahunan WHERE tahun = ?");
        $stmt_jatah->bind_param("s", $tahun);
        $stmt_jatah->execute();
        $result_jatah = $stmt_jatah->get_result();
        if ($row_jatah = $result_jatah->fetch_assoc()) {
            $jatah_cuti_tahunan = (int) $row_jatah['jumlah'];
        }
        $stmt_jatah->close();
        $jatah_cuti_cache = $jatah_cuti_tahunan;
    } else {
        $jatah_cuti_tahunan = $jatah_cuti_cache;
    }

    // 1b. Ambil semua tanggal libur nasional
    if (!isset($hari_libur_nasional_cache)) {
        $hari_libur_nasional_cache = [];
        $stmt_libur = $conn->prepare("SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND YEAR(tanggal_merah) = ? AND deleted_at IS NULL");
        $stmt_libur->bind_param("s", $tahun);
        $stmt_libur->execute();
        $result_libur = $stmt_libur->get_result();
        while ($row_libur = $result_libur->fetch_assoc()) {
            $hari_libur_nasional_cache[] = $row_libur['tanggal_merah'];
        }
        $stmt_libur->close();
    }
    $hari_libur_nasional = $hari_libur_nasional_cache;
    
    // 1c. Ambil riwayat cuti karyawan yang relevan
    $riwayat_cuti_karyawan = [];
    $tahun_sebelumnya = $tahun - 1;
    $stmt_cuti = $conn->prepare("SELECT tgl_mulai, tgl_selesai FROM cuti WHERE nip = ? AND verif = 'Disetujui' AND potong_gaji = 1 AND deleted_at IS NULL AND (YEAR(tgl_mulai) = ? OR YEAR(tgl_mulai) = ?)");
    $stmt_cuti->bind_param("sss", $nip, $tahun, $tahun_sebelumnya);
    $stmt_cuti->execute();
    $riwayat_cuti_karyawan = $stmt_cuti->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_cuti->close();


    // LANGKAH 2: HITUNG AKUMULASI CUTI PADA DUA PERIODE PENTING
    // =========================================================================
    
    $total_cuti_hingga_bulan_lalu = 0;
    $total_cuti_hingga_dua_bulan_lalu = 0;

    // Tentukan batas akhir untuk "bulan lalu" dan "dua bulan lalu"
    $periode_akhir_bulan_lalu = new DateTime("last day of $tahun-$bulan-01 -1 month");
    $periode_akhir_dua_bulan_lalu = new DateTime("last day of $tahun-$bulan-01 -2 month");

    // Loop melalui riwayat cuti karyawan untuk menghitung akumulasi
    foreach ($riwayat_cuti_karyawan as $cuti) {
        $tgl_mulai = new DateTime($cuti['tgl_mulai']);
        $tgl_selesai = new DateTime($cuti['tgl_selesai']);
        $tgl_selesai->modify('+1 day'); // Tambah 1 hari untuk iterasi
        $rentang_tanggal = new DatePeriod($tgl_mulai, new DateInterval('P1D'), $tgl_selesai);

        foreach ($rentang_tanggal as $hari) {
            // Abaikan hari yang tidak valid
            if ($hari->format('N') == 7) continue; // Abaikan hari Minggu
            if (in_array($hari->format('Y-m-d'), $hari_libur_nasional)) continue;

            // Tambahkan ke total akumulasi yang sesuai
            if ($hari <= $periode_akhir_dua_bulan_lalu) {
                $total_cuti_hingga_dua_bulan_lalu++;
            }
            if ($hari <= $periode_akhir_bulan_lalu) {
                $total_cuti_hingga_bulan_lalu++;
            }
        }
    }

    // LANGKAH 3: HITUNG HARI DENDA UNTUK PERIODE GAJIAN SAAT INI
    // =========================================================================

    // Kelebihan cuti yang sudah dihitung sampai akhir bulan lalu
    $kelebihan_cuti_bulan_lalu = max(0, $total_cuti_hingga_bulan_lalu - $jatah_cuti_tahunan);

    // Kelebihan cuti yang sudah dihitung sampai akhir dua bulan lalu
    $kelebihan_cuti_dua_bulan_lalu = max(0, $total_cuti_hingga_dua_bulan_lalu - $jatah_cuti_tahunan);

    // Hari yang dikenakan denda pada bulan ini adalah penambahan kelebihan cuti dari bulan lalu
    $hari_dikenakan_denda = $kelebihan_cuti_bulan_lalu - $kelebihan_cuti_dua_bulan_lalu;
    

    // LANGKAH 4: KALKULASI FINAL DENDA CUTI
    // =========================================================================
    // if ($hari_dikenakan_denda > 0 && isset($total_gapok) && $total_gapok > 0) {
        $gaji_per_hari_kerja = $total_gapok / 26;
        $total_denda_cuti = $gaji_per_hari_kerja * $hari_dikenakan_denda;
    // }

} catch (Exception $e) {
    // Jika terjadi error (misal format tanggal salah), set denda ke 0
    $total_denda_cuti = 0;
}

// =============================================================================
// AKHIR BLOK PERHITUNGAN DENDA CUTI
// Variabel $total_denda_cuti siap digunakan untuk perhitungan total gaji.
// =============================================================================
?>