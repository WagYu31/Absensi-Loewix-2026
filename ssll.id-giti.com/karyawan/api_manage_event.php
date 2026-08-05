<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

include '../conn.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        $id = $_POST['id'] ?? '';
        $tanggal_merah = $_POST['tanggal_merah'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');
        $libur = $_POST['libur'] ?? 'no';

        if (empty($tanggal_merah) || empty($keterangan)) {
            echo json_encode(['status' => 'error', 'message' => 'Tanggal dan keterangan wajib diisi.']);
            exit();
        }

        if (empty($id)) { // Tambah Baru
            $sql = "INSERT INTO kalender_kerja (tanggal_merah, keterangan, libur) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $tanggal_merah, $keterangan, $libur);
        } else { // Edit
            $sql = "UPDATE kalender_kerja SET tanggal_merah = ?, keterangan = ?, libur = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $tanggal_merah, $keterangan, $libur, $id);
        }
        
        if ($stmt->execute()) { echo json_encode(['status' => 'success']); }
        else { echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $conn->error]); }
        $stmt->close();
        break;

    case 'delete':
        $id = $_POST['id'] ?? '';
        if (empty($id)) { exit(json_encode(['status' => 'error', 'message' => 'ID tidak valid.'])); }

        date_default_timezone_set('Asia/Jakarta');
        $deleted_at = date('Y-m-d H:i:s');
        $sql = "UPDATE kalender_kerja SET deleted_at = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $deleted_at, $id);
        if ($stmt->execute()) { echo json_encode(['status' => 'success']); }
        else { echo json_encode(['status' => 'error', 'message' => 'Gagal hapus.']); }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
        break;
}

$conn->close();
?>