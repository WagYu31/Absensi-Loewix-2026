<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

include '../conn.php';

$id_absen = isset($_POST['id_absen']) ? (int)$_POST['id_absen'] : 0;
$jam_baru = isset($_POST['jam_baru']) ? trim($_POST['jam_baru']) : '';

if ($id_absen <= 0 || empty($jam_baru)) {
    echo json_encode(['success' => false, 'message' => 'ID Absen dan Jam Baru harus diisi.']);
    exit();
}

// Validate Time Format (HH:MM or HH:MM:SS)
if (strlen($jam_baru) === 5) {
    $jam_baru .= ':00';
}

if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $jam_baru)) {
    echo json_encode(['success' => false, 'message' => 'Format jam tidak valid. Gunakan format HH:MM:SS.']);
    exit();
}

// 1. Fetch current absen_manual details
$stmt_get = $conn->prepare("
    SELECT am.id, am.tgl_absen, am.tipe_absen, am.verif, am.nip, k.pin_absen, k.nik, k.nama 
    FROM absen_manual am
    JOIN karyawan k ON am.nip = k.nip
    WHERE am.id = ?
");
$stmt_get->bind_param("i", $id_absen);
$stmt_get->execute();
$res = $stmt_get->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Data absensi tidak ditemukan.']);
    exit();
}

$row = $res->fetch_assoc();
$stmt_get->close();

$old_tgl_absen = $row['tgl_absen'];
$tgl_only = date('Y-m-d', strtotime($old_tgl_absen));
$new_tgl_absen = $tgl_only . ' ' . $jam_baru;

$conn->begin_transaction();

try {
    // 1. Update absen_manual
    $stmt_up_manual = $conn->prepare("UPDATE absen_manual SET tgl_absen = ? WHERE id = ?");
    $stmt_up_manual->bind_param("si", $new_tgl_absen, $id_absen);
    $stmt_up_manual->execute();
    $stmt_up_manual->close();

    // 2. If already approved, update or replace row in `absen` table as well
    if ($row['verif'] === 'Yes') {
        $old_tgl_scan = date('d-m-Y H:i:s', strtotime($old_tgl_absen));
        $new_tgl_scan = date('d-m-Y H:i:s', strtotime($new_tgl_absen));
        $new_jam = $jam_baru;

        // Delete existing entry in `absen` matching old tgl_scan
        $stmt_del = $conn->prepare("DELETE FROM absen WHERE pin = ? AND nip = ? AND tgl_scan = ?");
        $stmt_del->bind_param("sss", $row['pin_absen'], $row['nik'], $old_tgl_scan);
        $stmt_del->execute();
        $stmt_del->close();

        // Insert updated entry in `absen`
        $stmt_ins = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_ins->bind_param("ssssss", $new_tgl_scan, $tgl_only, $new_jam, $row['pin_absen'], $row['nik'], $row['nama']);
        $stmt_ins->execute();
        $stmt_ins->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Jam absensi berhasil diperbarui menjadi ' . $jam_baru]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah jam: ' . $e->getMessage()]);
}

$conn->close();
?>
