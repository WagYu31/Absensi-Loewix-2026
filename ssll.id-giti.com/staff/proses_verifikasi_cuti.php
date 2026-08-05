<?php
session_start();
header('Content-Type: application/json'); 

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

include '../conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$cuti_id = isset($_POST['cuti_id']) ? (int)$_POST['cuti_id'] : 0;

if (empty($action) || empty($cuti_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit();
}

if ($action === 'terima') {
    $jenis_cuti = $_POST['jenis_cuti'] ?? 'hak';
    $potong_gaji = isset($_POST['potong_gaji']) ? (int)$_POST['potong_gaji'] : 0;

    $sql = "UPDATE cuti SET verif = 'Disetujui', jenis = ?, potong_gaji = ?, reason = 'ok' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sii", $jenis_cuti, $potong_gaji, $cuti_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Pengajuan cuti berhasil disetujui.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang diperbarui. Mungkin sudah disetujui sebelumnya.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengeksekusi query: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query: ' . $conn->error]);
    }

} elseif ($action === 'tolak') {
    $alasan = $_POST['alasan'] ?? '';
    if (empty($alasan)) {
        echo json_encode(['status' => 'error', 'message' => 'Alasan penolakan tidak boleh kosong.']);
        exit();
    }

    $sql = "UPDATE cuti SET verif = 'Ditolak', reason = ?, potong_gaji = '0' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $alasan, $cuti_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Pengajuan cuti berhasil ditolak.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang diperbarui.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengeksekusi query: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query: ' . $conn->error]);
    }

} elseif ($action === 'delete') {
    
    $sql = "UPDATE cuti SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $cuti_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Pengajuan cuti berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang dihapus (mungkin ID tidak ditemukan).']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengeksekusi query: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query: ' . $conn->error]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
}

$conn->close();
?>