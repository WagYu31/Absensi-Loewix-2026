<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../index.php');
    exit();
}

include '../conn.php';
include 'get-kar-login-data.php';

function getDistanceBetweenPoints($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'meters')
{
    $earthRadius = ($unit === 'kilometers') ? 6371 : 6371000;
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

define('TARGET_OFFICE_LAT', -6.130189784035325);
define('TARGET_OFFICE_LON', 106.75142085117402);
define('MAX_OFFICE_RADIUS_METERS', 150);

$currentDay = date('l');
$isSaturday = ($currentDay === 'Saturday');
$todayDate = date('Y-m-d');

$final_shifting = $shifting;
$sql_shift_req = "SELECT shifting FROM shift_req WHERE nip = '$pinAbsen' AND '$todayDate' BETWEEN tgl_mulai AND tgl_selesai LIMIT 1";
$res_shift_req = $conn->query($sql_shift_req);
if ($res_shift_req && $res_shift_req->num_rows > 0) {
    $row_shift_req = $res_shift_req->fetch_assoc();
    $final_shifting = $row_shift_req['shifting'];
}

$current_page_basename = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Online - Grav-Tech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main-styles.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="../assets/css/presensi-styles.css">
    <style>
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.9); z-index: 9999; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; text-align: center; backdrop-filter: blur(8px); }
        .loading-spinner { width: 3.5rem; height: 3.5rem; border-width: 0.25em; color: #3b82f6; }
    </style>
</head>
<body>
    <?php include 'nav/sidebar.php'; ?>
    <div id="fullScreenLoader" class="loading-overlay d-none">
        <div class="spinner-border loading-spinner mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
        <h5 class="fw-bold">Sedang Mengirim Data...</h5>
        <p class="small text-white-50">Mohon jangan tutup atau refresh halaman ini.</p>
    </div>
    <div class="main-content-wrapper">
        <div class="presensi-header-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-3 px-2 presensi-top-bar">
                    <h5 class="text-light mb-0 page-title-presensi"><i class="fas fa-fingerprint me-2"></i>PRESENSI ONLINE</h5>
                    <div class="text-light text-end time-date-display">
                        <span id="realTimeClockDisplay" class="d-block fw-bold"><?php echo date('H:i:s'); ?></span>
                        <small class="d-block mt-1 opacity-75"><?php echo date('d F Y'); ?></small>
                    </div>
                </div>
                <div class="employee-info-presensi card card-body shadow-sm mx-1">
                    <div class="d-flex align-items-center">
                        <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="Foto Profil" class="employee-photo-presensi me-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/60/003c9c/ffffff?Text=<?php echo strtoupper(substr($nama, 0, 1)); ?>';">
                        <div>
                            <h6 class="mb-0 employee-name-presensi"><?php echo htmlspecialchars($nama); ?></h6>
                            <small class="employee-details-presensi"><?php echo htmlspecialchars($jabatan); ?> &bull; NIK: <?php echo htmlspecialchars($nik); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-content presensi-main-content px-lg-4 px-md-3 px-0">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-7 col-md-9">
                        <div class="card presensi-action-card shadow-lg">
                            <div class="card-body p-lg-4">
                                <div class="text-center mb-3">
                                    <h5 class="section-title-presensi-card mb-2">JADWAL ANDA HARI INI</h5>
                                    <p class="shift-name-presensi-card mb-2">
                                        Shift: <?php
                                                $shiftNames = ['P' => 'Pagi', 'M' => 'Tengah', 'N' => 'Siang', 'S' => 'Siang', 'T' => 'Harco (HC)'];
                                                echo $shiftNames[$final_shifting] ?? $final_shifting;
                                                ?>
                                    </p>
                                    <?php
                                    $shiftSchedule = '';
                                    if ($isSaturday) {
                                        $shiftSchedule = '08.30 - 13.00';
                                    } else {
                                        switch ($final_shifting) {
                                            case 'P': $shiftSchedule = '07.00 - 16.00'; break;
                                            case 'M': $shiftSchedule = '08.30 - 17.30'; break;
                                            case 'N': $shiftSchedule = '09.00 - 18.00'; break;
                                            case 'S': $shiftSchedule = '09.30 - 18.30'; break;
                                            case 'T': $shiftSchedule = '09.10 - 18.00'; break; 
                                            default: $shiftSchedule = 'Tidak Terdefinisi';
                                        }
                                    }
                                    ?>
                                    <p class="schedule-display-presensi-card my-2"><?php echo $shiftSchedule; ?></p>
                                </div>
                                <hr class="my-3 presensi-divider">
                                <div class="status-area-presensi mb-3">
                                    <div id="locationStatus" class="d-flex align-items-center justify-content-center location-status-presensi-card">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Mengambil lokasi...
                                    </div>
                                    <div id="locationWarning" class="alert alert-warning d-none text-center mt-2 py-2 small">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        Anda di luar lokasi kantor, jika lokasi tidak sesuai, pastikan GPS handphone aktif dan browser tidak memblokir lokasi pada situs ini.
                                    </div>
                                    <div id="lateWarning" class="alert alert-danger d-none text-center mt-2 py-2 late-warning-presensi-card">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <span id="lateMessage"></span>
                                    </div>
                                </div>
                                <div class="button-area-presensi mt-3">
                                    <button class="btn btn-check-in-presensi w-100 mb-2 py-3" id="btnCheckIn" disabled><i class="fas fa-hand-point-up fa-fw me-2"></i>MASUK</button>
                                    <button class="btn btn-check-out-presensi w-100 mb-2 py-3" id="btnCheckOut" disabled><i class="fas fa-door-open fa-fw me-2"></i>PULANG</button>
                                    <a href="riwayat-absen.php" class="btn btn-riwayat-absen w-100 py-3"><strong><i class="fas fa-calendar-check fa-fw me-2"></i>CEK ABSEN KAMU DISINI</strong></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer">Copyright &copy; Gravitti Technology <?php echo date("Y"); ?>. All Rights Reserved.<br><small>Version 1.1.0</small></div>
            </div>
        </div>
        <?php include 'nav/bottom-nav.php'; ?>
        <div class="modal fade camera-modal-presensi" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="attendanceTypeTitle"><i class="fas fa-camera me-2 text-primary"></i>Ambil Foto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 position-relative">
                        <video id="cameraPreview" autoplay playsinline class="camera-video-presensi"></video>
                        <canvas id="photoCanvas" class="d-none photo-canvas-presensi"></canvas>
                        <button id="captureBtn" class="capture-btn-presensi" title="Ambil Foto"><i class="fas fa-camera"></i></button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary fw-bold" id="uploadPhotoBtn" disabled><i class="fas fa-cloud-arrow-up me-2"></i>Upload & Kirim</button>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            const targetLat = <?php echo TARGET_OFFICE_LAT; ?>;
            const targetLng = <?php echo TARGET_OFFICE_LON; ?>;
            const employeeNip = '<?php echo htmlspecialchars($nip); ?>';
            const employeeName = '<?php echo htmlspecialchars($nama); ?>';
            const employeePin = '<?php echo htmlspecialchars($pinAbsen); ?>';
            const employeeNik = '<?php echo htmlspecialchars($nik); ?>';
            let userLat = null;
            let userLng = null;
            let userLocationAddress = "Lokasi tidak diketahui";
            let stream = null;
            let attendanceType = ''; 

            function updateClockDisplay() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                $('#realTimeClockDisplay').text(timeString);
                checkLateStatus(); 
                checkAbsenConditions(); 
            }

            function getDistanceFromLatLonInM(lat1, lon1, lat2, lon2) {
                function deg2rad(deg) { return deg * (Math.PI / 180); }
                const R = 6371000;
                const dLat = deg2rad(lat2 - lat1);
                const dLon = deg2rad(lon2 - lon1);
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            function getUserLocation() {
                const locationStatusEl = $('#locationStatus');
                locationStatusEl.html('<i class="fas fa-spinner fa-spin me-2 text-primary"></i> Mengambil lokasi...');
                if (!navigator.geolocation) {
                    locationStatusEl.html('<i class="fas fa-times-circle text-danger me-2"></i> Geolocation tidak didukung.');
                    checkAbsenConditions();
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${userLat}&lon=${userLng}`)
                            .then(response => response.json())
                            .then(data => {
                                userLocationAddress = data.display_name || `Lat: ${userLat.toFixed(4)}, Lng: ${userLng.toFixed(4)}`;
                                const distance = getDistanceFromLatLonInM(userLat, userLng, targetLat, targetLng);
                                let distanceText = distance !== null ? ` (Jarak: ${distance.toFixed(0)}m)` : '';
                                let locationIconClass = distance !== null && distance <= 150 ? 'text-success' : 'text-warning';
                                locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> ${userLocationAddress.substring(0, 40)}... ${distanceText}`);
                                if (distance > 150) { $('#locationWarning').removeClass('d-none'); } else { $('#locationWarning').addClass('d-none'); }
                            })
                            .catch(error => {
                                userLocationAddress = `Lat: ${userLat.toFixed(4)}, Lng: ${userLng.toFixed(4)}`;
                                locationStatusEl.html(`<i class="fas fa-map-marker-alt text-info me-2"></i> ${userLocationAddress} (Alamat gagal diambil)`);
                            });
                        checkAbsenConditions();
                    },
                    function(error) {
                        let errorMsg = 'Gagal mengambil lokasi: ';
                        switch (error.code) {
                            case error.PERMISSION_DENIED: errorMsg += "Izin lokasi ditolak."; break;
                            case error.POSITION_UNAVAILABLE: errorMsg += "Informasi lokasi tidak tersedia."; break;
                            case error.TIMEOUT: errorMsg += "Timeout permintaan lokasi."; break;
                            default: errorMsg += "Error tidak diketahui."; break;
                        }
                        locationStatusEl.html(`<i class="fas fa-times-circle text-danger me-2"></i> ${errorMsg}`);
                        checkAbsenConditions();
                    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            }

            function checkLateStatus() {
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                <?php
                $lateThresholdPHP = '';
                if ($isSaturday) {
                    $lateThresholdPHP = '08:30';
                } else {
                    switch ($final_shifting) {
                        case 'P': $lateThresholdPHP = '07:00'; break;
                        case 'M': $lateThresholdPHP = '08:30'; break;
                        case 'N': $lateThresholdPHP = '09:00'; break;
                        case 'S': $lateThresholdPHP = '09:30'; break;
                        case 'T': $lateThresholdPHP = '09:10'; break;
                        default: $lateThresholdPHP = '23:59';
                    }
                }
                echo "const lateThresholdJS = '$lateThresholdPHP';";
                ?>
                if (lateThresholdJS === '23:59') { $('#lateWarning').addClass('d-none'); return; }
                const [thresholdHour, thresholdMinute] = lateThresholdJS.split(':').map(Number);
                let isLate = false;
                if (currentHour > thresholdHour || (currentHour === thresholdHour && currentMinute > thresholdMinute)) { isLate = true; }
                const hasAlreadyCheckedIn = <?php echo mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM absen_manual WHERE nip='$nip' AND tipe_absen='masuk' AND DATE(tgl_absen)='" . date('Y-m-d') . "' LIMIT 1")) > 0 ? 'true' : 'false'; ?>;
                if (isLate && !hasAlreadyCheckedIn) {
                    $('#lateWarning').removeClass('d-none');
                    $('#lateMessage').text('Anda terlambat!');
                } else { $('#lateWarning').addClass('d-none'); }
            }

            function checkAbsenConditions() {
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                <?php
                $lH = 11; $lM = 0;
                if ($isSaturday) {
                    $lH = 10; $lM = 30;
                } else {
                    switch ($final_shifting) {
                        case 'P': $lH = 9; $lM = 0; break;
                        case 'M': $lH = 10; $lM = 30; break;
                        case 'N': $lH = 11; $lM = 0; break;
                        case 'S': $lH = 11; $lM = 30; break;
                        case 'T': $lH = 11; $lM = 10; break;
                        default: $lH = 11; $lM = 0; break;
                    }
                }
                echo "const limitHour = $lH;\n";
                echo "                const limitMinute = $lM;\n";
                ?>
                
                let isCheckInTime = false;
                if (currentHour < limitHour || (currentHour === limitHour && currentMinute < limitMinute)) {
                    isCheckInTime = true;
                }
                
                const isCheckOutTime = !isCheckInTime;
                const isLocationActive = (userLat !== null && userLng !== null);

                const hasCheckedInToday = <?php echo mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM absen_manual WHERE nip='$nip' AND tipe_absen='masuk' AND DATE(tgl_absen)='" . date('Y-m-d') . "' LIMIT 1")) > 0 ? 'true' : 'false'; ?>;
                const hasCheckedOutToday = <?php echo mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM absen_manual WHERE nip='$nip' AND tipe_absen='pulang' AND DATE(tgl_absen)='" . date('Y-m-d') . "' LIMIT 1")) > 0 ? 'true' : 'false'; ?>;

                if (!hasCheckedInToday && isCheckInTime && isLocationActive) { $('#btnCheckIn').prop('disabled', false); } 
                else { $('#btnCheckIn').prop('disabled', true); }

                if (isCheckOutTime && !hasCheckedOutToday && isLocationActive) { $('#btnCheckOut').prop('disabled', false); } 
                else { $('#btnCheckOut').prop('disabled', true); }
            }

            function getDeviceName() {
                const ua = navigator.userAgent;
                if (/android/i.test(ua)) {
                    const match = ua.match(/\(([^)]+)\)/);
                    if (match && match[1]) {
                        let parts = match[1].split(';');
                        return parts[parts.length - 1].trim().split(' Build')[0];
                    }
                    return "Android Device";
                }
                if (/iPhone|iPad|iPod/i.test(ua)) return "iPhone/iPad";
                return "PC/Browser";
            }

            $('#btnCheckIn, #btnCheckOut').click(function() {
                if ($(this).prop('disabled')) return;
                attendanceType = $(this).attr('id') === 'btnCheckIn' ? 'masuk' : 'pulang';
                $('#attendanceTypeTitle').html('<i class="fas fa-camera me-2 text-primary"></i>Ambil Foto Absen ' + (attendanceType === 'masuk' ? 'Masuk' : 'Pulang'));
                $('#cameraModal').modal('show');
            });

            $('#cameraModal').on('shown.bs.modal', function() { startCamera(); });
            $('#cameraModal').on('hidden.bs.modal', function() {
                stopCamera();
                $('#cameraPreview').removeClass('d-none');
                $('#photoCanvas').addClass('d-none');
                $('#uploadPhotoBtn').prop('disabled', true).html('<i class="fas fa-cloud-arrow-up me-2"></i>Upload & Kirim');
            });

            function startCamera() {
                const video = document.getElementById('cameraPreview');
                $('#cameraPreview').removeClass('d-none');
                $('#photoCanvas').addClass('d-none');
                $('#uploadPhotoBtn').prop('disabled', true);
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false })
                    .then(function(s) { stream = s; video.srcObject = stream; video.play(); })
                    .catch(function(err) {
                        alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin.');
                        $('#cameraModal').modal('hide');
                    });
            }

            function stopCamera() { if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; } }

            $('#captureBtn').click(function() {
                const video = document.getElementById('cameraPreview');
                const canvas = document.getElementById('photoCanvas');
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth || video.offsetWidth;
                canvas.height = video.videoHeight || video.offsetHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                $('#cameraPreview').addClass('d-none');
                $('#photoCanvas').removeClass('d-none');
                $('#uploadPhotoBtn').prop('disabled', false);
                stopCamera();
            });

            $('#uploadPhotoBtn').click(function() {
                const canvas = document.getElementById('photoCanvas');
                const imageData = canvas.toDataURL('image/jpeg', 0.75); 
                const deviceName = getDeviceName();
                $('#cameraModal').modal('hide');
                $('#fullScreenLoader').removeClass('d-none');
                window.onbeforeunload = function() { return "Proses pengiriman data sedang berlangsung."; };
                $.ajax({
                    url: 'process_attendance.php',
                    type: 'POST',
                    data: { tipe_absen: attendanceType, foto_absen: imageData, nip: employeeNip, pin: employeePin, nik_karyawan: employeeNik, lokasi_absen: userLocationAddress, latitude: userLat, longitude: userLng, device_name: deviceName },
                    dataType: 'json',
                    success: function(response) {
                        window.onbeforeunload = null;
                        if (response.success) { alert('Absen ' + attendanceType + ' berhasil dicatat!'); location.reload(); } 
                        else { $('#fullScreenLoader').addClass('d-none'); alert('Gagal: ' + response.message); $('#cameraModal').modal('show'); }
                    },
                    error: function() { window.onbeforeunload = null; $('#fullScreenLoader').addClass('d-none'); alert('Terjadi kesalahan koneksi.'); }
                });
            });

            $(document).ready(function() {
                updateClockDisplay();
                setInterval(updateClockDisplay, 1000);
                getUserLocation();
                var currentPath = "<?php echo $current_page_basename; ?>";
                $('.sidebar-menu a').each(function() {
                    var linkHref = $(this).attr('href').split("?")[0];
                    if (linkHref === currentPath) { $(this).addClass('active'); }
                });
                $('.custom-nav__link').each(function() {
                    var linkHref = $(this).attr('href').split("?")[0];
                    if (linkHref === currentPath) { $(this).addClass('active'); }
                });
            });
        </script>
</body>
</html>