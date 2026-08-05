<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login kembali.']);
    exit();
}

include '../conn.php';

$nip = $_GET['nip'] ?? $_POST['nip'] ?? '';
$status = $_GET['status'] ?? $_POST['status'] ?? '';

if (empty($nip) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Parameter nip dan status harus diisi.']);
    exit();
}

// Clean and normalize status
$status = (strtolower($status) === 'aktif') ? 'aktif' : 'tidak aktif';

$stmt = $conn->prepare("UPDATE karyawan SET status_karyawan = ? WHERE nip = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Gagal mempersiapkan query database: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ss", $status, $nip);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status karyawan berhasil diubah menjadi ' . $status, 'status' => $status]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>
