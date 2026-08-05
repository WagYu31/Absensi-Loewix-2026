<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

include '../conn.php';

if (!isset($_POST['id_absen']) || !ctype_digit($_POST['id_absen']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
    exit();
}

$id_absen = (int)$_POST['id_absen'];
$status_verif = $_POST['status'];

if (!in_array($status_verif, ['Yes', 'No'])) {
     echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
    exit();
}

// 1. Ambil semua detail yang diperlukan dari data absensi manual dan karyawan
$stmt_get = $conn->prepare("
    SELECT am.tgl_absen, am.nama, am.nip, k.pin_absen, k.nik 
    FROM absen_manual am
    JOIN karyawan k ON am.nip = k.nip
    WHERE am.id = ?
");
$stmt_get->bind_param("i", $id_absen);
$stmt_get->execute();
$result_details = $stmt_get->get_result();
if ($result_details->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Data absensi tidak ditemukan.']);
    exit();
}
$details = $result_details->fetch_assoc();
$stmt_get->close();

// Mulai Transaksi Database untuk memastikan integritas data
$conn->begin_transaction();

try {
    // LANGKAH UTAMA: Update status verifikasi di tabel absen_manual
    $stmt_update_manual = $conn->prepare("UPDATE absen_manual SET verif = ? WHERE id = ?");
    $stmt_update_manual->bind_param("si", $status_verif, $id_absen);
    $stmt_update_manual->execute();
    $stmt_update_manual->close();

    // =========================================================================
    // LOGIKA BERDASARKAN STATUS (APPROVE / REJECT)
    // =========================================================================

    if ($status_verif === 'Yes') {
        // JIKA DISETUJUI: Masukkan data ke tabel 'absen'
        
        $tgl_absen_dt = new DateTime($details['tgl_absen']);
        $tgl_scan_db = $tgl_absen_dt->format('d-m-Y H:i:s'); // Sesuai format tgl_scan di tabel absen
        $tanggal_db = $tgl_absen_dt->format('Y-m-d');
        $jam_db = $tgl_absen_dt->format('H:i:s');

        // Pastikan tidak ada duplikat sebelum insert
        $stmt_check = $conn->prepare("SELECT id FROM absen WHERE pin = ? AND nip = ? AND tgl_scan = ?");
        $stmt_check->bind_param("sss", $details['pin_absen'], $details['nik'], $tgl_scan_db);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $stmt_insert_absen = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert_absen->bind_param(
                "ssssss",
                $tgl_scan_db,
                $tanggal_db,
                $jam_db,
                $details['pin_absen'],
                $details['nik'], // Sesuai aturan: absen.nip diisi dengan karyawan.nik
                $details['nama']
            );
            $stmt_insert_absen->execute();
            $stmt_insert_absen->close();
        }
        $stmt_check->close();

    } else { // Jika status 'No' (Ditolak)
        // JIKA DITOLAK: Hapus data yang mungkin sudah ada di tabel 'absen'
        
        $tgl_absen_dt = new DateTime($details['tgl_absen']);
        $tgl_scan_db = $tgl_absen_dt->format('d-m-Y H:i:s');

        $stmt_delete_absen = $conn->prepare("DELETE FROM absen WHERE pin = ? AND nip = ? AND tgl_scan = ?");
        $stmt_delete_absen->bind_param(
            "sss",
            $details['pin_absen'],
            $details['nik'],
            $tgl_scan_db
        );
        $stmt_delete_absen->execute();
        $stmt_delete_absen->close();
    }

    // Jika semua proses berhasil, simpan perubahan secara permanen
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (mysqli_sql_exception $e) {
    // Jika ada satu saja error, batalkan semua perubahan
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada database: ' . $e->getMessage()]);
}

$conn->close();
?>