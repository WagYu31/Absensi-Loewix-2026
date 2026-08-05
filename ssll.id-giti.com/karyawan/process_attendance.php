<?php
// Buffer all output to prevent PHP warnings/errors from corrupting JSON
ob_start();

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');
session_start();

// Helper to return clean JSON response
function sendJsonResponse($success, $message) {
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['nip']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    sendJsonResponse(false, 'Akses tidak diizinkan. Silakan login kembali.');
}

include '../conn.php';

$session_nip = $_SESSION['nip']; 

define('TARGET_OFFICE_LAT', -6.130189784035325);
define('TARGET_OFFICE_LON', 106.75142085117402);
define('MAX_OFFICE_RADIUS_METERS', 150);

function getDistanceBetweenPoints($latitude1, $longitude1, $latitude2, $longitude2) {
    $earthRadius = 6371000; 

    $latFrom = deg2rad(floatval($latitude1));
    $lonFrom = deg2rad(floatval($longitude1));
    $latTo = deg2rad(floatval($latitude2));
    $lonTo = deg2rad(floatval($longitude2));

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

$type = $_POST['tipe_absen'] ?? '';
$imageData = $_POST['foto_absen'] ?? '';
$pin_from_client = $_POST['pin'] ?? ''; 
$lokasi_absen = $_POST['lokasi_absen'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$device_name = $_POST['device_name'] ?? 'Unknown Device';

$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; 

if (empty($type) || !in_array(strtolower($type), ['masuk', 'pulang'])) {
    sendJsonResponse(false, 'Tipe absensi tidak valid.');
}
if (empty($imageData)) {
    sendJsonResponse(false, 'Data gambar tidak boleh kosong.');
}
if (empty($lokasi_absen)) {
    $lokasi_absen = "Lokasi tidak terdeteksi"; 
}

$lokasi_koordinat_str = null;
$verif_status = 'Pending';

if (
    $latitude !== null && $longitude !== null &&
    is_numeric($latitude) && is_numeric($longitude) &&
    $latitude >= -90 && $latitude <= 90 &&
    $longitude >= -180 && $longitude <= 180
) {
    $lokasi_koordinat_str = $latitude . "," . $longitude;
    $distance = getDistanceBetweenPoints($latitude, $longitude, TARGET_OFFICE_LAT, TARGET_OFFICE_LON);
    
    if ($distance <= MAX_OFFICE_RADIUS_METERS) {
        $verif_status = 'Yes';
    } else {
        $verif_status = 'Pending';
    }
} else {
    $lokasi_koordinat_str = "Koordinat tidak valid/tersedia";
    $verif_status = 'Pending';
}

// Auto-approve test mode attendances so they sync immediately to report tables
$sql_check_test = "SELECT shifting FROM shift_req WHERE nip = '$session_nip' AND CURDATE() BETWEEN tgl_mulai AND tgl_selesai LIMIT 1";
$res_check_test = $conn->query($sql_check_test);
$user_shift_code = '';
if ($res_check_test && $res_check_test->num_rows > 0) {
    $user_shift_code = $res_check_test->fetch_assoc()['shifting'];
}
if ($user_shift_code === 'TEST' || $session_nip === 'TEST001') {
    $verif_status = 'Yes';
}

$stmt_karyawan = $conn->prepare("SELECT nama, nik FROM karyawan WHERE nip = ?");
if (!$stmt_karyawan) {
    sendJsonResponse(false, 'Kesalahan koneksi database: ' . $conn->error);
}
$stmt_karyawan->bind_param("s", $session_nip);
$stmt_karyawan->execute();
$result_karyawan = $stmt_karyawan->get_result();

if ($result_karyawan->num_rows === 0) {
    sendJsonResponse(false, 'Data karyawan tidak ditemukan.');
}
$karyawan_data = $result_karyawan->fetch_assoc();
$nama_karyawan_db = $karyawan_data['nama'];
$nik_karyawan_db = $karyawan_data['nik'];
$stmt_karyawan->close();

if (strpos($imageData, 'data:image/jpeg;base64,') === 0) {
    $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
    $file_extension = '.jpg';
} elseif (strpos($imageData, 'data:image/png;base64,') === 0) {
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $file_extension = '.png';
} else {
    sendJsonResponse(false, 'Format gambar tidak didukung.');
}
$imageData = str_replace(' ', '+', $imageData); 
$imageBinary = base64_decode($imageData);

if ($imageBinary === false) {
    sendJsonResponse(false, 'Gagal mendekode gambar.');
}

// Ensure Upload Directory Exists and Has Full Write Permissions (0777)
$upload_dir = '../uploads/attendance/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
@chmod($upload_dir, 0777);

$filename = 'presensi_' . $session_nip . '_' . date('Ymd_His') . $file_extension;
$filepath = $upload_dir . $filename;

if (@file_put_contents($filepath, $imageBinary) === false) {
    sendJsonResponse(false, 'Gagal menyimpan file gambar ke folder uploads/attendance/. Periksa izin folder server.');
}

try {
    // Auto-expand PIN and NIP columns in DB if needed to accommodate longer NIPs / PINs (e.g. TEST001)
    @$conn->query("ALTER TABLE absen_manual MODIFY COLUMN pin VARCHAR(50)");
    @$conn->query("ALTER TABLE absen_manual MODIFY COLUMN nip VARCHAR(50)");
    @$conn->query("ALTER TABLE absen MODIFY COLUMN pin VARCHAR(50)");
    @$conn->query("ALTER TABLE absen MODIFY COLUMN nip VARCHAR(50)");

    $query = "INSERT INTO absen_manual (
                tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, ip, device_name, verif
              ) VALUES (
                NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
              )";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        if (file_exists($filepath)) @unlink($filepath);
        sendJsonResponse(false, 'Kesalahan query database: ' . $conn->error);
    }

    $stmt->bind_param("ssssssssss", 
        $type, 
        $filename, 
        $pin_from_client, 
        $session_nip, 
        $nama_karyawan_db, 
        $lokasi_absen, 
        $lokasi_koordinat_str,
        $ip_address,
        $device_name,
        $verif_status
    );

    if ($stmt->execute()) {
        if ($verif_status === 'Yes') {
            $tgl_scan_absen = date('d-m-Y H:i:s');
            $tanggal_absen = date('Y-m-d');
            $jam_absen = date('H:i:s');

            $stmt_absen = $conn->prepare("INSERT INTO absen (tgl_scan, tanggal, jam, pin, nip, nama) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_absen) {
                $stmt_absen->bind_param("ssssss", 
                    $tgl_scan_absen, 
                    $tanggal_absen, 
                    $jam_absen, 
                    $pin_from_client, 
                    $nik_karyawan_db, 
                    $nama_karyawan_db
                );
                $stmt_absen->execute();
                $stmt_absen->close();
            }
        }

        sendJsonResponse(true, 'Presensi ' . $type . ' berhasil dicatat.');
    } else {
        if (file_exists($filepath)) @unlink($filepath);
        sendJsonResponse(false, 'Gagal menyimpan data presensi: ' . $stmt->error);
    }
    $stmt->close();
} catch (Throwable $e) {
    if (file_exists($filepath)) @unlink($filepath);
    sendJsonResponse(false, 'Kesalahan sistem: ' . $e->getMessage());
}

$conn->close();
?>