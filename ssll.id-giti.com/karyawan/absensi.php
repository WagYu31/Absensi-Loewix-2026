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
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Presensi Online 3D - Gravitti Tech</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a97d5963a4.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="../assets/css/main-styles.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/bottom-nav.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/footer.css?v=<?php echo $asset_version; ?>">
    <link rel="stylesheet" href="../assets/css/presensi-styles.css?v=<?php echo $asset_version; ?>">
    
    <style>
        /* Taste Skill 3D Design Overrides */
        :root {
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            --card-radius-lg: 28px;
            --btn-radius: 16px;
            --primary-3d: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #1d4ed8 100%);
            --success-3d: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);
            --danger-3d: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #b91c1c 100%);
            --warning-3d: linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #b45309 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #e2e8f0 !important;
        }

        .main-content-wrapper {
            background: #e2e8f0;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.15) 0px, transparent 50%) !important;
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            touch-action: manipulation;
        }

        .presensi-header-section {
            background: var(--header-gradient) !important;
            color: #fff;
            padding: 1.75rem 0 4rem 0 !important;
            margin-bottom: -60px !important;
            position: relative;
            z-index: 5;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.3) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-title-presensi {
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            letter-spacing: 0.8px;
            color: #ffffff !important;
        }

        #realTimeClockDisplay {
            font-size: 1.35rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.15) !important;
            padding: 4px 14px !important;
            border-radius: 20px !important;
            display: inline-block;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .employee-info-presensi.card {
            background: rgba(255, 255, 255, 0.12) !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #fff !important;
            border-radius: 22px !important;
            padding: 0.95rem 1.25rem !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        }

        .employee-photo-presensi {
            width: 54px !important;
            height: 54px !important;
            border-radius: 50% !important;
            object-fit: cover;
            border: 2.5px solid #ffffff !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
        }

        .employee-name-presensi {
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            color: #ffffff !important;
        }

        /* 3D Presensi Action Card */
        .presensi-action-card.card {
            background: rgba(255, 255, 255, 0.88) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            border-radius: var(--card-radius-lg) !important;
            border: 1px solid rgba(255, 255, 255, 0.9) !important;
            box-shadow: 
                0 30px 60px -12px rgba(15, 23, 42, 0.15),
                0 18px 36px -18px rgba(15, 23, 42, 0.12),
                inset 0 1px 1px rgba(255, 255, 255, 0.9) !important;
            transform-style: preserve-3d;
            will-change: transform;
            transition: transform 0.15s ease-out, box-shadow 0.3s ease;
        }

        .presensi-action-card .section-title-presensi-card {
            color: #334155 !important;
            font-weight: 800 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .presensi-action-card .schedule-display-presensi-card {
            font-size: 2.75rem !important;
            font-weight: 900 !important;
            line-height: 1.1;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            letter-spacing: -1.5px;
            margin: 0.4rem 0 !important;
        }

        @media (max-width: 576px) {
            .presensi-action-card .schedule-display-presensi-card {
                font-size: 2.25rem !important;
            }
        }

        .presensi-action-card .shift-name-presensi-card {
            font-size: 0.85rem !important;
            color: #2563eb !important;
            font-weight: 700 !important;
            background: rgba(37, 99, 235, 0.08) !important;
            padding: 6px 16px !important;
            border-radius: 20px !important;
            display: inline-block;
            border: 1px solid rgba(37, 99, 235, 0.15) !important;
        }

        .status-area-presensi .location-status-presensi-card {
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 0.85rem 1.1rem !important;
            background-color: rgba(248, 250, 252, 0.95) !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 16px !important;
            min-height: 48px;
            color: #1e293b !important;
        }

        /* Tactile 3D Buttons */
        .button-area-presensi .btn {
            font-size: 0.95rem !important;
            font-weight: 800 !important;
            height: 54px !important;
            border-radius: var(--btn-radius) !important;
            letter-spacing: 0.4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease-out !important;
            border: none !important;
            cursor: pointer;
        }

        .btn-check-in-presensi {
            background: var(--success-3d) !important;
            color: #ffffff !important;
            box-shadow: 
                0 8px 20px rgba(16, 185, 129, 0.35),
                0 4px 0 #047857 !important;
        }

        .btn-check-in-presensi:hover:not(:disabled), .btn-check-in-presensi:focus:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 
                0 12px 25px rgba(16, 185, 129, 0.45),
                0 6px 0 #065f46 !important;
            color: #ffffff !important;
        }

        .btn-check-in-presensi:active:not(:disabled) {
            transform: translateY(2px);
            box-shadow: 
                0 4px 10px rgba(16, 185, 129, 0.3),
                0 1px 0 #065f46 !important;
        }

        .btn-check-in-presensi:disabled {
            background: #cbd5e1 !important;
            color: #64748b !important;
            box-shadow: 0 4px 0 #94a3b8 !important;
            opacity: 0.7;
        }

        .btn-check-out-presensi {
            background: var(--danger-3d) !important;
            color: #ffffff !important;
            box-shadow: 
                0 8px 20px rgba(239, 68, 68, 0.35),
                0 4px 0 #b91c1c !important;
        }

        .btn-check-out-presensi:hover:not(:disabled), .btn-check-out-presensi:focus:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 
                0 12px 25px rgba(239, 68, 68, 0.45),
                0 6px 0 #991b1b !important;
            color: #ffffff !important;
        }

        .btn-check-out-presensi:active:not(:disabled) {
            transform: translateY(2px);
            box-shadow: 
                0 4px 10px rgba(239, 68, 68, 0.3),
                0 1px 0 #991b1b !important;
        }

        .btn-check-out-presensi:disabled {
            background: #cbd5e1 !important;
            color: #64748b !important;
            box-shadow: 0 4px 0 #94a3b8 !important;
            opacity: 0.7;
        }

        .btn-riwayat-absen {
            background: var(--warning-3d) !important;
            color: #ffffff !important;
            box-shadow: 
                0 8px 20px rgba(245, 158, 11, 0.35),
                0 4px 0 #b45309 !important;
        }

        .btn-riwayat-absen:hover, .btn-riwayat-absen:focus {
            transform: translateY(-2px);
            box-shadow: 
                0 12px 25px rgba(245, 158, 11, 0.45),
                0 6px 0 #92400e !important;
                color: #ffffff !important;
        }

        .btn-riwayat-absen:active {
            transform: translateY(2px);
            box-shadow: 
                0 4px 10px rgba(245, 158, 11, 0.3),
                0 1px 0 #92400e !important;
        }

        /* Perfect Mobile Camera Modal (Fits screen perfectly, no black void) */
        #cameraModal {
            z-index: 1060 !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }
        #cameraModal .modal-dialog {
            max-width: 360px !important;
            width: 92% !important;
            margin: 0.75rem auto !important;
        }
        #cameraModal .modal-content {
            background: #0f172a !important;
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6) !important;
        }
        #cameraModal .modal-header {
            background: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
            padding: 10px 14px !important;
        }
        #cameraModal .modal-title {
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            color: #ffffff !important;
        }
        #cameraModal .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
            z-index: 1070 !important;
        }
        .camera-video-presensi,
        .photo-preview-img {
            width: 100% !important;
            height: 320px !important;
            max-height: 320px !important;
            object-fit: cover !important;
            object-position: center !important;
            display: block !important;
            background: #0f172a !important;
        }

        /* 3D Circular Blue Camera Shutter Button */
        .capture-btn-presensi {
            position: absolute !important;
            bottom: 12px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            width: 52px !important;
            height: 52px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
            border: 3px solid #60a5fa !important;
            color: #ffffff !important;
            font-size: 1.2rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            z-index: 1070 !important;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.6) !important;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            padding: 0 !important;
            outline: none !important;
        }
        .capture-btn-presensi i {
            font-size: 1.25rem !important;
            color: #ffffff !important;
        }
        .capture-btn-presensi:hover {
            transform: translateX(-50%) scale(1.08) !important;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.8) !important;
        }
        .capture-btn-presensi:active {
            transform: translateX(-50%) scale(0.95) !important;
        }

        #cameraModal .modal-footer {
            background: #0f172a !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 10px 14px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px !important;
        }
        #cameraModal .modal-footer .btn-outline-secondary {
            flex: 1 !important;
            background: transparent !important;
            border: 1.5px solid #334155 !important;
            color: #cbd5e1 !important;
            border-radius: 12px !important;
            padding: 9px !important;
            font-size: 0.9rem !important;
            font-weight: 700 !important;
            z-index: 1070 !important;
        }
        #cameraModal .modal-footer .btn-outline-secondary:hover {
            background: #1e293b !important;
            color: #ffffff !important;
        }
        #cameraModal .modal-footer .btn-primary {
            flex: 1 !important;
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
            border: none !important;
            color: #ffffff !important;
            border-radius: 12px !important;
            padding: 9px !important;
            font-size: 0.9rem !important;
            font-weight: 800 !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4) !important;
            z-index: 1070 !important;
        }

        .retake-btn-presensi {
            position: absolute !important;
            bottom: 12px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            background: rgba(15, 23, 42, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: #ffffff !important;
            border-radius: 20px !important;
            padding: 6px 16px !important;
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            z-index: 1070 !important;
            backdrop-filter: blur(8px) !important;
            cursor: pointer !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.5) !important;
            transition: all 0.2s ease !important;
        }
        .retake-btn-presensi:hover {
            background: rgba(30, 41, 59, 0.95) !important;
            transform: translateX(-50%) scale(1.05) !important;
        }

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
                        <div class="card presensi-action-card shadow-lg" id="card3d">
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
                                        <i class="fas fa-spinner fa-spin me-2 text-primary"></i> Mengambil lokasi...
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
    </div><!-- /main-content-wrapper -->

    <!-- Modal Camera Placed Outside Main Wrapper for Proper Body Level Stacking Context -->
    <div class="modal fade camera-modal-presensi" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 360px; width: 92%; margin: 0.5rem auto;">
            <div class="modal-content" style="background: #0f172a; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.15); overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
                <div class="modal-header" style="background: #0f172a; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 10px 14px; color: #fff;">
                    <h5 class="modal-title fs-6 fw-bold mb-0 text-white" id="attendanceTypeTitle"><i class="fas fa-camera me-2 text-primary"></i>Ambil Foto</h5>
                    <button type="button" class="btn-close btn-close-white opacity-75" id="closeCameraXBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative" style="height: 320px; max-height: 320px; overflow: hidden; background: #0f172a;">
                    <video id="cameraPreview" autoplay playsinline muted class="camera-video-presensi" style="width: 100%; height: 320px; object-fit: cover; object-position: center; display: block;"></video>
                    <img id="photoPreviewImg" class="d-none photo-preview-img" style="width: 100%; height: 320px; object-fit: cover; object-position: center; display: block;">
                    <canvas id="photoCanvas" class="d-none" style="display: none;"></canvas>
                    <input type="file" id="nativeCameraInput" accept="image/*" capture="user" class="d-none" style="display: none;">
                    <button id="captureBtn" class="capture-btn-presensi" title="Ambil Foto"><i class="fas fa-camera"></i></button>
                    <button id="retakeBtn" class="retake-btn-presensi d-none" title="Ulang Foto"><i class="fas fa-rotate-left me-1.5"></i>Foto Ulang</button>
                </div>
                <div class="modal-footer" style="background: #0f172a; border-top: 1px solid rgba(255, 255, 255, 0.1); padding: 10px 14px; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline-secondary flex-fill text-white-50 border-secondary rounded-3 py-2 fw-bold small" id="closeCameraBatalBtn">Batal</button>
                    <button type="button" class="btn btn-primary flex-fill rounded-3 py-2 fw-bold small text-white shadow" id="uploadPhotoBtn" disabled><i class="fas fa-cloud-arrow-up me-1.5"></i>Upload & Kirim</button>
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

        function closeCameraModal() {
            stopCamera();
            $('#cameraModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
        }

        $(document).on('click touchstart', '#closeCameraXBtn, #closeCameraBatalBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeCameraModal();
        });

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
            
            const cachedLat = localStorage.getItem('last_user_lat');
            const cachedLng = localStorage.getItem('last_user_lng');
            const cachedTime = localStorage.getItem('last_user_time');
            
            if (cachedLat && cachedLng && cachedTime && (Date.now() - parseInt(cachedTime)) < 3600000) {
                userLat = parseFloat(cachedLat);
                userLng = parseFloat(cachedLng);
                userLocationAddress = localStorage.getItem('last_user_addr') || `Lat: ${userLat.toFixed(4)}, Lng: ${userLng.toFixed(4)}`;
                
                const distance = getDistanceFromLatLonInM(userLat, userLng, targetLat, targetLng);
                let distanceText = distance !== null ? ` (Jarak: ${distance.toFixed(0)}m)` : '';
                let locationIconClass = distance !== null && distance <= 150 ? 'text-success' : 'text-warning';
                
                locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> ${userLocationAddress.substring(0, 35)}...${distanceText}`);
                if (distance > 150) { $('#locationWarning').removeClass('d-none'); } else { $('#locationWarning').addClass('d-none'); }
                
                checkAbsenConditions();
            } else {
                locationStatusEl.html('<i class="fas fa-spinner fa-spin me-2 text-primary"></i> Mengambil lokasi instan...');
            }

            if (!navigator.geolocation) {
                locationStatusEl.html('<i class="fas fa-times-circle text-danger me-2"></i> Geolocation tidak didukung.');
                checkAbsenConditions();
                return;
            }

            function handlePositionSuccess(position) {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                
                localStorage.setItem('last_user_lat', userLat);
                localStorage.setItem('last_user_lng', userLng);
                localStorage.setItem('last_user_time', Date.now());
                
                const distance = getDistanceFromLatLonInM(userLat, userLng, targetLat, targetLng);
                let distanceText = distance !== null ? ` (Jarak: ${distance.toFixed(0)}m)` : '';
                let locationIconClass = distance !== null && distance <= 150 ? 'text-success' : 'text-warning';
                
                locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> Lat: ${userLat.toFixed(4)}, Lng: ${userLng.toFixed(4)}${distanceText}`);
                if (distance > 150) { $('#locationWarning').removeClass('d-none'); } else { $('#locationWarning').addClass('d-none'); }
                
                checkAbsenConditions();

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${userLat}&lon=${userLng}`, { signal: AbortSignal.timeout(3000) })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            userLocationAddress = data.display_name;
                            localStorage.setItem('last_user_addr', userLocationAddress);
                            locationStatusEl.html(`<i class="fas fa-map-marker-alt ${locationIconClass} me-2"></i> ${userLocationAddress.substring(0, 35)}...${distanceText}`);
                        }
                    })
                    .catch(() => { });
            }

            navigator.geolocation.getCurrentPosition(
                handlePositionSuccess,
                function(error) {
                    navigator.geolocation.getCurrentPosition(
                        handlePositionSuccess,
                        function(err) {
                            if (!userLat) {
                                let errorMsg = 'Gagal mengambil lokasi. ';
                                if (err.code === err.PERMISSION_DENIED) errorMsg += "Izin lokasi ditolak di HP.";
                                else errorMsg += "Aktifkan GPS HP Anda.";
                                locationStatusEl.html(`<i class="fas fa-exclamation-triangle text-warning me-2"></i> ${errorMsg}`);
                                checkAbsenConditions();
                            }
                        },
                        { enableHighAccuracy: true, timeout: 5000, maximumAge: 300000 }
                    );
                },
                { enableHighAccuracy: false, timeout: 3000, maximumAge: 300000 }
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

        $('#cameraModal').on('shown.bs.modal', function() { 
            $('#photoPreviewImg').addClass('d-none').attr('src', '');
            $('#cameraPreview').removeClass('d-none');
            $('#captureBtn').removeClass('d-none');
            $('#retakeBtn').addClass('d-none');
            startCamera(); 
        });

        $('#cameraModal').on('hidden.bs.modal', function() {
            stopCamera();
            $('#cameraPreview').removeClass('d-none');
            $('#photoPreviewImg').addClass('d-none').attr('src', '');
            $('#photoCanvas').addClass('d-none');
            $('#captureBtn').removeClass('d-none');
            $('#retakeBtn').addClass('d-none');
            $('#uploadPhotoBtn').prop('disabled', true).html('<i class="fas fa-cloud-arrow-up me-1.5"></i>Upload & Kirim');
        });

        function startCamera() {
            const video = document.getElementById('cameraPreview');
            $('#cameraPreview').removeClass('d-none');
            $('#photoPreviewImg').addClass('d-none').attr('src', '');
            $('#photoCanvas').addClass('d-none');
            $('#captureBtn').removeClass('d-none');
            $('#retakeBtn').addClass('d-none');
            $('#uploadPhotoBtn').prop('disabled', true);
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false })
                .then(function(s) { 
                    stream = s; 
                    video.srcObject = stream; 
                    video.muted = true;
                    video.play().catch(function(e) { console.log(e); }); 
                })
                .catch(function(err) {
                    alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin.');
                    closeCameraModal();
                });
        }

        function stopCamera() { if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; } }

        $('#nativeCameraInput').change(function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const dataUrl = evt.target.result;
                    const photoImg = document.getElementById('photoPreviewImg');
                    photoImg.src = dataUrl;
                    $('#photoPreviewImg').removeClass('d-none');
                    $('#cameraPreview').addClass('d-none');
                    $('#captureBtn').addClass('d-none');
                    $('#retakeBtn').removeClass('d-none');
                    $('#uploadPhotoBtn').prop('disabled', false);
                    stopCamera();
                };
                reader.readAsDataURL(file);
            }
        });

        function isCanvasBlack(ctx, width, height) {
            try {
                const imgData = ctx.getImageData(Math.floor(width / 4), Math.floor(height / 4), Math.floor(width / 2), Math.floor(height / 2)).data;
                let sum = 0;
                for (let i = 0; i < imgData.length; i += 40) {
                    sum += (imgData[i] + imgData[i+1] + imgData[i+2]);
                }
                return (sum / (imgData.length / 40)) < 15;
            } catch(e) {
                return true;
            }
        }

        $('#captureBtn').click(function() {
            const video = document.getElementById('cameraPreview');
            const canvas = document.getElementById('photoCanvas');

            if (video && video.videoWidth > 0) {
                let w = video.videoWidth;
                let h = video.videoHeight;
                const maxDim = 640;

                if (w > maxDim || h > maxDim) {
                    if (w >= h) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    } else {
                        w = Math.round((w * maxDim) / h);
                        h = maxDim;
                    }
                }

                canvas.width = w;
                canvas.height = h;

                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, w, h);

                if (!isCanvasBlack(context, w, h)) {
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    const photoImg = document.getElementById('photoPreviewImg');
                    photoImg.src = dataUrl;

                    $('#photoPreviewImg').removeClass('d-none');
                    $('#cameraPreview').addClass('d-none');
                    $('#captureBtn').addClass('d-none');
                    $('#retakeBtn').removeClass('d-none');
                    $('#uploadPhotoBtn').prop('disabled', false);
                    return;
                }
            }

            // Fallback for Android WebView security restrictions: Open Native Device Camera
            document.getElementById('nativeCameraInput').click();
        });

        $('#retakeBtn').click(function() {
            $('#photoPreviewImg').addClass('d-none').attr('src', '');
            $('#cameraPreview').removeClass('d-none');
            $('#captureBtn').removeClass('d-none');
            $('#retakeBtn').addClass('d-none');
            $('#uploadPhotoBtn').prop('disabled', true);
            if (!stream) {
                startCamera();
            }
        });

        $('#uploadPhotoBtn').click(function() {
            const photoImg = document.getElementById('photoPreviewImg');
            let imageData = photoImg.src;
            if (!imageData || !imageData.startsWith('data:image')) {
                const canvas = document.getElementById('photoCanvas');
                imageData = canvas.toDataURL('image/jpeg', 0.85);
            }

            if (!imageData || imageData.length < 500) {
                alert('Gagal mengambil data foto. Silakan foto ulang.');
                return;
            }

            const deviceName = getDeviceName();
            closeCameraModal();
            $('#fullScreenLoader').removeClass('d-none');
            window.onbeforeunload = function() { return "Proses pengiriman data sedang berlangsung."; };
            $.ajax({
                url: 'process_attendance.php',
                type: 'POST',
                data: { tipe_absen: attendanceType, foto_absen: imageData, nip: employeeNip, pin: employeePin, nik_karyawan: employeeNik, lokasi_absen: userLocationAddress, latitude: userLat, longitude: userLng, device_name: deviceName },
                dataType: 'json',
                timeout: 20000,
                success: function(response) {
                    window.onbeforeunload = null;
                    if (response && response.success) { 
                        alert('Absen ' + attendanceType + ' berhasil dicatat!'); 
                        location.reload(); 
                    } else { 
                        $('#fullScreenLoader').addClass('d-none'); 
                        alert('Gagal: ' + (response ? response.message : 'Respon tidak valid')); 
                        $('#cameraModal').modal('show'); 
                    }
                },
                error: function(xhr, status, error) { 
                    window.onbeforeunload = null; 
                    $('#fullScreenLoader').addClass('d-none'); 
                    let errorMsg = 'Terjadi kesalahan pengiriman data.';
                    if (xhr.status === 413) {
                        errorMsg = 'Ukuran foto terlalu besar untuk server. Silakan foto ulang.';
                    } else if (xhr.responseText) {
                        try {
                            const errObj = JSON.parse(xhr.responseText);
                            if (errObj.message) errorMsg = errObj.message;
                        } catch(e) {
                            errorMsg = 'Error Server (' + xhr.status + '): ' + xhr.responseText.substring(0, 150);
                        }
                    }
                    alert(errorMsg);
                    $('#cameraModal').modal('show'); 
                }
            });
        });

        $(document).ready(function() {
            updateClockDisplay();
            setInterval(updateClockDisplay, 1000);
            getUserLocation();

            const card = document.getElementById('card3d');
            if (card) {
                function apply3DTilt(clientX, clientY) {
                    const rect = card.getBoundingClientRect();
                    const centerX = rect.left + rect.width / 2;
                    const centerY = rect.top + rect.height / 2;
                    const xAxis = (centerX - clientX) / 18;
                    const yAxis = (clientY - centerY) / 18;
                    card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
                }

                function reset3DTilt() {
                    card.style.transition = 'transform 0.4s ease';
                    card.style.transform = `rotateY(0deg) rotateX(0deg)`;
                    setTimeout(() => { card.style.transition = 'transform 0.15s ease-out'; }, 400);
                }

                document.addEventListener('mousemove', (e) => {
                    apply3DTilt(e.clientX, e.clientY);
                });

                document.addEventListener('mouseleave', reset3DTilt);

                card.addEventListener('touchmove', (e) => {
                    if (e.touches.length > 0) {
                        const touch = e.touches[0];
                        apply3DTilt(touch.clientX, touch.clientY);
                    }
                }, { passive: true });

                card.addEventListener('touchend', reset3DTilt);
            }

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