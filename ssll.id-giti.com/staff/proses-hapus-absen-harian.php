<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

include '../conn.php';

$nik = isset($_POST['nik']) ? trim($_POST['nik']) : '';
$tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : ''; // format YYYY-MM-DD

if (empty($nik) || empty($tanggal)) {
    echo json_encode(['success' => false, 'message' => 'NIK dan Tanggal harus diisi.']);
    exit();
}

// 1. Ambil data karyawan
$stmt = $conn->prepare("SELECT nip, nik, nama, pin_absen FROM karyawan WHERE nik = ? OR nip = ? LIMIT 1");
$stmt->bind_param("ss", $nik, $nik);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Data karyawan tidak ditemukan.']);
    exit();
}

$kar = $res->fetch_assoc();
$k_nip = $kar['nip'];
$k_nik = $kar['nik'];
$k_pin = $kar['pin_absen'];
$k_nama = $kar['nama'];
$stmt->close();

$tgl_d_m_y = date('d-m-Y', strtotime($tanggal));
$tgl_d_n_y = date('j-n-Y', strtotime($tanggal));
$tgl_y_m_d = date('Y-m-d', strtotime($tanggal));

$conn->begin_transaction();
$deleted_absen = 0;
$deleted_manual = 0;

try {
    // 2. Hapus dari tabel absen (fingerprint / import / sync)
    $sql_del_absen = "DELETE FROM absen WHERE (nip = ? OR nip = ? OR pin = ?) AND (
        tgl_scan LIKE CONCAT(?, '%') OR 
        tgl_scan LIKE CONCAT(?, '%') OR 
        tgl_scan LIKE CONCAT(?, '%')
    )";
    $stmt_del_a = $conn->prepare($sql_del_absen);
    if ($stmt_del_a) {
        $stmt_del_a->bind_param("ssssss", $k_nik, $k_nip, $k_pin, $tgl_d_m_y, $tgl_d_n_y, $tgl_y_m_d);
        $stmt_del_a->execute();
        $deleted_absen = $stmt_del_a->affected_rows;
        $stmt_del_a->close();
    }

    // 3. Hapus dari tabel absen_manual (kamera / mobile)
    $sql_del_man = "DELETE FROM absen_manual WHERE (nip = ? OR nip = ? OR pin = ?) AND (
        DATE(tgl_absen) = ? OR 
        tgl_absen LIKE CONCAT(?, '%') OR 
        tgl_absen LIKE CONCAT(?, '%')
    )";
    $stmt_del_m = $conn->prepare($sql_del_man);
    if ($stmt_del_m) {
        $stmt_del_m->bind_param("ssssss", $k_nik, $k_nip, $k_pin, $tgl_y_m_d, $tgl_y_m_d, $tgl_d_m_y);
        $stmt_del_m->execute();
        $deleted_manual = $stmt_del_m->affected_rows;
        $stmt_del_m->close();
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Berhasil menghapus data absen $k_nama pada tanggal " . date('d/m/Y', strtotime($tanggal)) . " ($deleted_absen data mesin, $deleted_manual data manual)."
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
}
?>
