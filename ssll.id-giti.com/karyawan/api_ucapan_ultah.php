<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['nip'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

include '../conn.php';

// Auto Create Table IF NOT EXISTS
$conn->query("CREATE TABLE IF NOT EXISTS ucapan_ultah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip_penerima VARCHAR(50) NOT NULL,
    nip_pengirim VARCHAR(50) NOT NULL,
    nama_pengirim VARCHAR(255) NOT NULL,
    ucapan TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (nip_penerima),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$senderNip = $_SESSION['nip'];

// Get Sender Name & Photo
$senderNama = "Rekan Kerja";
$resSender = $conn->query("SELECT nama FROM karyawan WHERE nip = '$senderNip' LIMIT 1");
if ($resSender && $resSender->num_rows > 0) {
    $rowS = $resSender->fetch_assoc();
    $senderNama = $rowS['nama'];
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'send') {
    $nip_penerima = trim($_POST['nip_penerima'] ?? '');
    $ucapan = trim($_POST['ucapan'] ?? '');

    if (empty($nip_penerima) || empty($ucapan)) {
        echo json_encode(['status' => 'error', 'message' => 'NIP penerima dan ucapan tidak boleh kosong.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO ucapan_ultah (nip_penerima, nip_pengirim, nama_pengirim, ucapan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nip_penerima, $senderNip, $senderNama, $ucapan);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Ucapan selamat ulang tahun berhasil dikirim!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ucapan.']);
    }
    $stmt->close();
    exit();
}

if ($action === 'fetch') {
    $today_md = date('m-d');
    
    // Ambil ucapan untuk karyawan yang ultah hari ini
    $sql = "SELECT u.*, k.pas_photo AS photo_pengirim 
            FROM ucapan_ultah u
            LEFT JOIN karyawan k ON u.nip_pengirim = k.nip
            WHERE DATE(u.created_at) = CURDATE()
            ORDER BY u.id DESC LIMIT 50";
            
    $res = $conn->query($sql);
    $wishes = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['time_formatted'] = date('H:i', strtotime($r['created_at']));
            $wishes[] = $r;
        }
    }
    echo json_encode(['status' => 'success', 'wishes' => $wishes]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
