<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION['nip']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    die(json_encode(['success' => false, 'message' => 'Akses tidak diizinkan. Silakan login kembali.']));
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

$ip_address = $_SERVER['REMOTE_ADDR']; 

if (empty($type) || !in_array(strtolower($type), ['masuk', 'pulang'])) {
    die(json_encode(['success' => false, 'message' => 'Tipe absensi tidak valid.']));
}
if (empty($imageData)) {
    die(json_encode(['success' => false, 'message' => 'Data gambar tidak boleh kosong.']));
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

$stmt_karyawan = $conn->prepare("SELECT nama, nik FROM karyawan WHERE nip = ?");
if (!$stmt_karyawan) {
    die(json_encode(['success' => false, 'message' => 'Kesalahan internal server.']));
}
$stmt_karyawan->bind_param("s", $session_nip);
$stmt_karyawan->execute();
$result_karyawan = $stmt_karyawan->get_result();

if ($result_karyawan->num_rows === 0) {
    die(json_encode(['success' => false, 'message' => 'Data karyawan tidak ditemukan.']));
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
    die(json_encode(['success' => false, 'message' => 'Format gambar tidak didukung.']));
}
$imageData = str_replace(' ', '+', $imageData); 
$imageBinary = base64_decode($imageData);

if ($imageBinary === false) {
    die(json_encode(['success' => false, 'message' => 'Gagal mendekode gambar.']));
}

$upload_dir = '../uploads/attendance/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

$filename = 'presensi_' . $session_nip . '_' . date('Ymd_His') . $file_extension;
$filepath = $upload_dir . $filename;

if (!file_put_contents($filepath, $imageBinary)) {
    die(json_encode(['success' => false, 'message' => 'Gagal menyimpan file gambar.']));
}

try {
    $query = "INSERT INTO absen_manual (
                tgl_absen, tipe_absen, image, pin, nip, nama, lokasi_absen, lokasi_koordinat, ip, device_name, verif
              ) VALUES (
                NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
              )";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        if (file_exists($filepath)) unlink($filepath);
        die(json_encode(['success' => false, 'message' => 'Kesalahan database.']));
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

        echo json_encode(['success' => true, 'message' => 'Presensi berhasil dicatat.']);
    } else {
        if (file_exists($filepath)) unlink($filepath);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data ke database.']);
    }
    $stmt->close();
} catch (Exception $e) {
    if (file_exists($filepath)) unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Kesalahan sistem: ' . $e->getMessage()]);
}

$conn->close();
?>