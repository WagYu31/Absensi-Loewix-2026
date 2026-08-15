<?php
session_start();

// Only allow admin/superadmin
if (!isset($_SESSION['nip']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak diizinkan.']);
    exit();
}

include '../conn.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$cuti_id = isset($_POST['cuti_id']) ? (int)$_POST['cuti_id'] : 0;

if (empty($action) || $cuti_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
    exit();
}

switch ($action) {

    case 'terima':
        $jenis_cuti = $_POST['jenis_cuti'] ?? '';
        $allowed_jenis = ['hak', 'khusus', 'dipotong'];
        if (!in_array(strtolower($jenis_cuti), $allowed_jenis)) {
            echo json_encode(['status' => 'error', 'message' => 'Jenis cuti tidak valid.']);
            exit();
        }

        // Determine potong_gaji based on jenis cuti
        $potong_gaji = (strtolower($jenis_cuti) === 'dipotong') ? '1' : '0';

        $stmt = $conn->prepare("UPDATE cuti SET verif = 'Disetujui', jenis = ?, potong_gaji = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ssi", $jenis_cuti, $potong_gaji, $cuti_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // If jenis is 'hak', deduct from jatah_cuti_tahunan
                if (strtolower($jenis_cuti) === 'hak') {
                    // Get the cuti details for duration calculation
                    $stmt_cuti = $conn->prepare("SELECT nip, tgl_mulai, tgl_selesai FROM cuti WHERE id = ?");
                    $stmt_cuti->bind_param("i", $cuti_id);
                    $stmt_cuti->execute();
                    $result_cuti = $stmt_cuti->get_result();
                    
                    if ($row_cuti = $result_cuti->fetch_assoc()) {
                        $nip_cuti = $row_cuti['nip'];
                        $tahun = date('Y', strtotime($row_cuti['tgl_mulai']));
                        
                        // Calculate duration (excluding Sundays and holidays)
                        $holidays = [];
                        $sql_holidays = "SELECT tanggal_merah FROM kalender_kerja WHERE libur = 'yes' AND deleted_at IS NULL";
                        $result_holidays = $conn->query($sql_holidays);
                        if ($result_holidays) {
                            while ($row_h = $result_holidays->fetch_assoc()) {
                                if (!empty($row_h['tanggal_merah'])) {
                                    $holidays[$row_h['tanggal_merah']] = true;
                                }
                            }
                        }
                        
                        $start = new DateTime($row_cuti['tgl_mulai']);
                        $end = new DateTime($row_cuti['tgl_selesai']);
                        $end->modify('+1 day');
                        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
                        $duration = 0;
                        foreach ($period as $date) {
                            $dayOfWeek = $date->format('N');
                            $dateString = $date->format('Y-m-d');
                            if ($dayOfWeek != 7 && !isset($holidays[$dateString])) {
                                $duration++;
                            }
                        }
                        
                        // Deduct from jatah_cuti_tahunan
                        $stmt_deduct = $conn->prepare("UPDATE jatah_cuti_tahunan SET sisa_cuti = sisa_cuti - ? WHERE nip = ? AND tahun = ? AND sisa_cuti >= ?");
                        $stmt_deduct->bind_param("isii", $duration, $nip_cuti, $tahun, $duration);
                        $stmt_deduct->execute();
                        $stmt_deduct->close();
                    }
                    $stmt_cuti->close();
                }
                
                echo json_encode(['status' => 'success', 'message' => 'Pengajuan cuti berhasil disetujui.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data cuti tidak ditemukan atau sudah dihapus.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyetujui cuti: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'tolak':
        $reason = $_POST['reason'] ?? '';
        $reason = trim($reason);

        $stmt = $conn->prepare("UPDATE cuti SET verif = 'Ditolak', reason = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("si", $reason, $cuti_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Pengajuan cuti berhasil ditolak.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data cuti tidak ditemukan atau sudah dihapus.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menolak cuti: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'delete':
        // Soft delete
        $stmt = $conn->prepare("UPDATE cuti SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $cuti_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Data pengajuan cuti berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data cuti tidak ditemukan atau sudah dihapus sebelumnya.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus cuti: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali: ' . htmlspecialchars($action)]);
        break;
}

$conn->close();
?>
