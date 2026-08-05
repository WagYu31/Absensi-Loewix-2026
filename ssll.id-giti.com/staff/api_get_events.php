<?php
header('Content-Type: application/json');
include '../conn.php';

$events = [];

try {
    // 1. Ambil data HARI LIBUR & ACARA SPESIAL (Bagian ini sudah benar)
    $sql_acara = "SELECT id, tanggal_merah, keterangan, libur FROM kalender_kerja WHERE deleted_at IS NULL";
    $result_acara = $conn->query($sql_acara);
    if ($result_acara) {
        while ($row = $result_acara->fetch_assoc()) {
            $className = ($row['libur'] === 'yes') ? 'event-libur-merah' : 'event-spesial-kuning';
            $events[] = [
                'id'        => $row['id'],
                'title'     => $row['keterangan'],
                'start'     => $row['tanggal_merah'],
                'allDay'    => true,
                'className' => $className,
                'extendedProps' => [
                    'type'     => 'acara_kantor',
                    'is_libur' => $row['libur'] ?? 'no'
                ]
            ];
        }
    }

    // =============================================================================
    // --- PERBAIKAN TOTAL PADA LOGIKA PENGAMBILAN ULANG TAHUN ---
    // =============================================================================

    // 2. Ambil data ULANG TAHUN KARYAWAN dengan logika yang sudah diperbaiki
    
    // Dapatkan rentang tanggal yang sedang dilihat di kalender dari parameter GET
    // FullCalendar secara otomatis mengirimkan ?start=...&end=...
    $view_start = new DateTime($_GET['start']);
    $view_end = new DateTime($_GET['end']);

    // Ambil semua karyawan yang memiliki tanggal lahir
    $sql_ultah = "SELECT nama, tanggal_lahir FROM karyawan WHERE status_karyawan = 'aktif' AND deleted_at IS NULL AND tanggal_lahir IS NOT NULL";
    $result_ultah = $conn->query($sql_ultah);

    if ($result_ultah) {
        while ($row = $result_ultah->fetch_assoc()) {
            $tgl_lahir_obj = new DateTime($row['tanggal_lahir']);
            
            // Tentukan tahun awal untuk pengecekan, yaitu tahun dari awal view kalender
            $tahun_cek = (int)$view_start->format('Y');

            // Cek ulang tahun untuk rentang maksimal 2 tahun (mengatasi view lintas tahun, misal Nov-Jan)
            for ($i = 0; $i < 2; $i++) {
                $tahun_ultah = $tahun_cek + $i;
                
                // Buat tanggal ulang tahun karyawan pada tahun yang sedang dicek
                $tanggal_ultah_saat_ini = new DateTime($tahun_ultah . '-' . $tgl_lahir_obj->format('m-d'));
                
                // Hanya tambahkan event jika tanggal ulang tahun masuk dalam rentang yang terlihat di kalender
                if ($tanggal_ultah_saat_ini >= $view_start && $tanggal_ultah_saat_ini < $view_end) {
                    $events[] = [
                        'title'         => 'Ultah: ' . $row['nama'],
                        'start'         => $tanggal_ultah_saat_ini->format('Y-m-d'), // Format YYYY-MM-DD yang tegas
                        'allDay'        => true,
                        'className'     => 'event-ultah-biru',
                        'extendedProps' => [ 'type' => 'ulang_tahun' ]
                    ];
                }
            }
        }
    }
    
} catch (Exception $e) {
    // Jika ada error apapun, kirim array kosong agar kalender tidak rusak
    $events = []; 
    // error_log("Gagal mengambil data event: " . $e->getMessage()); // Opsional: catat error untuk admin
}

echo json_encode($events);
$conn->close();
?>